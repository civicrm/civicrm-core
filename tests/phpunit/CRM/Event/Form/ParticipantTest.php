<?php

declare(strict_types = 1);
use Civi\Api4\Address;
use Civi\Api4\Event;
use Civi\Api4\LineItem;
use Civi\Api4\LocBlock;
use Civi\Api4\Participant;
use Civi\Api4\Phone;
use Civi\Test\FormTrait;
use Civi\Test\FormWrapper;
use Civi\Test\FormWrappers\EventFormParticipant;

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_ParticipantTest extends CiviUnitTestCase {

  use FormTrait;
  use CRMTraits_Financial_OrderTrait;
  use CRMTraits_Financial_PriceSetTrait;
  use CRMTraits_Custom_CustomDataTrait;

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->revertTemplateToReservedTemplate();
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
      'role_id' => [CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Attendee')],
    ])->postProcess();
    $this->assertEquals($this->getEventID(), $form->getEventID());
    $this->callAPISuccessGetSingle('Participant', ['id' => $form->getParticipantID()]);
  }

  public function testSubmitDualRole(): void {
    $email = $this->getForm([], [
      'status_id' => 1,
      'register_date' => date('Ymd'),
      'send_receipt' => 1,
      'from_email_address' => 'admin@email.com',
      'role_id' => [
        CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Volunteer'),
        CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Speaker'),
      ],
    ])->postProcess()->getFirstMailBody();
    $this->assertStringContainsString('Volunteer, Speaker', $email);
  }

  public function testSubmitWithCustomData(): void {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Participant', 'extends_entity_column_id' => 1, 'extends_entity_column_value' => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Volunteer')]);
    $email = $this->getForm([], [
      'status_id' => 1,
      'register_date' => date('Ymd'),
      'send_receipt' => 1,
      'from_email_address' => 'admin@email.com',
      'role_id' => [CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Volunteer')],
      $this->getCustomFieldName() => 'Random thing',
    ])->postProcess()->getFirstMailBody();
    $this->assertStringContainsStrings($email, ['Enter text here', 'Random thing', 'Group with field text']);
  }

  /**
   * Test financial items pending transaction is later altered.
   *
   * @throws \Exception
   */
  public function testSubmitUnpaidPriceChangeWhileStillPending(): void {
    $this->eventCreatePaid();
    $_REQUEST['cid'] = $this->individualCreate();
    $form = $this->getFormObject('CRM_Event_Form_Participant', [
      'is_monetary' => 1,
      'register_date' => date('Ymd'),
      'is_pay_later' => 1,
      'payment_processor_id' => 0,
      'record_contribution' => TRUE,
      'financial_type_id' => 1,
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      $this->getPriceFieldKey() => $this->ids['PriceFieldValue']['PaidEvent_student'],
      'check_number' => '879',
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
      'role_id' => [0 => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Attendee')],
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Pending from pay later'),
      'source' => 'I wrote this',
      'note' => 'I wrote a note',
      'event_id' => $this->getEventID(),

    ]);
    $form->preProcess();
    $form->buildForm();
    $form->postProcess();
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $this->assertEquals(2, $contribution['contribution_status_id']);
    $this->callAPISuccessGetSingle('FinancialItem', []);

    $lineItem = LineItem::get()
      ->addWhere('entity_id', '=', $participant['id'])
      ->addWhere('entity_table', '=', 'civicrm_participant')
      ->addOrderBy('id')
      ->execute()->first();
    $this->assertEquals(100, $lineItem['line_total']);
    $financialItems = $this->callAPISuccess('FinancialItem', 'get', []);
    $sum = 0;
    foreach ($financialItems['values'] as $financialItem) {
      $sum += $financialItem['amount'];
    }
    $this->assertEquals(100, $sum);

    $priceSetID = $this->ids['PriceSet']['PaidEvent'];
    $eventFeeBlock = CRM_Price_BAO_PriceSet::getSetDetail($priceSetID)[$priceSetID]['fields'];
    $priceSetParams[$this->getPriceFieldKey()] = $this->ids['PriceFieldValue']['PaidEvent_family_package'];
    CRM_Price_BAO_LineItem::changeFeeSelections($priceSetParams, $participant['id'], 'participant', $contribution['id'], $eventFeeBlock);
    // Check that no payment records have been created.
    // In https://lab.civicrm.org/dev/financial/issues/94 we had an issue where payments were created when none happened.
    $payments = $this->callAPISuccess('Payment', 'get', [])['values'];
    $this->assertCount(0, $payments);
    $lineItems = LineItem::get()
      ->addWhere('entity_id', '=', $participant['id'])
      ->addWhere('entity_table', '=', 'civicrm_participant')
      ->addOrderBy('id')
      ->execute();
    // Participants is updated to 0 but line remains.
    $this->assertEquals(0, $lineItems[0]['line_total']);
    $this->assertEquals(1550.55, $lineItems[1]['line_total']);
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
    $priceSetBlock = CRM_Price_BAO_PriceSet::getSetDetail($this->getPriceSetID('PaidEvent'))[$this->getPriceSetID('PaidEvent')]['fields'];

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
    $_REQUEST['mode'] = 'live';
    $paymentProcessorID = $this->processorCreate(['is_test' => 0]);
    $form = $this->submitForm(['is_monetary' => 1, 'financial_type_id' => 1], $this->getSubmitParamsForCreditCardPayment($paymentProcessorID), TRUE);
    $this->assertStringContainsStrings($form->getFirstMailBody(), [
      'Junko Adams<br/>',
      '790L Lincoln St S<br />
Baltimore, New York 10545<br />
United States<br />',
    ]);
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $this->assertEquals('2018-09-04 00:00:00', $participant['participant_register_date']);
    $this->assertEquals('Offline Registration for Event: Annual CiviCRM meet by: ', $participant['participant_source']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $this->assertEquals(20, $contribution['total_amount']);
    $this->assertEquals(['Family Deal - 1'], $contribution['amount_level']);
    $this->assertEquals('Debit Card', $contribution['payment_instrument']);
    $this->assertNotEmpty($contribution['receipt_date']);
    // Just check it's not something weird like 1970 without getting into flakey-precise.
    $this->assertGreaterThan(strtotime('yesterday'), strtotime($contribution['receipt_date']));
    $lineItem = $this->callAPISuccessGetSingle('LineItem', []);
    $expected = [
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_participant',
      'qty' => 1,
      'label' => 'Family Deal',
      'unit_price' => 20,
      'line_total' => 20,
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
    $paymentProcessorID = $this->processorCreate(['is_test' => 0]);
    $_REQUEST['mode'] = 'live';
    \Civi\Payment\System::singleton()->getById($paymentProcessorID)->setDoDirectPaymentResult(['payment_status_id' => 'failed']);
    $this->submitForm(['is_monetary' => 1, 'financial_type_id' => 1], $this->getSubmitParamsForCreditCardPayment($paymentProcessorID), TRUE);
    $this->assertPrematureExit();
  }

  /**
   * Test offline participant mail.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   * @throws CRM_Core_Exception
   */
  public function testParticipantOfflineReceipt(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->swapMessageTemplateForTestTemplate('event_offline_receipt', 'text');
    $this->swapMessageTemplateForTestTemplate('event_offline_receipt');
    // Create an email associated with the logged in contact
    $loggedInContactID = $this->createLoggedInUser();
    $email = $this->callAPISuccess('Email', 'create', [
      'contact_id' => $loggedInContactID,
      'is_primary' => 1,
      'email' => 'testLoggedInReceiptEmail@civicrm.org',
      'location_type_id' => 1,
    ]);

    //Modify html to contain event_type_id token.
    $result = $this->callAPISuccess('MessageTemplate', 'get', [
      'sequential' => 1,
      'return' => ['id', 'msg_html'],
      'workflow_name' => 'event_offline_receipt',
      'is_default' => 1,
    ]);
    $oldMsg = $result['values'][0]['msg_html'];
    $newMsg = substr_replace($oldMsg, '<p>Test event type - {event.event_type_id}</p>', 0, 0);
    $this->callAPISuccess('MessageTemplate', 'create', [
      'id' => $result['id'],
      'msg_html' => $newMsg,
    ]);

    // Use the email created as the from email ensuring we are passing a numeric from to test dev/core#1069
    $this->setCurrencySeparators($thousandSeparator);
    $paymentProcessorID = $this->processorCreate(['is_test' => 0]);
    $_REQUEST['mode'] = 'Live';
    $submitParams = $this->getSubmitParamsForCreditCardPayment($paymentProcessorID);
    $submitParams['from_email_address'] = $email['id'];
    $message = $this->submitForm(['is_monetary' => 1, 'financial_type_id' => 1, 'pay_later_receipt' => 'pay us'], $submitParams, TRUE)->getFirstMail();
    $participantID = Participant::get()->addWhere('event_id', '=', $this->getEventID('PaidEvent'))->execute()->first()['id'];
    //Check if type is correctly populated in mails.
    //Also check the string email is present not numeric from.
    $this->assertStringContainsStrings($message['headers'] . $message['body'], [
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
      $this->formatMoneyInput(20.00),
      'event.loc_block_id.phone_id.phone:::1235',
      'event.loc_block_id.phone_id.phone_type_id:label:::Mobile',
      'event.loc_block_id.phone_id.phone_ext:::456',
      'event.confirm_email_text::Just do it',
      'contribution.total_amount:::' . Civi::format()->money(20.00),
      'contribution.total_amount|raw:::20.00',
      'contribution.paid_amount:::' . Civi::format()->money(20.00),
      'contribution.paid_amount|raw:::20.00',
      'contribution.balance_amount:::' . Civi::format()->money(0),
      'contribution.balance_amount|raw is zero:::Yes',
      'contribution.balance_amount|raw string is zero:::Yes',
      'contribution.balance_amount|boolean:::No',
      'contribution.paid_amount|boolean:::Yes',
      '<p>Test event type - 1</p>event.location:8 Baker Street<br />
Upstairs<br />
London,',
    ]);

    $this->callAPISuccess('Email', 'delete', ['id' => $email['id']]);
  }

  public function assertStringContainsStrings(string $string, array $expectedStrings): void {
    foreach ($expectedStrings as $expectedString) {
      $this->assertStringContainsString($expectedString, $string);
    }
  }

  /**
   * Get prepared form object.
   *
   * @param array $eventParams
   * @param array $submittedValues
   * @param bool $isQuickConfig
   *
   * @return \Civi\Test\FormWrappers\EventFormParticipant
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function getForm(array $eventParams = [], array $submittedValues = [], bool $isQuickConfig = FALSE): EventFormParticipant {
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
    $submittedValues['_qf_default'] = 'Builder:refresh';
    $submittedValues['receipt_text'] = 'Contact the Development Department if you need to make any changes to your registration.';
    return $this->getTestForm('CRM_Event_Form_Participant', $submittedValues, ['cid' => $submittedValues['contact_id']])->processForm(FormWrapper::BUILT);
  }

  /**
   * Submit the participant form.
   *
   * @param array $eventParams
   * @param array $submittedValues
   * @param bool $isQuickConfig
   *
   * @return \Civi\Test\FormWrappers\EventFormParticipant
   */
  protected function submitForm(array $eventParams = [], array $submittedValues = [], bool $isQuickConfig = FALSE): EventFormParticipant {
    $form = $this->getForm($eventParams, $submittedValues, $isQuickConfig);
    $form->processForm();
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
    $this->eventCreatePaid();
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

    $_REQUEST['cid'] = $this->individualCreate();
    /** @var CRM_Event_Form_Participant $form */
    $form = $this->getFormObject('CRM_Event_Form_Participant', [
      'register_date' => date('Ymd'),
      'contact_id' => $this->ids['Contact']['individual_0'],
      'receive_date' => '2018-09-01',
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Pending from pay later'),
      'role_id' => [1],
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
      'check_number' => '879',
      'send_receipt' => '1',
      'from_email_address' => 'admin@example.com',
    ]);
    $form->preProcess();
    $form->buildForm();
    $form->postProcess();
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
      'from_email_address' => '"FIXME" <info@EXAMPLE.ORG>',
      'send_receipt' => 1,
      'receipt_text' => '',
      'source' => '',
    ];
  }

  /**
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitWithDeferredRecognition(): void {
    Civi::settings()->set('deferred_revenue_enabled', TRUE);
    $futureDate = date('Y') + 1 . '-09-20';
    $this->submitForm(['is_monetary' => 1, 'financial_type_id' => 1, 'start_date' => $futureDate], [
      'record_contribution' => TRUE,
      'financial_type_id' => 1,
    ]);
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
   */
  public function testSubmitPartialPayment(bool $isQuickConfig): void {
    $this->swapMessageTemplateForInput('event_offline_receipt', '', 'text');
    $email = $this->submitForm(['is_monetary' => 1, 'start_date' => '2023-02-15 15:00', 'end_date' => '2023-02-15 18:00'], [
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'total_amount' => '20',
      'send_receipt' => '1',
      'contact_id' => $this->getContactID(),
      'register_date' => '2020-01-31 00:50:00',
      'role_id' => [0 => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Attendee')],
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'status_id', 'Partially paid'),
      'source' => 'I wrote this',
      'note' => 'I wrote a note',
    ], $isQuickConfig)->getFirstMail();
    $this->assertPartialPaymentResult($isQuickConfig, $email);
  }

  /**
   * Test submitting a partially paid event registration, recording a pending contribution.
   *
   * @dataProvider getBooleanDataProvider
   *
   * @param bool $isQuickConfig
   */
  public function testSubmitPendingPartiallyPaidAddPayment(bool $isQuickConfig): void {
    $message = $this->submitForm(['is_monetary' => 1, 'start_date' => '2023-02-15 15:00', 'end_date' => '2023-02-15 18:00'], [], $isQuickConfig)->getFirstMail();
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->callAPISuccessGetValue('Contribution', ['return' => 'id']),
      'total_amount'  => 20,
      'check_number' => 879,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
    ]);
    $this->assertPartialPaymentResult($isQuickConfig, $message, FALSE);
  }

  /**
   * Test submitting a pending contribution on an event and then adding a partial payment.
   *
   * This tests
   *
   * @dataProvider getBooleanDataProvider
   *
   * @param bool $isQuickConfig
   */
  public function testSubmitPendingAddPayment(bool $isQuickConfig): void {
    $this->swapMessageTemplateForInput('event_offline_receipt', '', 'text');
    $message = $this->submitForm(['is_monetary' => 1, 'start_date' => '2023-02-15 15:00', 'end_date' => '2023-02-15 18:00'], [], $isQuickConfig)->getFirstMail();
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->callAPISuccessGetValue('Contribution', ['return' => 'id']),
      'total_amount'  => 20,
      'check_number' => 879,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
    ]);
    $this->assertPartialPaymentResult($isQuickConfig, $message, FALSE);
  }

  /**
   * @param bool $isQuickConfig
   * @param array $message
   * @param bool $isPartPaymentMadeOnParticipantForm
   *   Was a completed contribution entered on the participant form.
   *   If an amount that is less than the total owing was paid on the participant form
   *   then any receipt triggered from that form would have the amount paid and balance.
   */
  protected function assertPartialPaymentResult(bool $isQuickConfig, array $message, bool $isPartPaymentMadeOnParticipantForm = TRUE): void {
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

    $this->assertStringContainsStrings($message['headers'] . $message['body'], [
      'From: "FIXME" <info@EXAMPLE.ORG>',
      'To: "Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
      'Subject: Event Confirmation - Annual CiviCRM meet - Mr. Anthony Anderson II',
      'Dear Anthony',
      'Contact the Development Department if you need to make any changes to your registration.',
      'Event Information and Location',
      'Annual CiviCRM meet',
      'Registered Email',
      'Contact the Development Department if you need to make any changes to your registration.',
      $this->formatMoneyInput(1550.55),
      $isQuickConfig ? ' Family Deal' : 'Fundraising Dinner - Family Deal',
      $isPartPaymentMadeOnParticipantForm ? 'Total Paid' : '',
      $isPartPaymentMadeOnParticipantForm ? $this->formatMoneyInput(20.00) : '',
      $isPartPaymentMadeOnParticipantForm ? 'Balance' : '',
      $isPartPaymentMadeOnParticipantForm ? $this->formatMoneyInput(1530.55) : $this->formatMoneyInput(1550.55),
      'Financial Type',
      'Event Fee',
      'February 15th, 2023  3:00 PM- 6:00 PM',
      'Check Number',
      '879',
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
      'register_date' => '2020-01-31 00:50:00',
      'role_id' => [CRM_Core_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'role_id', 'Attendee')],
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
    $this->swapMessageTemplateForInput('event_online_receipt', '{domain.name} {contact.first_name}');

    $this->createEventOrder();
    Event::update()->addWhere('id', '=', $this->getEventID())->setValues([
      'start_date' => 'next week',
      'allow_selfcancelxfer' => TRUE,
    ])->execute();
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['return' => 'id']);
    $toContactID = $this->individualCreate([], 'to');
    $participantId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantPayment', $contribution['id'], 'participant_id', 'contribution_id');
    $mail = $this->getTestForm('CRM_Event_Form_SelfSvcTransfer', [
      'contact_id' => $toContactID,
    ], [
      'pid' => $participantId,
      'is_backoffice' => 1,
    ])->processForm()->getFirstMailBody();
    $this->assertStringContainsString('Default Domain Name Anthony', $mail);
    $this->revertTemplateToReservedTemplate();

    //Assert participant is transferred to $toContactId.
    $participant = $this->callAPISuccess('Participant', 'getsingle', [
      'return' => ['transferred_to_contact_id'],
      'id' => $participantId,
    ]);
    $this->assertEquals($participant['transferred_to_contact_id'], $toContactID);

    //Assert $toContactId has a new registration.
    $toParticipant = $this->callAPISuccess('Participant', 'getsingle', [
      'contact_id' => $toContactID,
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
