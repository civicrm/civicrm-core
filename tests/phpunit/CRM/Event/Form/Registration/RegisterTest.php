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

    $this->participantCreate(['event_id' => $event['id'], 'contact_id' => $waitlistContact, 'status_id' => 'On waitlist']);

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

}
