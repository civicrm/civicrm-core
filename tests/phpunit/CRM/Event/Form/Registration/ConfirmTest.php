<?php

declare(strict_types = 1);
use Civi\Api4\Participant;
use Civi\Api4\PriceFieldValue;
use Civi\Test\FormTrait;

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_Registration_ConfirmTest extends CiviUnitTestCase {

  use CRMTraits_Event_ScenarioTrait;
  use CRMTraits_Financial_PriceSetTrait;
  use CRMTraits_Profile_ProfileTrait;
  use FormTrait;

  public function tearDown(): void {
    $this->revertTemplateToReservedTemplate();
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Initial test of submit function.
   */
  public function testSubmit(): void {
    $this->submitPaidEvent();
    $this->assertMailSentContainingStrings([
      'Dear Kim,',
      'Thank you for your registration.',
      'This is a confirmation that your registration has been received and your status has been updated to Registered.',
      'Friday September 16th, 2022 12:00 PM-Saturday September 17th, 2022 12:00 PM',
      'Add event to Google Calendar',
    ]);
  }

  public function assertSentMailHasStrings(array $strings): void {
    foreach ($strings as $string) {
      $this->assertSentMailHasString($string);
    }
  }

  public function assertSentMailHasString(string $string): void {
    $this->assertStringContainsString($string, $this->sentMail[0]);
  }

  /**
   * Test mail does not have calendar links if 'is_show_calendar_links = FALSE'
   */
  public function testNoCalendarLinks() : void {
    $this->submitPaidEvent(['is_show_calendar_links' => FALSE]);
    $this->assertMailSentNotContainingStrings([
      'Download iCalendar entry for this event',
      'Add event to Google Calendar',
      'civicrm/event/ical',
    ]);
  }

  public function assertSentMailNotHasStrings(array $strings): void {
    foreach ($strings as $string) {
      $this->assertSentMailNotHasString($string);
    }
  }

  public function assertSentMailNotHasString(string $string): void {
    $this->assertStringNotContainsString($string, $this->sentMail[0]);
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
    $this->setCurrencySeparators($thousandSeparator);
    $paymentProcessorID = $this->processorCreate();
    /** @var \CRM_Core_Payment_Dummy $processor */
    $processor = Civi\Payment\System::singleton()->getById($paymentProcessorID);
    $processor->setDoDirectPaymentResult(['fee_amount' => 1.67]);
    $event = $this->eventCreatePaid([
      'payment_processor' => [$paymentProcessorID],
      'selfcancelxfer_time' => 72,
    ]);
    $this->submitForm($event['id'], [
      'first_name' => 'k',
      'last_name' => 'p',
      'email-Primary' => 'demo@example.com',
      'price_' . $this->getPriceFieldID('PaidEvent') => $this->ids['PriceFieldValue']['PaidEvent_standard'],
    ] + $this->getCreditCardParameters($paymentProcessorID));
    $this->callAPISuccessGetCount('Participant', [], 1);
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $this->assertEquals(300, $contribution['total_amount']);
    $this->assertEquals(1.67, $contribution['fee_amount']);
    $this->assertEquals(298.33, $contribution['net_amount']);
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
      'amount' => '300.00',
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
    $this->assertMailSentContainingHeaderString('Registration Confirmation - Annual CiviCRM meet');
    $this->assertMailSentContainingStrings([
      'Event Information and Location',
      'Expires: January ' . (date('Y') + 1),
      'Visa',
      '************1111',
      'This is a confirmation that your registration has been received and your status has been updated to <strong>Registered</strong>',
      'You may transfer your registration to another participant or cancel your registration up to 72 hours before the event',
    ]);
  }

  /**
   * Tests payment processor receives contactID when registering for paid event from waitlist.
   *
   * https://github.com/civicrm/civicrm-core/pull/23358, https://lab.civicrm.org/extensions/stripe/-/issues/347
   */
  public function testWaitlistRegistrationContactIDParam(): void {
    $this->hookClass->setHook('civicrm_alterPaymentProcessorParams', [$this, 'checkPaymentParameters']);
    $paymentProcessorID = $this->processorCreate();
    $event = $this->eventCreatePaid(['payment_processor' => [$paymentProcessorID]]);
    $_REQUEST['mode'] = 'live';
    // Add someone to the waitlist.
    $waitlistContactID = $this->individualCreate();
    $waitlistParticipantID = $this->participantCreate(['event_id' => $event['id'], 'contact_id' => $waitlistContactID, 'status_id' => 'On waitlist']);

    $waitlistParticipant = $this->callAPISuccess('Participant', 'getsingle', ['id' => $waitlistParticipantID, 'return' => ['participant_status']]);
    $this->assertEquals('On waitlist', $waitlistParticipant['participant_status'], 'Invalid participant status. Expecting: On waitlist');
    $this->submitForm($this->getEventID(), [
      'first_name' => 'bob',
      'last_name' => 'smith',
      'email-Primary' => 'bob@example.com',
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => [
        'M' => '1',
        'Y' => date('Y') + 1,
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Bob',
      'billing_middle_name' => '',
      'billing_last_name' => 'Smith',
      'billing_street_address-5' => 'p',
      'billing_city-5' => 'p',
      'billing_state_province_id-5' => '1061',
      'billing_postal_code-5' => '7',
      'billing_country_id-5' => '1228',
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      $this->getPriceFieldFormLabel('PaidEvent') => $this->ids['PriceFieldValue']['PaidEvent_student'],
      'payment_processor_id' => $paymentProcessorID,
      'participant_id' => $waitlistParticipantID,
      'billing_state_province-5' => 'AP',
      'billing_country-5' => 'US',
      'hidden_processor' => 1,
    ]);
    $waitlistParticipant = $this->callAPISuccess('Participant', 'getsingle', ['id' => $waitlistParticipantID, 'return' => ['participant_status']]);
    $this->assertEquals('Registered', $waitlistParticipant['participant_status'], 'Invalid participant status. Expecting: Registered');
  }

  public function checkPaymentParameters($paymentObject, $parameters): void {
    $requiredFields = ['contactID', 'amount', 'invoiceID', 'currency', 'billing_first_name', 'billing_last_name'];
    foreach ($requiredFields as $field) {
      $this->assertNotEmpty($parameters[$field], $field . ' is required to have a value');
    }
  }

  /**
   * Test for Tax amount for multiple participant.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTaxMultipleParticipant(): void {
    $this->createLoggedInUser();
    $this->createScenarioMultipleParticipantPendingWithTax();
    $participants = Participant::get()
      ->addWhere('event_id', '=', $this->getEventID())
      ->addSelect('contact_id', 'contact_id.job_title')->execute();
    $this->assertCount(3, $participants);
    $contribution = $this->callAPISuccessGetSingle(
      'Contribution',
      [
        'return' => ['tax_amount', 'total_amount', 'amount_level'],
      ]
    );
    $this->assertContains(' (multiple participants)', $contribution['amount_level']);
    $this->assertEquals(60, $contribution['tax_amount'], 'Invalid Tax amount.');
    $this->assertEquals(660, $contribution['total_amount'], 'Invalid Tax amount.');
    $mailSent = $this->sentMail;
    $this->assertCount(3, $mailSent, 'Three mails should have been sent to the 3 participants.');

    // The first one is the primary & has the job titles for all 3.
    $this->assertStringContainsString('Dear Participant1', $mailSent[0]['body']);
    $this->assertStringContainsString('job_title	oracle', $mailSent[0]['body']);
    $this->assertStringContainsString('job_title	wizard', $mailSent[0]['body']);
    $this->assertStringContainsString('job_title	seer', $mailSent[0]['body']);

    // The second has only it's own job title.
    $this->assertStringContainsString('Dear Participant2', $mailSent[1]['body']);
    $this->assertStringNotContainsString('job_title	oracle', $mailSent[1]['body']);
    $this->assertStringContainsString('job_title	wizard', $mailSent[1]['body']);
    $this->assertStringNotContainsString('job_title	seer', $mailSent[1]['body']);

    // The third has only it's own job title.
    $this->assertStringContainsString('Dear Participant3', $mailSent[2]['body']);
    $this->assertStringNotContainsString('job_title	oracle', $mailSent[2]['body']);
    $this->assertStringNotContainsString('job_title	wizard', $mailSent[2]['body']);
    $this->assertStringContainsString('job_title	seer', $mailSent[2]['body']);

    $mut = new CiviMailUtils($this);
    $this->validateAllContributions();
    $this->validateAllPayments();
    $this->callAPISuccess('Payment', 'create', ['total_amount' => 990, 'payment_type_id' => 'Cash', 'contribution_id' => $contribution['id']]);
    $mailSent = $mut->getAllMessages();
    $this->assertCount(3, $mailSent);

    $this->assertStringContainsString('Registered', $mailSent[0]);

    $this->assertStringContainsString('Dear Participant1', $mailSent[2]);
    $this->assertStringContainsString('job_title	oracle', $mailSent[2]);
    $this->assertStringContainsString('job_title	wizard', $mailSent[2]);
    $this->assertStringContainsString('job_title	seer', $mailSent[2]);

    $this->assertStringContainsString('Dear Participant2', $mailSent[0]);
    $this->assertStringNotContainsString('job_title	oracle', $mailSent[0]);
    $this->assertStringContainsString('job_title	wizard', $mailSent[0]);
    $this->assertStringNotContainsString('job_title	seer', $mailSent[0]);

    $this->assertStringContainsString('Dear Participant3', $mailSent[1]);
    $this->assertStringNotContainsString('job_title	oracle', $mailSent[1]);
    $this->assertStringNotContainsString('job_title	wizard', $mailSent[1]);
    $this->assertStringContainsString('job_title	seer', $mailSent[1]);
  }

  /**
   * Test price set level participant count with multiple participants.
   *
   * In this scenario each price field value has a participant count
   * and the total (7) exceeds the available places (6).
   *
   * @throws \CRM_Core_Exception
   */
  public function testEventFullHandlingMultipleParticipants(): void {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $this->eventCreatePaid(['max_participants' => 6, 'processor_id' => $paymentProcessorID]);
    $participantCount = [
      'standard' => 1,
      'student' => 4,
      'student_plus' => 2,
    ];
    foreach ($participantCount as $key => $count) {
      PriceFieldValue::update(FALSE)
        ->addWhere('id', '=', $this->ids['PriceFieldValue']['PaidEvent_' . $key])
        ->setValues(['count' => $count])->execute();
    }
    $this->getTestForm('CRM_Event_Form_Registration_Register', [
      'first_name' => 'Participant1',
      'last_name' => 'LastName',
      'email-Primary' => 'participant1@example.com',
      'additional_participants' => 2,
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      'payment_processor_id' => 0,
      'price_' . $this->ids['PriceField']['PaidEvent'] => $this->ids['PriceFieldValue']['PaidEvent_standard'],
    ], ['id' => $this->getEventID()])
      ->addSubsequentForm('CRM_Event_Form_Registration_AdditionalParticipant', [
        'first_name' => 'Participant2',
        'last_name' => 'LastName',
        'job_title' => 'wizard',
        'email-Primary' => 'participant2@example.com',
        'priceSetId' => $this->getPriceSetID('PaidEvent'),
        'price_' . $this->ids['PriceField']['PaidEvent'] => $this->ids['PriceFieldValue']['PaidEvent_student'],
      ])
      ->addSubsequentForm('CRM_Event_Form_Registration_AdditionalParticipant', [
        'first_name' => 'Participant3',
        'last_name' => 'LastName',
        'job_title' => 'seer',
        'email-Primary' => 'participant3@example.com',
        'priceSetId' => $this->getPriceSetID('PaidEvent'),
        'price_' . $this->ids['PriceField']['PaidEvent'] => $this->ids['PriceFieldValue']['PaidEvent_student_plus'],
      ])
      ->addSubsequentForm('CRM_Event_Form_Registration_Confirm')
      ->processForm();
    // nothing to see here - just a no-fail test at this stage.
  }

  /**
   * Test stock template for multiple participant.
   *
   * The goal is to ensure no leakage.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMailMultipleParticipant(): void {
    $this->createScenarioMultipleParticipantPendingWithTax();
    $mailSent = $this->sentMail;
    // amounts paid = [300, 100, 200];
    // The first participant, as the primary participant, (only) will have the full total in the email
    $this->assertStringContainsString('$600', $mailSent[0]['body']);
    $this->assertStringNotContainsString('$600', $mailSent[1]['body']);
    $this->assertStringNotContainsString('$600', $mailSent[2]['body']);

    // The $100 paid by the second participant will be in the emails to the primary but and second participant
    $this->assertStringContainsString('$100', $mailSent[0]['body']);
    $this->assertStringContainsString('$100', $mailSent[1]['body']);
    $this->assertStringNotContainsString('$100', $mailSent[2]['body']);

    // The $200 paid by the second participant will be in the emails to the primary but and third participant
    $this->assertStringContainsString('$200', $mailSent[0]['body']);
    $this->assertStringNotContainsString('$200', $mailSent[1]['body']);
    $this->assertStringContainsString('$200', $mailSent[2]['body']);
  }

  /**
   * Test online registration for event with no price options selected as per CRM-19964.
   */
  public function testOnlineRegistrationNoPrice(): void {
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
        'first_name' => 'Bruce',
        'last_name' => 'Wayne',
        'email-Primary' => 'bruce@gotham.com',
        'price_' . $priceField['id'] => 0,
        'priceSetId' => $priceField['values'][$priceField['id']]['price_set_id'],
        'payment_processor_id' => $paymentProcessorID,
        'button' => '_qf_Register_upload',
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
    $tplVar = $smarty->getTemplateVars();
    $this->assertEquals([
      'CustomPre' => ['First Name' => NULL],
      'CustomPreGroupTitle' => 'Public title',
    ], $tplVar['primaryParticipantProfile']);
  }

  /**
   * Create an event with a "pre" profile
   *
   * @throws \CRM_Core_Exception
   */
  private function submitEventWithNoteInProfile($note): void {
    if (empty($this->ids['Event'])) {
      $this->eventCreateUnpaid();
      $this->addUFField($this->ids['UFGroup']['event_post_post_event'], 'note', 'Contact', 'Comment');
    }

    $form = $this->getFormWrapper([
      'email-Primary' => 'demo@example.com',
      'note' => $note,
      'job_title' => 'Magician',
    ], $this->getEventID());
    $form->processForm();
    $form->checkTemplateVariable('primaryParticipantProfile', [
      'CustomPre' => ['Comment' => $note, 'job_title' => 'Magician'],
      'CustomPreGroupTitle' => 'Public Event Post Post Profile',
    ]);
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
    $this->submitEventWithNoteInProfile('This is note 1');
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $this->assertMailSentContainingStrings(['Comment', 'This is note 1']);
    $this->callAPISuccess('Participant', 'delete', ['id' => $participant['id']]);

    //now that the contact has one note, register this contact again with a different note
    //and confirm that the note shown in the email is the current one
    // Create an event with an attached profile containing a note
    $this->submitEventWithNoteInProfile('This is note 2');
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $this->assertMailSentContainingStrings(['Comment', 'This is note 2']);
    $this->callAPISuccess('Participant', 'delete', ['id' => $participant['id']]);

    //finally, submit a blank note and confirm that the note shown in the email is blank
    $this->submitEventWithNoteInProfile('');
    $participant = $this->callAPISuccessGetSingle('Participant', []);
    $this->assertMailSentNotContainingString('This is note');
    $this->callAPISuccess('Participant', 'delete', ['id' => $participant['id']]);
  }

  /**
   * Ensure we send to the submitted email, not the primary email, if different.
   *
   * event#64.
   */
  public function testSubmitNonPrimaryEmail(): void {
    $event = $this->eventCreateUnpaid();
    $mut = new CiviMailUtils($this, TRUE);
    $this->submitFormLegacy($event['id'], [
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
        'year' => '2019',
        'month' => '1',
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
   */
  protected function submitForm(int $eventID, array $submittedValues): void {
    $form = $this->getFormWrapper($submittedValues, $eventID);
    $form->processForm();
  }

  /**
   * Submit the confirm form.
   *
   * @deprecated use SubmitForm
   *
   * @param int $eventID
   * @param array $submittedValues Submitted Values
   */
  protected function submitFormLegacy(int $eventID, array $submittedValues): void {
    $_REQUEST['id'] = $eventID;
    /* @var CRM_Event_Form_Registration_Register $form */
    $form = $this->getFormObject('CRM_Event_Form_Registration_Register', $submittedValues[0] ?? $submittedValues);
    $form->preProcess();
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
   * @param array $eventParams
   * @param array $submitValues
   */
  protected function submitPaidEvent(array $eventParams = [], array $submitValues = []): void {
    $this->dummyProcessorCreate();
    $event = $this->eventCreatePaid(['payment_processor' => [$this->ids['PaymentProcessor']['dummy_live']], 'confirm_email_text' => '', 'is_pay_later' => FALSE, 'start_date' => '2022-09-16 12:00', 'end_date' => '2022-09-17 12:00'] + $eventParams);
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

  /**
   * Ensure an email is sent when confirm is disabled for unpaid events.
   */
  public function testSubmitUnpaidEventNoConfirm(): void {
    $this->eventCreateUnpaid(['is_confirm_enabled' => FALSE]);
    $form = $this->getTestForm('CRM_Event_Form_Registration_Register', [
      'email-Primary' => 'demo@example.com',
    ], ['id' => $this->getEventID()]);
    $form->processForm();
    $this->assertMailSentContainingStrings(['Event']);
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
    $this->submitFormLegacy(
      $event['id'], [
        [
          'first_name' => 'Bruce No Contribute',
          'last_name' => 'Wayne',
          'email-Primary' => 'bruce@gotham.com',
          'is_primary' => 1,
          'is_pay_later' => 0,
        ],
      ]
    );
    $mut->checkMailLog([
      'Dear Bruce No Contribute,',
      'Thank you for your registration.',
      'This is a confirmation that your registration has been received and your status has been updated to Registered.',
    ]);
    $mut->stop();
    $mut->clearMessages();
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviContribute');
  }

  /**
   * @param array $submittedValues
   * @param int $eventID
   *
   * @return \Civi\Test\FormWrapper|\Civi\Test\FormWrappers\EventFormOnline|\Civi\Test\FormWrappers\EventFormParticipant|null
   */
  public function getFormWrapper(array $submittedValues, int $eventID) {
    return $this->getTestForm('CRM_Event_Form_Registration_Register',
      $submittedValues,
      ['id' => $eventID])
      ->addSubsequentForm('CRM_Event_Form_Registration_Confirm');
  }

  /**
   * @param int $paymentProcessorID
   *
   * @return array
   */
  public function getCreditCardParameters(int $paymentProcessorID): array {
    return [
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => [
        'M' => '1',
        'Y' => date('Y') + 1,
      ],
      'priceSetId' => $this->getPriceSetID('PaidEvent'),
      'billing_state_province-5' => 'AP',
      'billing_country-5' => 'US',
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'p',
      'billing_middle_name' => '',
      'billing_last_name' => 'p',
      'billing_street_address-5' => 'p',
      'billing_city-5' => 'p',
      'billing_state_province_id-5' => '1061',
      'billing_postal_code-5' => '7',
      'billing_country_id-5' => '1228',
      'payment_processor_id' => $paymentProcessorID,
      'hidden_processor' => '1',
    ];
  }

}
