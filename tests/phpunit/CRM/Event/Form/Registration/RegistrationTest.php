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

/**
 * Class CRM_Event_Form_RegistrationTest
 * @group headless
 */
class CRM_Event_Form_Registration_RegistrationTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * CRM-19626 - Test minimum value configured for priceset.
   */
  public function testMinValueForPriceSet() {
    $form = new CRM_Event_Form_Registration();
    $form->controller = new CRM_Core_Controller();

    $minAmt = 100;
    $feeAmt = 1000;
    $event = $this->eventCreate();
    $priceSetId = $this->eventPriceSetCreate($feeAmt, $minAmt);
    $priceSet = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
    $form->_values['fee'] = $form->_feeBlock = $priceSet['fields'];
    $form->_values['event'] = $event['values'][$event['id']];
    $form->_skipDupeRegistrationCheck = 1;

    $priceField = $this->callAPISuccess('PriceField', 'get', ['price_set_id' => $priceSetId]);
    $params = [
      'email-Primary' => 'someone@example.com',
      'priceSetId' => $priceSetId,
    ];
    // Check empty values for price fields.
    foreach (array_keys($priceField['values']) as $fieldId) {
      $params['price_' . $fieldId] = 0;
    }
    $form->set('priceSetId', $priceSetId);
    $form->set('priceSet', $priceSet);
    $form->set('name', 'CRM_Event_Form_Registration_Register');
    $files = [];
    $errors = CRM_Event_Form_Registration_Register::formRule($params, $files, $form);

    //Assert the validation Error.
    $expectedResult = [
      '_qf_default' => ts('A minimum amount of %1 should be selected from Event Fee(s).', [1 => CRM_Utils_Money::format($minAmt)]),
    ];
    $this->checkArrayEquals($expectedResult, $errors);
  }

  /**
   * event#30
   */
  public function testDoubleWaitlistRegistration() {
    // By default, waitlist participant statuses are disabled (which IMO is poor UX).
    $sql = "UPDATE civicrm_participant_status_type SET is_active = 1";
    CRM_Core_DAO::executeQuery($sql);

    // Create an event, fill its participant slots.
    $event = $this->eventCreate([
      'has_waitlist' => 1,
      'max_participants' => 1,
      'start_date' => 20351021,
      'end_date' => 20351023,
      'registration_end_date' => 20351015,
    ]);
    $this->participantCreate(['event_id' => $event['id']]);

    // Add someone to the waitlist.
    $waitlistContact = $this->individualCreate();

    $firstWaitlist = $this->participantCreate(['event_id' => $event['id'], 'contact_id' => $waitlistContact, 'status_id' => 'On waitlist']);

    // We should now have two participants.
    $this->callAPISuccessGetCount('Participant', ['event_id' => $event['id']], 2);

    $form = new CRM_Event_Form_Registration_Register();
    $form->controller = new CRM_Core_Controller();
    $form->set('id', $event['id']);
    $form->set('cid', $waitlistContact);
    // We SHOULD get an error when double registering a waitlisted user.
    try {
      $form->preProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      return;
    }
    $this->fail('Waitlisted users shouldn\'t be allowed to re-register.');
  }

}
