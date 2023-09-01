<?php

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Registration_ConfirmTest extends CiviUnitTestCase {

  use CRMTraits_Financial_PriceSetTrait;
  use CRMTraits_Profile_ProfileTrait;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * Initial test of submit function.
   */
  public function testSubmit(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->submitPaidEvent();

    $mut->checkMailLog([
      'Dear Kim,  Thank you for your registration.  This is a confirmation that your registration has been received and your status has been updated to Registered.',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Initial test of submit function for paid event.
   *
   * @param string $thousandSeparator
   *
   * @throws \CRM_Core_Exception
   *
   * @dataProvider getThousandSeparators
   */
  public function testPaidSubmit(string $thousandSeparator): void {
    // @todo - figure out why this doesn't pass validate financials
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $this->setCurrencySeparators($thousandSeparator);
    $mut = new CiviMailUtils($this);
    $paymentProcessorID = $this->processorCreate();
    /** @var \CRM_Core_Payment_Dummy $processor */
    $processor = Civi\Payment\System::singleton()->getById($paymentProcessorID);
    $processor->setDoDirectPaymentResult(['fee_amount' => 1.67]);
    $event = $this->eventCreatePaid();
    $individualID = $this->individualCreate();
    CRM_Event_Form_Registration_Confirm::testSubmit([
      'id' => $event['id'],
      'contributeMode' => 'direct',
      'registerByID' => $individualID,
      'paymentProcessorObj' => CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID),
      'totalAmount' => 8000.67,
      'params' => [
        [
          'first_name' => 'k',
          'last_name' => 'p',
          'email-Primary' => 'demo@example.com',
          'hidden_processor' => '1',
          'credit_card_number' => '4111111111111111',
          'cvv2' => '123',
          'credit_card_exp_date' => [
            'M' => '1',
            'Y' => date('Y') + 1,
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
          'priceSetId' => '6',
          'price_7' => [
            13 => 1,
          ],
          'payment_processor_id' => $paymentProcessorID,
          'bypass_payment' => '',
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
    $this->assertNotEmpty($contribution['receipt_date']);
    $this->assertNotContains(' (multiple participants)', $contribution['amount_level']);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['payment_processor_id', 'card_type_id.label', 'pan_truncation'],
      ]
    );
    $this->assertEquals($paymentProcessorID, $financialTrxn['payment_processor_id']);
    $this->assertEquals('Visa', $financialTrxn['card_type_id.label']);
    $this->assertEquals(1111, $financialTrxn['pan_truncation']);

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
    $mut->checkMailLog([
      'Event Information and Location',
      'Registration Confirmation - Annual CiviCRM meet',
      'Expires: January ' . (date('Y') + 1),
      'Visa',
      '************1111',
      'This is a confirmation that your registration has been received and your status has been updated to <strong> Registered</strong>',
    ]);
    $mut->clearMessages();
  }

  /**
   * Tests missing contactID when registering for paid event from waitlist
   * https://github.com/civicrm/civicrm-core/pull/23358, https://lab.civicrm.org/extensions/stripe/-/issues/347
   *
   * @throws \CRM_Core_Exception
   */
  public function testWaitlistRegistrationContactIDParam(): void {
    $paymentProcessorID = $this->processorCreate();
    /** @var \CRM_Core_Payment_Dummy $processor */
    $processor = Civi\Payment\System::singleton()->getById($paymentProcessorID);
    $processor->setDoDirectPaymentResult(['fee_amount' => 1.67]);
    $params = ['financial_type_id' => 1];
    $event = $this->eventCreatePaid($params, [['name' => 'test', 'amount' => 8000.67]]);
    $individualID = $this->individualCreate();
    //$this->submitForm($event['id'], [
    $form = CRM_Event_Form_Registration_Confirm::testSubmit([
      'id' => $this->getEventID(),
      'contributeMode' => 'direct',
      'registerByID' => $individualID,
      'paymentProcessorObj' => CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID),
      'amount' => 8000.67,
      'amount_level' => 'Tiny-tots (ages 5-8) - 1',
      'params' => [
        [
          'first_name' => 'k',
          'last_name' => 'p',
          'email-Primary' => 'demo@example.com',
          'hidden_processor' => '1',
          'credit_card_number' => '4111111111111111',
          'cvv2' => '123',
          'credit_card_exp_date' => [
            'M' => '1',
            'Y' => date('Y') + 1,
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
          'priceSetId' => '6',
          'price_7' => [
            13 => 1,
          ],
          'payment_processor_id' => $paymentProcessorID,
          'bypass_payment' => '',
          'is_primary' => 1,
          'is_pay_later' => 0,
          'contact_id' => $individualID,
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

    $value = $form->get('value');
    $this->assertArrayHasKey('contact_id', $value, 'contact_id missing in $value array');
    $this->assertEquals($value['contact_id'], $individualID, 'Invalid contact_id in $value array.');

    // Add someone to the waitlist.
    $waitlistContactId = $this->individualCreate();
    $waitlistContact   = $this->callAPISuccess('Contact', 'getsingle', ['id' => $waitlistContactId]);
    $waitlistParticipantId = $this->participantCreate(['event_id' => $event['id'], 'contact_id' => $waitlistContactId, 'status_id' => 'On waitlist']);

    $waitlistParticipant = $this->callAPISuccess('Participant', 'getsingle', ['id' => $waitlistParticipantId, 'return' => ['participant_status']]);
    $this->assertEquals('On waitlist', $waitlistParticipant['participant_status'], 'Invalid participant status. Expecting: On waitlist');

    $form = CRM_Event_Form_Registration_Confirm::testSubmit([
      'id' => $this->getEventID(),
      'contributeMode' => 'direct',
      'registerByID' => $waitlistContactId,
      'paymentProcessorObj' => CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID),
      'amount' => 8000.67,
      'amount_level' => 'Tiny-tots (ages 5-8) - 1',
      'params' => [
        [
          'first_name' => $waitlistContact['first_name'],
          'last_name' => $waitlistContact['last_name'],
          'email-Primary' => $waitlistContact['email'],
          'hidden_processor' => '1',
          'credit_card_number' => '4111111111111111',
          'cvv2' => '123',
          'credit_card_exp_date' => [
            'M' => '1',
            'Y' => date('Y') + 1,
          ],
          'credit_card_type' => 'Visa',
          'billing_first_name' => $waitlistContact['first_name'],
          'billing_middle_name' => '',
          'billing_last_name' => $waitlistContact['last_name'],
          'billing_street_address-5' => 'p',
          'billing_city-5' => 'p',
          'billing_state_province_id-5' => '1061',
          'billing_postal_code-5' => '7',
          'billing_country_id-5' => '1228',
          'priceSetId' => '6',
          'price_7' => [
            13 => 1,
          ],
          'payment_processor_id' => $paymentProcessorID,
          'bypass_payment' => '',
          'is_primary' => 1,
          'is_pay_later' => 0,
          'participant_id' => $waitlistParticipantId,
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
          'invoiceID' => '68adc34957a29171948e8643ce906332',
          'button' => '_qf_Register_upload',
          'billing_state_province-5' => 'AP',
          'billing_country-5' => 'US',
        ],
      ],
    ]);
    $this->callAPISuccessGetCount('Participant', [], 2);

    $waitlistParticipant = $this->callAPISuccess('Participant', 'getsingle', ['id' => $waitlistParticipantId, 'return' => ['participant_status']]);
    $this->assertEquals('Registered', $waitlistParticipant['participant_status'], 'Invalid participant status. Expecting: Registered');

    $value = $form->get('value');
    $this->assertArrayHasKey('contactID', $value, 'contactID missing in waitlist registration $value array');
    $this->assertEquals($value['contactID'], $waitlistParticipant['contact_id'], 'Invalid contactID in waitlist $value array.');
  }

  /**
   * Test for Tax amount for multiple participant.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTaxMultipleParticipant(): void {
    // @todo - figure out why this doesn't pass validate financials
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $mut = new CiviMailUtils($this);
    $event = $this->eventCreatePaid();
    $this->swapMessageTemplateForTestTemplate('event_online_receipt', 'text');
    CRM_Event_Form_Registration_Confirm::testSubmit([
      'id' => $event['id'],
      'contributeMode' => 'direct',
      'registerByID' => $this->createLoggedInUser(),
      'totalAmount' => 440,
      'event' => $event,
      'params' => [
        [
          'first_name' => 'Participant1',
          'last_name' => 'LastName',
          'email-Primary' => 'participant1@example.com',
          'additional_participants' => 2,
          'payment_processor_id' => 0,
          'bypass_payment' => '',
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
          'entryURL' => "http://dmaster.local/civicrm/event/register?reset=1&amp;id={$event['id']}",
          'first_name' => 'Participant2',
          'last_name' => 'LastName',
          'email-Primary' => 'participant2@example.com',
          'campaign_id' => NULL,
          'is_pay_later' => 1,
          'participant_role_id' => '1',
          'currencyID' => 'USD',
          'amount_level' => 'Tiny-tots (ages 9-18) - 1',
          'amount' => '200.00',
          'tax_amount' => 20,
        ],
        [
          'entryURL' => "http://dmaster.local/civicrm/event/register?reset=1&amp;id={$event['id']}",
          'first_name' => 'Participant3',
          'last_name' => 'LastName',
          'email-Primary' => 'participant3@example.com',
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
    $participants = $this->callAPISuccess('Participant', 'get', [])['values'];
    $this->assertCount(3, $participants);
    $contribution = $this->callAPISuccessGetSingle(
      'Contribution',
      [
        'return' => ['tax_amount', 'total_amount', 'amount_level'],
      ]
    );
    $this->assertContains(' (multiple participants)', $contribution['amount_level']);
    $this->assertEquals(40, $contribution['tax_amount'], 'Invalid Tax amount.');
    $this->assertEquals(440, $contribution['total_amount'], 'Invalid Tax amount.');
    $mailSent = $mut->getAllMessages();
    $this->assertCount(3, $mailSent, 'Three mails should have been sent to the 3 participants.');
    $this->assertStringContainsString('contactID:::' . $contribution['contact_id'], $mailSent[0]);
    $this->assertStringContainsString('contactID:::' . ($contribution['contact_id'] + 1), $mailSent[1]);

    $this->callAPISuccess('Payment', 'create', ['total_amount' => 100, 'payment_type_id' => 'Cash', 'contribution_id' => $contribution['id']]);
    $mailSent = $mut->getAllMessages();
    $this->assertCount(6, $mailSent);

    $this->assertStringContainsString('participant_status:::Registered', $mailSent[3]);
    $this->assertStringContainsString('Dear Participant2', $mailSent[3]);

    $this->assertStringContainsString('contactID:::' . ($contribution['contact_id'] + 1), $mailSent[3]);
    $this->assertStringContainsString('contactID:::' . ($contribution['contact_id'] + 2), $mailSent[4]);
    $this->assertStringContainsString('contactID:::' . $contribution['contact_id'], $mailSent[5]);
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test online registration for event with no price options selected as per CRM-19964.
   */
  public function testOnlineRegNoPrice(): void {
    $this->processorCreate(['is_default' => TRUE, 'user_name' => 'Test', 'is_test' => FALSE]);
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
    $event = $this->eventCreatePaid($params);
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
    $this->submitForm(
      $event['id'], [
        [
          'first_name' => 'Bruce',
          'last_name' => 'Wayne',
          'email-Primary' => 'bruce@gotham.com',
          'price_' . $priceField['id'] => '',
          'priceSetId' => $priceField['values'][$priceField['id']]['price_set_id'],
          'payment_processor_id' => $paymentProcessorID,
          'amount' => 0,
          'amount_level' => '',
          'bypass_payment' => '',
          'is_primary' => 1,
          'is_pay_later' => 0,
          'campaign_id' => NULL,
          'defaultRole' => 1,
          'participant_role_id' => '1',
          'tax_amount' => NULL,
          'ip_address' => '127.0.0.1',
          'invoiceID' => '57adc34957a29171948e8643ce906332',
          'button' => '_qf_Register_upload',
        ],
      ]
    );
    $contribution = $this->callAPISuccess('Contribution', 'get', ['invoice_id' => '57adc34957a29171948e8643ce906332']);
    $this->assertEquals('0', $contribution['count'], 'Contribution should not be created for zero fee event registration when no price field selected.');
  }

  /**
   * Test form profile assignment.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAssignProfiles(): void {
    $event = $this->eventCreateUnpaid();
    $this->createJoinedProfile(['entity_table' => 'civicrm_event', 'entity_id' => $event['id']]);

    $_REQUEST['id'] = $event['id'];
    /** @var \CRM_Event_Form_Registration_Confirm $form */
    $form = $this->getFormObject('CRM_Event_Form_Registration_Confirm');
    $form->set('params', [[]]);
    $form->set('values', [
      'event' => $event,
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
   * Submit (unpaid) event registration with a note field
   *
   * @param array $event
   * @param int|null $contact_id
   *
   * @return array
   */
  private function submitWithNote(array $event, ?int $contact_id): array {
    if ($contact_id === NULL) {
      $contact_id = $this->createLoggedInUser();
    }
    $mut = new CiviMailUtils($this, TRUE);
    $this->submitForm($event['id'], [
      [
        'email-Primary' => 'demo@example.com',
        'note' => $event['note'],
      ],
    ]);
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $mut->checkMailLog(['Comment: ' . $event['note'] . chr(0x0A)]);
    $mut->stop();
    $mut->clearMessages();
    //return ['contact_id' => $contact_id, 'participant_id' => $participant['id']];
    return [$contact_id, $participant['id']];
  }

  /**
   * Create an event with a "pre" profile
   *
   * @throws \CRM_Core_Exception
   */
  private function creatEventWithProfile($event): array {
    if (empty($event)) {
      $event = $this->eventCreateUnpaid();
      $this->createJoinedProfile(['entity_table' => 'civicrm_event', 'entity_id' => $event['id']]);
      $this->addUFField($this->ids['UFGroup']['our profile'], 'note', 'Contact', 'Comment');
    }

    $_REQUEST['id'] = $event['id'];
    /** @var \CRM_Event_Form_Registration_Confirm $form */
    $form = $this->getFormObject('CRM_Event_Form_Registration_Confirm');
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
   * @param int $ufGroupID
   * @param string $fieldName
   * @param string $fieldType
   * @param string $fieldLabel
   *
   * @return array
   *   API result array
   */
  private function addUFField(int $ufGroupID, string $fieldName, string $fieldType, string $fieldLabel): array {
    $params = [
      'field_name' => $fieldName,
      'field_type' => $fieldType,
      'visibility' => 'Public Pages and Listings',
      'weight' => 1,
      'label' => $fieldLabel,
      'is_searchable' => 1,
      'is_active' => 1,
      'uf_group_id' => $ufGroupID,
    ];
    return $this->callAPISuccess('UFField', 'create', $params);
  }

  /**
   * /dev/event#10
   * Test submission with a note in the profile, ensuring the confirmation
   * email reflects the submitted value
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function testNoteSubmission(): void {
    // Create an event with an attached profile containing a note
    $event = $this->creatEventWithProfile(NULL);
    $event['custom_pre_id'] = $this->ids['UFGroup']['our profile'];
    $event['note'] = 'This is note 1';
    [$contact_id, $participant_id] = $this->submitWithNote($event, NULL);

    civicrm_api3('Participant', 'delete', ['id' => $participant_id]);

    //now that the contact has one note, register this contact again with a different note
    //and confirm that the note shown in the email is the current one
    $event = $this->creatEventWithProfile($event);
    $event['custom_pre_id'] = $this->ids['UFGroup']['our profile'];
    $event['note'] = 'This is note 2';
    [$contact_id, $participant_id] = $this->submitWithNote($event, $contact_id);
    civicrm_api3('Participant', 'delete', ['id' => $participant_id]);

    //finally, submit a blank note and confirm that the note shown in the email is blank
    $event = $this->creatEventWithProfile($event);
    $event['custom_pre_id'] = $this->ids['UFGroup']['our profile'];
    $event['note'] = '';
    $this->submitWithNote($event, $contact_id);
  }

  /**
   * Ensure we send to the submitted email, not the primary email, if different.
   *
   * event#64.
   */
  public function testSubmitNonPrimaryEmail(): void {
    $event = $this->eventCreateUnpaid();
    $mut = new CiviMailUtils($this, TRUE);
    $this->submitForm($event['id'], [
      [
        'first_name' => 'k',
        'last_name' => 'p',
        'email-Other' => 'nonprimaryemail@example.com',
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
        'priceSetId' => '6',
        'price_7' => [
          13 => 1,
        ],
        'payment_processor_id' => '1',
        'bypass_payment' => '',
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
        'invoiceID' => '57adc34957a29171948e8643ce906332',
        'button' => '_qf_Register_upload',
        'billing_state_province-5' => 'AP',
        'billing_country-5' => 'US',
      ],
    ]);
    $mut->checkMailLog(['nonprimaryemail@example.com']);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Submit the confirm form.
   *
   * Note that historically the passed parameters were 'set' on the form and
   * thus needed to mimic the form's internal workings. The form now
   * treats the `$submittedValues` as if they were submitted by the user, which
   * more robustly tests the form processing.
   *
   * @param int $eventID
   * @param array $submittedValues Submitted Values
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  protected function submitForm(int $eventID, array $submittedValues): void {
    $_REQUEST['id'] = $eventID;
    /* @var CRM_Event_Form_Registration_Register $form */
    $form = $this->getFormObject('CRM_Event_Form_Registration_Register', $submittedValues[0] ?? $submittedValues);
    $form->buildForm();
    $form->postProcess();
    /* @var CRM_Event_Form_Registration_Confirm $form */
    $form = $this->getFormObject('CRM_Event_Form_Registration_Confirm');
    $form->preProcess();
    $form->buildForm();
    $form->postProcess();
    // This allows us to rinse & repeat form submission in the same test, without leakage.
    $this->formController = NULL;
  }

  /**
   * Submit a paid event with some default values.
   *
   * @param array $submitValues
   */
  protected function submitPaidEvent(array $submitValues = []): void {
    $this->dummyProcessorCreate();
    $event = $this->eventCreatePaid(['payment_processor' => [$this->ids['PaymentProcessor']['dummy_live']], 'confirm_email_text' => '', 'is_pay_later' => FALSE]);
    $this->submitForm($event['id'], array_merge([
      'email-Primary' => 'demo@example.com',
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Kim',
      'billing_middle_name' => '',
      'billing_last_name' => 'Reality',
      'billing_street_address-5' => 'p',
      'billing_city-5' => 'p',
      'billing_state_province_id-5' => '1061',
      'billing_postal_code-5' => '7',
      'billing_country_id-5' => '1228',
      'priceSetId' => $this->ids['PriceSet']['PaidEvent'],
      $this->getPriceFieldFormLabel('PaidEvent') => $this->ids['PriceFieldValue']['PaidEvent_student'],
      'payment_processor_id' => '1',
      'year' => '2019',
      'month' => '1',
      'button' => '_qf_Register_upload',
      'billing_state_province-5' => 'AP',
      'billing_country-5' => 'US',
    ], $submitValues));
  }

  public function testRegistrationWithoutCiviContributeEnabled(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $event = $this->eventCreateUnpaid([
      'has_waitlist' => 1,
      'max_participants' => 1,
      'start_date' => 20351021,
      'end_date' => 20351023,
      'registration_end_date' => 20351015,
    ]);
    CRM_Core_BAO_ConfigSetting::disableComponent('CiviContribute');
    $this->submitForm(
      $event['id'], [
        [
          'first_name' => 'Bruce No Contribute',
          'last_name' => 'Wayne',
          'email-Primary' => 'bruce@gotham.com',
          'is_primary' => 1,
          'is_pay_later' => 0,
          'campaign_id' => NULL,
          'defaultRole' => 1,
          'participant_role_id' => '1',
          'button' => '_qf_Register_upload',
        ],
      ]
    );
    $mut->checkMailLog([
      'Dear Bruce No Contribute,  Thank you for your registration.  This is a confirmation that your registration has been received and your status has been updated to Registered.',
    ]);
    $mut->stop();
    $mut->clearMessages();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviContribute');
  }

}
