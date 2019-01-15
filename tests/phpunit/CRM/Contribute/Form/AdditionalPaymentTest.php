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
  protected $_processorParams = array();

  /**
   * Payment instrument mapping.
   *
   * @var array
   */
  protected $paymentInstruments = array();

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
    $this->_params = array(
      'total_amount' => 100,
      'currency' => 'USD',
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
    );
    $this->_processorParams = array(
      'domain_id' => 1,
      'name' => 'Dummy',
      'payment_processor_type_id' => 10,
      'financial_account_id' => 12,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
      'billing_mode' => 1,
    );

    $instruments = $this->callAPISuccess('contribution', 'getoptions', array('field' => 'payment_instrument_id'));
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
    $this->checkResults(array(30, 70), 2);
    $mut->assertSubjects(['Payment Receipt -']);
    $mut->checkMailLog([
      'Dear Anthony Anderson',
      'Payment Details',
      'Total Fees: $ 100.00',
      'This Payment Amount: $ 70.00',
      'Balance Owed: $ 0.00 ',
    ]);

    $mut->stop();
  }

  /**
   * Test the submit function that completes the partially paid Contribution.
   */
  public function testAddPaymentForPartialyPaidContribution() {
    $this->createContribution('Partially paid');

    // pay additional amount
    $this->submitPayment(70);
    $this->checkResults(array(30, 70), 2);
  }

  /**
   * Test the submit function that completes the partially paid Contribution with multiple payments.
   */
  public function testMultiplePaymentForPartialyPaidContribution() {
    $this->createContribution('Partially paid');

    // pay additional amount
    $this->submitPayment(50);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    // pay additional amount
    $this->submitPayment(20);
    $this->checkResults(array(30, 50, 20), 3);
  }

  /**
   * Test the submit function that completes the partially paid Contribution with multiple payments.
   */
  public function testMultiplePaymentForPartiallyPaidContributionWithOneCreditCardPayment() {
    $this->createContribution('Partially paid');

    // pay additional amount
    $this->submitPayment(50);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    // pay additional amount by using credit card
    $this->submitPayment(20, 'live');
    $this->checkResults(array(30, 50, 20), 3);
  }

  /**
   * Test the submit function that completes the pending pay later Contribution using Credit Card.
   */
  public function testAddPaymentUsingCreditCardForPendingPayLaterContribution() {
    $this->createContribution('Pending');

    // pay additional amount by using Credit Card
    $this->submitPayment(100, 'live');
    $this->checkResults(array(100), 1);
  }

  /**
   * Test the submit function that completes the pending pay later Contribution.
   */
  public function testAddPaymentForPendingPayLaterContribution() {
    $this->createContribution('Pending');

    // pay additional amount
    $this->submitPayment(70);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    // pay additional amount
    $this->submitPayment(30);
    $this->checkResults(array(30, 70), 2);
  }

  /**
   * Test the Membership status after completing the pending pay later Contribution.
   */
  public function testMembershipStatusAfterCompletingPayLaterContribution() {
    $this->createContribution('Pending');
    $membership = $this->createPendingMembershipAndRecordContribution($this->_contributionId);
    // pay additional amount
    $this->submitPayment(100);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $contributionMembership = $this->callAPISuccessGetSingle('Membership', array('id' => $membership["id"]));
    $membershipStatus = $this->callAPISuccessGetSingle('MembershipStatus', array('id' => $contributionMembership["status_id"]));
    $this->assertEquals('New', $membershipStatus['name']);
  }

  private function createPendingMembershipAndRecordContribution($contributionId) {
    $this->_individualId = $this->individualCreate();
    $membershipTypeAnnualFixed = $this->callAPISuccess('membership_type', 'create', array(
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
    ));
    $membershipStatuses = CRM_Member_PseudoConstant::membershipStatus();
    $pendingStatusId = array_search('Pending', $membershipStatuses);
    $membership = $this->callAPISuccess('Membership', 'create', array(
      'contact_id' => $this->_individualId,
      'membership_type_id' => $membershipTypeAnnualFixed['id'],
    ));
    // Updating Membership status to Pending
    $membership = $this->callAPISuccess('Membership', 'create', array(
      'id' => $membership["id"],
      'status_id' => $pendingStatusId,
    ));
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'create', array(
      'membership_id' => $membership["id"],
      'contribution_id' => $contributionId,
    ));
    return $membership;
  }

  /**
   * Test the submit function that completes the pending pay later Contribution with multiple payments.
   */
  public function testMultiplePaymentForPendingPayLaterContribution() {
    $this->createContribution('Pending');

    // pay additional amount
    $this->submitPayment(40);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(20);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(30);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(10);
    $this->checkResults(array(40, 20, 30, 10), 4);
  }

  /**
   * Test the submit function that completes the pending pay later Contribution with multiple payments.
   */
  public function testMultiplePaymentForPendingPayLaterContributionWithOneCreditCardPayment() {
    $this->createContribution('Pending');

    // pay additional amount
    $this->submitPayment(50);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(20, 'live');
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(20);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    $this->submitPayment(10, 'live');
    $this->checkResults(array(50, 20, 20, 10), 4);
  }

  /**
   * Function to create pending pay later or partially paid conntribution.
   *
   * @param string $typeofContribution
   *
   */
  public function createContribution($typeofContribution = 'Pending') {
    if ($typeofContribution == 'Partially paid') {
      $contributionParams = array_merge($this->_params, array(
        'partial_payment_total' => 100.00,
        'partial_amount_to_pay' => 30,
        'contribution_status_id' => 1,
      ));
    }
    elseif ($typeofContribution == 'Pending') {
      $contributionParams = array_merge($this->_params, array(
        'contribution_status_id' => 2,
        'is_pay_later' => 1,
      ));
    }
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $contribution['id']));
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

    $submitParams = array(
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
    );
    if ($mode) {
      $submitParams += array(
        'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
        'payment_processor_id' => $this->paymentProcessorID,
        'credit_card_exp_date' => array('M' => 5, 'Y' => 2025),
        'credit_card_number' => '411111111111111',
        'cvv2' => 234,
        'credit_card_type' => 'Visa',
        'billing_city-5' => 'Vancouver',
        'billing_state_province_id-5' => 1059,
        'billing_postal_code-5' => 1321312,
        'billing_country_id-5' => 1228,
      );
    }
    else {
      $submitParams += array(
        'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
        'check_number' => 'check-12345',
      );
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
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $this->_contributionId));
    $this->assertNotEmpty($contribution);
    $this->assertEquals('Completed', $contribution['contribution_status']);

    $this->callAPISuccessGetCount('EntityFinancialTrxn', array(
      'entity_table' => "civicrm_contribution",
      'entity_id' => $this->_contributionId,
      'financial_trxn_id.is_payment' => 1,
      'financial_trxn_id.total_amount' => array('IN' => $amounts),
    ), $count);
  }

}
