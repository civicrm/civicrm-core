<?php

use Civi\Api4\Address;
use Civi\Api4\LocBlock;
use Civi\Api4\Participant;
use Civi\Api4\Phone;
use Civi\Test\FormTrait;

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_ParticipantTest extends CiviUnitTestCase {

  use CRMTraits_Financial_OrderTrait;
  use CRMTraits_Financial_PriceSetTrait;
  use FormTrait;

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Initial test of submit function.
   *
   * @throws \Exception
   */
  public function testSubmit(): void {
    $form = $this->getForm([], [
      'register_date' => date('Ymd'),
      'status_id' => 1,
      'role_id' => 1,
    ]);
    $form->postProcess();
    $this->assertEquals($this->getEventID(), $form->getEventID());
    $this->callAPISuccessGetSingle('Participant', []);
  }

  /**
   * Test financial items pending transaction is later altered.
   *
   * @throws \Exception
   */
  public function testSubmitUnpaidPriceChangeWhileStillPending(): void {
    $form = $this->getForm(['is_monetary' => 1, 'financial_type_id' => 1], [], TRUE);

    $form->_lineItem = [
      0 => [
        13 => [
          'price_field_id' => $this->getPriceFieldID(),
          'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_standard'],
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
        ],
      ],
    ];
    $form->setAction(CRM_Core_Action::ADD);
    $form->_priceSetId = $this->getPriceSetID('PaidEvent');
    $form->submit([
      'register_date' => date('Ymd'),
      'status_id' => 5,
      'role_id' => 1,
      'event_id' => $form->getEventID(),
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      $this->getPriceFieldKey() => $this->ids['PriceFieldValue']['PaidEvent_student'],
      'is_pay_later' => 1,
      'amount_level' => 'Too much',
      'fee_amount' => 55,
      'total_amount' => 55,
      'payment_processor_id' => 0,
      'record_contribution' => TRUE,
      'financial_type_id' => 1,
      'contribution_status_id' => 2,
      'payment_instrument_id' => 1,
      'receive_date' => date('Y-m-d'),
    ]);
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $this->assertEquals(2, $contribution['contribution_status_id']);
    $this->callAPISuccessGetSingle('FinancialItem', []);

    $priceSetParams[$this->getPriceFieldKey()] = $this->ids['PriceFieldValue']['PaidEvent_family_package'];
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($participant['id'], 'participant');
    $this->assertEquals(55, $lineItem[1]['subTotal']);
    $financialItems = $this->callAPISuccess('FinancialItem', 'get', []);
    $sum = 0;
    foreach ($financialItems['values'] as $financialItem) {
      $sum += $financialItem['amount'];
    }
    $this->assertEquals(55, $sum);

    $priceSetID = $this->ids['PriceSet']['PaidEvent'];
    $eventFeeBlock = CRM_Price_BAO_PriceSet::getSetDetail($priceSetID)[$priceSetID]['fields'];
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $participant['id'], 'participant', $contribution['id'], $eventFeeBlock);
    // Check that no payment records have been created.
    // In https://lab.civicrm.org/dev/financial/issues/94 we had an issue where payments were created when none happened.
    $payments = $this->callAPISuccess('Payment', 'get', [])['values'];
    $this->assertCount(0, $payments);
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($participant['id']);
    // Participants is updated to 0 but line remains.
    $this->assertEquals(0, $lineItem[1]['subTotal']);
    $this->assertEquals(1550.55, $lineItem[2]['subTotal']);
    $financialItems = $this->callAPISuccess('FinancialItem', 'get', []);

    $sum = 0;
    foreach ($financialItems['values'] as $financialItem) {
      $sum += $financialItem['amount'];
    }
    $this->assertEquals(1550.55, $sum);
  }

  /**
   * (dev/core#310) : Test to ensure payments are correctly allocated, when
   * an event fee is changed for a multi-line item event registration
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaymentAllocationOnMultiLineItemEvent(): void {
    // USE-CASE :
    // 1. Create a Price set with two price fields
    // 2. Register for a Event using both the price field A($55 - qty 1) and B($10 - qty 1)
    // 3. Now after registration, edit the participant, change the fee of price B from $10 to $50 (i.e. change qty from 1 to 5)
    // 4. After submission check that related contribution's status is changed to 'Partially Paid'
    // 5. Record the additional amount which $40 ($50-$10)
    // Expected : Check the amount of new Financial Item created is $40
    $this->createParticipantRecordsFromTwoFieldPriceSet();
    $priceSetBlock = CRM_Price_BAO_PriceSet::getSetDetail($this->getPriceSetID('PaidEvent'), TRUE, FALSE)[$this->getPriceSetID('PaidEvent')]['fields'];

    $priceSetParams = [
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      // The 1 & 5 refer to qty as they are text fields.
      'price_' . $this->ids['PriceField']['first_text_field'] => 5,
      'price_' . $this->ids['PriceField']['second_text_field'] => 1,
    ];
    $participant = $this->callAPISuccess('Participant', 'get', []);
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $participant['id'], 'participant', $contribution['id'], $priceSetBlock);

    $financialItems = $this->callAPISuccess('FinancialItem', 'get', [])['values'];
    $sum = 0;
    foreach ($financialItems as $financialItem) {
      $sum += $financialItem['amount'];
    }
    $this->assertEquals(105, $sum);
    $this->assertCount(3, $financialItems);

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
    $this->assertEquals(40, $result[2]['amount']);
    $this->assertCount(4, $result);
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
  public function testSubmitWithPayment(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $form = $this->getForm(['is_monetary' => 1, 'financial_type_id' => 1], [], TRUE);
    $form->_mode = 'Live';
    $paymentProcessorID = $this->processorCreate(['is_test' => 0]);
    $form->submit($this->getSubmitParamsForCreditCardPayment($paymentProcessorID));
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $this->assertEquals('2018-09-04 00:00:00', $participant['participant_register_date']);
    $this->assertEquals('Offline Registration for Event: Annual CiviCRM meet by: ', $participant['participant_source']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $this->assertEquals(1550.55, $contribution['total_amount']);
    $this->assertEquals('Debit Card', $contribution['payment_instrument']);
    $lineItem = $this->callAPISuccessGetSingle('LineItem', []);
    $expected = [
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_participant',
      'qty' => 1,
      'label' => 'Family Deal',
      'unit_price' => 1550.55,
      'line_total' => 1550.55,
      'participant_count' => 0,
      'price_field_id' => $this->getPriceFieldID(),
      'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_family_package'],
      'tax_amount' => 0,
      // Interestingly the financial_type_id set in this test is ignored but currently locking in what is happening with this test so setting to 'actual'
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Event Fee'),
    ];
    foreach ($expected as $key => $value) {
      $this->assertEquals($value, $lineItem[$key], $key);
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
  public function testSubmitWithFailedPayment(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $form = $this->getForm(['is_monetary' => 1, 'financial_type_id' => 1], [], TRUE);
    $form->_mode = 'Live';
    $paymentProcessorID = $this->processorCreate(['is_test' => 0]);
    Civi\Payment\System::singleton()->getById($paymentProcessorID)->setDoDirectPaymentResult(['payment_status_id' => 'failed']);

    $form->_fromEmails = [
      'from_email_id' => ['abc@gmail.com' => 1],
    ];
    try {
      $form->submit($this->getSubmitParamsForCreditCardPayment($paymentProcessorID));
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      return;
    }
    $this->fail('should have hit premature exit');
  }

  /**
   * Test offline participant mail.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   * @throws \Exception
   */
  public function testParticipantOfflineReceipt(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->swapMessageTemplateForTestTemplate('event_offline_receipt', 'text');
    $this->swapMessageTemplateForTestTemplate('event_offline_receipt', 'html');
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
    $workflowId = $this->callAPISuccess('OptionValue', 'get', [
      'return' => ['id'],
      'option_group_id' => 'msg_tpl_workflow_event',
      'name' => 'event_offline_receipt',
    ]);

    //Modify html to contain event_type_id token.
    $result = $this->callAPISuccess('MessageTemplate', 'get', [
      'sequential' => 1,
      'return' => ['id', 'msg_html'],
      'workflow_id' => $workflowId['id'],
      'is_default' => 1,
    ]);
    $oldMsg = $result['values'][0]['msg_html'];
    $pos = strpos($oldMsg, 'Please print this confirmation');
    $newMsg = substr_replace($oldMsg, '<p>Test event type - {$event.event_type_id}</p>', $pos, 0);
    $this->callAPISuccess('MessageTemplate', 'create', [
      'id' => $result['id'],
      'msg_html' => $newMsg,
    ]);

    // Use the email created as the from email ensuring we are passing a numeric from to test dev/core#1069
    $this->setCurrencySeparators($thousandSeparator);
    $form = $this->getForm(['is_monetary' => 1, 'financial_type_id' => 1, 'pay_later_receipt' => 'pay us'], [], TRUE);
    $form->_mode = 'Live';
    $form->_fromEmails = [
      'from_email_id' => [$email['id'] => 1],
    ];
    $paymentProcessorID = $this->processorCreate(['is_test' => 0]);
    $submitParams = $this->getSubmitParamsForCreditCardPayment($paymentProcessorID);
    $submitParams['from_email_address'] = $email['id'];
    $form->submit($submitParams);
    $participantID = Participant::get()->addWhere('event_id', '=', $this->getEventID('PaidEvent'))->execute()->first()['id'];
    //Check if type is correctly populated in mails.
    //Also check the string email is present not numeric from.
    $mut->checkMailLog([
      'contactID:::' . $this->getContactID(),
      'contact.id:::' . $this->getContactID(),
      'eventID:::' . $this->getEventID('PaidEvent'),
      'event.id:::' . $this->getEventID('PaidEvent'),
      'participantID:::' . $participantID,
      'participant.id:::' . $participantID,
      '<p>Test event type - 1</p>',
      'event.title:::Annual CiviCRM meet',
      'participant.status_id:name:::Registered',
      'testloggedinreceiptemail@civicrm.org',
      'event.pay_later_receipt:::pay us',
      $this->formatMoneyInput(1550.55),
      'event.loc_block_id.phone_id.phone:::1235',
      'event.loc_block_id.phone_id.phone_type_id:label:::Mobile',
      'event.loc_block_id.phone_id.phone_ext:::456',
      'event.confirm_email_text::Just do it',
      'contribution.total_amount:::' . Civi::format()->money(1550.55),
      'contribution.total_amount|raw:::1550.55',
      'contribution.paid_amount:::' . Civi::format()->money(1550.55),
      'contribution.paid_amount|raw:::1550.55',
      'contribution.balance_amount:::' . Civi::format()->money(0),
      'contribution.balance_amount|raw is zero:::Yes',
      'contribution.balance_amount|raw string is zero:::Yes',
      'contribution.balance_amount|boolean:::No',
      'contribution.paid_amount|boolean:::Yes',
      '<p>Test event type - 1</p>event.location:8 Baker Street<br />
London,',
      '$location.address.1.display:<div class="location vcard"><span class="adr"><span class="street-address">8 Baker Street</span><br />
<span class="extended-address">Upstairs</span><br />
<span class="locality">London</span>,<br />
</span></div>',
    ]);

    $this->callAPISuccess('Email', 'delete', ['id' => $email['id']]);
  }

  /**
   * Get prepared form object.
   *
   * @param array $eventParams
   * @param array $submittedValues
   * @param bool $isQuickConfig
   *
   * @return CRM_Event_Form_Participant
   *
   * @throws \CRM_Core_Exception
   */
  protected function getForm(array $eventParams = [], array $submittedValues = [], bool $isQuickConfig = FALSE): CRM_Event_Form_Participant {
    $submittedValues['contact_id'] = $this->ids['Contact']['event'] = $this->individualCreate();

    if (!empty($eventParams['is_monetary'])) {
      $phone = Phone::create()->setValues(['phone' => 1235, 'phone_type_id:name' => 'Mobile', 'phone_ext' => 456])->execute()->first();
      $address = Address::create()->setValues(['street_address' => '8 Baker Street', 'supplemental_address_1' => 'Upstairs', 'city' => 'London'])->execute()->first();
      $locationBlockID = LocBlock::create()->setValues(['phone_id' => $phone['id'], 'address_id' => $address['id']])->execute()->first()['id'];
      $event = $this->eventCreatePaid(array_merge([
        'loc_block_id' => $locationBlockID,
        'confirm_email_text' => "Just do it\n Now",
        'is_show_location' => TRUE,
      ], $eventParams), ['is_quick_config' => $isQuickConfig]);
      $submittedValues = array_merge($this->getRecordContributionParams('Partially paid'), $submittedValues);
    }
    else {
      $event = $this->eventCreateUnpaid($eventParams);
    }
    $submittedValues['event_id'] = $event['id'];
    $_REQUEST['cid'] = $submittedValues['contact_id'];
    /** @var CRM_Event_Form_Participant $form */
    $form = $this->getFormObject('CRM_Event_Form_Participant', $submittedValues);
    $form->preProcess();
    $form->buildForm();
    return $form;
  }

  /**
   * Create a Price set with two price field of type Text.
   *
   * Financial Type:  'Event Fee' and 'Event Fee 2' respectively.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createParticipantRecordsFromTwoFieldPriceSet(): void {
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
        'price_set_id' => $this->getPriceSetID('PaidEvent'),
        'is_enter_qty' => 1,
        'html_type' => 'Text',
        'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
      ];
      $fieldParams['label'] = $fieldToCreate['label'];
      $fieldParams['name'] = CRM_Utils_String::titleToVar($fieldToCreate['label']);
      $fieldParams['price'] = $fieldToCreate['amount'];
      $this->ids['PriceField'][strtolower($fieldParams['name'])] = $textPriceFieldID = $this->callAPISuccess('PriceField', 'create', $fieldParams)['id'];
      $this->ids['PriceFieldValue'][strtolower($fieldParams['name'])] = (int) $this->callAPISuccess('PriceFieldValue', 'getsingle', ['price_field_id' => $textPriceFieldID])['id'];
    }

    $form->_lineItem = [
      0 => [
        13 => [
          'price_field_id' => $this->ids['PriceField']['second_text_field'],
          'price_field_value_id' => $this->ids['PriceFieldValue']['second_text_field'],
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
          'price_field_id' => $this->ids['PriceField']['first_text_field'],
          'price_field_value_id' => $this->ids['PriceFieldValue']['first_text_field'],
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
    $form->_priceSetId = $this->getPriceSetID('PaidEvent');

    $form->submit([
      'register_date' => date('Ymd'),
      'receive_date' => '2018-09-01',
      'status_id' => 5,
      'role_id' => 1,
      'event_id' => $this->getEventID('PaidEvent'),
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      'price_' . $this->ids['PriceField']['first_text_field'] => [$this->ids['PriceFieldValue']['first_text_field'] => 1],
      'price_' . $this->ids['PriceField']['second_text_field'] => [$this->ids['PriceFieldValue']['second_text_field'] => 1],
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

  /**
   * Get params for submit function.
   *
   * @param int $paymentProcessorID
   *
   * @return array
   */
  private function getSubmitParamsForCreditCardPayment(int $paymentProcessorID): array {
    return [
      'register_date' => '2018-09-04',
      'status_id' => 1,
      'role_id' => 1,
      'event_id' => $this->getEventID('PaidEvent'),
      'credit_card_number' => 4444333322221111,
      'cvv2' => 123,
      'credit_card_exp_date' => [
        'M' => 9,
        'Y' => 2025,
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Junko',
      'billing_middle_name' => '',
      'billing_last_name' => 'Adams',
      'billing_street_address-5' => '790L Lincoln St S',
      'billing_city-5' => 'Baltimore',
      'billing_state_province_id-5' => 1031,
      'billing_postal_code-5' => 10545,
      'billing_country_id-5' => 1228,
      'payment_processor_id' => $paymentProcessorID,
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      $this->getPriceFieldKey()  => $this->ids['PriceFieldValue']['PaidEvent_family_package'],
      'amount_level' => 'Too much',
      'fee_amount' => $this->formatMoneyInput(1550.55),
      'total_amount' => $this->formatMoneyInput(1550.55),
      'from_email_address' => '"FIXME" <info@EXAMPLE.ORG>',
      'send_receipt' => 1,
      'receipt_text' => '',
    ];
  }

  /**
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitWithDeferredRecognition(): void {
    Civi::settings()->set('deferred_revenue_enabled', TRUE);
    $futureDate = date('Y') + 1 . '-09-20';
    $form = $this->getForm(['is_monetary' => 1, 'financial_type_id' => 1, 'start_date' => $futureDate], [
      'record_contribution' => TRUE,
      'financial_type_id' => 1,
    ]);
    $form->postProcess();
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    // Api doesn't retrieve it & we don't much want to change that as we want to feature freeze BAO_Query.
    $this->assertEquals($futureDate . ' 00:00:00', CRM_Core_DAO::singleValueQuery("SELECT revenue_recognition_date FROM civicrm_contribution WHERE id = {$contribution['id']}"));
  }

  /**
   * Test submitting a partially paid event registration.
   *
   * In this case the participant status is selected as 'partially paid' and
   * a contribution is created for the full amount with a payment equal to the
   * entered amount.
   *
   * @dataProvider getBooleanDataProvider
   *
   * @param bool $isQuickConfig
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPartialPayment(bool $isQuickConfig): void {
    $mailUtil = new CiviMailUtils($this, TRUE);
    $form = $this->getForm(['is_monetary' => 1, 'start_date' => '2023-02-15 15:00', 'end_date' => '2023-02-15 18:00'], [
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'total_amount' => '20',
      'send_receipt' => '1',
      'contact_id' => $this->getContactID(),
      'register_date' => '2020-01-31 00:50:00',
      'role_id' => [0 => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Attendee')],
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Partially paid'),
      'source' => 'I wrote this',
      'note' => 'I wrote a note',
    ], $isQuickConfig);
    $this->callAPISuccess('PriceSet', 'create', ['is_quick_config' => $isQuickConfig, 'id' => $this->getPriceSetID('PaidEvent')]);
    $form->postProcess();
    $this->assertPartialPaymentResult($isQuickConfig, $mailUtil);
  }

  /**
   * Test submitting a partially paid event registration, recording a pending contribution.
   *
   * @dataProvider getBooleanDataProvider
   *
   * @param bool $isQuickConfig
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPendingPartiallyPaidAddPayment(bool $isQuickConfig): void {
    $mailUtil = new CiviMailUtils($this, TRUE);
    $form = $this->getForm(['is_monetary' => 1, 'start_date' => '2023-02-15 15:00', 'end_date' => '2023-02-15 18:00'], [], $isQuickConfig);
    $paymentInstrumentID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check');
    $form->postProcess();
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->callAPISuccessGetValue('Contribution', ['return' => 'id']),
      'total_amount'  => 20,
      'check_number' => 879,
      'payment_instrument_id' => $paymentInstrumentID,
    ]);
    $this->assertPartialPaymentResult($isQuickConfig, $mailUtil, FALSE);
  }

  /**
   * Test submitting a partially paid event registration, recording a pending contribution.
   *
   * This tests
   *
   * @dataProvider getBooleanDataProvider
   *
   * @param bool $isQuickConfig
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPendingAddPayment(bool $isQuickConfig): void {
    $mailUtil = new CiviMailUtils($this, TRUE);
    $form = $this->getForm(['is_monetary' => 1, 'start_date' => '2023-02-15 15:00', 'end_date' => '2023-02-15 18:00'], [], $isQuickConfig);
    $paymentInstrumentID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check');
    $form->postProcess();
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->callAPISuccessGetValue('Contribution', ['return' => 'id']),
      'total_amount'  => 20,
      'check_number' => 879,
      'payment_instrument_id' => $paymentInstrumentID,
    ]);
    $this->assertPartialPaymentResult($isQuickConfig, $mailUtil, FALSE);
  }

  /**
   * @param bool $isQuickConfig
   * @param \CiviMailUtils $mut
   * @param bool $isAmountPaidOnForm
   *   Was the amount paid entered on the form (if so this should be on the receipt)
   */
  protected function assertPartialPaymentResult(bool $isQuickConfig, CiviMailUtils $mut, bool $isAmountPaidOnForm = TRUE): void {
    $paymentInstrumentID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check');
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $expected = [
      'contact_id' => $this->getContactID(),
      'total_amount' => '1550.55',
      'fee_amount' => '0.00',
      'net_amount' => '1550.55',
      'contribution_source' => 'I wrote this',
      'amount_level' => '',
      'is_template' => '0',
      'financial_type' => 'Event Fee',
      'payment_instrument' => 'Check',
      'contribution_status' => 'Partially paid',
      'check_number' => '879',
    ];
    $this->assertAttributesEquals($expected, $contribution);

    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $this->assertAttributesEquals([
      'contact_id' => $this->getContactID(),
      'event_title' => 'Annual CiviCRM meet',
      'participant_fee_level' => [0 => 'Family Deal - 1'],
      'participant_fee_amount' => '1550.55',
      'participant_fee_currency' => 'USD',
      'event_type' => 'Conference',
      'participant_status' => 'Partially paid',
      'participant_role' => 'Attendee',
      'participant_source' => 'I wrote this',
      'participant_note' => 'I wrote a note',
      'participant_is_pay_later' => '0',
    ], $participant);
    $lineItem = $this->callAPISuccessGetSingle('LineItem', []);
    $this->assertAttributesEquals([
      'entity_table' => 'civicrm_participant',
      'entity_id' => $participant['id'],
      'contribution_id' => $contribution['id'],
      'price_field_id' => $this->getPriceFieldID(),
      'label' => 'Family Deal',
      'qty' => '1.00',
      'unit_price' => '1550.55',
      'line_total' => '1550.55',
      'participant_count' => '0',
      'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_family_package'],
      'financial_type_id' => '4',
      'tax_amount' => '0.00',
    ], $lineItem);

    $payment = $this->callAPISuccessGetSingle('FinancialTrxn', ['is_payment' => 1]);
    $this->assertAttributesEquals([
      'to_financial_account_id' => 6,
      'from_financial_account_id' => 7,
      'total_amount' => 20,
      'fee_amount' => '0.00',
      'net_amount' => 20,
      'currency' => 'USD',
      'status_id' => '1',
      'payment_instrument_id' => $paymentInstrumentID,
      'check_number' => '879',
    ], $payment);

    $financialItem = $this->callAPISuccessGetSingle('FinancialItem', []);
    $this->assertAttributesEquals([
      'description' => 'Family Deal',
      'contact_id' => $this->getContactID(),
      'amount' => 1550.55,
      'currency' => 'USD',
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_FinancialItem', 'status_id', 'Partially paid'),
      'entity_table' => 'civicrm_line_item',
      'entity_id' => $lineItem['id'],
      'financial_account_id' => 4,
    ], $financialItem);

    $mut->checkMailLog([
      'From: "FIXME" <info@EXAMPLE.ORG>',
      'To: Anthony Anderson <anthony_anderson@civicrm.org>',
      'Subject: Event Confirmation - Annual CiviCRM meet - Mr. Anthony Anderson II',
      'Dear Anthony,Contact the Development Department if you need to make any changes to your registration.',
      'Event Information and Location',
      'Annual CiviCRM meet',
      'Registered Email',
      'Just do it',
      $isQuickConfig ? $this->formatMoneyInput(1550.55) . ' Family Deal - 1' : 'Fundraising Dinner - Family...',
      $isAmountPaidOnForm ? 'Total Paid: $20.00' : 'Total Paid: ',
      $isAmountPaidOnForm ? 'Balance: $1,530.55' : 'Balance: $1,550.55',
      'Financial Type: Event Fee',
      'Paid By: Check',
      'February 15th, 2023  3:00 PM- 6:00 PM',
      'Check Number: 879',
    ]);
  }

  /**
   * Get the price field id that has been created for the test.
   *
   * @return int
   */
  protected function getPriceFieldID(): int {
    return (int) reset($this->ids['PriceField']);
  }

  /**
   * Get the array key for the configured price field.
   *
   * @return string
   */
  protected function getPriceFieldKey(): string {
    return 'price_' . $this->getPriceFieldID();
  }

  /**
   * Get the price field value id that has been created for the test.
   *
   * @return int
   */
  protected function getPriceFieldValueID(): int {
    return (int) $this->ids['PriceFieldValue']['PaidEvent_standard'];
  }

  /**
   * Get the parameters for recording a contribution.
   *
   * @param string $participantStatus
   *
   * @return array
   */
  protected function getRecordContributionParams(string $participantStatus): array {
    return [
      'hidden_feeblock' => '1',
      'hidden_eventFullMsg' => '',
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      $this->getPriceFieldKey() => $this->ids['PriceFieldValue']['PaidEvent_family_package'],
      'check_number' => '879',
      'record_contribution' => '1',
      'financial_type_id' => '4',
      'receive_date' => '2020-01-31 00:51:00',
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      'trxn_id' => '',
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      'total_amount' => '20',
      'send_receipt' => '1',
      'from_email_address' => '"FIXME" <info@EXAMPLE.ORG>',
      'receipt_text' => 'Contact the Development Department if you need to make any changes to your registration.',
      'hidden_custom' => '1',
      'hidden_custom_group_count' => ['' => 1],
      'custom_4_-1' => '',
      'register_date' => '2020-01-31 00:50:00',
      'role_id' => [0 => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Attendee')],
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', $participantStatus),
      'source' => 'I wrote this',
      'note' => 'I wrote a note',
    ];
  }

  /**
   * Check if participant is transferred correctly.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTransferParticipantRegistration(): void {
    //Register a contact to a sample event.
    $this->createEventOrder();
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['return' => 'id']);
    //Check line item count of the contribution id before transfer.
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($contribution['id']);
    $this->assertCount(2, $lineItems);
    $participantId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantPayment', $contribution['id'], 'participant_id', 'contribution_id');
    /** @var CRM_Event_Form_SelfSvcTransfer $form */
    $form = $this->getFormObject('CRM_Event_Form_SelfSvcTransfer');
    $toContactId = $this->individualCreate();
    $mut = new CiviMailUtils($this);
    $this->swapMessageTemplateForInput('event_online_receipt', '{domain.name} {contact.first_name}');
    $form->transferParticipantRegistration($toContactId, $participantId);
    $mut->checkAllMailLog(['Default Domain Name Anthony']);
    $mut->clearMessages();
    $this->revertTemplateToReservedTemplate();

    //Assert participant is transferred to $toContactId.
    $participant = $this->callAPISuccess('Participant', 'getsingle', [
      'return' => ['transferred_to_contact_id'],
      'id' => $participantId,
    ]);
    $this->assertEquals($participant['transferred_to_contact_id'], $toContactId);

    //Assert $toContactId has a new registration.
    $toParticipant = $this->callAPISuccess('Participant', 'getsingle', [
      'contact_id' => $toContactId,
    ]);
    $this->assertEquals($toParticipant['participant_registered_by_id'], $participantId);

    //Check line item count of the contribution id remains the same.
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($contribution['id']);
    $this->assertCount(2, $lineItems);
    // There should be 2 participant payments on the contribution & 0 others existing.
    $this->callAPISuccessGetCount('ParticipantPayment', ['contribution_id' => $contribution['id']], 2);
    $this->callAPISuccessGetCount('ParticipantPayment', [], 2);
    $this->callAPISuccessGetCount('ParticipantPayment', ['participant_id' => $toParticipant['id']], 1);
    $this->callAPISuccessGetCount('ParticipantPayment', ['participant_id' => $participantId], 0);
  }

  /**
   * Get created contact ID.
   *
   * @return int
   */
  protected function getContactID(): int {
    if (empty($this->ids['Contact']['event'])) {
      $this->ids['Contact']['event'] = $this->individualCreate([]);
    }
    return $this->ids['Contact']['event'];
  }

}
