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

use Civi\Api4\Contribution;

/**
 *  Test APIv3 civicrm_contribute_recur* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class api_v3_ContributionPageTest extends CiviUnitTestCase {
  use CRMTraits_Financial_PriceSetTrait;

  protected $testAmount = 34567;
  protected $params;
  protected $id = 0;
  protected $contactIds = [];
  protected $_entity = 'ContributionPage';
  protected $contribution_result;
  protected $_priceSetParams = [];
  /**
   * Payment processor details.
   * @var array
   */
  protected $_paymentProcessor = [];

  /**
   * @var array
   *   - contribution_page
   *   - price_set
   *   - price_field
   *   - price_field_value
   */
  protected $_ids = [];

  /**
   * Should financials be checked after the test but before tear down.
   *
   * @var bool
   */
  protected $isValidateFinancialsOnPostAssert = TRUE;

  /**
   * Setup for test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->contactIds[] = $this->individualCreate();
    $this->params = [
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => $this->testAmount,
      'is_pay_later' => 1,
      'pay_later_text' => 'Send check',
      'is_monetary' => TRUE,
      'is_email_receipt' => TRUE,
      'receipt_from_email' => 'yourconscience@donate.com',
      'receipt_from_name' => 'Ego Freud',
    ];

    $this->_priceSetParams = [
      'is_quick_config' => 1,
      'extends' => 'CiviContribute',
      'financial_type_id' => 'Donation',
      'title' => 'my Page',
    ];
  }

  /**
   * Tear down after test.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function tearDown(): void {
    foreach ($this->contactIds as $id) {
      $this->callAPISuccess('contact', 'delete', ['id' => $id]);
    }
    $this->quickCleanup(['civicrm_system_log']);
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test creating a contribution page.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionPage(int $version): void {
    $this->basicCreateTest($version);
  }

  /**
   * Test getting a contribution page.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetBasicContributionPage(int $version): void {
    $this->_apiversion = $version;
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->id = $createResult['id'];
    $getParams = [
      'currency' => 'NZD',
      'financial_type_id' => 1,
    ];
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  /**
   * Test get with amount as a parameter.
   */
  public function testGetContributionPageByAmount(): void {
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->id = $createResult['id'];
    $getParams = [
      // 3456
      'amount' => '' . $this->testAmount,
      'currency' => 'NZD',
      'financial_type_id' => 1,
    ];
    $getResult = $this->callAPISuccess($this->_entity, 'get', $getParams);
    $this->assertEquals(1, $getResult['count']);
  }

  /**
   * Test page deletion.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testDeleteContributionPage(int $version): void {
    $this->basicDeleteTest($version);
  }

  /**
   * Test getfields function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetFieldsContributionPage(): void {
    $result = $this->callAPISuccess($this->_entity, 'getfields', ['action' => 'create']);
    $this->assertEquals(12, $result['values']['start_date']['type']);
  }

  /**
   * Test form submission with basic price set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmit(): void {
    $this->setUpContributionPage();
    $submitParams = $this->getBasicSubmitParams();

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', ['contribution_page_id' => $this->_ids['contribution_page'], 'return' => ['non_deductible_amount']]);
    //assert non-deductible amount
    $this->assertEquals(5.00, $contribution['non_deductible_amount']);
  }

  /**
   * Test form submission with basic price set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitZeroDollar(): void {
    $this->setUpContributionPage();
    $priceFieldID = reset($this->_ids['price_field']);
    $submitParams = [
      'price_' . $priceFieldID => $this->_ids['price_field_value']['cheapskate'],
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 0,
      'priceSetId' => $this->_ids['price_set'][0],
      'payment_processor_id' => '',
    ];

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'return' => ['non_deductible_amount', 'total_amount'],
    ]);

    $this->assertEquals($this->formatMoneyInput(0), $contribution['non_deductible_amount']);
    $this->assertEquals($this->formatMoneyInput(0), $contribution['total_amount']);
  }

  /**
   * Test form submission with billing first & last name where the contact does NOT
   * otherwise have one.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitNewBillingNameData(): void {
    $this->setUpContributionPage();
    $contact = $this->callAPISuccess('Contact', 'create', ['contact_type' => 'Individual', 'email' => 'wonderwoman@amazon.com']);
    $contact = $this->submitPageWithBilling($contact);
    $this->assertEquals([
      'first_name' => 'Wonder',
      'last_name' => 'Woman',
      'display_name' => 'Wonder Woman',
      'sort_name' => 'Woman, Wonder',
      'id' => $contact['id'],
      'contact_id' => $contact['id'],
    ], $contact['values'][$contact['id']]);

  }

  /**
   * Test form submission with billing first & last name where the contact does
   * otherwise have one and should not be overwritten.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitNewBillingNameDoNotOverwrite(): void {
    $this->setUpContributionPage();
    $contact = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'email' => 'wonderwoman@amazon.com',
      'first_name' => 'Super',
      'last_name' => 'Boy',
    ]);
    $contact = $this->submitPageWithBilling($contact);

    $this->assertEquals([
      'first_name' => 'Super',
      'last_name' => 'Boy',
      'display_name' => 'Super Boy',
      'sort_name' => 'Boy, Super',
      'id' => $contact['id'],
      'contact_id' => $contact['id'],
    ], $contact['values'][$contact['id']]);

  }

  /**
   * Test process with instant payment when more than one configured for the page.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-16923
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testSubmitRecurMultiProcessorInstantPayment(): void {
    $this->setUpContributionPage();
    $this->setupPaymentProcessor();
    $paymentProcessor2ID = $this->paymentProcessorCreate([
      'payment_processor_type_id' => 'Dummy',
      'name' => 'processor 2',
      'class_name' => 'Payment_Dummy',
      'billing_mode' => 1,
    ]);
    $dummyPP = Civi\Payment\System::singleton()->getById($paymentProcessor2ID);
    $dummyPP->setDoDirectPaymentResult([
      'payment_status_id' => 1,
      'trxn_id' => 'create_first_success',
      'fee_amount' => .85,
    ]);
    $processor = $dummyPP->getPaymentProcessor();
    $this->callAPISuccess('ContributionPage', 'create', [
      'id' => $this->_ids['contribution_page'],
      'payment_processor' => [$paymentProcessor2ID, $this->_ids['payment_processor']],
    ]);

    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    $submitParams = [
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessor2ID,
    ];

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
      'return' => ['trxn_id', 'total_amount', 'fee_amount', 'net_amount'],
    ]);
    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $this->assertEquals(10, $contribution['total_amount']);
    $this->assertEquals(.85, $contribution['fee_amount']);
    $this->assertEquals(9.15, $contribution['net_amount']);
    $this->_checkFinancialRecords([
      'id' => $contribution['id'],
      'total_amount' => $contribution['total_amount'],
      'payment_instrument_id' => $processor['payment_instrument_id'],
    ], 'online');
  }

  /**
   * Test submit with a membership block in place.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockNotSeparatePayment(): void {
    $this->setUpMembershipContributionPage(FALSE, FALSE, ['minimum_fee' => 0]);
    $submitParams = [
      $this->getPriceFieldLabel('membership') => $this->getPriceFieldValue('general'),
      'id' => (int) $this->_ids['contribution_page'],
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
    ];

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', ['contribution_page_id' => $this->_ids['contribution_page']]);
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'getsingle', ['contribution_id' => $contribution['id']]);
    $this->callAPISuccessGetSingle('LineItem', ['contribution_id' => $contribution['id'], 'entity_id' => $membershipPayment['id']]);
  }

  /**
   * Test submit with a membership block in place works with renewal.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockNotSeparatePaymentProcessorInstantRenew(): void {
    $this->setUpMembershipContributionPage();
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1]);
    $submitParams = $this->getSubmitParamsContributionPlusMembership(TRUE);
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['contribution_page_id' => $this->_ids['contribution_page']]);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', ['contribution_id' => $contribution['id'], 'return' => 'membership_id']);
    $this->callAPISuccessGetCount('LineItem', [
      'entity_table' => 'civicrm_membership',
      'entity_id' => $membershipPayment['id'],
    ], 1);

    $submitParams['contact_id'] = $contribution['contact_id'];

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $this->callAPISuccessGetCount('LineItem', [
      'entity_table' => 'civicrm_membership',
      'entity_id' => $membershipPayment['id'],
    ], 2);
    $membership = $this->callAPISuccessGetSingle('Membership', [
      'id' => $membershipPayment['membership_id'],
      'return' => ['end_date', 'join_date', 'start_date'],
    ]);
    $this->assertEquals(date('Y-m-d'), $membership['start_date']);
    $this->assertEquals(date('Y-m-d'), $membership['join_date']);
    $this->assertEquals(date('Y-m-d', strtotime('+ 2 year - 1 day')), $membership['end_date']);
  }

  /**
   * Test submit with a membership block in place.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockNotSeparatePaymentWithEmail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage();
    $this->addProfile('supporter_profile', $this->_ids['contribution_page']);

    $submitParams = [
      $this->getPriceFieldLabel('contribution') => 1,
      $this->getPriceFieldLabel('membership') => $this->getPriceFieldValue('general'),
      'id' => (int) $this->_ids['contribution_page'],
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'selectMembership' => $this->ids['MembershipType'][0],
      'email-Primary' => 'billy-goat@the-bridge.net',
      'payment_processor_id' => $this->_paymentProcessor['id'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
    ];

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['contribution_page_id' => $this->_ids['contribution_page']]);
    $this->callAPISuccess('membership_payment', 'getsingle', ['contribution_id' => $contribution['id']]);
    $mut->checkMailLog([
      'Membership Type: General',
      'Test Frontend title',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a membership block in place.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitMembershipBlockNotSeparatePaymentZeroDollarsWithEmail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage(FALSE, FALSE, ['minimum_fee' => 0]);
    $this->addProfile('supporter_profile', $this->_ids['contribution_page']);

    $submitParams = [
      $this->getPriceFieldLabel('membership') => $this->getPriceFieldValue('general'),
      'id' => (int) $this->_ids['contribution_page'],
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruffier',
      'email-Primary' => 'billy-goat@the-new-bridge.net',
      'payment_processor_id' => $this->params['payment_processor_id'],
    ];

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['contribution_page_id' => $this->_ids['contribution_page']]);
    $this->callAPISuccess('membership_payment', 'getsingle', ['contribution_id' => $contribution['id']]);
    //Assert only one mail is being sent.
    $msgs = $mut->getAllMessages();
    $this->assertCount(1, $msgs);

    $mut->checkMailLog([
      'Membership Type: General',
      'Gruffier',
    ], [
      'Amount',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a pay later and check line item in mails.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockIsSeparatePaymentPayLaterWithEmail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage(TRUE);
    $submitParams = [
      $this->getPriceFieldLabel('contribution') => 1,
      $this->getPriceFieldLabel('membership') => $this->getPriceFieldValue('general'),
      'id' => (int) $this->_ids['contribution_page'],
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'is_pay_later' => 1,
      'email-Primary' => 'billy-goat@the-bridge.net',
    ];

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $this->_ids['contribution_page']])['values'];
    $this->assertCount(2, $contributions);
    $this->callAPISuccess('membership_payment', 'getsingle', ['contribution_id' => ['IN' => array_keys($contributions)]]);
    $mut->checkMailLog([
      'Membership Amount -...             $ 2.00',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a membership block in place.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockIsSeparatePayment(): void {
    $this->setUpMembershipContributionPage(TRUE);
    $submitParams = $this->getSubmitParamsContributionPlusMembership();
    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $this->validateSeparateMembershipPaymentContributions($this->_ids['contribution_page']);
  }

  /**
   * Test submit with a membership block in place.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockIsSeparatePaymentWithPayLater(): void {
    $this->setUpMembershipContributionPage(TRUE);
    $this->ids['MembershipType'] = [$this->membershipTypeCreate(['minimum_fee' => 2])];
    //Pay later
    $submitParams = [
      $this->getPriceFieldLabel('contribution') => 1,
      $this->getPriceFieldLabel('membership') => $this->getPriceFieldValue('general'),
      'id' => (int) $this->_ids['contribution_page'],
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'is_pay_later' => 1,
    ];

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $this->_ids['contribution_page'], 'return' => 'contribution_status_id']);
    $this->assertCount(2, $contributions['values']);
    foreach ($contributions['values'] as $val) {
      $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'), $val['contribution_status_id']);
    }

    //Membership should be in Pending state.
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'getsingle', ['return' => ['membership_id', 'contribution_id']]);
    $this->assertArrayHasKey($membershipPayment['contribution_id'], $contributions['values']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id'], 'return' => ['status_id', 'contact_id']]);
    $pendingStatus = $this->callAPISuccessGetSingle('MembershipStatus', ['return' => ['id'], 'name' => 'Pending']);
    $this->assertEquals($membership['status_id'], $pendingStatus['id']);
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
  }

  /**
   * Test submit with a membership block in place.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockIsSeparatePaymentWithEmail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage(TRUE);
    $this->addProfile('supporter_profile', $this->_ids['contribution_page']);

    $submitParams = $this->getSubmitParamsContributionPlusMembership(TRUE);
    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $this->validateSeparateMembershipPaymentContributions($submitParams['id']);
    // We should have two separate email messages, each with their own amount
    // line and no total line.
    $mut->checkAllMailLog(
      [
        'Amount: $ 2.00',
        'Amount: $ 88.00',
        'Membership Fee',
      ],
      [
        'Total: $',
      ]
    );
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a membership block in place.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockIsSeparatePaymentZeroDollarsPayLaterWithEmail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->ids['MembershipType'] = [$this->membershipTypeCreate(['minimum_fee' => 0])];
    $this->setUpMembershipContributionPage(TRUE);
    $this->addProfile('supporter_profile', $this->_ids['contribution_page']);

    $submitParams = $this->getSubmitParamsContributionPlusMembership();
    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page');
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $this->_ids['contribution_page'], 'return' => 'contact_id'])['values'];
    $this->assertCount(2, $contributions);
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'getsingle', ['return' => ['contribution_id', 'membership_id']]);
    $this->assertArrayKeyExists($membershipPayment['contribution_id'], $contributions);
    $membership = $this->callAPISuccessGetSingle('Membership', ['id' => $membershipPayment['membership_id'], 'return' => 'contact_id']);
    $this->assertEquals($membership['contact_id'], $contributions[$membershipPayment['contribution_id']]['contact_id']);
    $mut->checkMailLog([
      'Gruff',
      'General Membership: $ 0.00',
      'Membership Fee',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a membership block in place.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockTwoTypesIsSeparatePayment(): void {
    $this->ids['MembershipType'] = [$this->membershipTypeCreate(['minimum_fee' => 6])];
    $this->ids['MembershipType'][] = $this->membershipTypeCreate(['name' => 'Student', 'minimum_fee' => 50]);
    $this->setUpMembershipContributionPage(TRUE);
    $submitParams = $this->getSubmitParamsContributionPlusMembership(FALSE, 'student');

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $this->_ids['contribution_page'], 'sequential' => TRUE])['values'];
    $this->assertCount(2, $contributions);
    $this->assertEquals('88.00', $contributions[0]['total_amount']);
    $this->assertEquals('50.00', $contributions[1]['total_amount']);
    $membershipPayment = $this->callAPISuccessGetSingle('MembershipPayment', ['return' => ['membership_id', 'contribution_id']]);
    $this->assertEquals($contributions[1]['id'], $membershipPayment['contribution_id']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id'], 'return' => 'contact_id']);
    $this->assertEquals($membership['contact_id'], $contributions[1]['contact_id']);
  }

  /**
   * Test submit with a membership block in place.
   *
   * We are expecting a separate payment for the membership vs the contribution.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockIsSeparatePaymentPaymentProcessorNow(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage(TRUE);
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessor['id']);
    $processor->setDoDirectPaymentResult(['payment_status_id' => 1, 'fee_amount' => .72]);
    $submitParams = $this->getSubmitParamsContributionPlusMembership(TRUE);

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contributions = $this->callAPISuccess('contribution', 'get', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ]);
    $this->assertCount(2, $contributions['values']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', ['return' => ['contribution_id', 'membership_id']]);
    $this->assertArrayHasKey($membershipPayment['contribution_id'], $contributions['values']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
    $lineItem = $this->callAPISuccessGetSingle('LineItem', ['entity_table' => 'civicrm_membership']);
    $this->assertEquals($membership['id'], $lineItem['entity_id']);
    $this->assertEquals($membershipPayment['contribution_id'], $lineItem['contribution_id']);
    $this->assertEquals(1, $lineItem['qty']);
    $this->assertEquals(2, $lineItem['unit_price']);
    $this->assertEquals(2, $lineItem['line_total']);
    foreach ($contributions['values'] as $contribution) {
      $this->assertEquals(.72, $contribution['fee_amount']);
      $this->assertEquals($contribution['total_amount'] - .72, $contribution['net_amount']);
    }
    // The total string is currently absent & it seems worse with - although at some point
    // it may have been intended
    $mut->checkAllMailLog(['$ 2.00', 'Contribution Amount', '$ 88.00'], ['Total:']);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a membership block in place.
   *
   * Ensure a separate payment for the membership vs the contribution, with
   * correct amounts.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @dataProvider getThousandSeparators
   */
  public function testSubmitMembershipBlockIsSeparatePaymentPaymentProcessorNowChargesCorrectAmounts(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->setUpMembershipContributionPage(TRUE);
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessor['id']);
    $processor->setDoDirectPaymentResult(['fee_amount' => .72]);
    $testKey = 'unique key for test hook';
    $submitParams = $this->getSubmitParamsContributionPlusMembership(TRUE);
    $submitParams['test_key'] = $testKey;

    // set custom hook
    $this->hookClass->setHook('civicrm_alterPaymentProcessorParams', [$this, 'hook_civicrm_alterPaymentProcessorParams']);

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $this->callAPISuccess('Contribution', 'get', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ]);

    $result = civicrm_api3('SystemLog', 'get', [
      'sequential' => 1,
      'message' => ['LIKE' => "%$testKey%"],
    ]);
    $this->assertCount(2, $result['values'], "Expected exactly 2 log entries matching $testKey.");

    // Examine logged entries to ensure correct values.
    $contribution_ids = [];
    $found_membership_amount = $found_contribution_amount = FALSE;
    foreach ($result['values'] as $value) {
      [, $json] = explode("$testKey:", $value['message']);
      $logged_contribution = json_decode($json, TRUE);
      $contribution_ids[] = $logged_contribution['contributionID'];
      if (!empty($logged_contribution['total_amount'])) {
        $amount = (int) $logged_contribution['total_amount'];
      }
      else {
        $amount = (int) $logged_contribution['amount'];
      }

      if ($amount === 2) {
        $found_membership_amount = TRUE;
      }
      if ($amount === 88) {
        $found_contribution_amount = TRUE;
      }
    }

    $distinct_contribution_ids = array_unique($contribution_ids);
    $this->assertCount(2, $distinct_contribution_ids, 'Expected exactly 2 log contributions with distinct contributionIDs.');
    $this->assertTrue($found_contribution_amount, 'Expected one log contribution with amount 88 (the contribution page amount)');
    $this->assertTrue($found_membership_amount, 'Expected one log contribution with amount 2 (the membership amount)');
  }

  /**
   * Test that when a transaction fails the pending contribution remains.
   *
   * An activity should also be created. CRM-16417.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPaymentProcessorFailure(): void {
    $this->setUpContributionPage();
    $this->setupPaymentProcessor();
    $this->createLoggedInUser();
    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    $submitParams = [
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2008],
      'cvv2' => 123,
    ];

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccessGetSingle('contribution', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 'Failed',
    ]);

    $this->callAPISuccessGetSingle('activity', [
      'source_record_id' => $contribution['id'],
      'activity_type_id' => 'Failed Payment',
    ]);

  }

  /**
   * Test submit recurring (yearly) membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPaymentYear(): void {
    $this->doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment(['duration_unit' => 'year', 'recur_frequency_unit' => 'year']);
  }

  /**
   * Test submit recurring (monthly) membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPaymentMonth(): void {
    $this->doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment(['duration_unit' => 'month', 'recur_frequency_unit' => 'month']);
  }

  /**
   * Test submit recurring (mismatched frequency unit) membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   */
  //public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPaymentDifferentFrequency() {
  //  $this->doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment(array('duration_unit' => 'year', 'recur_frequency_unit' => 'month'));
  //}

  /**
   * Helper function for testSubmitMembershipPriceSetPaymentProcessorRecurInstantPayment*
   *
   * @param array $params
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment(array $params = []): void {
    $this->params['is_recur'] = 1;
    $this->params['recur_frequency_unit'] = $params['recur_frequency_unit'];
    $membershipTypeParams['duration_unit'] = $params['duration_unit'];
    if ($params['recur_frequency_unit'] === $params['duration_unit']) {
      $durationUnit = $params['duration_unit'];
    }
    else {
      $durationUnit = NULL;
    }
    $this->setUpMembershipContributionPage(FALSE, FALSE, $membershipTypeParams);
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);
    $processor = $dummyPP->getPaymentProcessor();

    if ($params['recur_frequency_unit'] === $params['duration_unit']) {
      // Membership will be in "New" state because it will get confirmed as payment matches
      $expectedMembershipStatus = 1;
    }
    else {
      // Membership will still be in "Pending" state as it won't get confirmed as payment doesn't match
      $expectedMembershipStatus = 5;
    }

    $submitParams = array_merge($this->getSubmitParamsMembership(TRUE), [
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => $this->params['recur_frequency_unit'],
    ]);

    $this->callAPIAndDocument('ContributionPage', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page');
    $contribution = $this->callAPISuccess('contribution', 'getsingle', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ]);
    $this->assertEquals($processor['payment_instrument_id'], $contribution['payment_instrument_id']);

    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', []);
    $this->assertEquals($membershipPayment['contribution_id'], $contribution['id']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    $this->assertEquals($membership['contact_id'], $contribution['contact_id']);
    $this->assertEquals($expectedMembershipStatus, $membership['status_id']);
    $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $contribution['contribution_recur_id']]);
    $this->assertEquals($contribution['contribution_recur_id'], $membership['contribution_recur_id']);

    $this->callAPISuccess('line_item', 'getsingle', ['contribution_id' => $contribution['id'], 'entity_id' => $membership['id']]);
    //renew it with processor setting completed - should extend membership
    $submitParams['contact_id'] = $contribution['contact_id'];
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_second_success']);
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $this->callAPISuccess('contribution', 'getsingle', [
      'id' => ['NOT IN' => [$contribution['id']]],
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ]);
    $renewedMembership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    if ($durationUnit) {
      // We only have an end_date if frequency units match, otherwise membership won't be auto-renewed and dates won't be calculated.
      $renewedMembershipEndDate = $this->membershipRenewalDate($durationUnit, $membership['end_date']);
      $this->assertEquals($renewedMembershipEndDate, $renewedMembership['end_date']);
    }
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $contribution['contribution_recur_id']]);
    $this->assertEquals($processor['payment_instrument_id'], $recurringContribution['payment_instrument_id']);
    $this->assertEquals(5, $recurringContribution['contribution_status_id']);
  }

  /**
   * Test submit recurring membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipComplexQuickConfigPaymentPaymentProcessorRecurInstantPayment(): void {
    $this->params['is_recur'] = 1;
    $this->params['recur_frequency_unit'] = $membershipTypeParams['duration_unit'] = 'year';
    // Add a membership so membership & contribution are not both 1.
    $preExistingMembershipID = $this->contactMembershipCreate(['contact_id' => $this->contactIds[0]]);
    $this->setUpMembershipContributionPage(FALSE, FALSE, $membershipTypeParams);
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);
    $processor = $dummyPP->getPaymentProcessor();

    $submitParams = array_merge($this->getSubmitParamsContributionPlusMembership(TRUE), [
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => $this->params['recur_frequency_unit'],
    ]);

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ]);
    $this->assertEquals($processor['payment_instrument_id'], $contribution['payment_instrument_id']);
    $membership = $this->validateContributionWithContributionAndMembershipLineItems($contribution['id'], $preExistingMembershipID);

    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $contribution['contribution_recur_id']]);

    $this->validateContributionWithContributionAndMembershipLineItems($contribution['id'], $preExistingMembershipID);

    //renew it with processor setting completed - should extend membership
    $renewContribution = $this->submitSecondContribution((int) $contribution['contact_id'], $submitParams, (int) $contribution['id']);
    $renewedMembership = $this->validateContributionWithContributionAndMembershipLineItems($renewContribution['id'], $preExistingMembershipID);
    $this->assertEquals(date('Y-m-d', strtotime('+ 1 ' . $this->params['recur_frequency_unit'], strtotime($membership['end_date']))), $renewedMembership['end_date']);
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $contribution['contribution_recur_id']]);
    $this->assertEquals($processor['payment_instrument_id'], $recurringContribution['payment_instrument_id']);
    $this->assertEquals(5, $recurringContribution['contribution_status_id']);
  }

  /**
   * Test submit recurring membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitMembershipComplexPriceSetPaymentPaymentProcessorRecurInstantPayment(): void {
    $this->params['is_recur'] = 1;
    $this->params['recur_frequency_unit'] = 'year';
    // Add a membership so membership & contribution are not both 1.
    $preExistingMembershipID = $this->contactMembershipCreate(['contact_id' => $this->contactIds[0]]);
    $this->createPriceSetWithPage();
    $this->addSecondOrganizationMembershipToPriceSet();
    $this->setupPaymentProcessor();

    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);
    $processor = $dummyPP->getPaymentProcessor();

    $submitParams = [
      'price_' . $this->_ids['price_field'][0] => $this->_ids['price_field_value']['cont'],
      'price_' . $this->_ids['price_field']['org1'] => $this->_ids['price_field_value']['org1'],
      'price_' . $this->_ids['price_field']['org2'] => $this->_ids['price_field_value']['org2'],
      'id' => (int) $this->_ids['contribution_page'],
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
      'frequency_interval' => 1,
      'frequency_unit' => $this->params['recur_frequency_unit'],
    ];

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page');
    $contribution = $this->callAPISuccess('contribution', 'getsingle', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ]);
    $this->assertEquals($processor['payment_instrument_id'], $contribution['payment_instrument_id']);

    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $membershipPayments = $this->callAPISuccess('membership_payment', 'get', [
      'sequential' => 1,
      'contribution_id' => $contribution['id'],
    ]);
    $this->assertEquals(2, $membershipPayments['count']);
    $lines = $this->validateTripleLines($contribution['id'], $preExistingMembershipID);
    $this->assertEquals($preExistingMembershipID + 2, $lines[2]['entity_id']);

    $this->callAPISuccessGetSingle('MembershipPayment', ['contribution_id' => $contribution['id'], 'membership_id' => $preExistingMembershipID + 1]);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $preExistingMembershipID + 1]);

    $renewContribution = $this->submitSecondContribution((int) $contribution['contact_id'], $submitParams, (int) $contribution['id']);
    $this->validateTripleLines($renewContribution['id'], $preExistingMembershipID);

    $renewedMembership = $this->callAPISuccessGetSingle('membership', ['id' => $preExistingMembershipID + 1]);
    $this->assertEquals(date('Y-m-d', strtotime('+ 1 ' . $this->params['recur_frequency_unit'], strtotime($membership['end_date']))), $renewedMembership['end_date']);
  }

  /**
   * Extend the price set with a second organisation's membership.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function addSecondOrganizationMembershipToPriceSet(): void {
    $organization2ID = $this->organizationCreate();
    $membershipTypes = $this->callAPISuccess('MembershipType', 'get', []);
    $this->ids['MembershipType'] = array_keys($membershipTypes['values']);
    $this->ids['MembershipType']['org2'] = $this->membershipTypeCreate(['contact_id' => $organization2ID, 'name' => 'Org 2']);
    $priceField = $this->callAPISuccess('PriceField', 'create', [
      'price_set_id' => $this->_ids['price_set'],
      'html_type' => 'Radio',
      'name' => 'Org1 Price',
      'label' => 'Org1Price',
    ]);
    $this->_ids['price_field']['org1'] = $priceField['id'];

    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
      'name' => 'org1 amount',
      'label' => 'org 1 Amount',
      'amount' => 2,
      'financial_type_id' => 'Member Dues',
      'format.only_id' => TRUE,
      'membership_type_id' => reset($this->ids['MembershipType']),
      'price_field_id' => $priceField['id'],
    ]);
    $this->_ids['price_field_value']['org1'] = $priceFieldValue;

    $priceField = $this->callAPISuccess('PriceField', 'create', [
      'price_set_id' => $this->_ids['price_set'],
      'html_type' => 'Radio',
      'name' => 'Org2 Price',
      'label' => 'Org2Price',
    ]);
    $this->_ids['price_field']['org2'] = $priceField['id'];

    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
      'name' => 'org2 amount',
      'label' => 'org 2 Amount',
      'amount' => 200,
      'financial_type_id' => 'Member Dues',
      'format.only_id' => TRUE,
      'membership_type_id' => $this->ids['MembershipType']['org2'],
      'price_field_id' => $priceField['id'],
    ]);
    $this->_ids['price_field_value']['org2'] = $priceFieldValue;

  }

  /**
   * Test submit recurring membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorSeparatePaymentRecurInstantPayment(): void {
    $this->setUpMembershipContributionPage(TRUE);
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);
    $submitParams = array_merge($this->getSubmitParamsContributionPlusMembership(TRUE), [
      'is_recur' => 1,
      'auto_renew' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    ]);

    $this->callAPIAndDocument('ContributionPage', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page');
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ]);

    $this->assertEquals(2, $contribution['count']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', []);
    $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    $this->assertNotEmpty($contribution['values'][$membershipPayment['contribution_id']]['contribution_recur_id']);
    $this->callAPISuccess('ContributionRecur', 'getsingle');
  }

  /**
   * Test submit recurring membership with delayed confirmation (Authorize.net
   * style)
   * - we process 2 membership transactions against with a recurring
   * contribution against a contribution page with a delayed processor
   * (Authorize.net style - denoted by NOT returning trxn_id)
   * - the first creates a pending membership, pending contribution, pending
   * recurring. Check these
   * - complete the transaction
   * - create another - end date should NOT be extended
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurDelayed(): void {
    $this->params['is_recur'] = 1;
    $this->params['recur_frequency_unit'] = 'year';
    $this->setUpMembershipContributionPage();
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 2]);
    $this->membershipTypeCreate(['name' => 'Student']);

    // Add a contribution & a couple of memberships so the id will not be 1 & will differ from membership id.
    // This saves us from 'accidental success'.
    $this->contributionCreate(['contact_id' => $this->contactIds[0]]);
    $this->contactMembershipCreate(['contact_id' => $this->contactIds[0]]);
    $this->contactMembershipCreate(['contact_id' => $this->contactIds[0], 'membership_type_id' => 'Student']);

    $submitParams = array_merge($this->getSubmitParamsMembership(TRUE), [
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => $this->params['recur_frequency_unit'],
    ]);

    $this->callAPIAndDocument('ContributionPage', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page');
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 2,
    ]);

    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', []);
    $this->assertEquals($membershipPayment['contribution_id'], $contribution['id']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    $this->assertEquals($membership['contact_id'], $contribution['contact_id']);
    $this->assertEquals(5, $membership['status_id']);

    $line = $this->callAPISuccess('line_item', 'getsingle', ['contribution_id' => $contribution['id']]);
    $this->assertEquals('civicrm_membership', $line['entity_table']);
    $this->assertEquals($membership['id'], $line['entity_id']);

    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
      'trxn_id' => 'ipn_called',
      'payment_processor_id' => $this->_paymentProcessor['id'],
    ]);
    $line = $this->callAPISuccess('line_item', 'getsingle', ['contribution_id' => $contribution['id']]);
    $this->assertEquals('civicrm_membership', $line['entity_table']);
    $this->assertEquals($membership['id'], $line['entity_id']);

    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    //renew it with processor setting completed - should extend membership
    $submitParams = array_merge($submitParams, [
      'contact_id' => $contribution['contact_id'],
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => $this->params['recur_frequency_unit'],
    ]);

    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 2]);
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $newContribution = $this->callAPISuccess('contribution', 'getsingle', [
      'id' => [
        'NOT IN' => [$contribution['id']],
      ],
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 2,
    ]);
    $line = $this->callAPISuccess('line_item', 'getsingle', ['contribution_id' => $newContribution['id']]);
    $this->assertEquals('civicrm_membership', $line['entity_table']);
    $this->assertEquals($membership['id'], $line['entity_id']);

    $renewedMembership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    //no renewal as the date hasn't changed
    $this->assertEquals($membership['end_date'], $renewedMembership['end_date']);
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $newContribution['contribution_recur_id']]);
    $this->assertEquals(2, $recurringContribution['contribution_status_id']);
  }

  /**
   * Test non-recur contribution with membership payment
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipIsSeparatePaymentNotRecur(): void {
    $this->setUpMembershipContributionPage(TRUE, TRUE);
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);
    $submitParams = array_merge($this->getSubmitParamsMembership(TRUE), [
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    ]);
    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $recur = $this->callAPISuccess('ContributionRecur', 'get', []);
    $this->assertEmpty($recur['count']);
  }

  /**
   * Set up membership contribution page.
   *
   * @param bool $isSeparatePayment
   * @param bool $isRecur
   * @param array $membershipTypeParams Parameters to pass to
   *   MembershipType.create API
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function setUpMembershipContributionPage(bool $isSeparatePayment = FALSE, bool $isRecur = FALSE, array $membershipTypeParams = []): void {
    $this->setUpMembershipBlockPriceSet($membershipTypeParams);
    $this->setupPaymentProcessor();
    $this->setUpContributionPage($isRecur, $this->getPriceSetID('membership_block'));

    $this->callAPISuccess('membership_block', 'create', [
      'entity_id' => $this->_ids['contribution_page'],
      'entity_table' => 'civicrm_contribution_page',
      'is_required' => TRUE,
      'is_active' => TRUE,
      'is_separate_payment' => $isSeparatePayment,
      'membership_type_default' => $this->ids['MembershipType'],
    ]);
  }

  /**
   * Get the label for the relevant field eg.
   *
   * price_2
   *
   * @param string $label
   *  Generally either contribution or membership.
   *
   * @return string
   */
  protected function getPriceFieldLabel(string $label): string {
    return 'price_' . $this->ids['PriceField'][$label];
  }

  /**
   * Get the label for the relevant field eg.
   *
   * price_2
   *
   * @param string $label
   *  Should be either contribution or membership.
   *
   * @return mixed|string
   */
  protected function getPriceFieldValue(string $label) {
    if (isset($this->_ids['PriceField'][$label])) {
      return 'price_' . $this->_ids['PriceFieldValue'][$label];
    }
    return $this->ids['PriceFieldValue']['membership_' . $label];
  }

  /**
   * Set up pledge block.
   */
  public function setUpPledgeBlock(): void {
    $params = [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $this->_ids['contribution_page'],
      'pledge_frequency_unit' => 'week',
      'is_pledge_interval' => 0,
      'pledge_start_date' => json_encode(['calendar_date' => date('Ymd', strtotime('+1 month'))]),
    ];
    $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::create($params);
    $this->_ids['pledge_block_id'] = $pledgeBlock->id;
  }

  /**
   * Add text field other amount to the price set.
   *
   * @throws \CRM_Core_Exception
   */
  public function addOtherAmountFieldToMembershipPriceSet(): void {
    $this->_ids['price_field']['other_amount'] = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => reset($this->_ids['price_set']),
      'name' => 'other_amount',
      'label' => 'Other Amount',
      'html_type' => 'Text',
      'format.only_id' => TRUE,
      'sequential' => 1,
    ]);
    $this->_ids['price_field_value']['other_amount'] = $this->callAPISuccess('price_field_value', 'create', [
      'financial_type_id' => 'Donation',
      'format.only_id' => TRUE,
      'label' => 'Other Amount',
      'amount' => 1,
      'price_field_id' => $this->_ids['price_field']['other_amount'],
    ]);
  }

  /**
   * Help function to set up contribution page with some defaults.
   *
   * @param bool $isRecur
   * @param int|null $priceSetID
   *
   * @throws \CRM_Core_Exception
   */
  public function setUpContributionPage(bool $isRecur = FALSE, ?int $priceSetID = NULL): void {
    if ($isRecur) {
      $this->params['is_recur'] = 1;
      $this->params['recur_frequency_unit'] = 'month';
    }
    $this->params['frontend_title'] = 'Test Frontend title';
    $contributionPageResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    if (!$priceSetID) {
      if (empty($this->_ids['price_set'])) {
        $priceSet = $this->callAPISuccess('price_set', 'create', $this->_priceSetParams);
        $this->_ids['price_set'][] = $priceSet['id'];
      }
      $priceSetID = reset($this->_ids['price_set']);
    }
    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageResult['id'], $priceSetID);

    if (empty($this->_ids['price_field'])) {
      $priceField = $this->callAPISuccess('price_field', 'create', [
        'price_set_id' => $priceSetID,
        'label' => 'Goat Breed',
        'html_type' => 'Radio',
      ]);
      $this->_ids['price_field'] = [$priceField['id']];
      if (empty($this->_ids['price_field_value'])) {
        $this->callAPISuccess('price_field_value', 'create', [
          'price_set_id' => $priceSetID,
          'price_field_id' => $priceField['id'],
          'label' => 'Long Haired Goat',
          'financial_type_id' => 'Donation',
          'amount' => 20,
          'non_deductible_amount' => 15,
        ]);
        $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
          'price_set_id' => $priceSetID,
          'price_field_id' => $priceField['id'],
          'label' => 'Shoe-eating Goat',
          'financial_type_id' => 'Donation',
          'amount' => 10,
          'non_deductible_amount' => 5,
        ]);
        $this->_ids['price_field_value'] = [$priceFieldValue['id']];

        $this->_ids['price_field_value']['cheapskate'] = $this->callAPISuccess('price_field_value', 'create', [
          'price_set_id' => $priceSetID,
          'price_field_id' => $priceField['id'],
          'label' => 'Stingy Goat',
          'financial_type_id' => 'Donation',
          'amount' => 0,
          'non_deductible_amount' => 0,
        ])['id'];
      }

    }
    $this->_ids['contribution_page'] = $contributionPageResult['id'];
  }

  /**
   * Helper function to set up contribution page which can be used to purchase a
   * membership type for different intervals.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUpMultiIntervalMembershipContributionPage(): void {
    $this->setupPaymentProcessor();
    $contributionPage = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->_ids['contribution_page'] = $contributionPage['id'];

    $this->ids['MembershipType'] = $this->membershipTypeCreate([
      // force auto-renew
      'auto_renew' => 2,
      'duration_unit' => 'month',
    ]);

    $priceSet = $this->callAPISuccess('PriceSet', 'create', [
      'is_quick_config' => 0,
      'extends' => 'CiviMember',
      'financial_type_id' => 'Member Dues',
      'title' => 'CRM-21177',
    ]);
    $this->_ids['price_set'] = $priceSet['id'];

    $priceField = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => $this->_ids['price_set'],
      'name' => 'membership_type',
      'label' => 'Membership Type',
      'html_type' => 'Radio',
    ]);
    $this->_ids['price_field'] = $priceField['id'];

    $priceFieldValueMonthly = $this->callAPISuccess('price_field_value', 'create', [
      'name' => 'CRM-21177_Monthly',
      'label' => 'CRM-21177 - Monthly',
      'amount' => 20,
      'membership_num_terms' => 1,
      'membership_type_id' => $this->ids['MembershipType'],
      'price_field_id' => $this->_ids['price_field'],
      'financial_type_id' => 'Member Dues',
    ]);
    $this->_ids['price_field_value_monthly'] = $priceFieldValueMonthly['id'];

    $priceFieldValueYearly = $this->callAPISuccess('price_field_value', 'create', [
      'name' => 'CRM-21177_Yearly',
      'label' => 'CRM-21177 - Yearly',
      'amount' => 200,
      'membership_num_terms' => 12,
      'membership_type_id' => $this->ids['MembershipType'],
      'price_field_id' => $this->_ids['price_field'],
      'financial_type_id' => 'Member Dues',
    ]);
    $this->_ids['price_field_value_yearly'] = $priceFieldValueYearly['id'];

    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $this->_ids['contribution_page'], $this->_ids['price_set']);

    $this->callAPISuccess('membership_block', 'create', [
      'entity_id' => $this->_ids['contribution_page'],
      'entity_table' => 'civicrm_contribution_page',
      'is_required' => TRUE,
      'is_separate_payment' => FALSE,
      'is_active' => TRUE,
      'membership_type_default' => $this->ids['MembershipType'],
    ]);
  }

  /**
   * Test submit with a membership block in place.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitMultiIntervalMembershipContributionPage(): void {
    $this->setUpMultiIntervalMembershipContributionPage();
    $submitParams = [
      'price_' . $this->_ids['price_field'] => $this->_ids['price_field_value_monthly'],
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 20,
      'first_name' => 'Billy',
      'last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_processor_id' => $this->_ids['payment_processor'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
      'auto_renew' => 1,
    ];
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);

    $submitParams['price_' . $this->_ids['price_field']] = $this->_ids['price_field_value_yearly'];
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);

    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'sequential' => 1,
      'api.ContributionRecur.getsingle' => [],
    ]);
    $this->assertEquals(1, $contribution['values'][0]['api.ContributionRecur.getsingle']['frequency_interval']);
    //$this->assertEquals(12, $contribution['values'][1]['api.ContributionRecur.getsingle']['frequency_interval']);
  }

  /**
   * Create a payment processor instance.
   *
   * @throws \CRM_Core_Exception
   */
  protected function setupPaymentProcessor(): void {
    $this->params['payment_processor_id'] = $this->_ids['payment_processor'] = $this->paymentProcessorCreate([
      'payment_processor_type_id' => 'Dummy',
      'class_name' => 'Payment_Dummy',
      'billing_mode' => 1,
    ]);
    $this->_paymentProcessor = $this->callAPISuccess('payment_processor', 'getsingle', ['id' => $this->params['payment_processor_id']]);
  }

  /**
   * Test submit recurring pledge.
   *
   * - we process 1 pledge with a future start date. A recur contribution and the pledge should be created with first payment date in the future.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPledgePaymentPaymentProcessorRecurFuturePayment(): void {
    $this->params['adjust_recur_start_date'] = TRUE;
    $this->params['is_pay_later'] = FALSE;
    $this->setUpContributionPage();
    $this->setUpPledgeBlock();
    $this->setupPaymentProcessor();
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);

    $submitParams = [
      'id' => (int) $this->_ids['contribution_page'],
      'price_' . $this->_ids['price_field'][0] => $this->_ids['price_field_value'][0],
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
      'pledge_frequency_interval' => 1,
      'pledge_frequency_unit' => 'week',
      'pledge_installments' => 3,
      'is_pledge' => TRUE,
      'pledge_block_id' => (int) $this->_ids['pledge_block_id'],
    ];

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page');

    // Check if contribution created.
    $contribution = $this->callAPISuccess('contribution', 'getsingle', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      // Will be pending when actual payment processor is used (dummy processor does not support future payments).
      'contribution_status_id' => 'Completed',
    ]);

    $this->assertEquals('create_first_success', $contribution['trxn_id']);

    // Check if pledge created.
    $pledge = $this->callAPISuccess('pledge', 'getsingle', []);
    $this->assertEquals(date('Ymd', strtotime($pledge['pledge_start_date'])), date('Ymd', strtotime('+1 month')));
    $this->assertEquals(30.00, $pledge['pledge_amount']);

    // Check if pledge payments created.
    $params = [
      'pledge_id' => $pledge['id'],
    ];
    $pledgePayment = $this->callAPISuccess('pledge_payment', 'get', $params);
    $this->assertEquals(3, $pledgePayment['count']);
    $this->assertEquals(date('Ymd', strtotime($pledgePayment['values'][1]['scheduled_date'])), date('Ymd', strtotime('+1 month')));
    $this->assertEquals(10.00, $pledgePayment['values'][1]['scheduled_amount']);
    // Will be pending when actual payment processor is used (dummy processor does not support future payments).
    $this->assertEquals(1, $pledgePayment['values'][1]['status_id']);

    // Check contribution recur record.
    $recur = $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $contribution['contribution_recur_id']]);
    $this->assertEquals(date('Ymd', strtotime($recur['start_date'])), date('Ymd', strtotime('+1 month')));
    $this->assertEquals(10.00, $recur['amount']);
    // In progress status.
    $this->assertEquals(5, $recur['contribution_status_id']);
  }

  /**
   * Test submit pledge payment.
   *
   * - test submitting a pledge payment using contribution form.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPledgePayment(): void {
    $this->testSubmitPledgePaymentPaymentProcessorRecurFuturePayment();
    $pledge = $this->callAPISuccess('Pledge', 'getsingle', []);
    $params = [
      'pledge_id' => $pledge['id'],
    ];
    $submitParams = [
      'id' => (int) $pledge['pledge_contribution_page_id'],
      'pledge_amount' => [2 => 1],
      'price_' . $this->_ids['price_field'][0] => $this->_ids['price_field_value'][0],
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
      'pledge_id' => $pledge['id'],
      'cid' => $pledge['contact_id'],
      'contact_id' => $pledge['contact_id'],
      'is_pledge' => TRUE,
      'pledge_block_id' => $this->_ids['pledge_block_id'],
    ];
    $pledgePayment = $this->callAPISuccess('pledge_payment', 'get', $params);
    $this->assertEquals(2, $pledgePayment['values'][2]['status_id']);

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page');

    // Check if contribution created.
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'contribution_page_id' => $pledge['pledge_contribution_page_id'],
      'contribution_status_id' => 'Completed',
      'contact_id' => $pledge['contact_id'],
      'contribution_recur_id' => ['IS NULL' => 1],
    ]);

    $this->assertEquals(10.00, $contribution['total_amount']);
    $pledgePayment = $this->callAPISuccess('PledgePayment', 'get', $params)['values'];
    $this->assertEquals(1, $pledgePayment[2]['status_id'], 'This pledge payment should have been completed');
    $this->assertEquals($contribution['id'], $pledgePayment[2]['contribution_id']);
  }

  /**
   * Test form submission with multiple option price set.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   * @throws \CRM_Core_Exception
   */
  public function testSubmitContributionPageWithPriceSet(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->_priceSetParams['is_quick_config'] = 0;
    $this->setUpContributionPage();
    $submitParams = [
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'first_name' => 'Billy',
      'last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'is_pay_later' => TRUE,
    ];
    $this->addPriceFields($submitParams);

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contribution = $this->callAPISuccessGetSingle('contribution', [
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 'Pending',
    ]);
    $this->assertEquals(80, $contribution['total_amount']);
    $lineItems = $this->callAPISuccess('LineItem', 'get', [
      'contribution_id' => $contribution['id'],
    ]);
    $this->assertEquals(3, $lineItems['count']);
    $totalLineAmount = 0;
    foreach ($lineItems['values'] as $lineItem) {
      $totalLineAmount += $lineItem['line_total'];
    }
    $this->assertEquals(80, $totalLineAmount);
  }

  /**
   * Function to add additional price fields to price set.
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  public function addPriceFields(array &$params): void {
    $priceSetID = reset($this->_ids['price_set']);
    $priceField = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => $priceSetID,
      'label' => 'Chicken Breed',
      'html_type' => 'CheckBox',
    ]);
    $priceFieldValue1 = $this->callAPISuccess('price_field_value', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Shoe-eating chicken -1',
      'financial_type_id' => 'Donation',
      'amount' => 30,
    ]);
    $priceFieldValue2 = $this->callAPISuccess('price_field_value', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Shoe-eating chicken -2',
      'financial_type_id' => 'Donation',
      'amount' => 40,
    ]);
    $params['price_' . $priceField['id']] = [
      $priceFieldValue1['id'] => 1,
      $priceFieldValue2['id'] => 1,
    ];
  }

  /**
   * Test Tax Amount is calculated properly when using PriceSet with Field Type = Text/Numeric Quantity
   *
   * The created contribution has 3 line items
   *
   * |qty  | unit_price| line_total| tax |total including tax|
   * | 1   | 10        | 10        | 0     |     10 |
   * | 180   | 16.95   | 3051      |305.1  |  3356.1|
   * | 110   | 2.95    | 324.5     | 32.45 |   356.95|
   *
   * Contribution total = 3723.05
   *  made up of  tax 337.55
   *          non tax 3385.5
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @dataProvider getThousandSeparators
   */
  public function testSubmitContributionPageWithPriceSetQuantity(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->_priceSetParams['is_quick_config'] = 0;
    $this->enableTaxAndInvoicing();
    $financialType = $this->createFinancialType();
    $financialTypeId = $financialType['id'];
    // This function sets the Tax Rate at 10% - it currently has no way to pass Tax Rate into it - so let's work with 10%
    $this->addTaxAccountToFinancialType($financialType['id']);

    $this->setUpContributionPage();
    $submitParams = [
      'id' => (int) $this->_ids['contribution_page'],
      'first_name' => 'J',
      'last_name' => 'T',
      'email' => 'JT@ohcanada.ca',
      'is_pay_later' => TRUE,
      'receive_date' => date('Y-m-d H:i:s'),
    ];

    // Add Existing PriceField
    // This is a Shoe-eating Goat; qty = 1; unit_price = $10.00; There is no sales tax on Goats
    $submitParams['price_' . $this->_ids['price_field'][0]] = reset($this->_ids['price_field_value']);

    // Create additional PriceSet/PriceField
    $priceSetID = reset($this->_ids['price_set']);
    $priceField = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => $priceSetID,
      'label' => 'Printing Rights',
      'html_type' => 'Text',
    ]);

    $this->callAPISuccess('PriceFieldValue', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Printing Rights',
      'financial_type_id' => $financialTypeId,
      'amount' => '16.95',
    ]);
    $priceFieldId = $priceField['id'];

    // Set quantity for our test
    $submitParams['price_' . $priceFieldId] = 180;

    $priceField = $this->callAPISuccess('PriceField', 'create', [
      'price_set_id' => $priceSetID,
      'label' => 'Another Line Item',
      'html_type' => 'Text',
    ]);

    $this->callAPISuccess('price_field_value', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Another Line Item',
      'financial_type_id' => $financialTypeId,
      'amount' => '2.95',
    ]);
    $priceFieldId = $priceField['id'];

    // Set quantity for our test
    $submitParams['price_' . $priceFieldId] = 110;

    // This is the correct Tax Amount - use it later to compare to what the CiviCRM Core came up with at the LineItem level
    $submitParams['tax_amount'] = (180 * 16.95 * 0.10 + 110 * 2.95 * 0.10);

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $this->validateAllContributions();

    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contribution_page_id' => $this->_ids['contribution_page'],
    ]);

    // Retrieve the lineItem that belongs to the Goat
    $lineItem1 = $this->callAPISuccessGetSingle('LineItem', [
      'contribution_id' => $contribution['id'],
      'label' => 'Shoe-eating Goat',
      'return' => ['line_total', 'tax_amount'],
    ]);

    // Retrieve the lineItem that belongs to the Printing Rights and check the tax_amount CiviCRM Core calculated for it
    $lineItem2 = $this->callAPISuccessGetSingle('LineItem', [
      'contribution_id' => $contribution['id'],
      'label' => 'Printing Rights',
      'return' => ['line_total', 'tax_amount'],
    ]);

    // Retrieve the lineItem that belongs to the Another Line Item and check the tax_amount CiviCRM Core calculated for it
    $lineItem3 = $this->callAPISuccessGetSingle('LineItem', [
      'contribution_id' => $contribution['id'],
      'label' => 'Another Line Item',
      'return' => ['line_total', 'tax_amount'],
    ]);

    $this->assertEquals($lineItem1['line_total'] + $lineItem2['line_total'] + $lineItem3['line_total'], round(10 + 180 * 16.95 + 110 * 2.95, 2), 'Line Item Total is incorrect.');
    $this->assertEquals(round($lineItem1['tax_amount'] + $lineItem2['tax_amount'] + $lineItem3['tax_amount'], 2), round(180 * 16.95 * 0.10 + 110 * 2.95 * 0.10, 2), 'Wrong Sales Tax Amount is calculated and stored.');
  }

  /**
   * Test validating a contribution page submit.
   *
   * @throws \CRM_Core_Exception
   */
  public function testValidate(): void {
    $this->setUpContributionPage();
    $errors = $this->callAPISuccess('ContributionPage', 'validate', array_merge($this->getBasicSubmitParams(), ['action' => 'submit']))['values'];
    $this->assertEmpty($errors);
  }

  /**
   * Test validating a contribution page submit in POST context.
   *
   * A likely use case for the validation is when the is being submitted and some handling is
   * to be done before processing but the validity of input needs to be checked first.
   *
   * For example Paypal Checkout will replace the confirm button with it's own but we are able to validate
   * before paypal launches it's modal. In this case the $_REQUEST is post but we need validation to succeed.
   *
   * @throws \CRM_Core_Exception
   */
  public function testValidatePost(): void {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $this->setUpContributionPage();
    $errors = $this->callAPISuccess('ContributionPage', 'validate', array_merge($this->getBasicSubmitParams(), ['action' => 'submit']))['values'];
    $this->assertEmpty($errors);
    unset($_SERVER['REQUEST_METHOD']);
  }

  /**
   * Test that an error is generated if required fields are not submitted.
   *
   * @throws \CRM_Core_Exception
   */
  public function testValidateOutputOnMissingRecurFields(): void {
    $this->params['is_recur_interval'] = 1;
    $this->setUpContributionPage(TRUE);
    $submitParams = array_merge($this->getBasicSubmitParams(), ['action' => 'submit']);
    $submitParams['is_recur'] = 1;
    $submitParams['frequency_interval'] = '';
    $submitParams['frequency_unit'] = '';
    $errors = $this->callAPISuccess('ContributionPage', 'validate', $submitParams)['values'];
    $this->assertEquals('Please enter a number for how often you want to make this recurring contribution (EXAMPLE: Every 3 months).', $errors['frequency_interval']);
  }

  /**
   * Implements hook_civicrm_alterPaymentProcessorParams().
   *
   * @throws CRM_Core_Exception
   * @noinspection PhpUnusedParameterInspection
   */
  public function hook_civicrm_alterPaymentProcessorParams($paymentObj, $rawParams, $cookedParams): void {
    // Ensure total_amount are the same if they're both given.
    $total_amount = $rawParams['total_amount'] ?? NULL;
    $amount = $rawParams['amount'] ?? NULL;
    if (!empty($total_amount) && !empty($amount) && round($total_amount, 2) !== round($amount, 2)) {
      throw new CRM_Core_Exception("total_amount '$total_amount' and amount '$amount' differ.");
    }

    // Log parameters for later debugging and testing.
    $message = "test hook: {$rawParams['test_key']}:";
    $log_params = array_intersect_key($rawParams, [
      'amount' => 1,
      'total_amount' => 1,
      'contributionID' => 1,
    ]);
    $message .= json_encode($log_params);
    $log = new CRM_Utils_SystemLogger();
    $log->debug($message, $_REQUEST);
  }

  /**
   * Get the params for a basic simple submit.
   *
   * @return array
   */
  protected function getBasicSubmitParams(): array {
    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    return [
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'priceSetId' => $this->_ids['price_set'][0],
      'payment_processor_id' => 0,
    ];
  }

  /**
   * Get params to use for a page a membership and contribution items
   *
   * @param bool $isCardPayment
   * @param string $membershipType
   *
   * @return array
   */
  private function getSubmitParamsContributionPlusMembership(bool $isCardPayment = FALSE, string $membershipType = 'general'): array {
    $params = $this->getSubmitParamsMembership($isCardPayment, $membershipType);
    $params[$this->getPriceFieldLabel('contribution')] = 1;
    return $params;
  }

  /**
   * Get submit params for the membership line only.
   *
   * @param bool $isCardPayment
   * @param string $membershipType
   *
   * @return array
   */
  protected function getSubmitParamsMembership(bool $isCardPayment = FALSE, string $membershipType = 'general'): array {
    $params = [
      $this->getPriceFieldLabel('membership') => $this->getPriceFieldValue($membershipType),
      'id' => (int) $this->_ids['contribution_page'],
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email-Primary' => 'billy-goat@the-bridge.net',
    ];

    if ($isCardPayment) {
      $params = array_merge([
        'payment_processor_id' => $this->_paymentProcessor['id'],
        'credit_card_number' => '4111111111111111',
        'credit_card_type' => 'Visa',
        'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
        'cvv2' => 123,
      ], $params);
    }
    return $params;
  }

  /**
   * Validate that separate membership payments are created with
   *
   *  - 2 contributions linked to the contribution page
   *  - consisting of 1 contribution matching the passed in
   *    contribution amount and one linked to a membership through
   *    the membership payment and the line item.
   *
   * @param int $contributionPageID
   * @param float|int $contributionAmount
   *
   * @return void
   *
   * @throws \CRM_Core_Exception
   */
  private function validateSeparateMembershipPaymentContributions(int $contributionPageID, $contributionAmount = 88): void {
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $contributionPageID, 'return' => 'contact_id'])['values'];
    $this->assertCount(2, $contributions);
    $lines = $this->callAPISuccess('LineItem', 'get', ['sequential' => 1, 'return' => 'line_total'])['values'];
    $this->assertEquals($contributionAmount, $lines[0]['line_total']);
    $membershipPayment = $this->callAPISuccessGetSingle('MembershipPayment', ['return' => ['contribution_id', 'membership_id']]);
    $this->assertArrayKeyExists($membershipPayment['contribution_id'], $contributions);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id'], 'return' => 'contact_id']);
    $this->assertEquals($membership['contact_id'], $contributions[$membershipPayment['contribution_id']]['contact_id']);
  }

  /**
   * Validates that one contribution has been created with 2 line items.
   *
   * Line items should be valid for one contribution and one membership.
   *
   * @param int $id
   * @param int $preExistingMembershipID
   *
   * @return array
   *   Membership
   *
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  private function validateContributionWithContributionAndMembershipLineItems(int $id, int $preExistingMembershipID): array {
    $lines = $this->callAPISuccess('line_item', 'get', [
      'sequential' => 1,
      'contribution_id' => $id,
    ])['values'];
    $this->assertCount(2, $lines);
    $this->assertEquals('civicrm_contribution', $lines[1]['entity_table']);
    $this->assertEquals($id, $lines[1]['entity_id']);
    $this->assertEquals('civicrm_membership', $lines[0]['entity_table']);
    $this->assertEquals($preExistingMembershipID + 1, $lines[0]['entity_id']);
    $this->callAPISuccessGetSingle('MembershipPayment', ['contribution_id' => $id, 'membership_id' => $preExistingMembershipID + 1]);
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'getsingle', ['contribution_id' => $id]);
    $this->assertEquals($membershipPayment['contribution_id'], $id);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    $this->assertEquals($membership['contact_id'], Contribution::get()
      ->addSelect('contact_id')
      ->addWhere('id', '=', $id)
      ->execute()->first()['contact_id']
    );
    $this->assertEquals(1, $membership['status_id']);
    return $membership;
  }

  /**
   * Submit the form a second time and make a second contribution.
   *
   * @param int $contact_id
   * @param array $submitParams
   * @param int $originalContributionID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function submitSecondContribution(int $contact_id, array $submitParams, int $originalContributionID): array {
    $submitParams['contact_id'] = $contact_id;
    Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor)->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_second_success']);
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    return $this->callAPISuccess('contribution', 'getsingle', [
      'id' => ['NOT IN' => [$originalContributionID]],
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ]);
  }

  /**
   * @param $contact
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  private function submitPageWithBilling($contact) {
    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    $submitParams = [
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Wonder',
      'billing_last_name' => 'Woman',
      'contactID' => $contact['id'],
      'email' => 'wonderwoman@amazon.com',
    ];

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    return $this->callAPISuccess('Contact', 'get', [
      'id' => $contact['id'],
      'return' => [
        'first_name',
        'last_name',
        'sort_name',
        'display_name',
      ],
    ]);
  }

  /**
   * Validate contribution with 3 line items.
   *
   * @param int $id
   * @param int $preExistingMembershipID
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  private function validateTripleLines(int $id, int $preExistingMembershipID) {
    $lines = $this->callAPISuccess('line_item', 'get', [
      'sequential' => 1,
      'contribution_id' => $id,
    ])['values'];
    $this->assertCount(3, $lines);
    $this->assertEquals('civicrm_membership', $lines[0]['entity_table']);
    $this->assertEquals($preExistingMembershipID + 1, $lines[0]['entity_id']);
    $this->assertEquals('civicrm_contribution', $lines[1]['entity_table']);
    $this->assertEquals($id, $lines[1]['entity_id']);
    $this->assertEquals('civicrm_membership', $lines[2]['entity_table']);
    return $lines;
  }

}
