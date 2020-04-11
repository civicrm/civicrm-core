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
 *  File for the MembershipTest class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 */

/**
 *  Test CRM_Member_Form_Membership functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Member_Form_MembershipTest extends CiviUnitTestCase {

  protected $_individualId;
  protected $_contribution;
  protected $_financialTypeId = 1;
  protected $_entity = 'Membership';
  protected $_params;
  protected $_ids = [];
  protected $_paymentProcessorID;

  /**
   * Membership type ID for annual fixed membership.
   *
   * @var int
   */
  protected $membershipTypeAnnualFixedID;

  /**
   * Parameters to create payment processor.
   *
   * @var array
   */
  protected $_processorParams = [];

  /**
   * ID of created membership.
   *
   * @var int
   */
  protected $_membershipID;

  /**
   * Payment instrument mapping.
   *
   * @var array
   */
  protected $paymentInstruments = [];

  /**
   * @var CiviMailUtils
   */
  protected $mut;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();

    $this->_individualId = $this->individualCreate();
    $this->_paymentProcessorID = $this->processorCreate();

    $this->loadXMLDataSet(__DIR__ . '/dataset/data.xml');

    $membershipTypeAnnualFixed = $this->callAPISuccess('membership_type', 'create', [
      'domain_id' => 1,
      'name' => 'AnnualFixed',
      'member_of_contact_id' => 23,
      'duration_unit' => 'year',
      'minimum_fee' => 50,
      'duration_interval' => 1,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '101',
      'fixed_period_rollover_day' => '1231',
      'relationship_type_id' => 20,
      'financial_type_id' => 2,
    ]);
    $this->membershipTypeAnnualFixedID = $membershipTypeAnnualFixed['id'];

    $instruments = $this->callAPISuccess('contribution', 'getoptions', ['field' => 'payment_instrument_id']);
    $this->paymentInstruments = $instruments['values'];
  }

  /**
   * Clean up after each test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(
      [
        'civicrm_relationship',
        'civicrm_membership_type',
        'civicrm_membership',
        'civicrm_uf_match',
        'civicrm_email',
      ]
    );
    foreach ([17, 18, 23, 32] as $contactID) {
      $this->callAPISuccess('contact', 'delete', ['id' => $contactID, 'skip_undelete' => TRUE]);
    }
    $this->callAPISuccess('relationship_type', 'delete', ['id' => 20]);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an empty contact_select_id value
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testFormRuleEmptyContact() {
    $params = [
      'contact_select_id' => 0,
      'membership_type_id' => [1 => NULL],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = CRM_Member_Form_Membership::formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('membership_type_id', $rc));

    $params['membership_type_id'] = [1 => 3];
    $rc = CRM_Member_Form_Membership::formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('join_date', $rc));
  }

  /**
   * Test that form rule fails if start date is before join date.
   *
   * Test CRM_Member_Form_Membership::formRule() with a parameter
   * that has an start date before the join date and a rolling
   * membership type.
   */
  public function testFormRuleRollingEarlyStart() {
    $unixNow = time();
    $unixYesterday = $unixNow - (24 * 60 * 60);
    $ymdYesterday = date('Y-m-d', $unixYesterday);
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => $ymdYesterday,
      'end_date' => '',
      'membership_type_id' => ['23', '15'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = call_user_func(['CRM_Member_Form_Membership', 'formRule'],
      $params, $files, $obj
    );
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('start_date', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an end date before the start date and a rolling
   *  membership type
   */
  public function testFormRuleRollingEarlyEnd() {
    $unixNow = time();
    $unixYesterday = $unixNow - (24 * 60 * 60);
    $ymdYesterday = date('Y-m-d', $unixYesterday);
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => date('Y-m-d'),
      'end_date' => $ymdYesterday,
      'membership_type_id' => ['23', '15'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = CRM_Member_Form_Membership::formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('end_date', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with end date but no start date and a rolling membership type.
   */
  public function testFormRuleRollingEndNoStart() {
    $unixNow = time();
    $unixYearFromNow = $unixNow + (365 * 24 * 60 * 60);
    $ymdYearFromNow = date('Y-m-d', $unixYearFromNow);
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => $ymdYearFromNow,
      'membership_type_id' => ['23', '15'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('start_date', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an end date and a lifetime membership type
   */
  public function testFormRuleRollingLifetimeEnd() {
    $unixNow = time();
    $unixYearFromNow = $unixNow + (365 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => date('Y-m-d'),
      'end_date' => date('Y-m-d',
        $unixYearFromNow
      ),
      'membership_type_id' => ['23', '25'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('status_id', $rc));
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has permanent override and no status
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testFormRulePermanentOverrideWithNoStatus() {
    $params = [
      'join_date' => date('Y-m-d'),
      'membership_type_id' => ['23', '25'],
      'is_override' => TRUE,
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('status_id', $rc));
  }

  public function testFormRuleUntilDateOverrideWithValidOverrideEndDate() {
    $params = [
      'join_date' => date('Y-m-d'),
      'membership_type_id' => ['23', '25'],
      'is_override' => TRUE,
      'status_id' => 1,
      'status_override_end_date' => date('Y-m-d'),
    ];
    $files = [];
    $membershipForm = new CRM_Member_Form_Membership();
    $validationResponse = CRM_Member_Form_Membership::formRule($params, $files, $membershipForm);
    $this->assertTrue($validationResponse);
  }

  public function testFormRuleUntilDateOverrideWithNoOverrideEndDate() {
    $params = [
      'join_date' => date('Y-m-d'),
      'membership_type_id' => ['23', '25'],
      'is_override' => CRM_Member_StatusOverrideTypes::UNTIL_DATE,
      'status_id' => 1,
    ];
    $files = [];
    $membershipForm = new CRM_Member_Form_Membership();
    $validationResponse = CRM_Member_Form_Membership::formRule($params, $files, $membershipForm);
    $this->assertType('array', $validationResponse);
    $this->assertEquals('Please enter the Membership override end date.', $validationResponse['status_override_end_date']);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one month from now and a rolling membership type
   */
  public function testFormRuleRollingJoin1MonthFromNow() {
    $unixNow = time();
    $unix1MFmNow = $unixNow + (31 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix1MFmNow),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => ['23', '15'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    // Should have found no valid membership status.
    $this->assertType('array', $rc);
    $this->assertTrue(array_key_exists('_qf_default', $rc));
  }

  /**
   * Test CRM_Member_Form_Membership::formRule() with a join date of today and a rolling membership type.
   */
  public function testFormRuleRollingJoinToday() {
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => ['23', '15'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    //  Should have found New membership status
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one month ago and a rolling membership type
   */
  public function testFormRuleRollingJoin1MonthAgo() {
    $unixNow = time();
    $unix1MAgo = $unixNow - (31 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix1MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => ['23', '15'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    // Should have found New membership status.
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date of six months ago and a rolling membership type.
   */
  public function testFormRuleRollingJoin6MonthsAgo() {
    $unixNow = time();
    $unix6MAgo = $unixNow - (180 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix6MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => ['23', '15'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    // Should have found Current membership status.
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one year+ ago and a rolling membership type
   */
  public function testFormRuleRollingJoin1YearAgo() {
    $unixNow = time();
    $unix1YAgo = $unixNow - (370 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix1YAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => ['23', '15'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    //  Should have found Grace membership status
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of two years ago and a rolling membership type
   */
  public function testFormRuleRollingJoin2YearsAgo() {
    $unixNow = time();
    $unix2YAgo = $unixNow - (2 * 365 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix2YAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => ['23', '15'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    //  Should have found Expired membership status
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of six months ago and a fixed membership type
   */
  public function testFormRuleFixedJoin6MonthsAgo() {
    $unixNow = time();
    $unix6MAgo = $unixNow - (180 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix6MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => ['23', '7'],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj->formRule($params, $files, $obj);

    //  Should have found Current membership status
    $this->assertTrue($rc);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   * @throws \CRM_Core_Exception
   */
  public function testSubmit($thousandSeparator) {
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->setCurrencySeparators($thousandSeparator);
    $form = $this->getForm();
    $form->preProcess();
    $this->mut = new CiviMailUtils($this, TRUE);
    $form->_mode = 'test';
    $this->createLoggedInUser();
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('2/d/Y'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '1',
      'source' => '',
      'total_amount' => $this->formatMoneyInput(1234.56),
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'payment_processor_id' => $this->_paymentProcessorID,
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => [
        'M' => '9',
        // TODO: Future proof
        'Y' => '2024',
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
      'send_receipt' => TRUE,
      'receipt_text' => 'Receipt text',
    ];
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->callAPISuccessGetCount('ContributionRecur', ['contact_id' => $this->_individualId], 0);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ]);

    //CRM-20264 : Check that CC type and number (last 4 digit) is stored during backoffice membership payment
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['card_type_id', 'pan_truncation'],
      ]
    );
    $this->assertEquals(1, $financialTrxn['card_type_id']);
    $this->assertEquals(1111, $financialTrxn['pan_truncation']);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);

    $this->_checkFinancialRecords([
      'id' => $contribution['id'],
      'total_amount' => 1234.56,
      'financial_account_id' => 2,
      'payment_instrument_id' => $this->callAPISuccessGetValue('PaymentProcessor', [
        'id' => $this->_paymentProcessorID,
        'return' => 'payment_instrument_id',
      ]),
    ], 'online');
    $this->mut->checkMailLog([
      CRM_Utils_Money::format('1234.56'),
      'Receipt text',
    ]);
    $this->mut->stop();
    $this->assertEquals([
      [
        'text' => 'AnnualFixed membership for Mr. Anthony Anderson II has been added. The new membership End Date is December 31st, ' . date('Y') . '. A membership confirmation and receipt has been sent to anthony_anderson@civicrm.org.',
        'title' => 'Complete',
        'type' => 'success',
        'options' => NULL,
      ],
    ], CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * Test the submit function of the membership form on membership type change.
   *  Check if the related contribuion is also updated if the minimum_fee didn't match
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionUpdateOnMembershipTypeChange() {
    // Step 1: Create a Membership via backoffice whose with 50.00 payment
    $form = $this->getForm();
    $form->preProcess();
    $this->mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $priceSet = $this->callAPISuccess('PriceSet', 'Get', ["extends" => "CiviMember"]);
    $form->set('priceSetId', $priceSet['id']);
    CRM_Price_BAO_PriceSet::buildPriceSet($form);
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
      'record_contribution' => 1,
      'total_amount' => 50,
      'receive_date' => date('Y-m-d', time()) . ' 20:36:00',
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'payment_processor_id' => $this->_paymentProcessorID,
    ];
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    // check the membership status after partial payment, if its Pending
    $this->assertEquals(array_search('New', CRM_Member_PseudoConstant::membershipStatus()), $membership['status_id']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->_individualId,
    ]);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertEquals(50.00, $contribution['total_amount']);
    $this->assertEquals(50.00, $contribution['net_amount']);

    // Step 2: Change the membership type whose minimum free is less than earlier membership
    $secondMembershipType = $this->callAPISuccess('membership_type', 'create', [
      'domain_id' => 1,
      'name' => 'Second Test Membership',
      'member_of_contact_id' => 23,
      'duration_unit' => 'month',
      'minimum_fee' => 25,
      'duration_interval' => 1,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '101',
      'fixed_period_rollover_day' => '1231',
      'relationship_type_id' => 20,
      'financial_type_id' => 2,
    ]);
    Civi::settings()->set('update_contribution_on_membership_type_change', TRUE);
    $form = $this->getForm();
    $form->preProcess();
    $form->_id = $membership['id'];
    $form->set('priceSetId', $priceSet['id']);
    CRM_Price_BAO_PriceSet::buildPriceSet($form);
    $form->_action = CRM_Core_Action::UPDATE;
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $secondMembershipType['id']],
      'record_contribution' => 1,
      'status_id' => 1,
      'total_amount' => 25,
      'receive_date' => date('Y-m-d', time()) . ' 20:36:00',
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'payment_processor_id' => $this->_paymentProcessorID,
    ];
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    // check the membership status after partial payment, if its Pending
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->_individualId,
    ]);
    $payment = CRM_Contribute_BAO_Contribution::getPaymentInfo($membership['id'], 'membership', FALSE, TRUE);
    // Check the contribution status on membership type change whose minimum fee was less than earlier memebership
    $this->assertEquals('Pending refund', $contribution['contribution_status']);
    // Earlier paid amount
    $this->assertEquals(50, $payment['paid']);
    // balance remaning
    $this->assertEquals(-25, $payment['balance']);
  }

  /**
   * Test the submit function of the membership form for partial payment.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitPartialPayment($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    // Step 1: submit a partial payment for a membership via backoffice
    $form = $this->getForm();
    $form->preProcess();
    $this->mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $priceSet = $this->callAPISuccess('PriceSet', 'Get', ["extends" => "CiviMember"]);
    $form->set('priceSetId', $priceSet['id']);

    CRM_Price_BAO_PriceSet::buildPriceSet($form);
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
      'receive_date' => date('Y-m-d', time()) . ' 20:36:00',
      'record_contribution' => 1,
      'total_amount' => $this->formatMoneyInput(50),
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments, TRUE),
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'payment_processor_id' => $this->_paymentProcessorID,
    ];
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    // check the membership status after partial payment, if its Pending
    $this->assertEquals(array_search('Pending', CRM_Member_PseudoConstant::membershipStatus(), TRUE), $membership['status_id']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $this->_individualId]);
    $this->callAPISuccess('Payment', 'create', ['contribution_id' => $contribution['id'], 'total_amount' => 25, 'payment_instrument_id' => 'Cash']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    // Step 2: submit the other half of the partial payment
    //  via AdditionalPayment form to complete the related contribution
    $form = new CRM_Contribute_Form_AdditionalPayment();
    $submitParams = [
      'contribution_id' => $contribution['contribution_id'],
      'contact_id' => $this->_individualId,
      'total_amount' => $this->formatMoneyInput(25),
      'currency' => 'USD',
      'financial_type_id' => 2,
      'receive_date' => '2015-04-21 23:27:00',
      'trxn_date' => '2017-04-11 13:05:11',
      'payment_processor_id' => 0,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments, TRUE),
      'check_number' => 'check-12345',
    ];
    $form->cid = $this->_individualId;
    $form->testSubmit($submitParams);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    // check the membership status after additional payment, if its changed to 'New'
    $this->assertEquals(array_search('New', CRM_Member_PseudoConstant::membershipStatus(), TRUE), $membership['status_id']);

    // check the contribution status and net amount after additional payment
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->_individualId,
    ]);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->validateAllPayments();
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitRecur() {
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $pendingVal = $this->callAPISuccessGetValue('OptionValue', [
      'return' => 'id',
      'option_group_id' => 'contribution_status',
      'label' => 'Pending Label**',
    ]);
    //Update label for Pending contribution status.
    $this->callAPISuccess('OptionValue', 'create', [
      'id' => $pendingVal,
      'label' => 'PendingEdited',
    ]);

    $form = $this->getForm();

    $this->callAPISuccess('MembershipType', 'create', [
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ]);
    $form->preProcess();
    $this->createLoggedInUser();
    $params = $this->getBaseSubmitParams();
    $form->_mode = 'test';
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->callAPISuccessGetCount('ContributionRecur', ['contact_id' => $this->_individualId], 1);

    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ]);

    //Check if Membership Payment is recorded.
    $this->callAPISuccessGetCount('MembershipPayment', [
      'membership_id' => $membership['id'],
      'contribution_id' => $contribution['id'],
    ], 1);

    // CRM-16992.
    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
    $this->assertEquals([
      [
        'text' => 'AnnualFixed membership for Mr. Anthony Anderson II has been added. The new membership End Date is ' . date('F jS, Y', strtotime('last day of this month')) . ' 12:00 AM.',
        'title' => 'Complete',
        'type' => 'success',
        'options' => NULL,
      ],
    ], CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * CRM-20946: Test the financial entires especially the reversed amount,
   *  after related Contribution is cancelled
   *
   * @throws \CRM_Core_Exception
   */
  public function testFinancialEntiriesOnCancelledContribution() {
    // Create two memberships for individual $this->_individualId, via a price set in the back end.
    $this->createTwoMembershipsViaPriceSetInBackEnd($this->_individualId);

    // cancel the related contribution via API
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 2,
    ]);
    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Cancelled'),
    ]);

    // fetch financial_trxn ID of the related contribution
    $sql = "SELECT financial_trxn_id
     FROM civicrm_entity_financial_trxn
     WHERE entity_id = %1 AND entity_table = 'civicrm_contribution'
     ORDER BY id DESC
     LIMIT 1
    ";
    $financialTrxnID = CRM_Core_DAO::singleValueQuery($sql, [1 => [$contribution['id'], 'Int']]);

    // fetch entity_financial_trxn records and compare their cancelled records
    $result = $this->callAPISuccess('EntityFinancialTrxn', 'Get', [
      'financial_trxn_id' => $financialTrxnID,
      'entity_table' => 'civicrm_financial_item',
    ]);
    // compare the reversed amounts of respective memberships after cancelling contribution
    $cancelledMembershipAmounts = [
      -20.00,
      -10.00,
    ];
    $count = 0;
    foreach ($result['values'] as $record) {
      $this->assertEquals($cancelledMembershipAmounts[$count], $record['amount']);
      $count++;
    }
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPayLaterWithBilling() {
    $form = $this->getForm();
    $form->preProcess();
    $this->createLoggedInUser();
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '2',
      'source' => '',
      'total_amount' => '50.00',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'payment_instrument_id' => 4,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'record_contribution' => TRUE,
      'trxn_id' => 777,
      'contribution_status_id' => 2,
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    ];
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 2,
    ]);
    $this->assertEquals($contribution['trxn_id'], 777);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
    $this->callAPISuccessGetSingle('address', [
      'contact_id' => $this->_individualId,
      'street_address' => '10 Test St',
      'postal_code' => 90210,
    ]);
  }

  /**
   * Test if membership is updated to New after contribution
   * is updated from Partially paid to Completed.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitUpdateMembershipFromPartiallyPaid() {
    $memStatus = CRM_Member_BAO_Membership::buildOptions('status_id', 'validate');

    //Perform a pay later membership contribution.
    $this->testSubmitPayLaterWithBilling();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->assertEquals($membership['status_id'], array_search('Pending', $memStatus));
    $contribution = $this->callAPISuccessGetSingle('MembershipPayment', [
      'membership_id' => $membership['id'],
    ]);
    $prevContribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contribution['contribution_id'],
      'payment_instrument_id' => 'Cash',
      'total_amount' => 5,
    ]);

    // Complete the contribution from offline form.
    $form = new CRM_Contribute_Form_Contribution();
    $submitParams = [
      'id' => $contribution['contribution_id'],
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'price_set_id' => 0,
    ];
    $fields = ['total_amount', 'net_amount', 'financial_type_id', 'receive_date', 'contact_id', 'payment_instrument_id'];
    foreach ($fields as $val) {
      $submitParams[$val] = $prevContribution[$val];
    }
    $form->testSubmit($submitParams, CRM_Core_Action::UPDATE);

    //Check if Membership is updated to New.
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->assertEquals($membership['status_id'], array_search('New', $memStatus));
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testSubmitRecurCompleteInstant() {
    $form = $this->getForm();
    $mut = new CiviMailUtils($this, TRUE);
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessorID);
    $processor->setDoDirectPaymentResult([
      'payment_status_id' => 1,
      'trxn_id' => 'kettles boil water',
      'fee_amount' => .14,
    ]);
    $processorDetail = $processor->getPaymentProcessor();
    $this->callAPISuccess('MembershipType', 'create', [
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ]);
    $form->preProcess();
    $this->createLoggedInUser();
    $params = $this->getBaseSubmitParams();
    $form->_mode = 'test';
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->callAPISuccessGetCount('ContributionRecur', ['contact_id' => $this->_individualId], 1);

    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ]);

    $this->assertEquals(.14, $contribution['fee_amount']);
    $this->assertEquals('kettles boil water', $contribution['trxn_id']);
    $this->assertEquals($processorDetail['payment_instrument_id'], $contribution['payment_instrument_id']);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
    $mut->checkMailLog([
      '===========================================================
Billing Name and Address
===========================================================
Test
10 Test St
Test, AR 90210
US',
      '===========================================================
Membership Information
===========================================================
Membership Type: AnnualFixed
Membership Start Date: ',
      '===========================================================
Credit Card Information
===========================================================
Visa
************1111
Expires: ',
    ]);
    $mut->stop();

  }

  /**
   * Test membership form with Failed Contribution.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFormWithFailedContribution() {
    $form = $this->getForm();
    $form->preProcess();
    $this->createLoggedInUser();
    $params = $this->getBaseSubmitParams();
    unset($params['price_set_id']);
    unset($params['credit_card_number']);
    unset($params['cvv2']);
    unset($params['credit_card_exp_date']);
    unset($params['credit_card_type']);
    unset($params['send_receipt']);
    unset($params['is_recur']);

    $params['record_contribution'] = TRUE;
    $params['contribution_status_id'] = array_search('Failed', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'));
    $form->_mode = NULL;
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->assertEquals($membership['status_id'], array_search('Pending', CRM_Member_PseudoConstant::membershipStatus(), TRUE));
  }

  /**
   * CRM-20955, CRM-20966:
   * Test creating two memberships with inheritance via price set in the back end,
   * checking that the correct primary & secondary memberships, contributions, line items
   * & membership_payment records are created.
   * Uses some data from tests/phpunit/CRM/Member/Form/dataset/data.xml .
   *
   * @throws \CRM_Core_Exception
   */
  public function testTwoInheritedMembershipsViaPriceSetInBackend() {
    // Create an organization and give it a "Member of" relationship to $this->_individualId.
    $orgID = $this->organizationCreate();
    $relationship = $this->callAPISuccess('relationship', 'create', [
      'contact_id_a' => $this->_individualId,
      'contact_id_b' => $orgID,
      'relationship_type_id' => 20,
      'is_active' => 1,
    ]);

    // Create two memberships for the organization, via a price set in the back end.
    $this->createTwoMembershipsViaPriceSetInBackEnd($orgID);

    // Check the primary memberships on the organization.
    $orgMembershipResult = $this->callAPISuccess('membership', 'get', [
      'contact_id' => $orgID,
    ]);
    $this->assertEquals(2, $orgMembershipResult['count'], "2 primary memberships should have been created on the organization.");
    $primaryMembershipIds = [];
    foreach ($orgMembershipResult['values'] as $membership) {
      $primaryMembershipIds[] = $membership['id'];
      $this->assertTrue(empty($membership['owner_membership_id']), "Membership on the organization has owner_membership_id so is inherited.");
    }

    // CRM-20955: check that correct inherited memberships were created for the individual,
    // for both of the primary memberships.
    $individualMembershipResult = $this->callAPISuccess('membership', 'get', [
      'contact_id' => $this->_individualId,
    ]);
    $this->assertEquals(2, $individualMembershipResult['count'], "2 inherited memberships should have been created on the individual.");
    foreach ($individualMembershipResult['values'] as $membership) {
      $this->assertNotEmpty($membership['owner_membership_id'], "Membership on the individual lacks owner_membership_id so is not inherited.");
      $this->assertNotContains($membership['id'], $primaryMembershipIds, "Inherited membership id should not be the id of a primary membership.");
      $this->assertContains($membership['owner_membership_id'], $primaryMembershipIds, "Inherited membership owner_membership_id should be the id of a primary membership.");
    }

    // CRM-20966: check that the correct membership contribution, line items
    // & membership_payment records were created for the organization.
    $contributionResult = $this->callAPISuccess('contribution', 'get', [
      'contact_id' => $orgID,
      'sequential' => 1,
      'api.line_item.get' => [],
      'api.membership_payment.get' => [],
    ]);
    $this->assertEquals(1, $contributionResult['count'], "One contribution should have been created for the organization's memberships.");

    $this->assertEquals(2, $contributionResult['values'][0]['api.line_item.get']['count'], "2 line items should have been created for the organization's memberships.");
    foreach ($contributionResult['values'][0]['api.line_item.get']['values'] as $lineItem) {
      $this->assertEquals('civicrm_membership', $lineItem['entity_table'], "Membership line item's entity_table should be 'civicrm_membership'.");
      $this->assertContains($lineItem['entity_id'], $primaryMembershipIds, "Membership line item's entity_id should be the id of a primary membership.");
    }

    $this->assertEquals(2, $contributionResult['values'][0]['api.membership_payment.get']['count'], "2 membership payment records should have been created for the organization's memberships.");
    foreach ($contributionResult['values'][0]['api.membership_payment.get']['values'] as $membershipPayment) {
      $this->assertEquals($contributionResult['values'][0]['id'], $membershipPayment['contribution_id'], "membership payment's contribution ID should be the ID of the organization's membership contribution.");
      $this->assertContains($membershipPayment['membership_id'], $primaryMembershipIds, "membership payment's membership ID should be the ID of a primary membership.");
    }

    // CRM-20966: check that deleting relationship used for inheritance does not delete contribution.
    $this->callAPISuccess('relationship', 'delete', [
      'id' => $relationship['id'],
    ]);

    $contributionResultAfterRelationshipDelete = $this->callAPISuccess('contribution', 'get', [
      'id' => $contributionResult['values'][0]['id'],
      'contact_id' => $orgID,
    ]);
    $this->assertEquals(1, $contributionResultAfterRelationshipDelete['count'], "Contribution has been wrongly deleted.");
  }

  /**
   * dev/core/issues/860:
   * Test creating two memberships via price set in the back end with a discount,
   * checking that the line items have correct amounts.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTwoMembershipsViaPriceSetInBackendWithDiscount() {
    // Register buildAmount hook to apply discount.
    $this->hookClass->setHook('civicrm_buildAmount', [$this, 'buildAmountMembershipDiscount']);

    // Create two memberships for individual $this->_individualId, via a price set in the back end.
    $this->createTwoMembershipsViaPriceSetInBackEnd($this->_individualId);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->_individualId,
    ]);
    // Note: we can't check for the contribution total being discounted, because the total is set
    // when the contribution is created via $form->testSubmit(), but buildAmount isn't called
    // until testSubmit() runs. Fixing that might involve making testSubmit() more sophisticated,
    // or just hacking total_amount for this case.

    $lineItemResult = $this->callAPISuccess('LineItem', 'get', [
      'contribution_id' => $contribution['id'],
    ]);
    $this->assertEquals(2, $lineItemResult['count']);
    $discountedItems = 0;
    foreach ($lineItemResult['values'] as $lineItem) {
      if (CRM_Utils_String::startsWith($lineItem['label'], 'Long Haired Goat')) {
        $this->assertEquals(15.0, $lineItem['line_total']);
        $this->assertEquals('Long Haired Goat - one leg free!', $lineItem['label']);
        $discountedItems++;
      }
    }
    $this->assertEquals(1, $discountedItems);
  }

  /**
   * Implements hook_civicrm_buildAmount() for testTwoMembershipsViaPriceSetInBackendWithDiscount().
   */
  public function buildAmountMembershipDiscount($pageType, &$form, &$amount) {
    foreach ($amount as $id => $priceField) {
      if (is_array($priceField['options'])) {
        foreach ($priceField['options'] as $optionId => $option) {
          if ($option['membership_type_id'] == 15) {
            // Long Haired Goat membership discount.
            $amount[$id]['options'][$optionId]['amount'] = $option['amount'] * 0.75;
            $amount[$id]['options'][$optionId]['label'] = $option['label'] . ' - one leg free!';
          }
        }
      }
    }
  }

  /**
   * Get a membership form object.
   *
   * We need to instantiate the form to run preprocess, which means we have to trick it about the request method.
   *
   * @return \CRM_Member_Form_Membership
   * @throws \CRM_Core_Exception
   */
  protected function getForm() {
    if (isset($_REQUEST['cid'])) {
      unset($_REQUEST['cid']);
    }
    $form = new CRM_Member_Form_Membership();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Core_Controller();
    return $form;
  }

  /**
   * @return array
   */
  protected function getBaseSubmitParams() {
    $params = [
      'cid' => $this->_individualId,
      'price_set_id' => 0,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      'campaign_id' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
      'auto_renew' => '1',
      'is_recur' => 1,
      'max_related' => 0,
      'num_terms' => '1',
      'source' => '',
      'total_amount' => '77.00',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => 11,
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => [
        'M' => '9',
        'Y' => date('Y') + 1,
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
      'send_receipt' => 1,
    ];
    return $params;
  }

  /**
   * Scenario builder:
   * create two memberships for the same individual, via a price set in the back end.
   *
   * @param int $contactId Id of contact on which the memberships will be created.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createTwoMembershipsViaPriceSetInBackEnd($contactId) {
    $form = $this->getForm();
    $form->preProcess();
    $this->createLoggedInUser();

    // create a price-set of price-field of type checkbox and each price-option corresponds to a membership type
    $priceSet = $this->callAPISuccess('price_set', 'create', [
      'is_quick_config' => 0,
      'extends' => 'CiviMember',
      'financial_type_id' => 1,
      'title' => 'my Page',
    ]);
    $priceSetID = $priceSet['id'];
    // create respective checkbox price-field
    $priceField = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => $priceSetID,
      'label' => 'Memberships',
      'html_type' => 'Checkbox',
    ]);
    $priceFieldID = $priceField['id'];
    // create two price options, each represent a membership type of amount 20 and 10 respectively
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Long Haired Goat',
      'amount' => 20,
      'financial_type_id' => 'Donation',
      'membership_type_id' => 15,
      'membership_num_terms' => 1,
    ]);
    $pfvIDs = [$priceFieldValue['id'] => 1];
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Shoe-eating Goat',
      'amount' => 10,
      'financial_type_id' => 'Donation',
      'membership_type_id' => 35,
      'membership_num_terms' => 2,
    ]);
    $pfvIDs[$priceFieldValue['id']] = 1;

    // register for both of these memberships via backoffice membership form submission
    $params = [
      'cid' => $contactId,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      "price_$priceFieldID" => $pfvIDs,
      "price_set_id" => $priceSetID,
      'membership_type_id' => [1 => 0],
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '2',
      'source' => '',
      'total_amount' => '30.00',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'payment_instrument_id' => 4,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'record_contribution' => TRUE,
      'trxn_id' => 777,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Pending'),
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    ];
    $form->testSubmit($params);
  }

  /**
   * Test membership status overrides when contribution is cancelled.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testContributionFormStatusUpdate() {

    $this->_individualId = $this->createLoggedInUser();
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $this->_individualId,
      'membership_type_id' => $this->membershipTypeAnnualFixedID,
      'status_id' => 'New',
    ]);

    $params = [
      'total_amount' => 50,
      'financial_type_id' => 2,
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled'),
    ];

    $contriParams = [
      'membership_id' => $membershipId,
      'total_amount' => 50,
      'financial_type_id' => 2,
      'contact_id' => $this->_individualId,
    ];
    $contribution = CRM_Member_BAO_Membership::recordMembershipContribution($contriParams);

    //Update Contribution to Cancelled.
    $form = new CRM_Contribute_Form_Contribution();
    $form->_id = $params['id'] = $contribution->id;
    $form->_mode = NULL;
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params, CRM_Core_Action::UPDATE);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);

    //Assert membership status overrides when the contribution cancelled.
    $this->assertEquals($membership['is_override'], TRUE);
    $this->assertEquals($membership['status_id'], $this->callAPISuccessGetValue('MembershipStatus', [
      'return' => 'id',
      'name' => 'Cancelled',
    ]));
  }

  /**
   * CRM-21656: Test the submit function of the membership form if Sales Tax is enabled.
   * This test simulates what happens when one hits Edit on a Contribution that has both LineItems and Sales Tax components
   * Without making any Edits -> check that the LineItem data remain the same
   * In addition (a data-integrity check) -> check that the LineItem data add up to the data at the Contribution level
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testLineItemAmountOnSalesTax() {
    $this->enableTaxAndInvoicing();
    $this->relationForFinancialTypeWithFinancialAccount(2);
    $form = $this->getForm();
    $form->preProcess();
    $this->mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $priceSet = $this->callAPISuccess('PriceSet', 'Get', ['extends' => 'CiviMember']);
    $form->set('priceSetId', $priceSet['id']);
    // we are simulating the creation of a Price Set in Administer -> CiviContribute -> Manage Price Sets so set is_quick_config = 0
    $this->callAPISuccess('PriceSet', 'Create', ["id" => $priceSet['id'], 'is_quick_config' => 0]);
    // clean the price options static variable to repopulate the options, in order to fetch tax information
    \Civi::$statics['CRM_Price_BAO_PriceField']['priceOptions'] = NULL;
    CRM_Price_BAO_PriceSet::buildPriceSet($form);
    // rebuild the price set form variable to include the tax information against each price options
    $form->_priceSet = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSet['id']));
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
      'record_contribution' => 1,
      'total_amount' => 55,
      'receive_date' => date('Y-m-d') . ' 20:36:00',
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments, TRUE),
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      //Member dues, see data.xml
      'financial_type_id' => 2,
      'payment_processor_id' => $this->_paymentProcessorID,
    ];
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);

    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $lineItem = $this->callAPISuccessGetSingle('LineItem', ['entity_id' => $membership['id'], 'entity_table' => 'civicrm_membership']);
    $this->assertEquals(1, $lineItem['qty']);
    $this->assertEquals(50.00, $lineItem['unit_price']);
    $this->assertEquals(50.00, $lineItem['line_total']);
    $this->assertEquals(5.00, $lineItem['tax_amount']);

    // Simply save the 'Edit Contribution' form
    $form = new CRM_Contribute_Form_Contribution();
    $form->_context = 'membership';
    $form->_values = $this->callAPISuccessGetSingle('Contribution', ['id' => $lineItem['contribution_id'], 'return' => ['total_amount', 'net_amount', 'fee_amount', 'tax_amount']]);
    $form->testSubmit([
      'contact_id' => $this->_individualId,
      'id' => $lineItem['contribution_id'],
      'financial_type_id' => 2,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ],
    CRM_Core_Action::UPDATE);

    // ensure that the LineItem data remain the same
    $lineItem = $this->callAPISuccessGetSingle('LineItem', ['entity_id' => $membership['id'], 'entity_table' => 'civicrm_membership']);
    $this->assertEquals(1, $lineItem['qty']);
    $this->assertEquals(50.00, $lineItem['unit_price']);
    $this->assertEquals(50.00, $lineItem['line_total']);
    $this->assertEquals(5.00, $lineItem['tax_amount']);

    // ensure that the LineItem data add up to the data at the Contribution level
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      [
        'contribution_id' => 1,
        'return' => ['tax_amount', 'total_amount'],
      ]
    );
    $this->assertEquals($contribution['total_amount'], $lineItem['line_total'] + $lineItem['tax_amount']);
    $this->assertEquals($contribution['tax_amount'], $lineItem['tax_amount']);

    $financialItems = $this->callAPISuccess('FinancialItem', 'get', []);
    $financialItems_sum = 0;
    foreach ($financialItems['values'] as $financialItem) {
      $financialItems_sum += $financialItem['amount'];
    }
    $this->assertEquals($contribution['total_amount'], $financialItems_sum);
  }

  /**
   * Test that membership end_date is correct for multiple terms for pending contribution
   *
   * @throws CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function testCreatePendingWithMultipleTerms() {
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $form = $this->getForm();
    $form->preProcess();
    $this->mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $membershipTypeAnnualRolling = $this->callAPISuccess('membership_type', 'create', [
      'domain_id' => 1,
      'name' => 'AnnualRollingNew',
      'member_of_contact_id' => 23,
      'duration_unit' => "year",
      'minimum_fee' => 50,
      'duration_interval' => 1,
      'period_type' => "rolling",
      'relationship_type_id' => 20,
      'relationship_direction' => 'b_a',
      'financial_type_id' => 2,
    ]);
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      'membership_type_id' => [23, $membershipTypeAnnualRolling['id']],
      'max_related' => '',
      'num_terms' => '3',
      'record_contribution' => 1,
      'source' => '',
      'total_amount' => $this->formatMoneyInput(150.00),
      'financial_type_id' => '2',
      'soft_credit_type_id' => '11',
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text' => '',
    ];
    $form->_contactID = $this->_individualId;
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contact_id' => $this->_individualId,
    ]);
    $endDate = (new DateTime(date('Y-m-d')))->modify('+3 years')->modify('-1 day');
    $endDate = $endDate->format("Y-m-d");

    $this->assertEquals($endDate, $membership['end_date'], 'Membership end date should be ' . $endDate);
    $this->assertEquals(1, count($contribution['values']), 'Pending contribution should be created.');
    $contribution = $contribution['values'][$contribution['id']];
    $additionalPaymentForm = new CRM_Contribute_Form_AdditionalPayment();
    $additionalPaymentForm->testSubmit([
      'total_amount' => 150.00,
      'trxn_date' => date("Y-m-d H:i:s"),
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'check_number' => 'check-12345',
      'trxn_id' => '',
      'currency' => 'USD',
      'fee_amount' => '',
      'financial_type_id' => 1,
      'net_amount' => '',
      'payment_processor_id' => 0,
      'contact_id' => $this->_individualId,
      'contribution_id' => $contribution['id'],
    ]);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contact_id' => $this->_individualId,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ]);
    $this->assertEquals($endDate, $membership['end_date'], 'Membership end date should be same (' . $endDate . ') after payment');
    $this->assertCount(1, $contribution['values'], 'Completed contribution should be fetched.');
  }

}
