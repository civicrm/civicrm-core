<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_ParticipantTest extends CiviUnitTestCase {

  public function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
  }

  /**
   * Initial test of submit function.
   *
   * @throws \Exception
   */
  public function testSubmit() {
    $event = $this->eventCreate();
    $contactID = $this->individualCreate();
    $form = $this->getFormObject('CRM_Event_Form_Participant');
    $form->_single = TRUE;
    $form->_contactId = $contactID;
    $form->setCustomDataTypes();
    $form->submit(array(
      'register_date' => 'now',
      'register_date_time' => '00:00:00',
      'status_id' => 1,
      'role_id' => 1,
      'event_id' => $event['id'],
    ));
    $participants = $this->callAPISuccess('Participant', 'get', array());
    $this->assertEquals(1, $participants['count']);
  }

  /**
   * Initial test of submit function.
   *
   * @throws \Exception
   */
  public function testSubmitWithPayment() {
    $event = $this->eventCreate(array('is_monetary' => 1, 'financial_type_id' => 1));
    $contactID = $this->individualCreate();
    $form = $this->getFormObject('CRM_Event_Form_Participant');
    $form->_single = TRUE;
    $form->_contactId = $contactID;
    $form->setCustomDataTypes();
    $form->_bltID = 5;
    $form->_eventId = $event['id'];
    $paymentProcessorID = $this->processorCreate(array('is_test' => 0));
    $form->_mode = 'Live';
    $form->_values['fee'] = array();
    $form->_isPaidEvent = TRUE;
    $form->_quickConfig = TRUE;
    $form->submit(array(
      'register_date' => 'now',
      'register_date_time' => '00:00:00',
      'status_id' => 1,
      'role_id' => 1,
      'event_id' => $event['id'],
      'credit_card_number' => 4444333322221111,
      'cvv2' => 123,
      'credit_card_exp_date' => array(
        'M' => 9,
        'Y' => 2025,
      ),
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Junko',
      'billing_middle_name' => '',
      'billing_last_name' => 'Adams',
      'billing_street_address-5' => '790L Lincoln St S',
      'billing_city-5' => 'Maryknoll',
      'billing_state_province_id-5' => 1031,
      'billing_postal_code-5' => 10545,
      'billing_country_id-5' => 1228,
      'payment_processor_id' => $paymentProcessorID,
      'priceSetId' => '6',
      'price_7' => array(
        13 => 1,
      ),
      'amount_level' => 'Too much',
      'fee_amount' => 55,
      'total_amount' => 55,
    ));
    $participants = $this->callAPISuccess('Participant', 'get', array());
    $this->assertEquals(1, $participants['count']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array());
    $this->assertEquals(55, $contribution['total_amount']);
  }

}
