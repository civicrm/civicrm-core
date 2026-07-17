<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Test\FormTrait;
use Civi\Test\FormWrapper;

/**
 * Class CRM_Event_Form_Registration_RegisterTest
 * @group headless
 */
class CRM_Event_Form_Registration_RegisterTest extends CiviUnitTestCase {
  use FormTrait;

  /**
   * CRM-19626 - Test minimum value configured for price set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMinValueForPriceSet(): void {
    $this->eventCreatePaid([], ['min_amount' => 100]);
    $submittedValues = [
      'email-Primary' => 'someone@example.com',
      'priceSetId' => $this->ids['PriceSet']['PaidEvent'],
      'price_' . $this->ids['PriceField']['PaidEvent'] => $this->ids['PriceFieldValue']['PaidEvent_student_early'],
      'payment_processor_id' => 0,
    ];
    $form = $this->getTestForm('CRM_Event_Form_Registration_Register', $submittedValues, ['id' => $this->getEventID()]);
    $form->processForm(FormWrapper::VALIDATED);

    //Assert the validation Error.
    $expectedResult = [
      '_qf_default' => ts('A minimum amount of %1 should be selected from Event Fee(s).', [1 => CRM_Utils_Money::format(100)]),
    ];
    $this->assertValidationError($expectedResult);
  }

  public function testValidateEventWithAvailableSpace(): void {
    $event = $this->eventCreateUnpaid(['max_participants' => 2]);
    $form = $this->getTestForm('CRM_Event_Form_Registration_Register', [
      'additional_participants' => 2,
      'email-Primary' => 'someone@example.com',
    ], ['id' => $this->getEventID()]);
    $form->processForm(FormWrapper::VALIDATED);
    $expectedResult = [
      'additional_participants' => 'There is only enough space left on this event for 2 participant(s).',
    ];
    $this->assertValidationError($expectedResult);
  }

  /**
   * event#30
   *
   * @throws \CRM_Core_Exception
   */
  public function testDoubleWaitlistRegistration(): void {
    // By default, waitlist participant statuses are disabled (which IMO is poor UX).
    $sql = 'UPDATE civicrm_participant_status_type SET is_active = 1';
    CRM_Core_DAO::executeQuery($sql);

    // Create an event, fill its participant slots.
    $event = $this->eventCreateUnpaid([
      'has_waitlist' => 1,
      'max_participants' => 1,
      'start_date' => 20351021,
      'end_date' => 20351023,
      'registration_end_date' => 20351015,
    ]);
    $this->participantCreate(['event_id' => $event['id']]);

    // Add someone to the waitlist.
    $waitlistContact = $this->individualCreate();

    $this->participantCreate(['event_id' => $event['id'], 'contact_id' => $waitlistContact, 'status_id.name' => 'On waitlist']);

    // We should now have two participants.
    $this->callAPISuccessGetCount('Participant', ['event_id' => $event['id']], 2);

    $form = $this->getTestForm('CRM_Event_Form_Registration_Register', [], [
      'id' => $this->getEventID(),
      'cid' => $waitlistContact,
    ]);
    // We SHOULD get an error when double registering a waitlisted user.
    try {
      $form->processForm(FormWrapper::PREPROCESSED);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      return;
    }
    $this->fail('Wait listed users shouldn\'t be allowed to re-register.');
  }

  /**
   * Test that current event is valid or not.
   *
   * @dataProvider eventDataProvider
   *
   * @return void
   */
  public function testValidEvent(string $fieldName, array $formValues, string $expectedMessage, string $dateFormat): void {
    $event = $this->eventCreateUnpaid();

    // calculate the datetime now since dataproviders run at the start of the
    // test suite and so it might be off by the time we get here
    $offset = NULL;
    if ($dateFormat) {
      // In this case we expect the value to be a relative date string.
      // Keep offset for later.
      $offset = $formValues[$fieldName];
      $formValues[$fieldName] = date($dateFormat, strtotime($offset));
    }

    $this->updateEvent($formValues);
    $form = $this->getTestForm('CRM_Event_Form_Registration_Register', [], [
      'id' => $this->getEventID(),
    ]);

    try {
      $form->processForm(FormWrapper::PREPROCESSED);
      $this->fail('Should have thrown an exception');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $message = CRM_Core_Session::singleton()->getStatus();
      if ($dateFormat) {
        // replace placeholder with formatted expected offset
        $expectedMessage = str_replace('%EXPECTED_DATE%', CRM_Utils_Date::customFormat(date($dateFormat, strtotime($offset))), $expectedMessage);
      }
      $this->assertEquals($expectedMessage, $message[0]['text']);
    }
  }

  public static function eventDataProvider(): array {
    return [
      'inactive_event' => [
        'field_name' => 'is_active',
        'form_values' => ['is_active' => FALSE],
        'expected_message' => 'The event you requested is currently unavailable (contact the site administrator for assistance).',
        'message_date_format' => '',
      ],
      'online_registration_disabled' => [
        'field_name' => 'is_online_registration',
        'form_values' => ['is_online_registration' => FALSE],
        'expected_message' => 'Online registration is not currently available for this event (contact the site administrator for assistance).',
        'message_date_format' => '',
      ],
      'event_is_template' => [
        'field_name' => 'is_template',
        'form_values' => ['is_template' => TRUE],
        'expected_message' => 'Event templates are not meant to be registered.',
        'message_date_format' => '',
      ],
      'start_date_in_future' => [
        'field_name' => 'registration_start_date',
        'form_values' => ['registration_start_date' => '+ 1 day'],
        'expected_message' => 'Registration for this event begins on %EXPECTED_DATE%',
        'message_date_format' => 'YmdH0000',
      ],
      'registration_end_date_in_past' => [
        'field_name' => 'registration_end_date',
        'form_values' => ['registration_end_date' => '- 1 day'],
        'expected_message' => 'Registration for this event ended on %EXPECTED_DATE%',
        'message_date_format' => 'YmdH0000',
      ],
      'event_end_date_in_past' => [
        'field_name' => 'end_date',
        // the stock event always puts the registration end in the future, so
        // we need to get rid of that to test this properly.
        'form_values' => [
          'end_date' => '- 1 day',
          'registration_start_date' => NULL,
          'registration_end_date' => NULL,
        ],
        'expected_message' => 'Registration for this event ended on %EXPECTED_DATE%',
        'message_date_format' => 'YmdHi',
      ],
    ];
  }

  /**
   * Test concurrent registration when is_confirm_enabled is disabled.
   */
  public function testConcurrentRegistrationNoConfirm(): void {
    // Create event with max 1 participant, confirmation screen disabled.
    $event = $this->eventCreateUnpaid([
      'max_participants' => 1,
      'is_confirm_enabled' => 0,
    ]);

    // Construct form wrapper for User B (the one who should get blocked).
    $form = $this->getTestForm('CRM_Event_Form_Registration_Register', [
      'email-Primary' => 'user_b@example.com',
    ], ['id' => $event['id']]);

    // Process form up to BUILT state (runs preProcess and buildForm when event is empty)
    $form->processForm(FormWrapper::BUILT);

    // Use Invasive to get the protected 'form' property
    /** @var \CRM_Event_Form_Registration_Register $innerForm */
    $innerForm = \Civi\Test\Invasive::get([$form, 'form']);

    // In the background, create a participant for the event (simulating User A completing registration first).
    $this->participantCreate(['event_id' => $event['id']]);

    // User B submits the form
    $innerForm->validate();

    // Verify if validation failed.
    // If it did not fail, it proceeds to postProcess, which we simulate here.
    if (empty($innerForm->_errors)) {
      try {
        $innerForm->postProcess();
      }
      catch (\CRM_Core_Exception_PrematureExitException $e) {
        $this->fail('Registration should not be allowed; exceeds the max_participants limit.');
      }
    }

    // Check how many participants are registered. Should remain 1.
    $count = $this->callAPISuccessGetCount('Participant', ['event_id' => $event['id']]);
    $this->assertEquals(1, $count, 'Should not exceed the max_participants limit.');

    // Also verify that a validation error was present.
    $this->assertNotEmpty($innerForm->_errors, 'Should have validation errors on the form.');
  }

}
