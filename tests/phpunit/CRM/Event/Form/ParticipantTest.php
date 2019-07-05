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
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testPaymentAllocationOnMultiLineItemEvent() {
    // USE-CASE :
    // 1. Create a Price set with two price fields
    // 2. Register for a Event using both the price field A($55 - qty 1) and B($10 - qty 1)
    // 3. Now after registration, edit the participant, change the fee of price B from $10 to $50 (i.e. change qty from 1 to 5)
    // 4. After submission check that related contribution's status is changed to 'Partially Paid'
    // 5. Record the additional amount which $40 ($50-$10)
    // Expected : Check the amount of new Financial Item created is $40
    $this->createParticipantRecordsFromTwoFieldPriceSet();
    $priceSetBlock = CRM_Price_BAO_PriceSet::getSetDetail($this->_ids['price_set'], TRUE, FALSE)[$this->_ids['price_set']]['fields'];;

    $priceSetParams = [
      'priceSetId' => $this->_ids['price_set'],
      // The 1 & 5 refer to qty as they are text fields.
      'price_' . $this->_ids['price_field']['first_text_field'] => 5,
      'price_' . $this->_ids['price_field']['second_text_field'] => 1,
    ];
    $participant = $this->callAPISuccess('Participant', 'get', []);
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($participant['id'], 'participant');
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $participant['id'], 'participant', $contribution['id'], $priceSetBlock, $lineItem);

    $financialItems = $this->callAPISuccess('FinancialItem', 'get', [])['values'];
    $sum = 0;
    foreach ($financialItems as $financialItem) {
      $sum += $financialItem['amount'];
    }
    $this->assertEquals(105, $sum);
    $this->assertEquals(3, count($financialItems));

    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contribution['id'],
      'participant_id' => $participant['id'],
      'total_amount' => 40.00,
      'currency' => 'USD',
      'payment_instrument_id' => 'Check',
      'check_number' => '#123',
    ]);

    $result = $this->callAPISuccess('EntityFinancialTrxn', 'get', ['entity_table' => 'civicrm_financial_item', 'sequential' => 1, 'return' => ['entity_table', 'amount']])['values'];
    // @todo check the values assigned to these as part of fixing dev/financial#34
  }

  /**
   * Initial test of submit function.
   *
   * @param string $thousandSeparator
   * @param array $fromEmails From Emails array to overwrite the default.
   *
   * @dataProvider getThousandSeparators
   *
   * @throws \Exception
   */
  public function testSubmitWithPayment($thousandSeparator, $fromEmails = []) {
    $this->setCurrencySeparators($thousandSeparator);
    $form = $this->getForm(array('is_monetary' => 1, 'financial_type_id' => 1));
    $form->_mode = 'Live';
    $form->_quickConfig = TRUE;
    $paymentProcessorID = $this->processorCreate(array('is_test' => 0));
    if (empty($fromEmails)) {
      $fromEmails = [
        'from_email_id' => ['abc@gmail.com' => 1],
      ];
    }
    $form->_fromEmails = $fromEmails;
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
      'from_email_address' => array_keys($form->_fromEmails['from_email_id'])[0],
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
    // Create an email associated with the logged in contact
    $loggedInContactID = $this->createLoggedInUser();
    $email = $this->callAPISuccess('Email', 'create', [
      'contact_id' => $loggedInContactID,
      'is_primary' => 1,
      'email' => 'testLoggedInReceiptEmail@civicrm.org',
      'location_type_id' => 1,
    ]);

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

    // Use the email created as the from email ensuring we are passing a numeric from to test dev/core#1069
    $this->testSubmitWithPayment($thousandSeparator, ['from_email_id' => [$email['id'] => 1]]);
    //Check if type is correctly populated in mails.
    //Also check the string email is present not numeric from.
    $mail = $mut->checkMailLog([
      '<p>Test event type - 1</p>',
      'testloggedinreceiptemail@civicrm.org',
      $this->formatMoneyInput(1550.55),
    ]);
    $this->callAPISuccess('Email', 'delete', ['id' => $email['id']]);
  }

  /**
   * Get prepared form object.
   *
   * @param array $eventParams
   *
   * @return CRM_Event_Form_Participant
   * @throws \CRM_Core_Exception
   */
  protected function getForm($eventParams = []) {
    if (!empty($eventParams['is_monetary'])) {
      $event = $this->eventCreatePaid($eventParams);
    }
    else {
      $event = $this->eventCreate($eventParams);
    }

    $contactID = $this->individualCreate();
    /** @var CRM_Event_Form_Participant $form*/
    $form = $this->getFormObject('CRM_Event_Form_Participant');
    $form->_single = TRUE;
    $form->_contactID = $form->_contactId = $contactID;
    $form->setCustomDataTypes();
    $form->_eventId = $event['id'];
    if (!empty($eventParams['is_monetary'])) {
      $form->_bltID = 5;
      $form->_values['fee'] = [];
      $form->_isPaidEvent = TRUE;
    }
    return $form;
  }

  /**
   * Create a Price set with two price field of type Text.
   *
   * Financial Type:  'Event Fee' and 'Event Fee 2' respectively.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected function createParticipantRecordsFromTwoFieldPriceSet() {
    // Create financial type - Event Fee 2
    $form = $this->getForm(['is_monetary' => 1, 'financial_type_id' => 1]);

    $textFieldsToCreate = [['amount' => 10, 'label' => 'First Text field'], ['amount' => 55, 'label' => 'Second Text field']];
    foreach ($textFieldsToCreate as $fieldToCreate) {
      $fieldParams = [
        'option_label' => ['1' => 'Price Field'],
        'option_value' => ['1' => $fieldToCreate['amount']],
        'option_name' => ['1' => $fieldToCreate['amount']],
        'option_amount' => ['1' => $fieldToCreate['amount']],
        'option_weight' => ['1' => $fieldToCreate['amount']],
        'is_display_amounts' => 1,
        'price_set_id' => $this->_ids['price_set'],
        'is_enter_qty' => 1,
        'html_type' => 'Text',
        'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
      ];
      $fieldParams['label'] = $fieldToCreate['label'];
      $fieldParams['name'] = CRM_Utils_String::titleToVar($fieldToCreate['label']);
      $fieldParams['price'] = $fieldToCreate['amount'];
      $this->_ids['price_field'][strtolower(CRM_Utils_String::titleToVar($fieldToCreate['label']))] = $textPriceFieldID = $this->callAPISuccess('PriceField', 'create', $fieldParams)['id'];
      $this->_ids['price_field_value'][strtolower(CRM_Utils_String::titleToVar($fieldToCreate['label']))]  = (int) $this->callAPISuccess('PriceFieldValue', 'getsingle', ['price_field_id' => $textPriceFieldID])['id'];
    }

    $form->_lineItem = [
      0 => [
        13 => [
          'price_field_id' => $this->_ids['price_field']['second_text_field'],
          'price_field_value_id' => $this->_ids['price_field_value']['second_text_field'],
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
          'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
          'tax_amount' => NULL,
          'non_deductible_amount' => '0.00',
        ],
        14 => [
          'price_field_id' => $this->_ids['price_field']['first_text_field'],
          'price_field_value_id' => $this->_ids['price_field_value']['first_text_field'],
          'label' => 'Event Fee 2',
          'field_title' => 'Event Fee 2',
          'description' => NULL,
          'qty' => 1,
          'unit_price' => 10.00,
          'line_total' => 10,
          'participant_count' => 0,
          'max_value' => NULL,
          'membership_type_id' => NULL,
          'membership_num_terms' => NULL,
          'auto_renew' => NULL,
          'html_type' => 'Text',
          'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
          'tax_amount' => NULL,
          'non_deductible_amount' => '0.00',
        ],
      ],
    ];
    $form->setAction(CRM_Core_Action::ADD);
    $form->_priceSetId = $this->_ids['price_set'];

    $form->submit([
      'register_date' => date('Ymd'),
      'receive_date' => '2018-09-01',
      'status_id' => 5,
      'role_id' => 1,
      'event_id' => $form->_eventId,
      'priceSetId' => $this->_ids['price_set'],
      'price_' . $this->_ids['price_field']['first_text_field'] => [$this->_ids['price_field_value']['first_text_field'] => 1],
      'price_' . $this->_ids['price_field']['second_text_field'] => [$this->_ids['price_field_value']['second_text_field'] => 1],
      'amount_level' => 'Too much',
      'fee_amount' => 65,
      'total_amount' => 65,
      'payment_processor_id' => 0,
      'record_contribution' => TRUE,
      'financial_type_id' => 1,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
    ]);
  }

}
