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
  protected $_membershipBlockAmount = 2;
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
      // 3456
      'amount' => '' . $this->testAmount,
      'currency' => 'NZD',
      'financial_type_id' => 1,
    );
    $getResult = $this->callAPISuccess($this->_entity, 'get', $getParams);
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
    $submitParams = $this->getBasicSubmitParams();

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $this->_ids['contribution_page']));
    //assert non-deductible amount
    $this->assertEquals(5.00, $contribution['non_deductible_amount']);
  }

  /**
   * Test form submission with basic price set.
   */
  public function testSubmitZeroDollar() {
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
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $this->_ids['contribution_page']));

    $this->assertEquals($this->formatMoneyInput(0), $contribution['non_deductible_amount']);
    $this->assertEquals($this->formatMoneyInput(0), $contribution['total_amount']);
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
    $processor = $dummyPP->getPaymentProcessor();
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
    $this->_checkFinancialRecords(array(
      'id' => $contribution['id'],
      'total_amount' => $contribution['total_amount'],
      'payment_instrument_id' => $processor['payment_instrument_id'],
    ), 'online');
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
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array('contribution_id' => $contribution['id']));
    $this->callAPISuccessGetSingle('LineItem', array('contribution_id' => $contribution['id'], 'entity_id' => $membershipPayment['id']));
  }

  /**
   * Test submit with a membership block in place works with renewal.
   */
  public function testSubmitMembershipBlockNotSeparatePaymentProcessorInstantRenew() {
    $this->setUpMembershipContributionPage();
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1));
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'selectMembership' => $this->_ids['membership_type'],
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $this->_ids['contribution_page']));
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array('contribution_id' => $contribution['id']));
    $this->callAPISuccessGetCount('LineItem', array(
      'entity_table' => 'civicrm_membership',
      'entity_id' => $membershipPayment['id'],
    ), 1);

    $submitParams['contact_id'] = $contribution['contact_id'];

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $this->callAPISuccessGetCount('LineItem', array(
      'entity_table' => 'civicrm_membership',
      'entity_id' => $membershipPayment['id'],
    ), 2);
    $membership = $this->callAPISuccessGetSingle('Membership', array(
      'id' => $membershipPayment['membership_id'],
      'return' => array('end_date', 'join_date', 'start_date'),
    ));
    $this->assertEquals(date('Y-m-d'), $membership['start_date']);
    $this->assertEquals(date('Y-m-d'), $membership['join_date']);
    $this->assertEquals(date('Y-m-d', strtotime('+ 2 year - 1 day')), $membership['end_date']);
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

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
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
      'payment_processor_id' => $this->params['payment_processor_id'],
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->callAPISuccess('membership_payment', 'getsingle', array('contribution_id' => $contribution['id']));
    //Assert only one mail is being sent.
    $msgs = $mut->getAllMessages();
    $this->assertCount(1, $msgs);

    $mut->checkMailLog(array(
      'Membership Type: General',
      'Gruffier',
    ), array(
      'Amount',
    ));
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a pay later abnd check line item in mails.
   */
  public function testSubmitMembershipBlockIsSeparatePaymentPayLaterWithEmail() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage();
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'is_pay_later' => 1,
      'selectMembership' => $this->_ids['membership_type'],
      'email-Primary' => 'billy-goat@the-bridge.net',
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->callAPISuccess('membership_payment', 'getsingle', array('contribution_id' => $contribution['id']));
    $mut->checkMailLog(array(
      'Membership Amount -...             $ 2.00',
    ));
    $mut->stop();
    $mut->clearMessages();
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

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contributions = $this->callAPISuccess('contribution', 'get', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->assertCount(2, $contributions['values']);
    $lines = $this->callAPISuccess('LineItem', 'get', array('sequential' => 1));
    $this->assertEquals(10, $lines['values'][0]['line_total']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertTrue(in_array($membershipPayment['contribution_id'], array_keys($contributions['values'])));
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockIsSeparatePaymentWithPayLater() {
    $this->setUpMembershipContributionPage(TRUE);
    $this->_ids['membership_type'] = array($this->membershipTypeCreate(array('minimum_fee' => 2)));
    //Pay later
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 0,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'is_pay_later' => 1,
      'selectMembership' => $this->_ids['membership_type'],
    );

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contributions = $this->callAPISuccess('contribution', 'get', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->assertCount(2, $contributions['values']);
    foreach ($contributions['values'] as $val) {
      $this->assertEquals('Pending', $val['contribution_status']);
    }

    //Membership should be in Pending state.
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertTrue(in_array($membershipPayment['contribution_id'], array_keys($contributions['values'])));
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $pendingStatus = $this->callAPISuccessGetSingle('MembershipStatus', array(
      'return' => array("id"),
      'name' => "Pending",
    ));
    $this->assertEquals($membership['status_id'], $pendingStatus['id']);
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

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contributions = $this->callAPISuccess('contribution', 'get', array('contribution_page_id' => $this->_ids['contribution_page']));
    $this->assertCount(2, $contributions['values']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertTrue(in_array($membershipPayment['contribution_id'], array_keys($contributions['values'])));
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
    // We should have two separate email messages, each with their own amount
    // line and no total line.
    $mut->checkAllMailLog(
      array(
        'Amount: $ 2.00',
        'Amount: $ 10.00',
        'Membership Fee',
      ),
      array(
        'Total: $',
      )
    );
    $mut->stop();
    $mut->clearMessages();
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
    $mut = new CiviMailUtils($this, TRUE);
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
      'email-Primary' => 'henry@8th.king',
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
    $lineItem = $this->callAPISuccessGetSingle('LineItem', array('entity_table' => 'civicrm_membership'));
    $this->assertEquals($lineItem['entity_id'], $membership['id']);
    $this->assertEquals($lineItem['contribution_id'], $membershipPayment['contribution_id']);
    $this->assertEquals($lineItem['qty'], 1);
    $this->assertEquals($lineItem['unit_price'], 2);
    $this->assertEquals($lineItem['line_total'], 2);
    foreach ($contributions['values'] as $contribution) {
      $this->assertEquals(.72, $contribution['fee_amount']);
      $this->assertEquals($contribution['total_amount'] - .72, $contribution['net_amount']);
    }
    // The total string is currently absent & it seems worse with - although at some point
    // it may have been intended
    $mut->checkAllMailLog(array('$ 2.00', 'Contribution Amount', '$ 10.00'), array('Total:'));
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
   * @dataProvider getThousandSeparators
   */
  public function testSubmitMembershipBlockIsSeparatePaymentPaymentProcessorNowChargesCorrectAmounts($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $this->setUpMembershipContributionPage(TRUE);
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessor['id']);
    $processor->setDoDirectPaymentResult(array('fee_amount' => .72));
    $test_uniq = uniqid();
    $contributionPageAmount = 10;
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => $contributionPageAmount,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email-Primary' => 'henry@8th.king',
      'selectMembership' => $this->_ids['membership_type'],
      'payment_processor_id' => $this->_paymentProcessor['id'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
      'TEST_UNIQ' => $test_uniq,
    );

    // set custom hook
    $this->hookClass->setHook('civicrm_alterPaymentProcessorParams', array($this, 'hook_civicrm_alterPaymentProcessorParams'));

    $this->callAPISuccess('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contributions = $this->callAPISuccess('contribution', 'get', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));

    $result = civicrm_api3('SystemLog', 'get', array(
      'sequential' => 1,
      'message' => array('LIKE' => "%{$test_uniq}%"),
    ));
    $this->assertCount(2, $result['values'], "Expected exactly 2 log entries matching {$test_uniq}.");

    // Examine logged entries to ensure correct values.
    $contribution_ids = array();
    $found_membership_amount = $found_contribution_amount = FALSE;
    foreach ($result['values'] as $value) {
      list($junk, $json) = explode("$test_uniq:", $value['message']);
      $logged_contribution = json_decode($json, TRUE);
      $contribution_ids[] = $logged_contribution['contributionID'];
      if (!empty($logged_contribution['total_amount'])) {
        $amount = $logged_contribution['total_amount'];
      }
      else {
        $amount = $logged_contribution['amount'];
      }

      if ($amount == $this->_membershipBlockAmount) {
        $found_membership_amount = TRUE;
      }
      if ($amount == $contributionPageAmount) {
        $found_contribution_amount = TRUE;
      }
    }

    $distinct_contribution_ids = array_unique($contribution_ids);
    $this->assertCount(2, $distinct_contribution_ids, "Expected exactly 2 log contributions with distinct contributionIDs.");
    $this->assertTrue($found_contribution_amount, "Expected one log contribution with amount '$contributionPageAmount' (the contribution page amount)");
    $this->assertTrue($found_membership_amount, "Expected one log contribution with amount '$this->_membershipBlockAmount' (the membership amount)");
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
      'contribution_status_id' => 'Failed',
    ));

    $this->callAPISuccessGetSingle('activity', array(
      'source_record_id' => $contribution['id'],
      'activity_type_id' => 'Failed Payment',
    ));

  }

  /**
   * Test submit recurring (yearly) membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPaymentYear() {
    $this->doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment(array('duration_unit' => 'year', 'recur_frequency_unit' => 'year'));
  }

  /**
   * Test submit recurring (monthly) membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPaymentMonth() {
    $this->doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment(array('duration_unit' => 'month', 'recur_frequency_unit' => 'month'));
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
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment($params = array()) {
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
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_first_success'));
    $processor = $dummyPP->getPaymentProcessor();

    if ($params['recur_frequency_unit'] === $params['duration_unit']) {
      // Membership will be in "New" state because it will get confirmed as payment matches
      $expectedMembershipStatus = 1;
    }
    else {
      // Membership will still be in "Pending" state as it won't get confirmed as payment doesn't match
      $expectedMembershipStatus = 5;
    }

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
      'frequency_unit' => $this->params['recur_frequency_unit'],
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));
    $this->assertEquals($processor['payment_instrument_id'], $contribution['payment_instrument_id']);

    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertEquals($membershipPayment['contribution_id'], $contribution['id']);
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contribution['contact_id']);
    $this->assertEquals($expectedMembershipStatus, $membership['status_id']);
    $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $contribution['contribution_recur_id']));

    $this->callAPISuccess('line_item', 'getsingle', array('contribution_id' => $contribution['id'], 'entity_id' => $membership['id']));
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
    if ($durationUnit) {
      // We only have an end_date if frequency units match, otherwise membership won't be autorenewed and dates won't be calculated.
      $renewedMembershipEndDate = $this->membershipRenewalDate($durationUnit, $membership['end_date']);
      $this->assertEquals($renewedMembershipEndDate, $renewedMembership['end_date']);
    }
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $contribution['contribution_recur_id']));
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
   */
  public function testSubmitMembershipComplexNonPriceSetPaymentPaymentProcessorRecurInstantPayment() {
    $this->params['is_recur'] = 1;
    $this->params['recur_frequency_unit'] = $membershipTypeParams['duration_unit'] = 'year';
    // Add a membership so membership & contribution are not both 1.
    $preExistingMembershipID = $this->contactMembershipCreate(array('contact_id' => $this->contactIds[0]));
    $this->setUpMembershipContributionPage(FALSE, FALSE, $membershipTypeParams);
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_first_success'));
    $processor = $dummyPP->getPaymentProcessor();

    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'price_' . $this->_ids['price_field']['cont'] => 88,
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
      'frequency_unit' => $this->params['recur_frequency_unit'],
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));
    $this->assertEquals($processor['payment_instrument_id'], $contribution['payment_instrument_id']);

    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', array());
    $this->assertEquals($membershipPayment['contribution_id'], $contribution['id']);
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals($membership['contact_id'], $contribution['contact_id']);
    $this->assertEquals(1, $membership['status_id']);
    $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $contribution['contribution_recur_id']));

    $lines = $this->callAPISuccess('line_item', 'get', array('sequential' => 1, 'contribution_id' => $contribution['id']));
    $this->assertEquals(2, $lines['count']);
    $this->assertEquals('civicrm_membership', $lines['values'][0]['entity_table']);
    $this->assertEquals($preExistingMembershipID + 1, $lines['values'][0]['entity_id']);
    $this->assertEquals('civicrm_contribution', $lines['values'][1]['entity_table']);
    $this->assertEquals($contribution['id'], $lines['values'][1]['entity_id']);
    $this->callAPISuccessGetSingle('MembershipPayment', array('contribution_id' => $contribution['id'], 'membership_id' => $preExistingMembershipID + 1));

    //renew it with processor setting completed - should extend membership
    $submitParams['contact_id'] = $contribution['contact_id'];
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_second_success'));
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $renewContribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'id' => array('NOT IN' => array($contribution['id'])),
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));
    $lines = $this->callAPISuccess('line_item', 'get', array('sequential' => 1, 'contribution_id' => $renewContribution['id']));
    $this->assertEquals(2, $lines['count']);
    $this->assertEquals('civicrm_membership', $lines['values'][0]['entity_table']);
    $this->assertEquals($preExistingMembershipID + 1, $lines['values'][0]['entity_id']);
    $this->assertEquals('civicrm_contribution', $lines['values'][1]['entity_table']);
    $this->assertEquals($renewContribution['id'], $lines['values'][1]['entity_id']);

    $renewedMembership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    $this->assertEquals(date('Y-m-d', strtotime('+ 1 ' . $this->params['recur_frequency_unit'], strtotime($membership['end_date']))), $renewedMembership['end_date']);
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $contribution['contribution_recur_id']));
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
   */
  public function testSubmitMembershipComplexPriceSetPaymentPaymentProcessorRecurInstantPayment() {
    $this->params['is_recur'] = 1;
    $this->params['recur_frequency_unit'] = $membershipTypeParams['duration_unit'] = 'year';
    // Add a membership so membership & contribution are not both 1.
    $preExistingMembershipID = $this->contactMembershipCreate(array('contact_id' => $this->contactIds[0]));
    $this->createPriceSetWithPage();
    $this->addSecondOrganizationMembershipToPriceSet();
    $this->setupPaymentProcessor();

    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_first_success'));
    $processor = $dummyPP->getPaymentProcessor();

    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => $this->_ids['price_field_value']['cont'],
      'price_' . $this->_ids['price_field']['org1'] => $this->_ids['price_field_value']['org1'],
      'price_' . $this->_ids['price_field']['org2'] => $this->_ids['price_field_value']['org2'],
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'selectMembership' => NULL,
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
      'frequency_interval' => 1,
      'frequency_unit' => $this->params['recur_frequency_unit'],
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));
    $this->assertEquals($processor['payment_instrument_id'], $contribution['payment_instrument_id']);

    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $membershipPayments = $this->callAPISuccess('membership_payment', 'get', array(
      'sequential' => 1,
      'contribution_id' => $contribution['id'],
    ));
    $this->assertEquals(2, $membershipPayments['count']);
    $lines = $this->callAPISuccess('line_item', 'get', array('sequential' => 1, 'contribution_id' => $contribution['id']));
    $this->assertEquals(3, $lines['count']);
    $this->assertEquals('civicrm_membership', $lines['values'][0]['entity_table']);
    $this->assertEquals($preExistingMembershipID + 1, $lines['values'][0]['entity_id']);
    $this->assertEquals('civicrm_contribution', $lines['values'][1]['entity_table']);
    $this->assertEquals($contribution['id'], $lines['values'][1]['entity_id']);
    $this->assertEquals('civicrm_membership', $lines['values'][2]['entity_table']);
    $this->assertEquals($preExistingMembershipID + 2, $lines['values'][2]['entity_id']);

    $this->callAPISuccessGetSingle('MembershipPayment', array('contribution_id' => $contribution['id'], 'membership_id' => $preExistingMembershipID + 1));
    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $preExistingMembershipID + 1));

    //renew it with processor setting completed - should extend membership
    $submitParams['contact_id'] = $contribution['contact_id'];
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_second_success'));
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $renewContribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'id' => array('NOT IN' => array($contribution['id'])),
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 1,
    ));
    $lines = $this->callAPISuccess('line_item', 'get', array('sequential' => 1, 'contribution_id' => $renewContribution['id']));
    $this->assertEquals(3, $lines['count']);
    $this->assertEquals('civicrm_membership', $lines['values'][0]['entity_table']);
    $this->assertEquals($preExistingMembershipID + 1, $lines['values'][0]['entity_id']);
    $this->assertEquals('civicrm_contribution', $lines['values'][1]['entity_table']);
    $this->assertEquals($renewContribution['id'], $lines['values'][1]['entity_id']);

    $renewedMembership = $this->callAPISuccessGetSingle('membership', array('id' => $preExistingMembershipID + 1));
    $this->assertEquals(date('Y-m-d', strtotime('+ 1 ' . $this->params['recur_frequency_unit'], strtotime($membership['end_date']))), $renewedMembership['end_date']);
  }

  /**
   * Extend the price set with a second organisation's membership.
   */
  public function addSecondOrganizationMembershipToPriceSet() {
    $organization2ID = $this->organizationCreate();
    $membershipTypes = $this->callAPISuccess('MembershipType', 'get', array());
    $this->_ids['membership_type'] = array_keys($membershipTypes['values']);
    $this->_ids['membership_type']['org2'] = $this->membershipTypeCreate(array('contact_id' => $organization2ID, 'name' => 'Org 2'));
    $priceField = $this->callAPISuccess('PriceField', 'create', array(
      'price_set_id' => $this->_ids['price_set'],
      'html_type' => 'Radio',
      'name' => 'Org1 Price',
      'label' => 'Org1Price',
    ));
    $this->_ids['price_field']['org1'] = $priceField['id'];

    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
      'name' => 'org1 amount',
      'label' => 'org 1 Amount',
      'amount' => 2,
      'financial_type_id' => 'Member Dues',
      'format.only_id' => TRUE,
      'membership_type_id' => reset($this->_ids['membership_type']),
      'price_field_id' => $priceField['id'],
    ));
    $this->_ids['price_field_value']['org1'] = $priceFieldValue;

    $priceField = $this->callAPISuccess('PriceField', 'create', array(
      'price_set_id' => $this->_ids['price_set'],
      'html_type' => 'Radio',
      'name' => 'Org2 Price',
      'label' => 'Org2Price',
    ));
    $this->_ids['price_field']['org2'] = $priceField['id'];

    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
      'name' => 'org2 amount',
      'label' => 'org 2 Amount',
      'amount' => 200,
      'financial_type_id' => 'Member Dues',
      'format.only_id' => TRUE,
      'membership_type_id' => $this->_ids['membership_type']['org2'],
      'price_field_id' => $priceField['id'],
    ));
    $this->_ids['price_field_value']['org2'] = $priceFieldValue;

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
    $this->params['recur_frequency_unit'] = $membershipTypeParams['duration_unit'] = 'year';
    $this->setUpMembershipContributionPage();
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 2));
    $this->membershipTypeCreate(array('name' => 'Student'));

    // Add a contribution & a couple of memberships so the id will not be 1 & will differ from membership id.
    // This saves us from 'accidental success'.
    $this->contributionCreate(array('contact_id' => $this->contactIds[0]));
    $this->contactMembershipCreate(array('contact_id' => $this->contactIds[0]));
    $this->contactMembershipCreate(array('contact_id' => $this->contactIds[0], 'membership_type_id' => 'Student'));

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
      'frequency_unit' => $this->params['recur_frequency_unit'],
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

    $line = $this->callAPISuccess('line_item', 'getsingle', array('contribution_id' => $contribution['id']));
    $this->assertEquals('civicrm_membership', $line['entity_table']);
    $this->assertEquals($membership['id'], $line['entity_id']);

    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
      'trxn_id' => 'ipn_called',
      'payment_processor_id' => $this->_paymentProcessor['id'],
    ));
    $line = $this->callAPISuccess('line_item', 'getsingle', array('contribution_id' => $contribution['id']));
    $this->assertEquals('civicrm_membership', $line['entity_table']);
    $this->assertEquals($membership['id'], $line['entity_id']);

    $membership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    //renew it with processor setting completed - should extend membership
    $submitParams = array_merge($submitParams, array(
      'contact_id' => $contribution['contact_id'],
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => $this->params['recur_frequency_unit'],
    ));

    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 2));
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $newContribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'id' => array(
        'NOT IN' => array($contribution['id']),
      ),
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 2,
    ));
    $line = $this->callAPISuccess('line_item', 'getsingle', array('contribution_id' => $newContribution['id']));
    $this->assertEquals('civicrm_membership', $line['entity_table']);
    $this->assertEquals($membership['id'], $line['entity_id']);

    $renewedMembership = $this->callAPISuccessGetSingle('membership', array('id' => $membershipPayment['membership_id']));
    //no renewal as the date hasn't changed
    $this->assertEquals($membership['end_date'], $renewedMembership['end_date']);
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $newContribution['contribution_recur_id']));
    $this->assertEquals(2, $recurringContribution['contribution_status_id']);
  }

  /**
   * Test non-recur contribution with membership payment
   */
  public function testSubmitMembershipIsSeparatePaymentNotRecur() {
    //Create recur contribution page.
    $this->setUpMembershipContributionPage(TRUE, TRUE);
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_first_success'));

    //Sumbit payment with recur disabled.
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
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
    );

    //Assert if recur contribution is created.
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $recur = $this->callAPISuccess('contribution_recur', 'get', array());
    $this->assertEmpty($recur['count']);
  }

  /**
   * Set up membership contribution page.
   * @param bool $isSeparatePayment
   * @param bool $isRecur
   * @param array $membershipTypeParams Parameters to pass to membershiptype.create API
   */
  public function setUpMembershipContributionPage($isSeparatePayment = FALSE, $isRecur = FALSE, $membershipTypeParams = array()) {
    $this->setUpMembershipBlockPriceSet($membershipTypeParams);
    $this->setupPaymentProcessor();
    $this->setUpContributionPage($isRecur);

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
   * Set up pledge block.
   */
  public function setUpPledgeBlock() {
    $params = array(
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $this->_ids['contribution_page'],
      'pledge_frequency_unit' => 'week',
      'is_pledge_interval' => 0,
      'pledge_start_date' => json_encode(array('calendar_date' => date('Ymd', strtotime("+1 month")))),
    );
    $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::create($params);
    $this->_ids['pledge_block_id'] = $pledgeBlock->id;
  }

  /**
   * The default data set does not include a complete default membership price set - not quite sure why.
   *
   * This function ensures it exists & populates $this->_ids with it's data
   */
  public function setUpMembershipBlockPriceSet($membershipTypeParams = array()) {
    $this->_ids['price_set'][] = $this->callAPISuccess('price_set', 'getvalue', array(
      'name' => 'default_membership_type_amount',
      'return' => 'id',
    ));
    if (empty($this->_ids['membership_type'])) {
      $membershipTypeParams = array_merge(array(
        'minimum_fee' => 2,
      ), $membershipTypeParams);
      $this->_ids['membership_type'] = array($this->membershipTypeCreate($membershipTypeParams));
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
        'amount' => $this->_membershipBlockAmount,
        'financial_type_id' => 'Donation',
        'format.only_id' => TRUE,
        'membership_type_id' => $membershipTypeID,
        'price_field_id' => $priceField['id'],
      ));
      $this->_ids['price_field_value'][] = $priceFieldValue;
    }
    if (!empty($this->_ids['membership_type']['org2'])) {
      $priceField = $this->callAPISuccess('price_field', 'create', array(
        'price_set_id' => reset($this->_ids['price_set']),
        'name' => 'membership_org2',
        'label' => 'Membership Org2',
        'html_type' => 'Checkbox',
        'sequential' => 1,
      ));
      $this->_ids['price_field']['org2'] = $priceField['id'];

      $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'name' => 'membership_org2',
        'label' => 'Membership org 2',
        'amount' => 55,
        'financial_type_id' => 'Member Dues',
        'format.only_id' => TRUE,
        'membership_type_id' => $this->_ids['membership_type']['org2'],
        'price_field_id' => $priceField['id'],
      ));
      $this->_ids['price_field_value']['org2'] = $priceFieldValue;
    }
    $priceField = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => reset($this->_ids['price_set']),
      'name' => 'Contribution',
      'label' => 'Contribution',
      'html_type' => 'Text',
      'sequential' => 1,
      'is_enter_qty' => 1,
    ));
    $this->_ids['price_field']['cont'] = $priceField['id'];
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
      'name' => 'contribution',
      'label' => 'Give me money',
      'amount' => 88,
      'financial_type_id' => 'Donation',
      'format.only_id' => TRUE,
      'price_field_id' => $priceField['id'],
    ));
    $this->_ids['price_field_value'][] = $priceFieldValue;
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
   * @param bool $isRecur
   */
  public function setUpContributionPage($isRecur = FALSE) {
    if ($isRecur) {
      $this->params['is_recur'] = 1;
      $this->params['recur_frequency_unit'] = 'month';
    }
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
        'non_deductible_amount' => 15,
      ));
      $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Shoe-eating Goat',
        'financial_type_id' => 'Donation',
        'amount' => 10,
        'non_deductible_amount' => 5,
      ));
      $this->_ids['price_field_value'] = array($priceFieldValue['id']);

      $this->_ids['price_field_value']['cheapskate'] = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Stingy Goat',
        'financial_type_id' => 'Donation',
        'amount' => 0,
        'non_deductible_amount' => 0,
      ))['id'];
    }
    $this->_ids['contribution_page'] = $contributionPageResult['id'];
  }

  /**
   * Helper function to set up contribution page which can be used to purchase a
   * membership type for different intervals.
   */
  public function setUpMultiIntervalMembershipContributionPage() {
    $this->setupPaymentProcessor();
    $contributionPage = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->_ids['contribution_page'] = $contributionPage['id'];

    $this->_ids['membership_type'] = $this->membershipTypeCreate(array(
      // force auto-renew
      'auto_renew' => 2,
      'duration_unit' => 'month',
    ));

    $priceSet = civicrm_api3('PriceSet', 'create', array(
      'is_quick_config' => 0,
      'extends' => 'CiviMember',
      'financial_type_id' => 'Member Dues',
      'title' => 'CRM-21177',
    ));
    $this->_ids['price_set'] = $priceSet['id'];

    $priceField = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => $this->_ids['price_set'],
      'name' => 'membership_type',
      'label' => 'Membership Type',
      'html_type' => 'Radio',
    ));
    $this->_ids['price_field'] = $priceField['id'];

    $priceFieldValueMonthly = $this->callAPISuccess('price_field_value', 'create', array(
      'name' => 'CRM-21177_Monthly',
      'label' => 'CRM-21177 - Monthly',
      'amount' => 20,
      'membership_num_terms' => 1,
      'membership_type_id' => $this->_ids['membership_type'],
      'price_field_id' => $this->_ids['price_field'],
      'financial_type_id' => 'Member Dues',
    ));
    $this->_ids['price_field_value_monthly'] = $priceFieldValueMonthly['id'];

    $priceFieldValueYearly = $this->callAPISuccess('price_field_value', 'create', array(
      'name' => 'CRM-21177_Yearly',
      'label' => 'CRM-21177 - Yearly',
      'amount' => 200,
      'membership_num_terms' => 12,
      'membership_type_id' => $this->_ids['membership_type'],
      'price_field_id' => $this->_ids['price_field'],
      'financial_type_id' => 'Member Dues',
    ));
    $this->_ids['price_field_value_yearly'] = $priceFieldValueYearly['id'];

    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $this->_ids['contribution_page'], $this->_ids['price_set']);

    $this->callAPISuccess('membership_block', 'create', array(
      'entity_id' => $this->_ids['contribution_page'],
      'entity_table' => 'civicrm_contribution_page',
      'is_required' => TRUE,
      'is_separate_payment' => FALSE,
      'is_active' => TRUE,
      'membership_type_default' => $this->_ids['membership_type'],
    ));
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMultiIntervalMembershipContributionPage() {
    $this->setUpMultiIntervalMembershipContributionPage();
    $submitParams = array(
      'price_' . $this->_ids['price_field'] => $this->_ids['price_field_value_monthly'],
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 20,
      'first_name' => 'Billy',
      'last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_processor_id' => $this->_ids['payment_processor'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
      'auto_renew' => 1,
    );
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);

    $submitParams['price_' . $this->_ids['price_field']] = $this->_ids['price_field_value_yearly'];
    $this->callAPISuccess('contribution_page', 'submit', $submitParams);

    $contribution = $this->callAPISuccess('Contribution', 'get', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'sequential' => 1,
      'api.ContributionRecur.getsingle' => array(),
    ));
    $this->assertEquals(1, $contribution['values'][0]['api.ContributionRecur.getsingle']['frequency_interval']);
    //$this->assertEquals(12, $contribution['values'][1]['api.ContributionRecur.getsingle']['frequency_interval']);
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

  /**
   * Test submit recurring pledge.
   *
   * - we process 1 pledge with a future start date. A recur contribution and the pledge should be created with first payment date in the future.
   */
  public function testSubmitPledgePaymentPaymentProcessorRecurFuturePayment() {
    $this->params['adjust_recur_start_date'] = TRUE;
    $this->params['is_pay_later'] = FALSE;
    $this->setUpContributionPage();
    $this->setUpPledgeBlock();
    $this->setupPaymentProcessor();
    $dummyPP = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
    $dummyPP->setDoDirectPaymentResult(array('payment_status_id' => 1, 'trxn_id' => 'create_first_success'));

    $submitParams = array(
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 100,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
      'pledge_frequency_interval' => 1,
      'pledge_frequency_unit' => 'week',
      'pledge_installments' => 3,
      'is_pledge' => TRUE,
      'pledge_block_id' => (int) $this->_ids['pledge_block_id'],
    );

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);

    // Check if contribution created.
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      // Will be pending when actual payment processor is used (dummy processor does not support future payments).
      'contribution_status_id' => 'Completed',
    ));

    $this->assertEquals('create_first_success', $contribution['trxn_id']);

    // Check if pledge created.
    $pledge = $this->callAPISuccess('pledge', 'getsingle', array());
    $this->assertEquals(date('Ymd', strtotime($pledge['pledge_start_date'])), date('Ymd', strtotime("+1 month")));
    $this->assertEquals($pledge['pledge_amount'], 300.00);

    // Check if pledge payments created.
    $params = array(
      'pledge_id' => $pledge['id'],
    );
    $pledgePayment = $this->callAPISuccess('pledge_payment', 'get', $params);
    $this->assertEquals($pledgePayment['count'], 3);
    $this->assertEquals(date('Ymd', strtotime($pledgePayment['values'][1]['scheduled_date'])), date('Ymd', strtotime("+1 month")));
    $this->assertEquals($pledgePayment['values'][1]['scheduled_amount'], 100.00);
    // Will be pending when actual payment processor is used (dummy processor does not support future payments).
    $this->assertEquals($pledgePayment['values'][1]['status_id'], 1);

    // Check contribution recur record.
    $recur = $this->callAPISuccess('contribution_recur', 'getsingle', array('id' => $contribution['contribution_recur_id']));
    $this->assertEquals(date('Ymd', strtotime($recur['start_date'])), date('Ymd', strtotime("+1 month")));
    $this->assertEquals($recur['amount'], 100.00);
    // In progress status.
    $this->assertEquals($recur['contribution_status_id'], 5);
  }

  /**
   * Test submit pledge payment.
   *
   * - test submitting a pledge payment using contribution form.
   */
  public function testSubmitPledgePayment() {
    $this->testSubmitPledgePaymentPaymentProcessorRecurFuturePayment();
    $pledge = $this->callAPISuccess('pledge', 'getsingle', array());
    $params = array(
      'pledge_id' => $pledge['id'],
    );
    $submitParams = array(
      'id' => (int) $pledge['pledge_contribution_page_id'],
      'pledge_amount' => array(2 => 1),
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_processor_id' => 1,
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => array('M' => 9, 'Y' => 2040),
      'cvv2' => 123,
      'pledge_id' => $pledge['id'],
      'cid' => $pledge['contact_id'],
      'contact_id' => $pledge['contact_id'],
      'amount' => 100.00,
      'is_pledge' => TRUE,
      'pledge_block_id' => $this->_ids['pledge_block_id'],
    );
    $pledgePayment = $this->callAPISuccess('pledge_payment', 'get', $params);
    $this->assertEquals($pledgePayment['values'][2]['status_id'], 2);

    $this->callAPIAndDocument('contribution_page', 'submit', $submitParams, __FUNCTION__, __FILE__, 'submit contribution page', NULL);

    // Check if contribution created.
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'contribution_page_id' => $pledge['pledge_contribution_page_id'],
      'contribution_status_id' => 'Completed',
      'contact_id' => $pledge['contact_id'],
      'contribution_recur_id' => array('IS NULL' => 1),
    ));

    $this->assertEquals(100.00, $contribution['total_amount']);
    $pledgePayment = $this->callAPISuccess('pledge_payment', 'get', $params);
    $this->assertEquals($pledgePayment['values'][2]['status_id'], 1, "This pledge payment should have been completed");
    $this->assertEquals($pledgePayment['values'][2]['contribution_id'], $contribution['id']);
  }

  /**
   * Test form submission with multiple option price set.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testSubmitContributionPageWithPriceSet($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $this->_priceSetParams['is_quick_config'] = 0;
    $this->setUpContributionPage();
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 80,
      'first_name' => 'Billy',
      'last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'is_pay_later' => TRUE,
    );
    $this->addPriceFields($submitParams);

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccessGetSingle('contribution', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_status_id' => 'Pending',
    ));
    $this->assertEquals(80, $contribution['total_amount']);
    $lineItems = $this->callAPISuccess('LineItem', 'get', array(
      'contribution_id' => $contribution['id'],
    ));
    $this->assertEquals(3, $lineItems['count']);
    $totalLineAmount = 0;
    foreach ($lineItems['values'] as $lineItem) {
      $totalLineAmount = $totalLineAmount + $lineItem['line_total'];
    }
    $this->assertEquals(80, $totalLineAmount);
  }

  /**
   * Function to add additional price fields to priceset.
   * @param array $params
   */
  public function addPriceFields(&$params) {
    $priceSetID = reset($this->_ids['price_set']);
    $priceField = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => $priceSetID,
      'label' => 'Chicken Breed',
      'html_type' => 'CheckBox',
    ));
    $priceFieldValue1 = $this->callAPISuccess('price_field_value', 'create', array(
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Shoe-eating chicken -1',
      'financial_type_id' => 'Donation',
      'amount' => 30,
    ));
    $priceFieldValue2 = $this->callAPISuccess('price_field_value', 'create', array(
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Shoe-eating chicken -2',
      'financial_type_id' => 'Donation',
      'amount' => 40,
    ));
    $params['price_' . $priceField['id']] = array(
      $priceFieldValue1['id'] => 1,
      $priceFieldValue2['id'] => 1,
    );
  }

  /**
   * Test Tax Amount is calculated properly when using PriceSet with Field Type = Text/Numeric Quantity
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testSubmitContributionPageWithPriceSetQuantity($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $this->_priceSetParams['is_quick_config'] = 0;
    $this->enableTaxAndInvoicing();
    $financialType = $this->createFinancialType();
    $financialTypeId = $financialType['id'];
    // This function sets the Tax Rate at 10% - it currently has no way to pass Tax Rate into it - so let's work with 10%
    $this->relationForFinancialTypeWithFinancialAccount($financialType['id'], 5);

    $this->setUpContributionPage();
    $submitParams = array(
      'price_' . $this->_ids['price_field'][0] => reset($this->_ids['price_field_value']),
      'id' => (int) $this->_ids['contribution_page'],
      'first_name' => 'J',
      'last_name' => 'T',
      'email' => 'JT@ohcanada.ca',
      'is_pay_later' => TRUE,
      'receive_date' => date('Y-m-d H:i:s'),
    );

    // Create PriceSet/PriceField
    $priceSetID = reset($this->_ids['price_set']);
    $priceField = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => $priceSetID,
      'label' => 'Printing Rights',
      'html_type' => 'Text',
    ));
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Printing Rights',
      'financial_type_id' => $financialTypeId,
      'amount' => '16.95',
    ));
    $priceFieldId = $priceField['id'];

    // Set quantity for our test
    $submitParams['price_' . $priceFieldId] = 180;

    // contribution_page submit requires amount and tax_amount - and that's ok we're not testing that - we're testing at the LineItem level
    $submitParams['amount'] = $this->formatMoneyInput(180 * 16.95);
    // This is the correct Tax Amount - use it later to compare to what the CiviCRM Core came up with at the LineItem level
    $submitParams['tax_amount'] = $this->formatMoneyInput(180 * 16.95 * 0.10);

    $this->callAPISuccess('contribution_page', 'submit', $submitParams);
    $contribution = $this->callAPISuccessGetSingle('contribution', array(
      'contribution_page_id' => $this->_ids['contribution_page'],
    ));

    // Retrieve the lineItem that belongs to the Printing Rights and check the tax_amount CiviCRM Core calculated for it
    $lineItem = $this->callAPISuccessGetSingle('LineItem', array(
      'contribution_id' => $contribution['id'],
      'label' => 'Printing Rights',
    ));

    $lineItem_TaxAmount = round($lineItem['tax_amount'], 2);

    $this->assertEquals($lineItem['line_total'], $contribution['total_amount'], 'Contribution total should match line total');
    $this->assertEquals($lineItem_TaxAmount, round(180 * 16.95 * 0.10, 2), 'Wrong Sales Tax Amount is calculated and stored.');
  }

  /**
   * Test validating a contribution page submit.
   */
  public function testValidate() {
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
   */
  public function testValidatePost() {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $this->setUpContributionPage();
    $errors = $this->callAPISuccess('ContributionPage', 'validate', array_merge($this->getBasicSubmitParams(), ['action' => 'submit']))['values'];
    $this->assertEmpty($errors);
    unset($_SERVER['REQUEST_METHOD']);
  }

  /**
   * Test that an error is generated if required fields are not submitted.
   */
  public function testValidateOutputOnMissingRecurFields() {
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
   * @throws \Exception
   */
  public function hook_civicrm_alterPaymentProcessorParams($paymentObj, &$rawParams, &$cookedParams) {
    // Ensure total_amount are the same if they're both given.
    $total_amount = CRM_Utils_Array::value('total_amount', $rawParams);
    $amount = CRM_Utils_Array::value('amount', $rawParams);
    if (!empty($total_amount) && !empty($amount) && $total_amount != $amount) {
      throw new Exception("total_amount '$total_amount' and amount '$amount' differ.");
    }

    // Log parameters for later debugging and testing.
    $message = __FUNCTION__ . ": {$rawParams['TEST_UNIQ']}:";
    $log_params = array_intersect_key($rawParams, array(
      'amount' => 1,
      'total_amount' => 1,
      'contributionID' => 1,
    ));
    $message .= json_encode($log_params);
    $log = new CRM_Utils_SystemLogger();
    $log->debug($message, $_REQUEST);
  }

  /**
   * Get the params for a basic simple submit.
   *
   * @return array
   */
  protected function getBasicSubmitParams() {
    $priceFieldID = reset($this->_ids['price_field']);
    $priceFieldValueID = reset($this->_ids['price_field_value']);
    $submitParams = [
      'price_' . $priceFieldID => $priceFieldValueID,
      'id' => (int) $this->_ids['contribution_page'],
      'amount' => 10,
      'priceSetId' => $this->_ids['price_set'][0],
      'payment_processor_id' => 0,
    ];
    return $submitParams;
  }

}
