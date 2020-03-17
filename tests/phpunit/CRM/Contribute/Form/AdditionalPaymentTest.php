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
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp() {
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
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
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
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
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
  public function testAddPaymentUsingCreditCardForPartiallyPaidContribution() {
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
      'Total Fee: $ 100.00',
      'This Payment Amount: $ 70.00',
      'Balance Owed: $ 0.00 ',
      'Billing Name and Address',
      'Vancouver, AE 1321312',
      'Visa',
      '***********1111',
      'Expires: May 2025',
    ]);

    $mut->stop();
    $mut->clearMessages();
    $this->validateAllPayments();
  }

  /**
   * Test the submit function that completes the partially paid Contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testAddPaymentForPartiallyPaidContribution() {
    $this->createPartiallyPaidOrder();

    // pay additional amount
    $this->submitPayment(70);
    $this->checkResults([30, 70], 2);
    $this->validateAllPayments();
  }

  /**
   * Test the submit function that completes the partially paid Contribution with multiple payments.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMultiplePaymentForPartiallyPaidContribution() {
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
    $this->validateAllPayments();
  }

  /**
   * Test the submit function that completes the partially paid Contribution with multiple payments.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMultiplePaymentForPartiallyPaidContributionWithOneCreditCardPayment() {
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
      'Total Fee: $ 100.00',
      'This Payment Amount: $ 50.00',
      'Balance Owed: $ 20.00 ',
      'Paid By: Check',
      'Check Number: check-12345',
    ],
    [
      'Billing Name and Address',
      'Visa',
    ]);
    $mut->stop();
    $mut->clearMessages();
    $this->validateAllPayments();
  }

  /**
   * Test the submit function that completes the pending pay later Contribution using Credit Card.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testAddPaymentUsingCreditCardForPendingPayLaterContribution() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->createPendingOrder();

    // pay additional amount by using Credit Card
    $this->submitPayment(100, 'live', TRUE);
    $this->checkResults([100], 1);

    $mut->checkMailLog([
      'Below you will find a receipt for this payment.',
      'Total Fee: $ 100.00',
      'This Payment Amount: $ 100.00',
      'Balance Owed: $ 0.00 ',
      'Paid By: Credit Card',
      '***********1111',
      'Billing Name and Address',
      'Vancouver, AE 1321312',
      'Expires: May 2025',

    ]);
    $mut->stop();
    $mut->clearMessages();
    $this->validateAllPayments();
  }

  /**
   * Test the submit function that completes the pending pay later Contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testAddPaymentForPendingPayLaterContribution() {
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
    $this->validateAllPayments();
  }

  /**
   * Test the Membership status after completing the pending pay later Contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMembershipStatusAfterCompletingPayLaterContribution() {
    $this->createPendingOrder();
    $membership = $this->createPendingMembershipAndRecordContribution($this->_contributionId);
    // pay additional amount
    $this->submitPayment(100);
    $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $contributionMembership = $this->callAPISuccessGetSingle('Membership', ['id' => $membership['id']]);
    $membershipStatus = $this->callAPISuccessGetSingle('MembershipStatus', ['id' => $contributionMembership['status_id']]);
    $this->assertEquals('New', $membershipStatus['name']);
    $this->validateAllPayments();
  }

  /**
   * @param $contributionId
   *
   * @return array|int
   *
   * @throws \CRM_Core_Exception
   */
  private function createPendingMembershipAndRecordContribution($contributionId) {
    $this->_individualId = $this->individualCreate();
    $membershipTypeAnnualFixed = $this->callAPISuccess('membership_type', 'create', [
      'domain_id' => 1,
      'name' => 'AnnualFixed',
      'member_of_contact_id' => 1,
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '101',
      'fixed_period_rollover_day' => '1231',
      'relationship_type_id' => 20,
      'financial_type_id' => 2,
    ]);
    $membershipStatuses = CRM_Member_PseudoConstant::membershipStatus();
    $pendingStatusId = array_search('Pending', $membershipStatuses, TRUE);
    $membership = $this->callAPISuccess('Membership', 'create', [
      'contact_id' => $this->_individualId,
      'membership_type_id' => $membershipTypeAnnualFixed['id'],
    ]);
    // Updating Membership status to Pending
    $membership = $this->callAPISuccess('Membership', 'create', [
      'id' => $membership['id'],
      'status_id' => $pendingStatusId,
    ]);
    $this->callAPISuccess('MembershipPayment', 'create', [
      'membership_id' => $membership['id'],
      'contribution_id' => $contributionId,
    ]);
    return $membership;
  }

  /**
   * Test the submit function that completes the pending pay later Contribution with multiple payments.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMultiplePaymentForPendingPayLaterContribution() {
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
    $this->validateAllPayments();
  }

  /**
   * Test the submit function that completes the pending pay later Contribution with multiple payments.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testMultiplePaymentForPendingPayLaterContributionWithOneCreditCardPayment() {
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
    $this->validateAllPayments();
  }

  /**
   * Function to submit payments for contribution.
   *
   * @param float $amount
   *  Payment Amount
   * @param string $mode
   *  Mode of Payment
   * @param bool $isEmailReceipt
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function submitPayment($amount, $mode = NULL, $isEmailReceipt = FALSE) {
    $form = new CRM_Contribute_Form_AdditionalPayment();

    $submitParams = [
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_individualId,
      'total_amount' => $amount,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'trxn_date' => '2017-04-11 13:05:11',
      'payment_processor_id' => 0,
      'is_email_receipt' => $isEmailReceipt,
      'from_email_address' => 'site@something.com',
    ];
    if ($mode) {
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
    $form->cid = $this->_individualId;
    $form->testSubmit($submitParams, $mode);
  }

  /**
   * Function to check result.
   *
   * @param array $amounts
   *    Array of payment amount for contribution
   * @param int $count
   *   Number payment for contribution
   *
   * @throws \CRM_Core_Exception
   */
  public function checkResults($amounts, $count) {
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
   * @throws \CRM_Core_Exception
   */
  protected function createPendingOrder() {
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
   * @throws \CRM_Core_Exception
   */
  protected function createPartiallyPaidOrder() {
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

}
