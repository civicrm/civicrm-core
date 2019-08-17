<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Registration_ConfirmTest extends CiviUnitTestCase {

  use CRMTraits_Profile_ProfileTrait;

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
    $mut = new CiviMailUtils($this, TRUE);
    CRM_Event_Form_Registration_Confirm::testSubmit([
      'id' => $event['id'],
      'contributeMode' => 'direct',
      'registerByID' => $this->createLoggedInUser(),
      'params' => [
        [
          'qfKey' => 'e6eb2903eae63d4c5c6cc70bfdda8741_2801',
          'entryURL' => 'http://dmaster.local/civicrm/event/register?reset=1&amp;id=3',
          'first_name' => 'k',
          'last_name' => 'p',
          'email-Primary' => 'demo@example.com',
          'hidden_processor' => '1',
          'credit_card_number' => '4111111111111111',
          'cvv2' => '123',
          'credit_card_exp_date' => [
            'M' => '1',
            'Y' => '2019',
          ],
          'credit_card_type' => 'Visa',
          'billing_first_name' => 'p',
          'billing_middle_name' => '',
          'billing_last_name' => 'p',
          'billing_street_address-5' => 'p',
          'billing_city-5' => 'p',
          'billing_state_province_id-5' => '1061',
          'billing_postal_code-5' => '7',
          'billing_country_id-5' => '1228',
          'scriptFee' => '',
          'scriptArray' => '',
          'priceSetId' => '6',
          'price_7' => [
            13 => 1,
          ],
          'payment_processor_id' => '1',
          'bypass_payment' => '',
          'MAX_FILE_SIZE' => '33554432',
          'is_primary' => 1,
          'is_pay_later' => 0,
          'campaign_id' => NULL,
          'defaultRole' => 1,
          'participant_role_id' => '1',
          'currencyID' => 'USD',
          'amount_level' => 'Tiny-tots (ages 5-8) - 1',
          'amount' => '800.00',
          'tax_amount' => NULL,
          'year' => '2019',
          'month' => '1',
          'ip_address' => '127.0.0.1',
          'invoiceID' => '57adc34957a29171948e8643ce906332',
          'button' => '_qf_Register_upload',
          'billing_state_province-5' => 'AP',
          'billing_country-5' => 'US',
        ],
      ],
    ]);
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $mut->checkMailLog([
      'Dear Logged In,  Thank you for your participation.  This letter is a confirmation that your registration has been received and your status has been updated to Registered.',
    ]);
    $mut->stop();
    $mut->clearMessages();
    $tplVars = CRM_Core_Smarty::singleton()->get_template_vars();
    $this->assertEquals($participant['id'], $tplVars['participantID']);

  }

  /**
   * Initial test of submit function for paid event.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   *
   * @throws \Exception
   */
  public function testPaidSubmit($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $paymentProcessorID = $this->processorCreate();
    /* @var \CRM_Core_Payment_Dummy $processor */
    $processor = Civi\Payment\System::singleton()->getById($paymentProcessorID);
    $processor->setDoDirectPaymentResult(['fee_amount' => 1.67]);
    $params = ['is_monetary' => 1, 'financial_type_id' => 1];
    $event = $this->eventCreate($params);
    $individualID = $this->individualCreate();
    CRM_Event_Form_Registration_Confirm::testSubmit([
      'id' => $event['id'],
      'contributeMode' => 'direct',
      'registerByID' => $individualID,
      'paymentProcessorObj' => CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID),
      'totalAmount' => $this->formatMoneyInput(8000.67),
      'params' => [
        [
          'qfKey' => 'e6eb2903eae63d4c5c6cc70bfdda8741_2801',
          'entryURL' => 'http://dmaster.local/civicrm/event/register?reset=1&amp;id=3',
          'first_name' => 'k',
          'last_name' => 'p',
          'email-Primary' => 'demo@example.com',
          'hidden_processor' => '1',
          'credit_card_number' => '4111111111111111',
          'cvv2' => '123',
          'credit_card_exp_date' => [
            'M' => '1',
            'Y' => '2019',
          ],
          'credit_card_type' => 'Visa',
          'billing_first_name' => 'p',
          'billing_middle_name' => '',
          'billing_last_name' => 'p',
          'billing_street_address-5' => 'p',
          'billing_city-5' => 'p',
          'billing_state_province_id-5' => '1061',
          'billing_postal_code-5' => '7',
          'billing_country_id-5' => '1228',
          'scriptFee' => '',
          'scriptArray' => '',
          'priceSetId' => '6',
          'price_7' => [
            13 => 1,
          ],
          'payment_processor_id' => $paymentProcessorID,
          'bypass_payment' => '',
          'MAX_FILE_SIZE' => '33554432',
          'is_primary' => 1,
          'is_pay_later' => 0,
          'campaign_id' => NULL,
          'defaultRole' => 1,
          'participant_role_id' => '1',
          'currencyID' => 'USD',
          'amount_level' => 'Tiny-tots (ages 5-8) - 1',
          'amount' => $this->formatMoneyInput(8000.67),
          'tax_amount' => NULL,
          'year' => '2019',
          'month' => '1',
          'ip_address' => '127.0.0.1',
          'invoiceID' => '57adc34957a29171948e8643ce906332',
          'button' => '_qf_Register_upload',
          'billing_state_province-5' => 'AP',
          'billing_country-5' => 'US',
        ],
      ],
    ]);
    $this->callAPISuccessGetCount('Participant', [], 1);
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $this->assertEquals(8000.67, $contribution['total_amount']);
    $this->assertEquals(1.67, $contribution['fee_amount']);
    $this->assertEquals(7999, $contribution['net_amount']);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['payment_processor_id', 'card_type_id.label', 'pan_truncation'],
      ]
    );
    $this->assertEquals(CRM_Utils_Array::value('payment_processor_id', $financialTrxn), $paymentProcessorID);
    $this->assertEquals(CRM_Utils_Array::value('card_type_id.label', $financialTrxn), 'Visa');
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), 1111);

    // This looks like it's missing an item for the main contribution - but just locking in current behaviour.
    $financialItems = $this->callAPISuccess('FinancialItem', 'get', [
      'return' => ['description', 'financial_account_id', 'amount', 'contact_id', 'currency', 'status_id', 'entity_table', 'entity_id'],
      'sequential' => 1,
    ])['values'];

    $entityFinancialTrxns = $this->callAPISuccess('EntityFinancialTrxn', 'get', ['sequential' => 1])['values'];

    $this->assertAPIArrayComparison([
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'financial_trxn_id' => $financialTrxn['id'],
      'amount' => '8000.67',
    ], $entityFinancialTrxns[0], ['id']);

    $this->assertAPIArrayComparison([
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'financial_trxn_id' => $financialTrxn['id'] + 1,
      'amount' => '1.67',
    ], $entityFinancialTrxns[1], ['id']);

    $this->assertAPIArrayComparison([
      'entity_table' => 'civicrm_financial_item',
      'entity_id' => $financialItems[0]['id'],
      'financial_trxn_id' => $financialTrxn['id'] + 1,
      'amount' => '1.67',
    ], $entityFinancialTrxns[2], ['id', 'entity_id']);
  }

  /**
   * Test for Tax amount for multiple participant.
   *
   * @throws \Exception
   */
  public function testTaxMultipleParticipant() {
    $params = ['is_monetary' => 1, 'financial_type_id' => 1];
    $event = $this->eventCreate($params);
    CRM_Event_Form_Registration_Confirm::testSubmit([
      'id' => $event['id'],
      'contributeMode' => 'direct',
      'registerByID' => $this->createLoggedInUser(),
      'totalAmount' => 440,
      'event' => reset($event['values']),
      'params' => [
        [
          'qfKey' => 'e6eb2903eae63d4c5c6cc70bfdda8741_2801',
          'entryURL' => "http://dmaster.local/civicrm/event/register?reset=1&amp;id={$event['id']}",
          'first_name' => 'Participant1',
          'last_name' => 'LastName',
          'email-Primary' => 'participant1@example.com',
          'scriptFee' => '',
          'scriptArray' => '',
          'additional_participants' => 2,
          'payment_processor_id' => 0,
          'bypass_payment' => '',
          'MAX_FILE_SIZE' => '33554432',
          'is_primary' => 1,
          'is_pay_later' => 1,
          'campaign_id' => NULL,
          'defaultRole' => 1,
          'participant_role_id' => '1',
          'currencyID' => 'USD',
          'amount_level' => 'Tiny-tots (ages 5-8) - 1',
          'amount' => '100.00',
          'tax_amount' => 10,
          'ip_address' => '127.0.0.1',
          'invoiceID' => '57adc34957a29171948e8643ce906332',
          'trxn_id' => '123456789',
          'button' => '_qf_Register_upload',
        ],
        [
          'qfKey' => 'e6eb2903eae63d4c5c6cc70bfdda8741_2801',
          'entryURL' => "http://dmaster.local/civicrm/event/register?reset=1&amp;id={$event['id']}",
          'first_name' => 'Participant2',
          'last_name' => 'LastName',
          'email-Primary' => 'participant2@example.com',
          'scriptFee' => '',
          'scriptArray' => '',
          'campaign_id' => NULL,
          'is_pay_later' => 1,
          'participant_role_id' => '1',
          'currencyID' => 'USD',
          'amount_level' => 'Tiny-tots (ages 9-18) - 1',
          'amount' => '200.00',
          'tax_amount' => 20,
        ],
        [
          'qfKey' => 'e6eb2903eae63d4c5c6cc70bfdda8741_2801',
          'entryURL' => "http://dmaster.local/civicrm/event/register?reset=1&amp;id={$event['id']}",
          'first_name' => 'Participant3',
          'last_name' => 'LastName',
          'email-Primary' => 'participant3@example.com',
          'scriptFee' => '',
          'scriptArray' => '',
          'campaign_id' => NULL,
          'is_pay_later' => 1,
          'participant_role_id' => '1',
          'currencyID' => 'USD',
          'amount_level' => 'Tiny-tots (ages 5-8) - 1',
          'amount' => '100.00',
          'tax_amount' => 10,
        ],
      ],
    ]);
    $this->callAPISuccessGetCount('Participant', [], 3);
    $contribution = $this->callAPISuccessGetSingle(
      'Contribution',
      [
        'return' => ['tax_amount', 'total_amount'],
      ]
    );
    $this->assertEquals($contribution['tax_amount'], 40, 'Invalid Tax amount.');
    $this->assertEquals($contribution['total_amount'], 440, 'Invalid Tax amount.');
  }

  /**
   * Test online registration for event with no price options selected as per CRM-19964.
   */
  public function testOnlineRegNoPrice() {
    $paymentProcessorID = $this->processorCreate(['is_default' => TRUE, 'user_name' => 'Test', 'is_test' => FALSE]);
    $paymentProcessorID = $this->processorCreate(['is_default' => TRUE, 'user_name' => 'Test', 'is_test' => TRUE]);
    $params = [
      'start_date' => date('YmdHis', strtotime('+ 1 week')),
      'end_date' => date('YmdHis', strtotime('+ 1 year')),
      'registration_start_date' => date('YmdHis', strtotime('- 1 day')),
      'registration_end_date' => date('YmdHis', strtotime('+ 1 year')),
      'payment_processor_id' => $paymentProcessorID,
      'is_monetary' => TRUE,
      'financial_type_id' => 'Event Fee',
    ];
    $event = $this->eventCreate($params);
    $priceFieldOptions = [
      'option_label' => 'Price Field',
      'option_value' => 100,
      'is_required' => FALSE,
      'html_type' => 'Text',
    ];
    $this->createPriceSet('event', $event['id'], $priceFieldOptions);

    $priceField = $this->callAPISuccess('PriceField', 'get',
      [
        'label' => 'Price Field',
      ]
    );
    // Create online event registration.
    CRM_Event_Form_Registration_Confirm::testSubmit([
      'id' => $event['id'],
      'contributeMode' => 'direct',
      'registerByID' => $this->createLoggedInUser(),
      'params' => [
        [
          'qfKey' => 'e6eb2903eae63d4c5c6cc70bfdda8741_2801',
          'entryURL' => "http://dmaster.local/civicrm/event/register?reset=1&amp;id={$event['id']}",
          'first_name' => 'Bruce',
          'last_name' => 'Wayne',
          'email-Primary' => 'bruce@gotham.com',
          'price_' . $priceField['id'] => '',
          'priceSetId' => $priceField['values'][$priceField['id']]['price_set_id'],
          'payment_processor_id' => $paymentProcessorID,
          'amount' => 0,
          'bypass_payment' => '',
          'MAX_FILE_SIZE' => '33554432',
          'is_primary' => 1,
          'is_pay_later' => 0,
          'campaign_id' => NULL,
          'defaultRole' => 1,
          'participant_role_id' => '1',
          'tax_amount' => NULL,
          'ip_address' => '127.0.0.1',
          'invoiceID' => '57adc34957a29171948e8643ce906332',
          'button' => '_qf_Register_upload',
          'scriptFee' => '',
          'scriptArray' => '',
        ],
      ],
    ]);
    $contribution = $this->callAPISuccess('Contribution', 'get', ['invoice_id' => '57adc34957a29171948e8643ce906332']);
    $this->assertEquals($contribution['count'], '0', "Contribution should not be created for zero fee event registration when no price field selected.");
  }

  /**
   * Test form profile assignment.
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function testAssignProfiles() {
    $event = $this->eventCreate();
    $this->createJoinedProfile(['entity_table' => 'civicrm_event', 'entity_id' => $event['id']]);

    /* @var \CRM_Event_Form_Registration_Confirm $form */
    $form = $this->getFormObject('CRM_Event_Form_Registration_Confirm');
    $form->set('params', [[]]);
    $form->set('id', $event['id']);
    $form->set('values', [
      'event' => $event['values'][$event['id']],
      'location' => [],
      'custom_pre_id' => $this->ids['UFGroup']['our profile'],
    ]);
    $form->preProcess();

    CRM_Event_Form_Registration_Confirm::assignProfiles($form);

    $smarty = CRM_Core_Smarty::singleton();
    $tplVar = $smarty->get_template_vars();
    $this->assertEquals([
      'CustomPre' => ['First Name' => NULL],
      'CustomPreGroupTitle' => 'Public title',
    ], $tplVar['primaryParticipantProfile']);
  }

  /**
   * Submit event registration with a note field
   *
   * @param  array $event
   * @param int $contact_id
   *
   * @throws \Exception
   */
  private function submitWithNote($event, $contact_id) {
    if (empty($contact_id)) {
      $contact_id = $this->createLoggedInUser();
    }
    $mut = new CiviMailUtils($this, TRUE);
    CRM_Event_Form_Registration_Confirm::testSubmit([
      'id' => $event['id'],
      'contributeMode' => 'direct',
      'registerByID' => $contact_id,
      'custom_pre_id' => $event['custom_pre_id'],
      'params' => [
        [
          'qfKey' => 'e6eb2903eae63d4c5c6cc70bfdda8741_2801',
          'entryURL' => 'http://dmaster.local/civicrm/event/register?reset=1&amp;id=3',
          'first_name' => 'k',
          'last_name' => 'p',
          'email-Primary' => 'demo@example.com',
          'hidden_processor' => '1',
          'credit_card_number' => '4111111111111111',
          'cvv2' => '123',
          'credit_card_exp_date' => [
            'M' => '1',
            'Y' => '2019',
          ],
          'credit_card_type' => 'Visa',
          'billing_first_name' => 'p',
          'billing_middle_name' => '',
          'billing_last_name' => 'p',
          'billing_street_address-5' => 'p',
          'billing_city-5' => 'p',
          'billing_state_province_id-5' => '1061',
          'billing_postal_code-5' => '7',
          'billing_country_id-5' => '1228',
          'scriptFee' => '',
          'scriptArray' => '',
          'priceSetId' => '6',
          'price_7' => [
            13 => 1,
          ],
          'payment_processor_id' => '1',
          'bypass_payment' => '',
          'MAX_FILE_SIZE' => '33554432',
          'is_primary' => 1,
          'is_pay_later' => 0,
          'campaign_id' => NULL,
          'defaultRole' => 1,
          'participant_role_id' => '1',
          'currencyID' => 'USD',
          'amount_level' => 'Tiny-tots (ages 5-8) - 1',
          'amount' => '800.00',
          'tax_amount' => NULL,
          'year' => '2019',
          'month' => '1',
          'ip_address' => '127.0.0.1',
          'invoiceID' => '57adc34957a29171948e8643ce906332',
          'button' => '_qf_Register_upload',
          'billing_state_province-5' => 'AP',
          'billing_country-5' => 'US',
          'note' => $event['note'],
        ],
      ],
    ]);
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $mut->checkMailLog(['Comment: ' . $event['note'] . chr(0x0A)]);
    $mut->stop();
    $mut->clearMessages();
    $tplVars = CRM_Core_Smarty::singleton()->get_template_vars();
    $this->assertEquals($participant['id'], $tplVars['participantID']);
    //return ['contact_id' => $contact_id, 'participant_id' => $participant['id']];
    return [$contact_id, $participant['id']];
  }

  /**
   * Create an event with a "pre" profile
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  private function creatEventWithProfile($event) {
    if (empty($event)) {
      $event = $this->eventCreate();
      $this->createJoinedProfile(['entity_table' => 'civicrm_event', 'entity_id' => $event['id']]);
      $this->uf_field_add($this->ids["UFGroup"]["our profile"], 'note', 'Contact', 'Comment');
    }

    /* @var \CRM_Event_Form_Registration_Confirm $form */
    $form = $this->getFormObject('CRM_Event_Form_Registration_Confirm');
    $form->set('params', [[]]);
    $form->set('id', $event['id']);
    $form->set('values', [
      'event' => $event['values'][$event['id']],
      'location' => [],
      'custom_pre_id' => $this->ids['UFGroup']['our profile'],
    ]);
    $form->preProcess();

    CRM_Event_Form_Registration_Confirm::assignProfiles($form);

    $smarty = CRM_Core_Smarty::singleton();
    $tplVar = $smarty->get_template_vars();
    $this->assertEquals([
      'CustomPre' => ['First Name' => NULL, 'Comment' => NULL],
      'CustomPreGroupTitle' => 'Public title',
    ], $tplVar['primaryParticipantProfile']);
    return $event;
  }

  /**
   * Add a field to the specified profile
   *
   * @param int $uf_group_id
   * @param string $field_name
   * @param string $field_type
   * @param string $field_label
   * @return array
   *   API result array
   */
  private function uf_field_add($uf_group_id, $field_name, $field_type, $field_label) {
    $params = [
      'field_name' => $field_name,
      'field_type' => $field_type,
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => $field_label,
      'is_searchable' => 1,
      'is_active' => 1,
      'uf_group_id' => $uf_group_id,
    ];
    $result = civicrm_api3('UFField', 'create', $params);
    return $result;
  }

  /**
   * /dev/event#10
   * Test submission with a note in the profile, ensuring the confirmation
   * email reflects the submitted value
   */
  public function testNoteSubmission() {
    //create an event with an attached profile containing a note
    $event = $this->creatEventWithProfile(NULL);
    $event['custom_pre_id'] = $this->ids["UFGroup"]["our profile"];
    $event['note'] = "This is note 1";
    [$contact_id, $participant_id] = $this->submitWithNote($event, NULL);
    civicrm_api3('Participant', 'delete', ['id' => $participant_id]);

    //now that the contact has one note, register this contact again with a different note
    //and confirm that the note shown in the email is the current one
    $event = $this->creatEventWithProfile($event);
    $event['custom_pre_id'] = $this->ids["UFGroup"]["our profile"];
    $event['note'] = "This is note 2";
    [$contact_id, $participant_id] = $this->submitWithNote($event, $contact_id);
    civicrm_api3('Participant', 'delete', ['id' => $participant_id]);

    //finally, submit a blank note and confirm that the note shown in the email is blank
    $event = $this->creatEventWithProfile($event);
    $event['custom_pre_id'] = $this->ids["UFGroup"]["our profile"];
    $event['note'] = "";
    [$contact_id, $participant_id] = $this->submitWithNote($event, $contact_id);
  }

}
