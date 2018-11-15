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
    $form = $this->getForm();
    $form->submit(array(
      'register_date' => date('Ymd'),
      'status_id' => 1,
      'role_id' => 1,
      'event_id' => $form->_eventId,
    ));
    $participants = $this->callAPISuccess('Participant', 'get', array());
    $this->assertEquals(1, $participants['count']);
  }

  /**
   * Test financial items pending transaction is later altered.
   *
   * @throws \Exception
   */
  public function testSubmitUnpaidPriceChangeWhileStillPending() {
    $form = $this->getForm(array('is_monetary' => 1, 'financial_type_id' => 1));
    $form->_quickConfig = TRUE;

    $form->_lineItem = array(
      0 => array(
        13 => array(
          'price_field_id' => $this->_ids['price_field'][0],
          'price_field_value_id' => $this->_ids['price_field_value'][0],
          'label' => 'Tiny-tots (ages 5-8)',
          'field_title' => 'Tournament Fees',
          'description' => NULL,
          'qty' => 1,
          'unit_price' => '800.000000000',
          'line_total' => 800.0,
          'participant_count' => 0,
          'max_value' => NULL,
          'membership_type_id' => NULL,
          'membership_num_terms' => NULL,
          'auto_renew' => NULL,
          'html_type' => 'Radio',
          'financial_type_id' => '4',
          'tax_amount' => NULL,
          'non_deductible_amount' => '0.00',
        ),
      ),
    );
    $form->setAction(CRM_Core_Action::ADD);
    $form->_priceSetId = $this->_ids['price_set'];
    $form->submit(array(
      'register_date' => date('Ymd'),
      'status_id' => 5,
      'role_id' => 1,
      'event_id' => $form->_eventId,
      'priceSetId' => $this->_ids['price_set'],
      'price_' . $this->_ids['price_field'][0]  => array(
        $this->_ids['price_field_value'][0] => 1,
      ),
      'is_pay_later' => 1,
      'amount_level' => 'Too much',
      'fee_amount' => 55,
      'total_amount' => 55,
      'payment_processor_id' => 0,
      'record_contribution' => TRUE,
      'financial_type_id' => 1,
      'contribution_status_id' => 2,
      'payment_instrument_id' => 1,
    ));
    $participants = $this->callAPISuccess('Participant', 'get', array());
    $this->assertEquals(1, $participants['count']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array());
    $this->assertEquals(2, $contribution['contribution_status_id']);
    $items = $this->callAPISuccess('FinancialItem', 'get', array());
    $this->assertEquals(1, $items['count']);

    $priceSetParams['price_' . $this->_ids['price_field'][0]] = $this->_ids['price_field_value'][1];
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($participants['id'], 'participant');
    $this->assertEquals(55, $lineItem[1]['subTotal']);
    $financialItems = $this->callAPISuccess('FinancialItem', 'get', array());
    $sum = 0;
    foreach ($financialItems['values'] as $financialItem) {
      $sum += $financialItem['amount'];
    }
    $this->assertEquals(55, $sum);

    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $participants['id'], 'participant', $contribution['id'], $this->eventFeeBlock, $lineItem, 100);
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($participants['id'], 'participant');
    // Participants is updated to 0 but line remains.
    $this->assertEquals(0, $lineItem[1]['subTotal']);
    $this->assertEquals(100, $lineItem[2]['subTotal']);
    $financialItems = $this->callAPISuccess('FinancialItem', 'get', array());

    $sum = 0;
    foreach ($financialItems['values'] as $financialItem) {
      $sum += $financialItem['amount'];
    }
    $this->assertEquals(100, $sum);
  }

  /**
   * (dev/core#310) : Test to ensure payments are correctly allocated, when a event fee is changed for a mult-line item event registration
   */
  public function testPaymentAllocationOnMultiLineItemEvent() {
    // USE-CASE :
    // 1. Create a Price set with two price field of type Text and FT (Financial Type) 'Event Fee' and 'Event Fee 2' respectively
    // 2. Register for a Event using both the price field A($55 - qty 1) and B($10 - qty 1)
    // 3. Now after registeration, edit the participant, change the fee of price B from $10 to $50 (i.e. change qty from 1 to 5)
    // 4. After submission check that related contribution's status is changed to 'Partially Paid'
    // 5. Record the additional amount which $40 ($50-$10)
    // Expected : Check the amount of new Financial Item created is $40

    // Create financial type - Event Fee 2
    $form = $this->getForm(array('is_monetary' => 1, 'financial_type_id' => 1), 'Text');

    // update price field 1
    $this->callAPISuccess('PriceFieldValue', 'create', ['id' => $this->_ids['price_field_value'][0], 'amount' => 55.00]);

    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes);
    $paramsField = array(
      'label' => 'Price Field 2',
      'name' => CRM_Utils_String::titleToVar('Price Field 2'),
      'html_type' => 'Text',
      'price' => 10,
      'option_label' => array('1' => 'Price Field'),
      'option_value' => array('1' => 10),
      'option_name' => array('1' => 10),
      'option_weight' => array('1' => 1),
      'option_amount' => array('1' => 10),
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => array('1' => 1),
      'price_set_id' => $this->_ids['price_set'],
      'is_enter_qty' => 1,
      'financial_type_id' => array_search('Campaign Contribution', $financialTypes),
    );
    // Create price set and its price fields
    $this->_ids['price_field'][] = CRM_Price_BAO_PriceField::create($paramsField)->id;
    $this->_ids['price_field_value'][] = $this->callAPISuccess('PriceFieldValue', 'getsingle', array('price_field_id' => $this->_ids['price_field'][1]))['id'];

    $form->_lineItem = array(
      0 => array(
        13 => array(
          'price_field_id' => $this->_ids['price_field'][0],
          'price_field_value_id' => $this->_ids['price_field_value'][0],
          'label' => 'Event Fee 1',
          'field_title' => 'Event Fee 1',
          'description' => NULL,
          'qty' => 1,
          'unit_price' => 55.00,
          'line_total' => 55.,
          'participant_count' => 0,
          'max_value' => NULL,
          'membership_type_id' => NULL,
          'membership_num_terms' => NULL,
          'auto_renew' => NULL,
          'html_type' => 'Text',
          'financial_type_id' => $this->getFinancialTypeId('Event Fee'),
          'tax_amount' => NULL,
          'non_deductible_amount' => '0.00',
        ),
        14 => array(
          'price_field_id' => $this->_ids['price_field'][1],
          'price_field_value_id' => $this->_ids['price_field_value'][1],
          'label' => 'Event Fee 2',
          'field_title' => 'Event Fee 2',
          'description' => NULL,
          'qty' => 1,
          'unit_price' => 10.00,
          'line_total' => 10.,
          'participant_count' => 0,
          'max_value' => NULL,
          'membership_type_id' => NULL,
          'membership_num_terms' => NULL,
          'auto_renew' => NULL,
          'html_type' => 'Text',
          'financial_type_id' => $this->getFinancialTypeId('Event Fee 2'),
          'tax_amount' => NULL,
          'non_deductible_amount' => '0.00',
        ),
      ),
    );
    $form->setAction(CRM_Core_Action::ADD);
    $form->_priceSetId = $this->_ids['price_set'];

    $form->submit(array(
      'register_date' => date('Ymd'),
      'status_id' => 5,
      'role_id' => 1,
      'event_id' => $form->_eventId,
      'priceSetId' => $this->_ids['price_set'],
      'price_' . $this->_ids['price_field'][0]  => array(
        $this->_ids['price_field_value'][0] => 1,
      ),
      'price_' . $this->_ids['price_field'][1]  => array(
        $this->_ids['price_field_value'][1] => 1,
      ),
      'amount_level' => 'Too much',
      'fee_amount' => 65,
      'total_amount' => 65,
      'payment_processor_id' => 0,
      'record_contribution' => TRUE,
      'financial_type_id' => 1,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
    ));

    $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($this->_ids['price_set'], TRUE, FALSE);
    $priceSet = CRM_Utils_Array::value($this->_ids['price_set'], $priceSet);
    $this->eventFeeBlock = CRM_Utils_Array::value('fields', $priceSet);

    $priceSetParams = [
      'priceSetId' => $this->_ids['price_set'],
      'price_' . $this->_ids['price_field'][0] => 1,
      'price_' . $this->_ids['price_field'][1] => 5,
    ];
    $participant = $this->callAPISuccess('Participant', 'get', []);
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($participant['id'], 'participant');
    $contribution = $this->callAPISuccessGetSingle('Contribution', array());
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $participant['id'], 'participant', $contribution['id'], $this->eventFeeBlock, $lineItem);

    $financialItems = $this->callAPISuccess('FinancialItem', 'get', []);
    $sum = 0;
    foreach ($financialItems['values'] as $financialItem) {
      $sum += $financialItem['amount'];
    }
    $this->assertEquals(105, $sum);

    $contribution = $this->callAPISuccessGetSingle('Contribution', array());
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $submittedValues = [
      'total_amount' => 40.00,
      'currency' => 'USD',
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      'check_number' => '#123',
    ];
    CRM_Contribute_BAO_Contribution::recordAdditionalPayment($contribution['id'], $submittedValues, 'owed', $participant['id']);

    $params = array('id' => $contribution['id']);
    $defaults = [];
    $contributions = CRM_Contribute_BAO_Contribution::retrieve($params, $defaults, $params);
    $contributions = array($contributions);
    CRM_Contribute_BAO_Contribution::addPayments($contributions, CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Partially paid'));

    list($financialItems, $taxItems) = CRM_Contribute_BAO_Contribution::getLastFinancialItems($contribution['id']);

    // Check that amount of last financial item for price field B is $40
    $this->assertEquals(40.00, $financialItems[$this->_ids['price_field_value'][1]]['amount']);

    $expectedEntityFinancialEntries = [
      // entry created for price field 1
      [
        'entity_table' => 'civicrm_financial_item',
        'amount' => 55.00,
      ],
      // entry created for price field 2
      [
        'entity_table' => 'civicrm_financial_item',
        'amount' => 10.00,
      ],
      // entry created for price field 2, after changing qty to 5 from 1, and due amount is $40
      [
        'entity_table' => 'civicrm_financial_item',
        'amount' => 40.00,
      ],
      // entry created after owed amount is paid
      [
        'entity_table' => 'civicrm_financial_item',
        'amount' => 40.00,
      ],
    ];
    $result = $this->callAPISuccess('EntityFinancialTrxn', 'get', ['entity_table' => 'civicrm_financial_item', 'sequential' => 1, 'return' => ['entity_table', 'amount']])['values'];
    foreach ($result as $key => $actualEntry) {
      unset($actualEntry['id']);
      $this->checkArrayEquals($actualEntry, $expectedEntityFinancialEntries[$key]);
    }
  }

  /**
   * Initial test of submit function.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   *
   * @throws \Exception
   */
  public function testSubmitWithPayment($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $form = $this->getForm(array('is_monetary' => 1, 'financial_type_id' => 1));
    $form->_mode = 'Live';
    $form->_quickConfig = TRUE;
    $paymentProcessorID = $this->processorCreate(array('is_test' => 0));
    $form->_fromEmails = array(
      'from_email_id' => array('abc@gmail.com' => 1),
    );
    $form->submit(array(
      'register_date' => date('Ymd'),
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
      'fee_amount' => $this->formatMoneyInput(1550.55),
      'total_amount' => $this->formatMoneyInput(1550.55),
      'from_email_address' => 'abc@gmail.com',
      'send_receipt' => 1,
      'receipt_text' => '',
    ));
    $participants = $this->callAPISuccess('Participant', 'get', array());
    $this->assertEquals(1, $participants['count']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array());
    $this->assertEquals(1550.55, $contribution['total_amount']);
    $this->assertEquals('Debit Card', $contribution['payment_instrument']);
  }

  /**
   * Test offline participant mail.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testParticipantOfflineReceipt($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
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

    $this->testSubmitWithPayment($thousandSeparator);
    //Check if type is correctly populated in mails.
    $mail = $mut->checkMailLog([
        '<p>Test event type - 1</p>',
        $this->formatMoneyInput(1550.55),
      ]
    );
  }

  /**
   * Get prepared form object.
   *
   * @param array $eventParams
   *
   * @return CRM_Event_Form_Participant
   */
  protected function getForm($eventParams = array(), $priceFieldType = 'Radio') {
    if (!empty($eventParams['is_monetary'])) {
      $event = $this->eventCreatePaid($eventParams, $priceFieldType);
    }
    else {
      $event = $this->eventCreate($eventParams);
    }

    $contactID = $this->individualCreate();
    $form = $this->getFormObject('CRM_Event_Form_Participant');
    $form->_single = TRUE;
    $form->_contactID = $form->_contactId = $contactID;
    $form->setCustomDataTypes();
    $form->_eventId = $event['id'];
    if (!empty($eventParams['is_monetary'])) {
      $form->_bltID = 5;
      $form->_values['fee'] = array();
      $form->_isPaidEvent = TRUE;
    }
    return $form;
  }

}
