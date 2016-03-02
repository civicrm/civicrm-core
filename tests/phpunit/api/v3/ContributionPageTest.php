<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *  Test APIv3 civicrm_contribute_recur* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class api_v3_ContributionPageTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $testAmount = 34567;
  protected $params;
  protected $id = 0;
  protected $contactIds = array();
  protected $_entity = 'contribution_page';
  protected $contribution_result = NULL;
  protected $_priceSetParams = array();
  /**
   * Payment processor details.
   * @var array
   */
  protected $_paymentProcessor = array();

  /**
   * @var array
   *   - contribution_page
   *   - price_set
   *   - price_field
   *   - price_field_value
   */
  protected $_ids = array();


  public $DBResetRequired = TRUE;

  public function setUp() {
    parent::setUp();
    $this->contactIds[] = $this->individualCreate();
    $this->params = array(
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => $this->testAmount,
      'is_pay_later' => 1,
      'is_monetary' => TRUE,
      'is_email_receipt' => TRUE,
      'receipt_from_email' => 'yourconscience@donate.com',
      'receipt_from_name' => 'Ego Freud',
    );

    $this->_priceSetParams = array(
      'is_quick_config' => 1,
      'extends' => 'CiviContribute',
      'financial_type_id' => 'Donation',
      'title' => 'my Page',
    );
  }

  public function tearDown() {
    foreach ($this->contactIds as $id) {
      $this->callAPISuccess('contact', 'delete', array('id' => $id));
    }
    $this->quickCleanUpFinancialEntities();
  }

  public function testCreateContributionPage() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetBasicContributionPage() {
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->id = $createResult['id'];
    $getParams = array(
      'currency' => 'NZD',
      'financial_type_id' => 1,
    );
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  public function testGetContributionPageByAmount() {
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->id = $createResult['id'];
    $getParams = array(
      'amount' => '' . $this->testAmount, // 3456
      'currency' => 'NZD',
      'financial_type_id' => 1,
    );
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  public function testDeleteContributionPage() {
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = array('id' => $createResult['id']);
    $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', array());
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testGetFieldsContributionPage() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', array('action' => 'create'));
    $this->assertEquals(12, $result['values']['start_date']['type']);
  }


  /**
   * Test form submission with basic price set.
   */
  public function testSubmit() {
    $this->setUpContributionPage();
    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    $submitParams = array(
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $this->_ids['contribution_page']));
  }

  /**
   * Test form submission with billing first & last name where the contact does NOT
   * otherwise have one.
   */
  public function testSubmitNewBillingNameData() {
    $this->setUpContributionPage();
    $contact = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'email' => 'wonderwoman@amazon.com'));
    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    $submitParams = array(
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Wonder',
      'billing_last_name' => 'Woman',
      'contactID' => $contact['id'],
      'email' => 'wonderwoman@amazon.com',
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contact = $this->callAPISuccess('Contact', 'get', array(
      'id' => $contact['id'],
      'return' => array(
        'first_name',
        'last_name',
        'sort_name',
        'display_name',
      ),
    ));
    $this->assertEquals(array(
      'first_name' => 'Wonder',
      'last_name' => 'Woman',
      'display_name' => 'Wonder Woman',
      'sort_name' => 'Woman, Wonder',
      'id' => $contact['id'],
      'contact_id' => $contact['id'],
    ), $contact['values'][$contact['id']]);

  }

  /**
   * Test form submission with billing first & last name where the contact does
   * otherwise have one and should not be overwritten.
   */
  public function testSubmitNewBillingNameDoNotOverwrite() {
    $this->setUpContributionPage();
    $contact = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual',
      'email' => 'wonderwoman@amazon.com',
      'first_name' => 'Super',
      'last_name' => 'Boy',
    ));
    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    $submitParams = array(
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Wonder',
      'billing_last_name' => 'Woman',
      'contactID' => $contact['id'],
      'email' => 'wonderwoman@amazon.com',
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contact = $this->callAPISuccess('Contact', 'get', array(
      'id' => $contact['id'],
      'return' => array(
        'first_name',
        'last_name',
        'sort_name',
        'display_name',
      ),
    ));
    $this->assertEquals(array(
      'first_name' => 'Super',
      'last_name' => 'Boy',
      'display_name' => 'Super Boy',
      'sort_name' => 'Boy, Super',
      'id' => $contact['id'],
      'contact_id' => $contact['id'],
    ), $contact['values'][$contact['id']]);

  }

  /**
   * Test process with instant payment when more than one configured for the page.
   *
   * CRM-16923
   */
  public function testSubmitRecurMultiProcessorInstantPayment() {
    $this->setUpContributionPage();
    $this->setupPaymentProcessor();
    $paymentProcessor2ID = $this->paymentProcessorCreate(array(
      'payment_processor_type_id' => 'Dummy',
      'name' => 'processor 2',
      'class_name' => 'Payment_Dummy',
      'billing_mode' => 1,
    ));
    $dummyPP = Civi\Payment\System::singleton()->getById($paymentProcessor2ID);
    $dummyPP->setDoDirectPaymentResult(array(
      'payment_status_id' => 1,
      'trxn_id' => 'create_first_success',
      'fee_amount' => .85,
    ));
    $this->callAPISuccess('ContributionPage', 'create', array(
      'id' => $this->_ids['contribution_page'],
      'payment_processor' => array($paymentProcessor2ID, $this->_ids['payment_processor']),
    ));

    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    $submitParams = array(
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessor2ID,
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));
    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $this->assertEquals(10, $contribution['total_amount']);
    $this->assertEquals(.85, $contribution['fee_amount']);
    $this->assertEquals(9.15, $contribution['net_amount']);
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockNotSeparatePayment() {
    $this->setUpMembershipContributionPage();
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'selectMembership' => $this->_ids['membership_type'],

    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->callAPISuccess('membership_payment', 'getsingle', array('contribution_id' => $contribution['id']));
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockNotSeparatePaymentWithEmail() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage();
    $this->addProfile('supporter_profile', $this->_ids['contribution_page']);

    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'selectMembership' => $this->_ids['membership_type'],
      'email-Primary' => 'billy-goat@the-bridge.net',
      'payment_processor_id' => $this->_paymentProcessor['id'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->callAPISuccess('membership_payment', 'getsingle', array('contribution_id' => $contribution['id']));
    $mut->checkMailLog(array(
      'Membership Type: General',
    ));
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockNotSeparatePaymentZeroDollarsWithEmail() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->_ids['membership_type'] = array($this->membershipTypeCreate(array('minimum_fee' => 0)));
    $this->setUpMembershipContributionPage();
    $this->addProfile('supporter_profile', $this->_ids['contribution_page']);

    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 0,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruffier',
      'selectMembership' => $this->_ids['membership_type'],
      'email-Primary' => 'billy-goat@the-new-bridge.net',
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->callAPISuccess('membership_payment', 'getsingle', array('contribution_id' => $contribution['id']));
    $mut->checkMailLog(array(
         'Membership Type: General',
         'Gruffier',
      ),
      array(
        'Amount',
      )
    );
    $mut->stop();
    $mut->clearMessages(999);
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockIsSeparatePayment() {
    $this->setUpMembershipContributionPage(TRUE);
    $this->_ids['membership_type'] = array($this->membershipTypeCreate(array('minimum_fee' => 2)));
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'selectMembership' => $this->_ids['membership_type'],
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contributions = $this->callAPISuccess('contribution', 'get', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->assertCount(2, $contributions['values']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertTrue(in_array($membershipPayment['contribution_id'], array_keys($contributions['values'])));
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockIsSeparatePaymentWithEmail() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage(TRUE);
    $this->addProfile('supporter_profile', $this->_ids['contribution_page']);

    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'selectMembership' => $this->_ids['membership_type'],
      'email-Primary' => 'billy-goat@the-bridge.net',
      'payment_processor_id' => $this->_paymentProcessor['id'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contributions = $this->callAPISuccess('contribution', 'get', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->assertCount(2, $contributions['values']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertTrue(in_array($membershipPayment['contribution_id'], array_keys($contributions['values'])));
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
    $mut->checkAllMailLog(array(
      '$ 2.00',
      'Membership Fee',
    ));
    $mut->stop();
    $mut->clearMessages(999);
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockIsSeparatePaymentZeroDollarsPayLaterWithEmail() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->_ids['membership_type'] = array($this->membershipTypeCreate(array('minimum_fee' => 0)));
    $this->setUpMembershipContributionPage(TRUE);
    $this->addProfile('supporter_profile', $this->_ids['contribution_page']);

    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 0,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruffalo',
      'selectMembership' => $this->_ids['membership_type'],
      'payment_processor_id' => 0,
      'email-Primary' => 'gruffalo@the-bridge.net',
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contributions = $this->callAPISuccess('contribution', 'get', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->assertCount(2, $contributions['values']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertTrue(in_array($membershipPayment['contribution_id'], array_keys($contributions['values'])));
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
    $mut->checkMailLog(array(
      'Gruffalo',
      'General Membership: $ 0.00',
      'Membership Fee',
    ));
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockTwoTypesIsSeparatePayment() {
    $this->_ids['membership_type'] = array($this->membershipTypeCreate(array('minimum_fee' => 6)));
    $this->_ids['membership_type'][] = $this->membershipTypeCreate(array('name' => 'Student', 'minimum_fee' => 50));
    $this->setUpMembershipContributionPage(TRUE);
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => $this->_ids['price_field_value'][1],
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'selectMembership' => $this->_ids['membership_type'][1],
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contributions = $this->callAPISuccess('contribution', 'get', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->assertCount(2, $contributions['values']);
    $ids = array_keys($contributions['values']);
    $this->assertEquals('10.00', $contributions['values'][$ids[0]]['total_amount']);
    $this->assertEquals('50.00', $contributions['values'][$ids[1]]['total_amount']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertArrayHasKey($membershipPayment['contribution_id'], $contributions['values']);
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
  }

  /**
   * Test submit with a membership block in place.
   *
   * We are expecting a separate payment for the membership vs the contribution.
   */
  public function testSubmitMembershipBlockIsSeparatePaymentPaymentProcessorNow() {
    $this->setUpMembershipContributionPage(TRUE);
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessor['id']);
    $processor->setDoDirectPaymentResult(array('fee_amount' => .72));
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'selectMembership' => $this->_ids['membership_type'],
      'payment_processor_id' => $this->_paymentProcessor['id'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contributions = $this->callAPISuccess('contribution', 'get', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));
    $this->assertCount(2, $contributions['values']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertTrue(in_array($membershipPayment['contribution_id'], array_keys($contributions['values'])));
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
    foreach ($contributions['values'] as $contribution) {
      $this->assertEquals(.72, $contribution['fee_amount']);
      $this->assertEquals($contribution['total_amount'] - .72, $contribution['net_amount']);
    }
  }

  /**
   * Test that when a transaction fails the pending contribution remains.
   *
   * An activity should also be created. CRM-16417.
   */
  public function testSubmitPaymentProcessorFailure() {
    $this->setUpContributionPage();
    $this->setupPaymentProcessor();
    $this->createLoggedInUser();
    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    $submitParams = array(
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2008),
      'cvv2' => 123,
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccessGetSingle('contribution', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 2,
    ));

    $this->callAPISuccessGetSingle('activity', array(
      'source_record_id' => $contribution['id'],
      'activity_type_id' => 'Failed Payment',
    ));

  }

  /**
   * Test submit recurring membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment() {
    $this->params['is_recur'] = 1;
    $this->params['recur_frequency_unit'] = 'month';
    $this->setUpMembershipContributionPage();
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_first_success'));

    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'selectMembership' => $this->_ids['membership_type'],
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));

    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertEquals($membershipPayment['contribution_id'], $contribution['id']);
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contribution['contact_id']);
    $this->assertEquals(1, $membership['status_id']);
    $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $contribution['contribution_recur_id']));
    //@todo - check with Joe about these not existing
    //$this->callAPISuccess('line_item', 'getsingle', array('contribution_id' => $contribution['id'], 'entity_id' => $membership['id']));
    //renew it with processor setting completed - should extend membership
    $submitParams['contact_id'] = $contribution['contact_id'];
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_second_success'));
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $this->callAPISuccess('contribution', 'getsingle', array(
      'id' => array('NOT IN' => array($contribution['id'])),
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));
    $renewedMembership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals(date('Y-m-d', strtotime('+ 1 year', strtotime($membership['end_date']))), $renewedMembership['end_date']);
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $contribution['contribution_recur_id']));
    $this->assertEquals(5, $recurringContribution['contribution_status_id']);
  }

  /**
   * Test submit recurring membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorSeparatePaymentRecurInstantPayment() {

    $this->setUpMembershipContributionPage(TRUE);
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_first_success'));

    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'selectMembership' => $this->_ids['membership_type'],
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
      'is_recur' => 1,
      'auto_renew' => TRUE,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));

    $this->assertEquals(2, $contribution['count']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertNotEmpty($contribution['values'][$membershipPayment['contribution_id']]['contribution_recur_id']);
    $this->callAPISuccess('contribution_recur', 'getsingle', array());
  }

  /**
   * Test submit recurring membership with delayed confirmation (Authorize.net style)
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with a delayed
   * processor (Authorize.net style - denoted by NOT returning trxn_id)
   * - the first creates a pending membership, pending contribution, penging recurring. Check these
   * - complete the transaction
   * - create another - end date should NOT be extended
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurDelayed() {
    $this->params['is_recur'] = 1;
    $this->params['recur_frequency_unit'] = 'month';
    $this->setUpMembershipContributionPage();
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 2));

    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'selectMembership' => $this->_ids['membership_type'],
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 2,
    ));
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertEquals($membershipPayment['contribution_id'], $contribution['id']);
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contribution['contact_id']);
    $this->assertEquals(5, $membership['status_id']);
    //@todo - check with Joe about these not existing
    //$this->callAPISuccess('line_item', 'getsingle', array('contribution_id' => $contribution['id'], 'entity_id' => $membership['id']));
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
      'trxn_id' => 'ipn_called',
      'payment_processor_id' => $this->_paymentProcessor['id'],
    ));
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    //renew it with processor setting completed - should extend membership
    $submitParams = array_merge($submitParams, array(
        'contact_id' => $contribution['contact_id'],
        'is_recur' => 1,
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
      )
    );
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 2));
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $newContribution = $this->callAPISuccess('contribution', 'getsingle', array(
        'id' => array(
          'NOT IN' => array($contribution['id']),
        ),
        'contribution_page_id' => $this->_ids['contribution_page'],
        'contribution_status_id' => 2,
      )
    );

    $renewedMembership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    //no renewal as the date hasn't changed
    $this->assertEquals($membership['end_date'], $renewedMembership['end_date']);
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $newContribution['contribution_recur_id']));
    $this->assertEquals(2, $recurringContribution['contribution_status_id']);
  }

  /**
   * Set up membership contribution page.
   * @param bool $isSeparatePayment
   */
  public function setUpMembershipContributionPage($isSeparatePayment = FALSE) {
    $this->setUpMembershipBlockPriceSet();
    $this->setupPaymentProcessor();
    $this->setUpContributionPage();

    $this->callAPISuccess('membership_block', 'create', array(
      'entity_id' => $this->_ids['contribution_page'],
      'entity_table' => 'civicrm_contribution_page',
      'is_required' => TRUE,
      'is_active' => TRUE,
      'is_separate_payment' => $isSeparatePayment,
      'membership_type_default' => $this->_ids['membership_type'],
    ));
  }

  /**
   * The default data set does not include a complete default membership price set - not quite sure why.
   *
   * This function ensures it exists & populates $this->_ids with it's data
   */
  public function setUpMembershipBlockPriceSet() {
    $this->_ids['price_set'][] = $this->callAPISuccess('price_set', 'getvalue', array(
      'name' => 'default_membership_type_amount',
      'return' => 'id',
    ));
    if (empty($this->_ids['membership_type'])) {
      $this->_ids['membership_type'] = array($this->membershipTypeCreate(array('minimum_fee' => 2)));
    }
    $priceField = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => reset($this->_ids['price_set']),
      'name' => 'membership_amount',
      'label' => 'Membership Amount',
      'html_type' => 'Radio',
      'sequential' => 1,
    ));
    $this->_ids['price_field'][] = $priceField['id'];

    foreach ($this->_ids['membership_type'] as $membershipTypeID) {
      $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'name' => 'membership_amount',
        'label' => 'Membership Amount',
        'amount' => 1,
        'financial_type_id' => 'Donation',
        'format.only_id' => TRUE,
        'membership_type_id' => $membershipTypeID,
        'price_field_id' => $priceField['id'],
      ));
      $this->_ids['price_field_value'][] = $priceFieldValue['id'];
    }
  }

  /**
   * Add text field other amount to the price set.
   */
  public function addOtherAmountFieldToMembershipPriceSet() {
    $this->_ids['price_field']['other_amount'] = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => reset($this->_ids['price_set']),
      'name' => 'other_amount',
      'label' => 'Other Amount',
      'html_type' => 'Text',
      'format.only_id' => TRUE,
      'sequential' => 1,
    ));
    $this->_ids['price_field_value']['other_amount'] = $this->callAPISuccess('price_field_value', 'create', array(
      'financial_type_id' => 'Donation',
      'format.only_id' => TRUE,
      'label' => 'Other Amount',
      'amount' => 1,
      'price_field_id' => $this->_ids['price_field']['other_amount'],
    ));
  }

  /**
   * Help function to set up contribution page with some defaults.
   */
  public function setUpContributionPage() {
    $contributionPageResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    if (empty($this->_ids['price_set'])) {
      $priceSet = $this->callAPISuccess('price_set', 'create', $this->_priceSetParams);
      $this->_ids['price_set'][] = $priceSet['id'];
    }
    $priceSetID = reset($this->_ids['price_set']);
    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageResult['id'], $priceSetID);

    if (empty($this->_ids['price_field'])) {
      $priceField = $this->callAPISuccess('price_field', 'create', array(
        'price_set_id' => $priceSetID,
        'label' => 'Goat Breed',
        'html_type' => 'Radio',
      ));
      $this->_ids['price_field'] = array($priceField['id']);
    }
    if (empty($this->_ids['price_field_value'])) {
      $this->callAPISuccess('price_field_value', 'create', array(
          'price_set_id' => $priceSetID,
          'price_field_id' => $priceField['id'],
          'label' => 'Long Haired Goat',
          'financial_type_id' => 'Donation',
          'amount' => 20,
          'financial_type_id' => 'Donation',
        )
      );
      $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
          'price_set_id' => $priceSetID,
          'price_field_id' => $priceField['id'],
          'label' => 'Shoe-eating Goat',
          'financial_type_id' => 'Donation',
          'amount' => 10,
          'financial_type_id' => 'Donation',
        )
      );
      $this->_ids['price_field_value'] = array($priceFieldValue['id']);
    }
    $this->_ids['contribution_page'] = $contributionPageResult['id'];
  }

  public static function setUpBeforeClass() {
    // put stuff here that should happen before all tests in this unit
  }

  public static function tearDownAfterClass() {
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_financial_type',
      'civicrm_contribution',
      'civicrm_contribution_page',
    );
    $unitTest = new CiviUnitTestCase();
    $unitTest->quickCleanup($tablesToTruncate);
  }

  /**
   * Create a payment processor instance.
   */
  protected function setupPaymentProcessor() {
    $this->params['payment_processor_id'] = $this->_ids['payment_processor'] = $this->paymentProcessorCreate(array(
      'payment_processor_type_id' => 'Dummy',
      'class_name' => 'Payment_Dummy',
      'billing_mode' => 1,
    ));
    $this->_paymentProcessor = $this->callAPISuccess('payment_processor', 'getsingle', array('id' => $this->params['payment_processor_id']));
  }

}
