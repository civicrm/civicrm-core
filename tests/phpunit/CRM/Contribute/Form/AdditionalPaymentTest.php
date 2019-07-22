<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_spool ORDER BY id DESC');
    parent::tearDown();
  }

  /**
   * Test the submit function that completes the partially paid Contribution using Credit Card.
   */
  public function testAddPaymentUsingCreditCardForPartialyPaidContribution() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->createContribution('Partially paid');

    // pay additional amount by using Credit Card
    $this->submitPayment(70, 'live', TRUE);
    $this->checkResults([30, 70], 2);
    $mut->assertSubjects(['Payment Receipt -']);
    $mut->checkMailLog([
      'Dear Anthony,',
      'Payment Details',
      'Total Fees: $ 100.00',
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
  }

  /**
   * Test the submit function that completes the partially paid Contribution.
   */
  public function testAddPaymentForPartialyPaidContribution() {
    $this->createContribution('Partially paid');

    // pay additional amount
    $this->submitPayment(70);
    $this->checkResults([30, 70], 2);
  }

  /**
   * Test the submit function that completes the partially paid Contribution with multiple payments.
   */
  public function testMultiplePaymentForPartiallyPaidContribution() {
    $this->createContribution('Partially paid');

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
    $this->assertEquals(2, count($activities));
    $this->assertEquals('$ 50.00 - Offline Payment for Contribution', $activities[0]['subject']);
    $this->assertEquals('$ 20.00 - Offline Payment for Contribution', $activities[1]['subject']);
    $this->assertEquals(CRM_Core_Session::singleton()->getLoggedInContactID(), $activities[0]['source_contact_id']);
    $this->assertEquals([$this->_individualId], $activities[0]['target_contact_id']);
    $this->assertEquals([], $activities[0]['assignee_contact_id']);
  }

  /**
   * Test the submit function that completes the partially paid Contribution with multiple payments.
   */
  public function testMultiplePaymentForPartiallyPaidContributionWithOneCreditCardPayment() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->createContribution('Partially paid');
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
    $mut->assertSubjects(['Payment Receipt -']);
    $mut->checkMailLog([
      'Dear Anthony,',
      'A payment has been received',
      'Total Fees: $ 100.00',
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
  }

  /**
   * Test the submit function that completes the pending pay later Contribution using Credit Card.
   */
  public function testAddPaymentUsingCreditCardForPendingPayLaterContribution() {
    $this->createContribution('Pending');

    // pay additional amount by using Credit Card
    $this->submitPayment(100, 'live');
    $this->checkResults([100], 1);
  }

  /**
   * Test the submit function that completes the pending pay later Contribution.
   */
  public function testAddPaymentForPendingPayLaterContribution() {
    $this->createContribution('Pending');

    // pay additional amount
    $this->submitPayment(70);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    // pay additional amount
    $this->submitPayment(30);
    $this->checkResults([30, 70], 2);
  }

  /**
   * Test the Membership status after completing the pending pay later Contribution.
   */
  public function testMembershipStatusAfterCompletingPayLaterContribution() {
    $this->createContribution('Pending');
    $membership = $this->createPendingMembershipAndRecordContribution($this->_contributionId);
    // pay additional amount
    $this->submitPayment(100);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $contributionMembership = $this->callAPISuccessGetSingle('Membership', ['id' => $membership["id"]]);
    $membershipStatus = $this->callAPISuccessGetSingle('MembershipStatus', ['id' => $contributionMembership["status_id"]]);
    $this->assertEquals('New', $membershipStatus['name']);
  }

  private function createPendingMembershipAndRecordContribution($contributionId) {
    $this->_individualId = $this->individualCreate();
    $membershipTypeAnnualFixed = $this->callAPISuccess('membership_type', 'create', [
      'domain_id' => 1,
      'name' => "AnnualFixed",
      'member_of_contact_id' => 1,
      'duration_unit' => "year",
      'duration_interval' => 1,
      'period_type' => "fixed",
      'fixed_period_start_day' => "101",
      'fixed_period_rollover_day' => "1231",
      'relationship_type_id' => 20,
      'financial_type_id' => 2,
    ]);
    $membershipStatuses = CRM_Member_PseudoConstant::membershipStatus();
    $pendingStatusId = array_search('Pending', $membershipStatuses);
    $membership = $this->callAPISuccess('Membership', 'create', [
      'contact_id' => $this->_individualId,
      'membership_type_id' => $membershipTypeAnnualFixed['id'],
    ]);
    // Updating Membership status to Pending
    $membership = $this->callAPISuccess('Membership', 'create', [
      'id' => $membership["id"],
      'status_id' => $pendingStatusId,
    ]);
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'create', [
      'membership_id' => $membership["id"],
      'contribution_id' => $contributionId,
    ]);
    return $membership;
  }

  /**
   * Test the submit function that completes the pending pay later Contribution with multiple payments.
   */
  public function testMultiplePaymentForPendingPayLaterContribution() {
    $this->createContribution('Pending');

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
   */
  public function testMultiplePaymentForPendingPayLaterContributionWithOneCreditCardPayment() {
    $this->createContribution('Pending');

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
   * Function to create pending pay later or partially paid conntribution.
   *
   * @param string $typeofContribution
   *
   */
  public function createContribution($typeofContribution = 'Pending') {
    if ($typeofContribution == 'Partially paid') {
      $contributionParams = array_merge($this->_params, [
        'partial_payment_total' => 100.00,
        'partial_amount_to_pay' => 30,
        'contribution_status_id' => 1,
      ]);
    }
    elseif ($typeofContribution == 'Pending') {
      $contributionParams = array_merge($this->_params, [
        'contribution_status_id' => 2,
        'is_pay_later' => 1,
      ]);
    }
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->assertNotEmpty($contribution);
    $this->assertEquals($typeofContribution, $contribution['contribution_status']);
    $this->_contributionId = $contribution['id'];
  }

  /**
   * Function to submit payments for contribution.
   *
   * @param float $amount
   *  Payment Amount
   * @param string $mode
   *  Mode of Payment
   * @param bool $isEmailReceipt
   */
  public function submitPayment($amount, $mode = NULL, $isEmailReceipt = FALSE) {
    $form = new CRM_Contribute_Form_AdditionalPayment();

    $submitParams = [
      'contribution_id' => $this->_contributionId,
      'contact_id' => $this->_individualId,
      'total_amount' => $amount,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'trxn_date' => '2017-04-11 13:05:11',
      'payment_processor_id' => 0,
      'is_email_receipt' => $isEmailReceipt,
      'from_email_address' => 'site@something.com',
    ];
    if ($mode) {
      $submitParams += [
        'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
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
        'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
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
   */
  public function checkResults($amounts, $count) {
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $this->_contributionId]);
    $this->assertNotEmpty($contribution);
    $this->assertEquals('Completed', $contribution['contribution_status']);

    $this->callAPISuccessGetCount('EntityFinancialTrxn', [
      'entity_table' => "civicrm_contribution",
      'entity_id' => $this->_contributionId,
      'financial_trxn_id.is_payment' => 1,
      'financial_trxn_id.total_amount' => ['IN' => $amounts],
    ], $count);
  }

}
