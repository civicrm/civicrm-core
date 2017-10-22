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
    $form = $this->commonPrepare();
    $form->submit(array(
      'register_date' => 'now',
      'register_date_time' => '00:00:00',
      'status_id' => 1,
      'role_id' => 1,
      'event_id' => $form->_eventId,
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
    $form = $this->commonPrepare(array('is_monetary' => 1, 'financial_type_id' => 1));
    $paymentProcessorID = $this->processorCreate(array('is_test' => 0));
    $form->_mode = 'Live';
    $form->_values['fee'] = array();
    $form->_isPaidEvent = TRUE;
    $form->_quickConfig = TRUE;
    $form->_fromEmails = array(
      'from_email_id' => array('abc@gmail.com' => 1),
    );
    $form->submit(array(
      'register_date' => 'now',
      'register_date_time' => '00:00:00',
      'status_id' => 1,
      'role_id' => 1,
      'event_id' => $form->_eventId,
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
      'from_email_address' => 'abc@gmail.com',
      'send_receipt' => 1,
      'receipt_text' => '',
    ));
    $participants = $this->callAPISuccess('Participant', 'get', array());
    $this->assertEquals(1, $participants['count']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array());
    $this->assertEquals(55, $contribution['total_amount']);
    $this->assertEquals('Debit Card', $contribution['payment_instrument']);
  }

  /**
   * Test offline participant mail.
   */
  public function testParticipantOfflineReceipt() {
    $mut = new CiviMailUtils($this, TRUE);

    //Get workflow id of event_offline receipt.
    $workflowId = $this->callAPISuccess('OptionValue', 'get', array(
      'return' => array("id"),
      'option_group_id' => "msg_tpl_workflow_event",
      'name' => "event_offline_receipt",
    ));

    //Modify html to contain event_type_id token.
    $result = $this->callAPISuccess('MessageTemplate', 'get', array(
      'sequential' => 1,
      'return' => array("id", "msg_html"),
      'workflow_id' => $workflowId['id'],
      'is_default' => 1,
    ));
    $oldMsg = $result['values'][0]['msg_html'];
    $pos = strpos($oldMsg, 'Please print this confirmation');
    $newMsg = substr_replace($oldMsg, '<p>Test event type - {$event.event_type_id}</p>', $pos, 0);
    $this->callAPISuccess('MessageTemplate', 'create', array(
      'id' => $result['id'],
      'msg_html' => $newMsg,
    ));

    $this->testSubmitWithPayment();
    //Check if type is correctly populated in mails.
    $mail = $mut->checkMailLog(array(
        '<p>Test event type - 1</p>',
      )
    );
  }

  /**
   * Shared preparation.
   *
   * @param array $eventParams
   *
   * @return CRM_Event_Form_Participant
   */
  protected function commonPrepare($eventParams = array()) {
    $event = $this->eventCreate($eventParams);
    $contactID = $this->individualCreate();
    $form = $this->getFormObject('CRM_Event_Form_Participant');
    $form->_single = TRUE;
    $form->_contactId = $contactID;
    $form->setCustomDataTypes();
    $form->_eventId = $event['id'];
    if (!empty($eventParams['is_monetary'])) {
      $form->_mode = 'Live';
      $form->_bltID = 5;
      $form->_values['fee'] = array();
      $form->_isPaidEvent = TRUE;
    }
    return $form;
  }

}
