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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_AdditionalPaymentTest extends CiviUnitTestCase {

  use CRMTraits_Financial_OrderTrait;

  /**
   * Contact ID.
   *
   * @var int
   */
  protected $_individualId;

  /**
   * Parameters to create contribution.
   *
   * @var array
   */
  protected $_params;

  /**
   * Contribution ID.
   *
   * @var int
   */
  protected $_contributionId;

  /**
   * Parameters to create payment processor.
   *
   * @var array
   */
  protected $_processorParams = [];

  /**
   * Payment instrument mapping.
   *
   * @var array
   */
  protected $paymentInstruments = [];

  /**
   * Dummy payment processor.
   *
   * @var CRM_Core_Payment_Dummy
   */
  protected $paymentProcessor;

  /**
   * Payment processor ID.
   *
   * @var int
   */
  protected $paymentProcessorID;

  /**
   * Setup function.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->createLoggedInUser();

    $this->_individualId = $this->individualCreate();
    $this->_params = [
      'total_amount' => 100,
      'currency' => 'USD',
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
    ];
    $this->_processorParams = [
      'domain_id' => 1,
      'name' => 'Dummy',
      'payment_processor_type_id' => 10,
      'financial_account_id' => 12,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'https://dummy.com',
      'url_recur' => 'https://dummy.com',
      'billing_mode' => 1,
    ];

    $instruments = $this->callAPISuccess('contribution', 'getoptions', ['field' => 'payment_instrument_id']);
    $this->paymentInstruments = $instruments['values'];

    $this->paymentProcessor = $this->dummyProcessorCreate();
    $processor = $this->paymentProcessor->getPaymentProcessor();
    $this->paymentProcessorID = $processor['id'];
  }

  /**
   * Clean up after each test.
   *
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_mailing_spool']);
    parent::tearDown();
  }

  /**
   * Test the submit function that completes the partially paid Contribution using Credit Card.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testAddPaymentUsingCreditCardForPartiallyPaidContribution(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->createPartiallyPaidOrder();

    // pay additional amount by using Credit Card
    $this->submitPayment(70, 'live', TRUE);
    $this->checkResults([30, 70], 2);
    $mut->assertSubjects(['Payment Receipt - Mr. Anthony Anderson II']);
    $mut->checkMailLog([
      'From: site@something.com',
      'Dear Anthony,',
      'Payment Details',
      'Total Fee: $100.00',
      'This Payment Amount: $70.00',
      'Billing Name and Address',
      'Vancouver, AE 1321312',
      'Visa',
      '***********1111',
      'Expires: May 2025',
    ]);

    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test the submit function that completes the partially paid Contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testAddPaymentForPartiallyPaidContribution(): void {
    $this->createPartiallyPaidOrder();

    // pay additional amount
    $this->submitPayment(70);
    $this->checkResults([30, 70], 2);
  }

  /**
   * Test the submit function that completes the partially paid Contribution with multiple payments.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMultiplePaymentForPartiallyPaidContribution(): void {
    $this->createPartiallyPaidOrder();

    // pay additional amount
    $this->submitPayment(50);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    // pay additional amount
    $this->submitPayment(20);
    $this->checkResults([30, 50, 20], 3);
    $activities = $this->callAPISuccess('Activity', 'get', [
      'source_record_id' => $this->_contributionId,
      'activity_type_id' => 'Payment',
      'options' => ['sort' => 'id'],
      'sequential' => 1,
      'return' => ['target_contact_id', 'assignee_contact_id', 'subject'],
    ])['values'];
    $this->assertCount(3, $activities);
    $this->assertEquals('$ 50.00 - Offline Payment for Contribution', $activities[1]['subject']);
    $this->assertEquals('$ 20.00 - Offline Payment for Contribution', $activities[2]['subject']);
    $this->assertEquals(CRM_Core_Session::singleton()->getLoggedInContactID(), $activities[0]['source_contact_id']);
    $this->assertEquals([$this->_individualId], $activities[0]['target_contact_id']);
    $this->assertEquals([], $activities[0]['assignee_contact_id']);
  }

  /**
   * Test the submit function that completes the partially paid Contribution with multiple payments.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMultiplePaymentForPartiallyPaidContributionWithOneCreditCardPayment(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->createPartiallyPaidOrder();
    // In general when there is tpl leakage we try to fix. At the moment, however,
    // the tpl leakage on credit card related things is kind of 'by-design' - or
    // at least we haven't found a way to replace the way in with Payment.send_confirmation
    // picks them up from the form process so we will just clear templates here to stop leakage
    // from previous tests causing a fail.
    // The reason this is hard to fix is that we save a billing address per contribution not
    // per payment so it's a problem with the data model
    CRM_Core_Smarty::singleton()->clearTemplateVars();

    // pay additional amount
    $this->submitPayment(50, NULL, TRUE);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    // pay additional amount by using credit card
    $this->submitPayment(20, 'live');
    $this->checkResults([30, 50, 20], 3);
    $mut->assertSubjects(['Payment Receipt - Mr. Anthony Anderson II']);
    $mut->checkMailLog([
      'Dear Anthony,',
      'Below you will find a receipt for this payment.',
      'Total Fee: $100.00',
      'This Payment Amount: $50.00',
      'Balance Owed: $20.00 ',
      'Paid By: Check',
      'Check Number: check-12345',
    ],
    [
      'Billing Name and Address',
      'Visa',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test the submit function that completes the pending pay later Contribution using Credit Card.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testAddPaymentUsingCreditCardForPendingPayLaterContribution(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->createPendingOrder();

    // pay additional amount by using Credit Card
    $this->submitPayment(100, 'live', TRUE);
    $this->checkResults([100], 1);

    $mut->checkMailLog([
      'Below you will find a receipt for this payment.',
      'Total Fee: $100.00',
      'This Payment Amount: $100.00',
      'Paid By: Credit Card',
      '***********1111',
      'Billing Name and Address',
      'Vancouver, AE 1321312',
      'Expires: May 2025',

    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test the submit function that completes the pending pay later Contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testAddPaymentForPendingPayLaterContribution(): void {
    $this->createPendingOrder();

    // pay additional amount
    $this->submitPayment(70);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);
    $this->assertEquals('2019-04-01 00:00:00', $contribution['receive_date']);
    $payment = $this->callAPISuccessGetSingle('Payment', ['contribution_id' => $contribution['id']]);
    $this->assertEquals('2017-04-11 13:05:11', $payment['trxn_date']);

    // pay additional amount
    $this->submitPayment(30);
    $this->checkResults([30, 70], 2);
  }

  /**
   * Test the Membership status after completing the pending pay later Contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipStatusAfterCompletingPayLaterContribution(): void {
    $this->createContributionAndMembershipOrder();
    $this->submitPayment(300);
    $this->callAPISuccessGetSingle('Contribution', ['id' => $this->ids['Contribution'][0]]);
    $this->callAPISuccessGetSingle('Membership', ['id' => $this->ids['Membership']['order'], 'status_id' => 'New']);
  }

  /**
   * Test the submit function that completes the pending pay later Contribution with multiple payments.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMultiplePaymentForPendingPayLaterContribution(): void {
    $this->createPendingOrder();

    // pay additional amount
    $this->submitPayment(40);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(20);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(30);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(10);
    $this->checkResults([40, 20, 30, 10], 4);
  }

  /**
   * Test the submit function that completes the pending pay later Contribution with multiple payments.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMultiplePaymentForPendingPayLaterContributionWithOneCreditCardPayment(): void {
    $this->createPendingOrder();

    // pay additional amount
    $this->submitPayment(50);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(20, 'live');
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(20);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(10, 'live');
    $this->checkResults([50, 20, 20, 10], 4);
  }

  /**
   * Function to submit payments for contribution.
   *
   * @param float $amount
   *  Payment Amount
   * @param string|null $mode
   *  Mode of Payment
   * @param bool $isEmailReceipt
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function submitPayment(float $amount, string $mode = NULL, bool $isEmailReceipt = FALSE): void {
    $submitParams = [
      'contact_id' => $this->ids['Contact']['order'] ?? $this->_individualId,
      'total_amount' => $amount,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'trxn_date' => '2017-04-11 13:05:11',
      'payment_processor_id' => 0,
      'is_email_receipt' => $isEmailReceipt,
      'from_email_address' => 'site@something.com',
    ];
    if ($mode) {
      $_REQUEST['mode'] = $mode;
      $submitParams += [
        'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments, TRUE),
        'payment_processor_id' => $this->paymentProcessorID,
        'credit_card_exp_date' => ['M' => 5, 'Y' => 2025],
        'credit_card_number' => '411111111111111',
        'cvv2' => 234,
        'credit_card_type' => 'Visa',
        'billing_city-5' => 'Vancouver',
        'billing_state_province_id-5' => 1059,
        'billing_postal_code-5' => 1321312,
        'billing_country_id-5' => 1228,
      ];
    }
    else {
      $submitParams += [
        'payment_instrument_id' => array_search('Check', $this->paymentInstruments, TRUE),
        'check_number' => 'check-12345',
      ];
    }
    $_REQUEST['id'] = $this->ids['Contribution'][0] ?? $this->_contributionId;
    /* @var CRM_Contribute_Form_AdditionalPayment $form*/
    $form = $this->getFormObject('CRM_Contribute_Form_AdditionalPayment', $submitParams);
    $form->buildForm();
    $form->postProcess();
  }

  /**
   * Function to check result.
   *
   * @param array $amounts
   *    Array of payment amount for contribution
   * @param int $count
   *   Number payment for contribution
   *
   */
  public function checkResults(array $amounts, int $count): void {
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertNotEmpty($contribution);
    $this->assertEquals('Completed', $contribution['contribution_status']);

    $this->callAPISuccessGetCount('EntityFinancialTrxn', [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $this->_contributionId,
      'financial_trxn_id.is_payment' => 1,
      'financial_trxn_id.total_amount' => ['IN' => $amounts],
    ], $count);
  }

  /**
   * Create a pending order.
   *
   */
  protected function createPendingOrder(): void {
    $orderParams = array_merge($this->_params, [
      'contribution_status_id' => 'Pending',
      'is_pay_later' => 1,
      'receive_date' => '2019-04-01',
    ]);
    $contribution = $this->callAPISuccess('Order', 'create', $orderParams);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->assertEquals('Pending', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution['contribution_status_id']));
    $this->_contributionId = $contribution['id'];
  }

  /**
   * Create a partially paid order.
   *
   */
  protected function createPartiallyPaidOrder(): void {
    $orderParams = array_merge($this->_params, [
      'total_amount' => 100.00,
      'contribution_status_id' => 'Pending',
      'api.Payment.create' => ['total_amount' => 30],
    ]);
    $contribution = $this->callAPISuccess('Order', 'create', $orderParams);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->assertEquals('Partially paid', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution['contribution_status_id']));
    $this->_contributionId = $contribution['id'];
  }

  /**
   * Test the Membership status renaming after completing the pending pay later Contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipStatusAfterCompletingPayLaterContributionWithRenamedMembershipStatus(): void {
    $this->renameNewMembershipStatus('Fresh');
    $this->createContributionAndMembershipOrder();
    $this->submitPayment(300);
    $this->callAPISuccessGetSingle('Contribution', ['id' => $this->ids['Contribution'][0]]);
    $this->callAPISuccessGetSingle('Membership', ['id' => $this->ids['Membership']['order'], 'status_id' => 'Fresh']);
  }

  /**
   * @param $membershipStatusName
   *
   */
  private function renameNewMembershipStatus($membershipStatusName): void {
    $params = [
      'name' => 'New',
      'api.MembershipStatus.create' => ['name' => $membershipStatusName],
    ];
    $this->callAPISuccess('MembershipStatus', 'get', $params);
  }

}
