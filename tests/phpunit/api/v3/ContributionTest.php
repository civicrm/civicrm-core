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
class api_v3_ContributionTest extends CiviUnitTestCase {

  protected $_individualId;
  protected $_contribution;
  protected $_financialTypeId = 1;
  protected $_apiversion;
  protected $_entity = 'Contribution';
  public $debug = 0;
  protected $_params;
  protected $_ids = array();
  protected $_pageParams = array();
  /**
   * Payment processor ID (dummy processor).
   *
   * @var int
   */
  protected $paymentProcessorID;

  /**
   * Parameters to create payment processor.
   *
   * @var array
   */
  protected $_processorParams = array();

  /**
   * ID of created event.
   *
   * @var int
   */
  protected $_eventID;

  /**
   * @var CiviMailUtils
   */
  protected $mut;

  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
    $this->_params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );
    $this->_processorParams = array(
      'domain_id' => 1,
      'name' => 'Dummy',
      'payment_processor_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', 'Dummy'),
      'financial_account_id' => 12,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
      'billing_mode' => 1,
    );
    $this->paymentProcessorID = $this->processorCreate();
    $this->_pageParams = array(
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'USD',
      'financial_account_id' => 1,
      'payment_processor' => $this->paymentProcessorID,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    );
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(array('civicrm_uf_match'));
    $financialAccounts = $this->callAPISuccess('FinancialAccount', 'get', array());
    foreach ($financialAccounts['values'] as $financialAccount) {
      if ($financialAccount['name'] == 'Test Tax financial account ' || $financialAccount['name'] == 'Test taxable financial Type') {
        $entityFinancialTypes = $this->callAPISuccess('EntityFinancialAccount', 'get', array(
          'financial_account_id' => $financialAccount['id'],
        ));
        foreach ($entityFinancialTypes['values'] as $entityFinancialType) {
          $this->callAPISuccess('EntityFinancialAccount', 'delete', array('id' => $entityFinancialType['id']));
        }
        $this->callAPISuccess('FinancialAccount', 'delete', array('id' => $financialAccount['id']));
      }
    }
  }

  /**
   * Test Get.
   */
  public function testGetContribution() {
    $contributionSettings = $this->enableTaxAndInvoicing();
    $invoice_prefix = CRM_Contribute_BAO_Contribution::checkContributeSettings('invoice_prefix', TRUE);
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );
    $this->_contribution = $this->callAPISuccess('contribution', 'create', $p);

    $params = array(
      'contribution_id' => $this->_contribution['id'],
    );

    $contributions = $this->callAPIAndDocument('contribution', 'get', $params, __FUNCTION__, __FILE__);
    $financialParams['id'] = $this->_financialTypeId;
    $default = NULL;
    CRM_Financial_BAO_FinancialType::retrieve($financialParams, $default);

    $this->assertEquals(1, $contributions['count']);
    $contribution = $contributions['values'][$contributions['id']];
    $this->assertEquals($contribution['contact_id'], $this->_individualId);
    // Note there was an assertion converting financial_type_id to 'Donation' which wasn't working.
    // Passing back a string rather than an id seems like an error/cruft.
    // If it is to be introduced we should discuss.
    $this->assertEquals($contribution['financial_type_id'], 1);
    $this->assertEquals($contribution['total_amount'], 100.00);
    $this->assertEquals($contribution['non_deductible_amount'], 10.00);
    $this->assertEquals($contribution['fee_amount'], 5.00);
    $this->assertEquals($contribution['net_amount'], 95.00);
    $this->assertEquals($contribution['trxn_id'], 23456);
    $this->assertEquals($contribution['invoice_id'], 78910);
    $this->assertEquals($contribution['invoice_number'], $invoice_prefix . $contributions['id']);
    $this->assertEquals($contribution['contribution_source'], 'SSF');
    $this->assertEquals($contribution['contribution_status'], 'Completed');
    // Create a second contribution - we are testing that 'id' gets the right contribution id (not the contact id).
    $p['trxn_id'] = '3847';
    $p['invoice_id'] = '3847';

    $contribution2 = $this->callAPISuccess('contribution', 'create', $p);

    // Now we have 2 - test getcount.
    $contribution = $this->callAPISuccess('contribution', 'getcount', array());
    $this->assertEquals(2, $contribution);
    // Test id only format.
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'id' => $this->_contribution['id'],
      'format.only_id' => 1,
    ));
    $this->assertEquals($this->_contribution['id'], $contribution, print_r($contribution, TRUE));
    // Test id only format.
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'id' => $contribution2['id'],
      'format.only_id' => 1,
    ));
    $this->assertEquals($contribution2['id'], $contribution);
    // Test id as field.
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'id' => $this->_contribution['id'],
    ));
    $this->assertEquals(1, $contribution['count']);

    // Test get by contact id works.
    $contribution = $this->callAPISuccess('contribution', 'get', array('contact_id' => $this->_individualId));

    $this->assertEquals(2, $contribution['count']);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $this->_contribution['id'],
    ));
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution2['id'],
    ));
  }

  /**
   * Test that test contributions can be retrieved.
   */
  public function testGetTestContribution() {
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, array('is_test' => 1)));
    $this->callAPISuccessGetSingle('Contribution', array('is_test' => 1));
  }

  /**
   * Test the 'return' param works for all fields.
   */
  public function testGetContributionReturnFunctionality() {
    $params = $this->_params;
    $params['check_number'] = 'bouncer';
    $params['payment_instrument_id'] = 'Check';
    $params['cancel_date'] = 'yesterday';
    $params['receipt_date'] = 'yesterday';
    $params['thankyou_date'] = 'yesterday';
    $params['revenue_recognition_date'] = 'yesterday';
    $params['amount_level'] = 'Unreasonable';
    $params['cancel_reason'] = 'You lose sucker';
    $params['creditnote_id'] = 'sudo rm -rf';
    $params['tax_amount'] = '1';
    $address = $this->callAPISuccess('Address', 'create', array(
      'street_address' => 'Knockturn Alley',
      'contact_id' => $this->_individualId,
      'location_type_id' => 'Home',
    ));
    $params['address_id'] = $address['id'];
    $contributionPage = $this->contributionPageCreate();
    $params['contribution_page_id'] = $contributionPage['id'];
    $contributionRecur = $this->callAPISuccess('ContributionRecur', 'create', array(
      'contact_id' => $this->_individualId,
      'frequency_interval' => 1,
      'amount' => 5,
    ));
    $params['contribution_recur_id'] = $contributionRecur['id'];

    $params['campaign_id'] = $this->campaignCreate();

    $contributionID = $this->contributionCreate($params);

    // update contribution with invoice number
    $params = array_merge($params, array(
      'id' => $contributionID,
      'invoice_number' => CRM_Utils_Array::value('invoice_prefix', Civi::settings()->get('contribution_invoice_settings')) . "" . $contributionID,
      'trxn_id' => 12345,
      'invoice_id' => 6789,
    ));
    $contributionID = $this->contributionCreate($params);

    $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $contributionID));
    $this->assertEquals('bouncer', $contribution['check_number']);
    $this->assertEquals('bouncer', $contribution['contribution_check_number']);

    $fields = CRM_Contribute_BAO_Contribution::fields();
    // Re-add these 2 to the fields to check. They were locked in but the metadata changed so we
    // need to specify them.
    $fields['address_id'] = $fields['contribution_address_id'];
    $fields['check_number'] = $fields['contribution_check_number'];

    $fieldsLockedIn = array(
      'contribution_id', 'contribution_contact_id', 'financial_type_id', 'contribution_page_id',
      'payment_instrument_id', 'receive_date', 'non_deductible_amount', 'total_amount',
      'fee_amount', 'net_amount', 'trxn_id', 'invoice_id', 'currency', 'cancel_date', 'cancel_reason',
      'receipt_date', 'thankyou_date', 'contribution_source', 'amount_level', 'contribution_recur_id',
      'is_test', 'is_pay_later', 'contribution_status_id', 'address_id', 'check_number', 'contribution_campaign_id',
      'creditnote_id', 'tax_amount', 'revenue_recognition_date', 'decoy',
    );
    $missingFields = array_diff($fieldsLockedIn, array_keys($fields));
    // If any of the locked in fields disappear from the $fields array we need to make sure it is still
    // covered as the test contract now guarantees them in the return array.
    $this->assertEquals($missingFields, array(29 => 'decoy'), 'A field which was covered by the test contract has changed.');
    foreach ($fields as $fieldName => $fieldSpec) {
      $contribution = $this->callAPISuccessGetSingle('Contribution', array('id' => $contributionID, 'return' => $fieldName));
      $returnField = $fieldName;
      if ($returnField == 'contribution_contact_id') {
        $returnField = 'contact_id';
      }
      $this->assertTrue((!empty($contribution[$returnField]) || $contribution[$returnField] === "0"), $returnField);
    }
  }

  /**
   * Test cancel reason works as a filter.
   */
  public function testFilterCancelReason() {
    $params = $this->_params;
    $params['cancel_date'] = 'yesterday';
    $params['cancel_reason'] = 'You lose sucker';
    $this->callAPISuccess('Contribution', 'create', $params);
    $params = $this->_params;
    $params['cancel_date'] = 'yesterday';
    $params['cancel_reason'] = 'You are a winner';
    $this->callAPISuccess('Contribution', 'create', $params);
    $this->callAPISuccessGetCount('Contribution', ['cancel_reason' => 'You are a winner'], 1);
  }

  /**
   * We need to ensure previous tested behaviour still works as part of the api contract.
   */
  public function testGetContributionLegacyBehaviour() {
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );
    $this->_contribution = $this->callAPISuccess('Contribution', 'create', $p);

    $params = array(
      'contribution_id' => $this->_contribution['id'],
    );
    $contribution = $this->callAPISuccess('contribution', 'get', $params);
    $financialParams['id'] = $this->_financialTypeId;
    $default = NULL;
    CRM_Financial_BAO_FinancialType::retrieve($financialParams, $default);

    $this->assertEquals(1, $contribution['count']);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], $this->_financialTypeId);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_type_id'], $this->_financialTypeId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['non_deductible_amount'], 10.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['fee_amount'], 5.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['net_amount'], 95.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 23456);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 78910);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');

    // Create a second contribution - we are testing that 'id' gets the right contribution id (not the contact id).
    $p['trxn_id'] = '3847';
    $p['invoice_id'] = '3847';

    $contribution2 = $this->callAPISuccess('contribution', 'create', $p);

    // now we have 2 - test getcount
    $contribution = $this->callAPISuccess('contribution', 'getcount', array());
    $this->assertEquals(2, $contribution);
    //test id only format
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'id' => $this->_contribution['id'],
      'format.only_id' => 1,
    ));
    $this->assertEquals($this->_contribution['id'], $contribution, print_r($contribution, TRUE));
    //test id only format
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'id' => $contribution2['id'],
      'format.only_id' => 1,
    ));
    $this->assertEquals($contribution2['id'], $contribution);
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'id' => $this->_contribution['id'],
    ));
    //test id as field
    $this->assertEquals(1, $contribution['count']);
    // $this->assertEquals($this->_contribution['id'], $contribution['id'] )  ;
    //test get by contact id works
    $contribution = $this->callAPISuccess('contribution', 'get', array('contact_id' => $this->_individualId));

    $this->assertEquals(2, $contribution['count']);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $this->_contribution['id'],
    ));
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution2['id'],
    ));
  }

  /**
   * Create an contribution_id=FALSE and financial_type_id=Donation.
   */
  public function testCreateEmptyContributionIDUseDonation() {
    $params = array(
      'contribution_id' => FALSE,
      'contact_id' => 1,
      'total_amount' => 1,
      'check_permissions' => FALSE,
      'financial_type_id' => 'Donation',
    );
    $this->callAPISuccess('contribution', 'create', $params);
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);
    $check = $this->callAPISuccess($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'id' => $result['id'],
    ));
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']]);
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testCreateGetFieldsWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $idsContact = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTest.php');
    $result = $this->callAPISuccess('Contribution', 'getfields', array());
    $this->assertArrayHasKey('custom_' . $ids['custom_field_id'], $result['values']);
    $this->assertArrayNotHasKey('custom_' . $idsContact['custom_field_id'], $result['values']);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->customFieldDelete($idsContact['custom_field_id']);
    $this->customGroupDelete($idsContact['custom_group_id']);
  }

  public function testCreateContributionNoLineItems() {

    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'skipLineItem' => 1,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $lineItems = $this->callAPISuccess('line_item', 'get', array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(0, $lineItems['count']);
  }

  /**
   * Test checks that passing in line items suppresses the create mechanism.
   */
  public function testCreateContributionChainedLineItems() {
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'skipLineItem' => 1,
      'api.line_item.create' => array(
        array(
          'price_field_id' => 1,
          'qty' => 2,
          'line_total' => '20',
          'unit_price' => '10',
        ),
        array(
          'price_field_id' => 1,
          'qty' => 1,
          'line_total' => '80',
          'unit_price' => '80',
        ),
      ),
    );

    $description = "Create Contribution with Nested Line Items.";
    $subfile = "CreateWithNestedLineItems";
    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);

    $lineItems = $this->callAPISuccess('line_item', 'get', array(
      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(2, $lineItems['count']);
  }

  public function testCreateContributionOffline() {
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1);
    $lineItems = $this->callAPISuccess('line_item', 'get', array(
      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(1, $lineItems['count']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['contribution_id']);
    $this->_checkFinancialRecords($contribution, 'offline');
    $this->contributionGetnCheck($params, $contribution['id']);
  }

  /**
   * Test create with valid payment instrument.
   */
  public function testCreateContributionWithPaymentInstrument() {
    $params = $this->_params + array('payment_instrument' => 'EFT');
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'sequential' => 1,
      'id' => $contribution['id'],
    ));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT', $contribution['values'][0]['payment_instrument']);

    $this->callAPISuccess('contribution', 'create', array(
      'id' => $contribution['id'],
      'payment_instrument' => 'Credit Card',
    ));
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'sequential' => 1,
      'id' => $contribution['id'],
    ));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Credit Card', $contribution['values'][0]['payment_instrument']);
  }

  public function testGetContributionByPaymentInstrument() {
    $params = $this->_params + array('payment_instrument' => 'EFT');
    $params2 = $this->_params + array('payment_instrument' => 'Cash');
    $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'create', $params2);
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'sequential' => 1,
      'contribution_payment_instrument' => 'Cash',
    ));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Cash', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('contribution', 'get', array('sequential' => 1, 'payment_instrument' => 'Cash'));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Cash', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'sequential' => 1,
      'payment_instrument_id' => 5,
    ));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'sequential' => 1,
      'payment_instrument' => 'EFT',
    ));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('contribution', 'create', array(
      'id' => $contribution['id'],
      'payment_instrument' => 'Credit Card',
    ));
    $contribution = $this->callAPISuccess('contribution', 'get', array('sequential' => 1, 'id' => $contribution['id']));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Credit Card', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
  }

  /**
   * CRM-16227 introduces invoice_id as a parameter.
   */
  public function testGetContributionByInvoice() {
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, array('invoice_id' => 'curly')));
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params), array('invoice_id' => 'churlish'));
    $this->callAPISuccessGetCount('Contribution', array(), 2);
    $this->callAPISuccessGetSingle('Contribution', array('invoice_id' => 'curly'));
    // The following don't work. They are the format we are trying to introduce but although the form uses this format
    // CRM_Contact_BAO_Query::convertFormValues puts them into the other format & the where only supports that.
    // ideally the where clause would support this format (as it does on contact_BAO_Query) and those lines would
    // come out of convertFormValues
    // $this->callAPISuccessGetSingle('Contribution', array('invoice_id' => array('LIKE' => '%ish%')));
    // $this->callAPISuccessGetSingle('Contribution', array('invoice_id' => array('NOT IN' => array('curly'))));
    // $this->callAPISuccessGetCount('Contribution', array('invoice_id' => array('LIKE' => '%ly%')), 2);
    // $this->callAPISuccessGetCount('Contribution', array('invoice_id' => array('IN' => array('curly', 'churlish'))),
    // 2);
  }

  /**
   * Check the credit note retrieval is case insensitive.
   */
  public function testGetCreditNoteCaseInsensitive() {
    $this->contributionCreate(array('contact_id' => $this->_individualId));
    $this->contributionCreate(array('creditnote_id' => 'cN1234', 'contact_id' => $this->_individualId, 'invoice_id' => rand(), 'trxn_id' => rand()));
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', array('creditnote_id' => 'CN1234'));
    $this->assertEquals($contribution['creditnote_id'], 'cN1234');
  }

  /**
   * Test retrieval by total_amount works.
   *
   * @throws Exception
   */
  public function testGetContributionByTotalAmount() {
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, array('total_amount' => '5')));
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, array('total_amount' => '10')));
    $this->callAPISuccessGetCount('Contribution', array('total_amount' => 10), 1);
    $this->callAPISuccessGetCount('Contribution', array('total_amount' => array('>' => 6)), 1);
    $this->callAPISuccessGetCount('Contribution', array('total_amount' => array('>' => 0)), 2);
    $this->callAPISuccessGetCount('Contribution', array('total_amount' => array('>' => -5)), 2);
    $this->callAPISuccessGetCount('Contribution', array('total_amount' => array('<' => 0)), 0);
    $this->callAPISuccessGetCount('Contribution', array(), 2);
  }

  /**
   * Create test with unique field name on source.
   */
  public function testCreateContributionSource() {

    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'contribution_source' => 'SSF',
      'contribution_status_id' => 1,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
  }

  /**
   * Create test with unique field name on source.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testCreateDefaultNow($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $params = $this->_params;
    unset($params['receive_date'], $params['net_amount']);

    $params['total_amount'] = $this->formatMoneyInput(5000.77);
    $params['fee_amount'] = $this->formatMoneyInput(.77);
    $params['skipCleanMoney'] = FALSE;

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->callAPISuccessGetSingle('contribution', array('id' => $contribution['id']));
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contribution['receive_date'])));
    $this->assertEquals(5000.77, $contribution['total_amount'], 'failed to handle ' . $this->formatMoneyInput(5000.77));
    $this->assertEquals(.77, $contribution['fee_amount']);
    $this->assertEquals(5000, $contribution['net_amount']);
  }

  /**
   * Create test with unique field name on source.
   */
  public function testCreateContributionNullOutThankyouDate() {

    $params = $this->_params;
    $params['thankyou_date'] = 'yesterday';

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->callAPISuccessGetSingle('contribution', array('id' => $contribution['id']));
    $this->assertEquals(date('Y-m-d', strtotime('yesterday')), date('Y-m-d', strtotime($contribution['thankyou_date'])));

    $params['thankyou_date'] = 'null';
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->assertTrue(empty($contribution['thankyou_date']));
  }

  /**
   * Create test with unique field name on source.
   */
  public function testCreateContributionSourceInvalidContact() {

    $params = array(
      'contact_id' => 999,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'contribution_source' => 'SSF',
      'contribution_status_id' => 1,
    );

    $this->callAPIFailure('contribution', 'create', $params, 'contact_id is not valid : 999');
  }

  public function testCreateContributionSourceInvalidContContact() {

    $params = array(
      'contribution_contact_id' => 999,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'contribution_source' => 'SSF',
      'contribution_status_id' => 1,
    );

    $this->callAPIFailure('contribution', 'create', $params);
  }

  /**
   * Test note created correctly.
   */
  public function testCreateContributionWithNote() {
    $description = "Demonstrates creating contribution with Note Entity.";
    $subfile = "ContributionCreateWithNote";
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'note' => 'my contribution note',
    );

    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $this->callAPISuccess('note', 'get', array(
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'sequential' => 1,
    ));
    $this->assertEquals('my contribution note', $result['values'][0]['note']);
    $this->callAPISuccess('contribution', 'delete', array('id' => $contribution['id']));
  }

  public function testCreateContributionWithNoteUniqueNameAliases() {
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'contribution_note' => 'my contribution note',
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $result = $this->callAPISuccess('note', 'get', array(
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'sequential' => 1,
    ));
    $this->assertEquals('my contribution note', $result['values'][0]['note']);
    $this->callAPISuccess('contribution', 'delete', array('id' => $contribution['id']));
  }

  /**
   * This is the test for creating soft credits.
   */
  public function testCreateContributionWithSoftCredit() {
    $description = "Demonstrates creating contribution with SoftCredit.";
    $subfile = "ContributionCreateWithSoftCredit";
    $contact2 = $this->callAPISuccess('Contact', 'create', array(
      'display_name' => 'superman',
      'contact_type' => 'Individual',
    ));
    $softParams = array(
      'contact_id' => $contact2['id'],
      'amount' => 50,
      'soft_credit_type_id' => 3,
    );

    $params = $this->_params + array('soft_credit' => array(1 => $softParams));
    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $this->callAPISuccess('contribution', 'get', array('return' => 'soft_credit', 'sequential' => 1));

    $this->assertEquals($softParams['contact_id'], $result['values'][0]['soft_credit'][1]['contact_id']);
    $this->assertEquals($softParams['amount'], $result['values'][0]['soft_credit'][1]['amount']);
    $this->assertEquals($softParams['soft_credit_type_id'], $result['values'][0]['soft_credit'][1]['soft_credit_type']);

    $this->callAPISuccess('contribution', 'delete', array('id' => $contribution['id']));
    $this->callAPISuccess('contact', 'delete', array('id' => $contact2['id']));
  }

  public function testCreateContributionWithSoftCreditDefaults() {
    $description = "Demonstrates creating contribution with Soft Credit defaults for amount and type.";
    $subfile = "ContributionCreateWithSoftCreditDefaults";
    $contact2 = $this->callAPISuccess('Contact', 'create', array(
      'display_name' => 'superman',
      'contact_type' => 'Individual',
    ));
    $params = $this->_params + array(
      'soft_credit_to' => $contact2['id'],
    );
    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $this->callAPISuccess('contribution', 'get', array('return' => 'soft_credit', 'sequential' => 1));

    $this->assertEquals($contact2['id'], $result['values'][0]['soft_credit'][1]['contact_id']);
    // Default soft credit amount = contribution.total_amount
    $this->assertEquals($this->_params['total_amount'], $result['values'][0]['soft_credit'][1]['amount']);
    $this->assertEquals(CRM_Core_OptionGroup::getDefaultValue("soft_credit_type"), $result['values'][0]['soft_credit'][1]['soft_credit_type']);

    $this->callAPISuccess('contribution', 'delete', array('id' => $contribution['id']));
    $this->callAPISuccess('contact', 'delete', array('id' => $contact2['id']));
  }

  /**
   * Test creating contribution with Soft Credit by passing in honor_contact_id.
   */
  public function testCreateContributionWithHonoreeContact() {
    $description = "Demonstrates creating contribution with Soft Credit by passing in honor_contact_id.";
    $subfile = "ContributionCreateWithHonoreeContact";
    $contact2 = $this->callAPISuccess('Contact', 'create', array(
      'display_name' => 'superman',
      'contact_type' => 'Individual',
    ));
    $params = $this->_params + array(
      'honor_contact_id' => $contact2['id'],
    );
    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $this->callAPISuccess('contribution', 'get', array('return' => 'soft_credit', 'sequential' => 1));

    $this->assertEquals($contact2['id'], $result['values'][0]['soft_credit'][1]['contact_id']);
    // Default soft credit amount = contribution.total_amount
    // Legacy mode in create api (honor_contact_id param) uses the standard "In Honor of" soft credit type
    $this->assertEquals($this->_params['total_amount'], $result['values'][0]['soft_credit'][1]['amount']);
    $softCreditValueTypeID = $result['values'][0]['soft_credit'][1]['soft_credit_type'];
    $this->assertEquals('in_honor_of', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $softCreditValueTypeID));

    $this->callAPISuccess('contribution', 'delete', array('id' => $contribution['id']));
    $this->callAPISuccess('contact', 'delete', array('id' => $contact2['id']));
  }

  /**
   * Test using example code.
   */
  public function testContributionCreateExample() {
    //make sure at least on page exists since there is a truncate in tear down
    $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    require_once 'api/v3/examples/Contribution/Create.php';
    $result = contribution_create_example();
    $id = $result['id'];
    $expectedResult = contribution_create_expectedresult();
    $this->checkArrayEquals($expectedResult, $result);
    $this->contributionDelete($id);
  }

  /**
   * Function tests that additional financial records are created when fee amount is recorded.
   */
  public function testCreateContributionWithFee() {
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'fee_amount' => 50,
      'financial_type_id' => 1,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['fee_amount'], 50.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['net_amount'], 50.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1);

    $lineItems = $this->callAPISuccess('line_item', 'get', array(

      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(1, $lineItems['count']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['contribution_id']);
    $lineItems = $this->callAPISuccess('line_item', 'get', array(

      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(1, $lineItems['count']);
    $this->_checkFinancialRecords($contribution, 'feeAmount');
  }

  /**
   * Function tests that additional financial records are created when online contribution is created.
   */
  public function testCreateContributionOnline() {
    CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    $this->assertAPISuccess($contributionPage);
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'contribution_page_id' => $contributionPage['id'],
      'payment_processor' => $this->paymentProcessorID,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,

    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1);
    $contribution['payment_instrument_id'] = $this->callAPISuccessGetValue('PaymentProcessor', array(
      'id' => $this->paymentProcessorID,
      'return' => 'payment_instrument_id',
    ));
    $this->_checkFinancialRecords($contribution, 'online');
  }

  /**
   * Check handling of financial type.
   *
   * In the interests of removing financial type / contribution type checks from
   * legacy format function lets test that the api is doing this for us
   */
  public function testCreateInvalidFinancialType() {
    $params = $this->_params;
    $params['financial_type_id'] = 99999;
    $this->callAPIFailure($this->_entity, 'create', $params, "'99999' is not a valid option for field financial_type_id");
  }

  /**
   * Check handling of financial type.
   *
   * In the interests of removing financial type / contribution type checks from
   * legacy format function lets test that the api is doing this for us
   */
  public function testValidNamedFinancialType() {
    $params = $this->_params;
    $params['financial_type_id'] = 'Donation';
    $this->callAPISuccess($this->_entity, 'create', $params);
  }

  /**
   * Tests that additional financial records are created.
   *
   * Checks when online contribution with pay later option is created
   */
  public function testCreateContributionPayLaterOnline() {
    CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $this->_pageParams['is_pay_later'] = 1;
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    $this->assertAPISuccess($contributionPage);
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'contribution_page_id' => $contributionPage['id'],
      'trxn_id' => 12345,
      'is_pay_later' => 1,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 2,

    );

    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 2);
    $this->_checkFinancialRecords($contribution, 'payLater');
  }

  /**
   * Function tests that additional financial records are created for online contribution with pending option.
   */
  public function testCreateContributionPendingOnline() {
    CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    $this->assertAPISuccess($contributionPage);
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'contribution_page_id' => $contributionPage['id'],
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 2,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 2);
    $this->_checkFinancialRecords($contribution, 'pending');
  }

  /**
   * Test that BAO defaults work.
   */
  public function testCreateBAODefaults() {
    unset($this->_params['contribution_source_id'], $this->_params['payment_instrument_id']);
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'id' => $contribution['id'],
      'api.contribution.delete' => 1,
    ));
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('Check', $contribution['payment_instrument']);
    $this->callAPISuccessGetCount('Contribution', ['id' => $contribution['id']], 0);
  }

  /**
   * Test that getsingle can be chained with delete.
   */
  public function testDeleteChainedGetSingle() {
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'id' => $contribution['id'],
      'api.contribution.delete' => 1,
    ));
    $this->callAPISuccessGetCount('Contribution', ['id' => $contribution['id']], 0);
  }

  /**
   * Function tests that line items, financial records are updated when contribution amount is changed.
   */
  public function testCreateUpdateContributionChangeTotal() {
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $lineItems = $this->callAPISuccess('line_item', 'getvalue', array(

      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => 'line_total',
    ));
    $this->assertEquals('100.00', $lineItems);
    $trxnAmount = $this->_getFinancialTrxnAmount($contribution['id']);
    // Financial trxn SUM = 100 + 5 (fee)
    $this->assertEquals('105.00', $trxnAmount);
    $newParams = array(

      'id' => $contribution['id'],
      'total_amount' => '125',
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);

    $lineItems = $this->callAPISuccess('line_item', 'getvalue', array(

      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => 'line_total',
    ));

    $this->assertEquals('125.00', $lineItems);
    $trxnAmount = $this->_getFinancialTrxnAmount($contribution['id']);

    // Financial trxn SUM = 125 + 5 (fee).
    $this->assertEquals('130.00', $trxnAmount);
    $this->assertEquals('125.00', $this->_getFinancialItemAmount($contribution['id']));
  }

  /**
   * Function tests that line items, financial records are updated when pay later contribution is received.
   */
  public function testCreateUpdateContributionPayLater() {
    $contribParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 2,
      'is_pay_later' => 1,

    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);

    $newParams = array_merge($contribParams, array(
      'id' => $contribution['id'],
      'contribution_status_id' => 1,
    ));
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $contribution = $contribution['values'][$contribution['id']];
    $this->assertEquals($contribution['contribution_status_id'], '1');
    $this->_checkFinancialItem($contribution['id'], 'paylater');
    $this->_checkFinancialTrxn($contribution, 'payLater');
  }

  /**
   * Function tests that financial records are updated when Payment Instrument is changed.
   */
  public function testCreateUpdateContributionPaymentInstrument() {
    $instrumentId = $this->_addPaymentInstrument();
    $contribParams = array(
      'contact_id' => $this->_individualId,
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,

    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);

    $newParams = array_merge($contribParams, array(
      'id' => $contribution['id'],
      'payment_instrument_id' => $instrumentId,
    ));
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->assertAPISuccess($contribution);
    $this->checkFinancialTrxnPaymentInstrumentChange($contribution['id'], 4, $instrumentId);

    // cleanup - delete created payment instrument
    $this->_deletedAddedPaymentInstrument();
  }

  /**
   * Function tests that financial records are updated when Payment Instrument is changed when amount is negative.
   */
  public function testCreateUpdateNegativeContributionPaymentInstrument() {
    $instrumentId = $this->_addPaymentInstrument();
    $contribParams = array(
      'contact_id' => $this->_individualId,
      'total_amount' => -100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,

    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);

    $newParams = array_merge($contribParams, array(
      'id' => $contribution['id'],
      'payment_instrument_id' => $instrumentId,
    ));
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->assertAPISuccess($contribution);
    $this->checkFinancialTrxnPaymentInstrumentChange($contribution['id'], 4, $instrumentId, -100);

    // cleanup - delete created payment instrument
    $this->_deletedAddedPaymentInstrument();
  }

  /**
   * Function tests that financial records are added when Contribution is Refunded.
   */
  public function testCreateUpdateContributionRefund() {
    $contributionParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'trxn_id' => 'original_payment',
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $newParams = array_merge($contributionParams, array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'refund_trxn_id' => 'the refund',
    ));

    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'refund');
    $this->_checkFinancialItem($contribution['id'], 'refund');
    $this->assertEquals('original_payment', $this->callAPISuccessGetValue('Contribution', array(
      'id' => $contribution['id'],
      'return' => 'trxn_id',
    )));
  }

  /**
   * Refund a contribution for a financial type with a contra account.
   *
   * CRM-17951 the contra account is a financial account with a relationship to a
   * financial type. It is not always configured but should be reflected
   * in the financial_trxn & financial_item table if it is.
   */
  public function testCreateUpdateChargebackContributionDefaultAccount() {
    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);
    $this->callAPISuccess('Contribution', 'create', array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Chargeback',
    ));
    $this->callAPISuccessGetSingle('Contribution', array('contribution_status_id' => 'Chargeback'));

    $lineItems = $this->callAPISuccessGetSingle('LineItem', array(
      'contribution_id' => $contribution['id'],
      'api.FinancialItem.getsingle' => array('amount' => array('<' => 0)),
    ));
    $this->assertEquals(1, $lineItems['api.FinancialItem.getsingle']['financial_account_id']);
    $this->callAPISuccessGetSingle('FinancialTrxn', array(
      'total_amount' => -100,
      'status_id' => 'Chargeback',
      'to_financial_account_id' => 6,
    ));
  }

  /**
   * Refund a contribution for a financial type with a contra account.
   *
   * CRM-17951 the contra account is a financial account with a relationship to a
   * financial type. It is not always configured but should be reflected
   * in the financial_trxn & financial_item table if it is.
   */
  public function testCreateUpdateChargebackContributionCustomAccount() {
    $financialAccount = $this->callAPISuccess('FinancialAccount', 'create', array(
      'name' => 'Chargeback Account',
      'is_active' => TRUE,
    ));

    $entityFinancialAccount = $this->callAPISuccess('EntityFinancialAccount', 'create', array(
      'entity_id' => $this->_financialTypeId,
      'entity_table' => 'civicrm_financial_type',
      'account_relationship' => 'Chargeback Account is',
      'financial_account_id' => 'Chargeback Account',
    ));

    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);
    $this->callAPISuccess('Contribution', 'create', array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Chargeback',
    ));
    $this->callAPISuccessGetSingle('Contribution', array('contribution_status_id' => 'Chargeback'));

    $lineItems = $this->callAPISuccessGetSingle('LineItem', array(
      'contribution_id' => $contribution['id'],
      'api.FinancialItem.getsingle' => array('amount' => array('<' => 0)),
    ));
    $this->assertEquals($financialAccount['id'], $lineItems['api.FinancialItem.getsingle']['financial_account_id']);

    $this->callAPISuccess('Contribution', 'delete', array('id' => $contribution['id']));
    $this->callAPISuccess('EntityFinancialAccount', 'delete', array('id' => $entityFinancialAccount['id']));
    $this->callAPISuccess('FinancialAccount', 'delete', array('id' => $financialAccount['id']));
  }

  /**
   * Refund a contribution for a financial type with a contra account.
   *
   * CRM-17951 the contra account is a financial account with a relationship to a
   * financial type. It is not always configured but should be reflected
   * in the financial_trxn & financial_item table if it is.
   */
  public function testCreateUpdateRefundContributionConfiguredContraAccount() {
    $financialAccount = $this->callAPISuccess('FinancialAccount', 'create', array(
      'name' => 'Refund Account',
      'is_active' => TRUE,
    ));

    $entityFinancialAccount = $this->callAPISuccess('EntityFinancialAccount', 'create', array(
      'entity_id' => $this->_financialTypeId,
      'entity_table' => 'civicrm_financial_type',
      'account_relationship' => 'Credit/Contra Revenue Account is',
      'financial_account_id' => 'Refund Account',
    ));

    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);
    $this->callAPISuccess('Contribution', 'create', array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
    ));

    $lineItems = $this->callAPISuccessGetSingle('LineItem', array(
      'contribution_id' => $contribution['id'],
      'api.FinancialItem.getsingle' => array('amount' => array('<' => 0)),
    ));
    $this->assertEquals($financialAccount['id'], $lineItems['api.FinancialItem.getsingle']['financial_account_id']);

    $this->callAPISuccess('Contribution', 'delete', array('id' => $contribution['id']));
    $this->callAPISuccess('EntityFinancialAccount', 'delete', array('id' => $entityFinancialAccount['id']));
    $this->callAPISuccess('FinancialAccount', 'delete', array('id' => $financialAccount['id']));
  }

  /**
   * Function tests that trxn_id is set when passed in.
   *
   * Here we ensure that the civicrm_financial_trxn.trxn_id & the civicrm_contribution.trxn_id are set
   * when trxn_id is passed in.
   */
  public function testCreateUpdateContributionRefundTrxnIDPassedIn() {
    $contributionParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'trxn_id' => 'original_payment',
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $newParams = array_merge($contributionParams, array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'trxn_id' => 'the refund',
    ));

    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'refund');
    $this->_checkFinancialItem($contribution['id'], 'refund');
    $this->assertEquals('the refund', $this->callAPISuccessGetValue('Contribution', array(
      'id' => $contribution['id'],
      'return' => 'trxn_id',
    )));
  }

  /**
   * Function tests that trxn_id is set when passed in.
   *
   * Here we ensure that the civicrm_contribution.trxn_id is set
   * when trxn_id is passed in but if refund_trxn_id is different then that
   * is kept for the refund transaction.
   */
  public function testCreateUpdateContributionRefundRefundAndTrxnIDPassedIn() {
    $contributionParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'trxn_id' => 'original_payment',
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $newParams = array_merge($contributionParams, array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'trxn_id' => 'cont id',
      'refund_trxn_id' => 'the refund',
    ));

    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'refund');
    $this->_checkFinancialItem($contribution['id'], 'refund');
    $this->assertEquals('cont id', $this->callAPISuccessGetValue('Contribution', array(
      'id' => $contribution['id'],
      'return' => 'trxn_id',
    )));
  }

  /**
   * Function tests that refund_trxn_id is set when passed in empty.
   *
   * Here we ensure that the civicrm_contribution.trxn_id is set
   * when trxn_id is passed in but if refund_trxn_id isset but empty then that
   * is kept for the refund transaction.
   */
  public function testCreateUpdateContributionRefundRefundNullTrxnIDPassedIn() {
    $contributionParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'trxn_id' => 'original_payment',
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $newParams = array_merge($contributionParams, array(
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'trxn_id' => 'cont id',
      'refund_trxn_id' => '',
    ));

    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'refund', NULL, array('trxn_id' => NULL));
    $this->_checkFinancialItem($contribution['id'], 'refund');
    $this->assertEquals('cont id', $this->callAPISuccessGetValue('Contribution', array(
      'id' => $contribution['id'],
      'return' => 'trxn_id',
    )));
  }

  /**
   * Function tests invalid contribution status change.
   */
  public function testCreateUpdateContributionInValidStatusChange() {
    $contribParams = array(
      'contact_id' => 1,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 1,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, array(
      'id' => $contribution['id'],
      'contribution_status_id' => 2,
    ));
    $this->callAPIFailure('contribution', 'create', $newParams, ts('Cannot change contribution status from Completed to Pending.'));

  }

  /**
   * Function tests that financial records are added when Pending Contribution is Canceled.
   */
  public function testCreateUpdateContributionCancelPending() {
    $contribParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 2,
      'is_pay_later' => 1,

    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, array(
      'id' => $contribution['id'],
      'contribution_status_id' => 3,
      'cancel_date' => '2012-02-02 09:00',
    ));
    //Check if trxn_date is same as cancel_date.
    $checkTrxnDate = array(
      'trxn_date' => '2012-02-02 09:00:00',
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'cancelPending', NULL, $checkTrxnDate);
    $this->_checkFinancialItem($contribution['id'], 'cancelPending');
  }

  /**
   * Function tests that financial records are added when Financial Type is Changed.
   */
  public function testCreateUpdateContributionChangeFinancialType() {
    $contribParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 1,

    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, array(
      'id' => $contribution['id'],
      'financial_type_id' => 3,
    ));
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'changeFinancial');
    $this->_checkFinancialItem($contribution['id'], 'changeFinancial');
  }

  /**
   * Test that update does not change status id CRM-15105.
   */
  public function testCreateUpdateWithoutChangingPendingStatus() {
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge($this->_params, array('contribution_status_id' => 2)));
    $this->callAPISuccess('contribution', 'create', array('id' => $contribution['id'], 'source' => 'new source'));
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array(
      'id' => $contribution['id'],
      'api.contribution.delete' => 1,
    ));
    $this->assertEquals(2, $contribution['contribution_status_id']);
  }

  /**
   * Test Updating a Contribution.
   *
   * CHANGE: we require the API to do an incremental update
   */
  public function testCreateUpdateContribution() {
    $contributionID = $this->contributionCreate(array(
      'contact_id' => $this->_individualId,
      'trxn_id' => 212355,
      'financial_type_id' => $this->_financialTypeId,
      'invoice_id' => 'old_invoice',
    ));
    $old_params = array(
      'contribution_id' => $contributionID,
    );
    $original = $this->callAPISuccess('contribution', 'get', $old_params);
    $this->assertEquals($original['id'], $contributionID);
    //set up list of old params, verify

    //This should not be required on update:
    $old_contact_id = $original['values'][$contributionID]['contact_id'];
    $old_payment_instrument = $original['values'][$contributionID]['instrument_id'];
    $old_fee_amount = $original['values'][$contributionID]['fee_amount'];
    $old_source = $original['values'][$contributionID]['contribution_source'];

    $old_trxn_id = $original['values'][$contributionID]['trxn_id'];
    $old_invoice_id = $original['values'][$contributionID]['invoice_id'];

    //check against values in CiviUnitTestCase::createContribution()
    $this->assertEquals($old_contact_id, $this->_individualId);
    $this->assertEquals($old_fee_amount, 5.00);
    $this->assertEquals($old_source, 'SSF');
    $this->assertEquals($old_trxn_id, 212355);
    $this->assertEquals($old_invoice_id, 'old_invoice');
    $params = array(
      'id' => $contributionID,
      'contact_id' => $this->_individualId,
      'total_amount' => 105.00,
      'fee_amount' => 7.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 22.00,
      'contribution_status_id' => 1,
      'note' => 'Donating for Noble Cause',
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $new_params = array(
      'contribution_id' => $contribution['id'],
    );
    $contribution = $this->callAPISuccessGetSingle('contribution', $new_params);

    $this->assertEquals($contribution['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['total_amount'], 105.00);
    $this->assertEquals($contribution['financial_type_id'], $this->_financialTypeId);
    $this->assertEquals($contribution['financial_type'], 'Donation');
    $this->assertEquals($contribution['instrument_id'], $old_payment_instrument);
    $this->assertEquals($contribution['non_deductible_amount'], 22.00);
    $this->assertEquals($contribution['fee_amount'], 7.00);
    $this->assertEquals($contribution['trxn_id'], $old_trxn_id);
    $this->assertEquals($contribution['invoice_id'], $old_invoice_id);
    $this->assertEquals($contribution['contribution_source'], $old_source);
    $this->assertEquals($contribution['contribution_status'], 'Completed');

    $this->assertEquals($contribution['net_amount'], $contribution['total_amount'] - $contribution['fee_amount']);

    $params = array(
      'contribution_id' => $contributionID,
    );
    $result = $this->callAPISuccess('contribution', 'delete', $params);
    $this->assertAPISuccess($result);
  }

  /**
   * Check that net_amount is updated when a contribution is updated.
   *
   * Update fee amount AND total amount, just fee amount, just total amount
   * and neither to check that net_amount is keep updated.
   */
  public function testUpdateContributionNetAmountVariants() {
    $contributionID = $this->contributionCreate(['contact_id' => $this->individualCreate()]);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contributionID,
      'total_amount' => 90,
      'fee_amount' => 6,
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contributionID,
      'return' => ['net_amount', 'fee_amount', 'total_amount'],
    ]);
    $this->assertEquals(6, $contribution['fee_amount']);
    $this->assertEquals(90, $contribution['total_amount']);
    $this->assertEquals(84, $contribution['net_amount']);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contributionID,
      'fee_amount' => 3,
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contributionID,
      'return' => ['net_amount', 'fee_amount', 'total_amount'],
    ]);
    $this->assertEquals(3, $contribution['fee_amount']);
    $this->assertEquals(90, $contribution['total_amount']);
    $this->assertEquals(87, $contribution['net_amount']);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contributionID,
      'total_amount' => 200,
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contributionID,
      'return' => ['net_amount', 'fee_amount', 'total_amount'],
    ]);
    $this->assertEquals(3, $contribution['fee_amount']);
    $this->assertEquals(200, $contribution['total_amount']);
    $this->assertEquals(197, $contribution['net_amount']);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contributionID,
      'payment_instrument' => 'Cash',
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contributionID,
      'return' => ['net_amount', 'fee_amount', 'total_amount'],
    ]);
    $this->assertEquals(3, $contribution['fee_amount']);
    $this->assertEquals(200, $contribution['total_amount']);
    $this->assertEquals(197, $contribution['net_amount']);
  }

  /**
   * Attempt (but fail) to delete a contribution without parameters.
   */
  public function testDeleteEmptyParamsContribution() {
    $params = array();
    $this->callAPIFailure('contribution', 'delete', $params);
  }

  public function testDeleteParamsNotArrayContribution() {
    $params = 'contribution_id= 1';
    $contribution = $this->callAPIFailure('contribution', 'delete', $params);
    $this->assertEquals($contribution['error_message'], 'Input variable `params` is not an array');
  }

  public function testDeleteWrongParamContribution() {
    $params = array(
      'contribution_source' => 'SSF',
    );
    $this->callAPIFailure('contribution', 'delete', $params);
  }

  public function testDeleteContribution() {
    $contributionID = $this->contributionCreate(array(
      'contact_id' => $this->_individualId,
      'financial_type_id' => $this->_financialTypeId,
    ));
    $params = array(
      'id' => $contributionID,
    );
    $this->callAPIAndDocument('contribution', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Test civicrm_contribution_search with empty params.
   *
   * All available contributions expected.
   */
  public function testSearchEmptyParams() {
    $params = array();

    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $p);

    $result = $this->callAPISuccess('contribution', 'get', $params);
    // We're taking the first element.
    $res = $result['values'][$contribution['id']];

    $this->assertEquals($p['contact_id'], $res['contact_id']);
    $this->assertEquals($p['total_amount'], $res['total_amount']);
    $this->assertEquals($p['financial_type_id'], $res['financial_type_id']);
    $this->assertEquals($p['net_amount'], $res['net_amount']);
    $this->assertEquals($p['non_deductible_amount'], $res['non_deductible_amount']);
    $this->assertEquals($p['fee_amount'], $res['fee_amount']);
    $this->assertEquals($p['trxn_id'], $res['trxn_id']);
    $this->assertEquals($p['invoice_id'], $res['invoice_id']);
    $this->assertEquals($p['source'], $res['contribution_source']);
    // contribution_status_id = 1 => Completed
    $this->assertEquals('Completed', $res['contribution_status']);

    $this->contributionDelete($contribution['id']);
  }

  /**
   * Test civicrm_contribution_search. Success expected.
   */
  public function testSearch() {
    $p1 = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'contribution_status_id' => 1,
    );
    $contribution1 = $this->callAPISuccess('contribution', 'create', $p1);

    $p2 = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 200.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 20.00,
      'trxn_id' => 5454565,
      'invoice_id' => 1212124,
      'fee_amount' => 50.00,
      'net_amount' => 60.00,
      'contribution_status_id' => 2,
    );
    $contribution2 = $this->callAPISuccess('contribution', 'create', $p2);

    $params = array(
      'contribution_id' => $contribution2['id'],
    );
    $result = $this->callAPISuccess('contribution', 'get', $params);
    $res = $result['values'][$contribution2['id']];

    $this->assertEquals($p2['contact_id'], $res['contact_id']);
    $this->assertEquals($p2['total_amount'], $res['total_amount']);
    $this->assertEquals($p2['financial_type_id'], $res['financial_type_id']);
    $this->assertEquals($p2['net_amount'], $res['net_amount']);
    $this->assertEquals($p2['non_deductible_amount'], $res['non_deductible_amount']);
    $this->assertEquals($p2['fee_amount'], $res['fee_amount']);
    $this->assertEquals($p2['trxn_id'], $res['trxn_id']);
    $this->assertEquals($p2['invoice_id'], $res['invoice_id']);
    // contribution_status_id = 2 => Pending
    $this->assertEquals('Pending', $res['contribution_status']);

    $this->contributionDelete($contribution1['id']);
    $this->contributionDelete($contribution2['id']);
  }

  /**
   * Test completing a transaction via the API.
   *
   * Note that we are creating a logged in user because email goes out from
   * that person
   */
  public function testCompleteTransaction() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->swapMessageTemplateForTestTemplate();
    $this->createLoggedInUser();
    $params = array_merge($this->_params, array('contribution_status_id' => 2));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
    ));
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $contribution['id']));
    $this->assertEquals('SSF', $contribution['contribution_source']);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contribution['receipt_date'])));
    $mut->checkMailLog(array(
      'email:::anthony_anderson@civicrm.org',
      'is_monetary:::1',
      'amount:::100.00',
      'currency:::USD',
      'receive_date:::' . date('Ymd', strtotime($contribution['receive_date'])),
      "receipt_date:::\n",
      'contributeMode:::notify',
      'title:::Contribution',
      'displayName:::Mr. Anthony Anderson II',
      'contributionStatus:::Completed',
    ));
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test completing a transaction via the API with a non-USD transaction.
   */
  public function testCompleteTransactionEuro() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->swapMessageTemplateForTestTemplate();
    $this->createLoggedInUser();
    $params = array_merge($this->_params, array('contribution_status_id' => 2, 'currency' => 'EUR'));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
    ));

    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $contribution['id']));
    $this->assertEquals('SSF', $contribution['contribution_source']);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contribution['receipt_date'])));

    $entityFinancialTransactions = $this->getFinancialTransactionsForContribution($contribution['id']);
    $entityFinancialTransaction = reset($entityFinancialTransactions);
    $financialTrxn = $this->callAPISuccessGetSingle('FinancialTrxn', array('id' => $entityFinancialTransaction['financial_trxn_id']));
    $this->assertEquals('EUR', $financialTrxn['currency']);

    $mut->checkMailLog(array(
      'email:::anthony_anderson@civicrm.org',
      'is_monetary:::1',
      'amount:::100.00',
      'currency:::EUR',
      'receive_date:::' . date('Ymd', strtotime($contribution['receive_date'])),
      "receipt_date:::\n",
      'contributeMode:::notify',
      'title:::Contribution',
      'displayName:::Mr. Anthony Anderson II',
      'contributionStatus:::Completed',
    ));
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test to ensure mail is sent on chosing pay later
   */
  public function testpayLater() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->swapMessageTemplateForTestTemplate();
    $this->createLoggedInUser();

    // create contribution page first
    $contributionPageParams = array(
      'title' => 'Help Support CiviCRM!',
      'financial_type_id' => 1,
      'is_monetary' => TRUE,
      'is_pay_later' => 1,
      'is_quick_config' => TRUE,
      'pay_later_text' => 'I will send payment by check',
      'pay_later_receipt' => 'This is a pay later reciept',
      'is_allow_other_amount' => 1,
      'min_amount' => 10.00,
      'max_amount' => 10000.00,
      'goal_amount' => 100000.00,
      'is_email_receipt' => 1,
      'is_active' => 1,
      'amount_block_is_active' => 1,
      'currency' => 'USD',
      'is_billing_required' => 0,
    );
    $contributionPageResult = $this->callAPISuccess('contribution_page', 'create', $contributionPageParams);

    // submit form values
    $priceSet = $this->callAPISuccess('price_set', 'getsingle', array('name' => 'default_contribution_amount'));
    $params = array(
      'id' => $contributionPageResult['id'],
      'contact_id' => $this->_individualId,
      'email-5' => 'anthony_anderson@civicrm.org',
      'payment_processor_id' => 0,
      'amount' => 100.00,
      'tax_amount' => '',
      'currencyID' => 'USD',
      'is_pay_later' => 1,
      'invoiceID' => 'f28e1ddc86f8c4a0ff5bcf46393e4bc8',
      'is_quick_config' => 1,
      'description' => 'Online Contribution: Help Support CiviCRM!',
      'price_set_id' => $priceSet['id'],
    );
    $this->callAPISuccess('contribution_page', 'submit', $params);

    $mut->checkMailLog(array(
      'is_pay_later:::1',
      'email:::anthony_anderson@civicrm.org',
      'pay_later_receipt:::' . $contributionPageParams['pay_later_receipt'],
      'displayName:::Mr. Anthony Anderson II',
      'contributionPageId:::' . $contributionPageResult['id'],
      'title:::' . $contributionPageParams['title'],
      'amount:::' . $params['amount'],
    ));
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test to check whether contact billing address is used when no contribution address
   */
  public function testBillingAddress() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->swapMessageTemplateForTestTemplate();
    $this->createLoggedInUser();

    //Scenario 1: When Contact don't have any address
    $params = array_merge($this->_params, array('contribution_status_id' => 2));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
    ));
    $mut->checkMailLog(array(
      'address:::',
    ));

    // Scenario 2: Contribution using address
    $address = $this->callAPISuccess('address', 'create', array(
      'street_address' => 'contribution billing st',
      'location_type_id' => 2,
      'contact_id' => $this->_params['contact_id'],
    ));
    $params = array_merge($this->_params, array(
      'contribution_status_id' => 2,
      'address_id' => $address['id'],
    )
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
    ));
    $mut->checkMailLog(array(
      'address:::contribution billing st',
    ));

    // Scenario 3: Contribution wtth no address but contact has a billing address
    $this->callAPISuccess('address', 'create', array(
      'id' => $address['id'],
      'street_address' => 'is billing st',
      'contact_id' => $this->_params['contact_id'],
    ));
    $params = array_merge($this->_params, array('contribution_status_id' => 2));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
    ));
    $mut->checkMailLog(array(
      'address:::is billing st',
    ));

    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test completing a transaction via the API.
   *
   * Note that we are creating a logged in user because email goes out from
   * that person
   */
  public function testCompleteTransactionFeeAmount() {
    $this->createLoggedInUser();
    $params = array_merge($this->_params, array('contribution_status_id' => 2));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
      'fee_amount' => '.56',
      'trxn_id' => '7778888',
    ));
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $contribution['id'], 'sequential' => 1));
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertEquals('7778888', $contribution['trxn_id']);
    $this->assertEquals('.56', $contribution['fee_amount']);
    $this->assertEquals('99.44', $contribution['net_amount']);
  }

  /**
   * CRM-19126 Add test to verify when complete transaction is called tax amount is not changed.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testCheckTaxAmount($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $contact = $this->createLoggedInUser();
    $financialType = $this->callAPISuccess('financial_type', 'create', array(
      'name' => 'Test taxable financial Type',
      'is_reserved' => 0,
      'is_active' => 1,
    ));
    $financialAccount = $this->callAPISuccess('financial_account', 'create', array(
      'name' => 'Test Tax financial account ',
      'contact_id' => $contact,
      'financial_account_type_id' => 2,
      'is_tax' => 1,
      'tax_rate' => 5.00,
      'is_reserved' => 0,
      'is_active' => 1,
      'is_default' => 0,
    ));
    $financialTypeId = $financialType['id'];
    $financialAccountId = $financialAccount['id'];
    $financialAccountParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialTypeId,
      'account_relationship' => 10,
      'financial_account_id' => $financialAccountId,
    );
    CRM_Financial_BAO_FinancialTypeAccount::add($financialAccountParams);

    $params = array_merge($this->_params, array('contribution_status_id' => 2, 'financial_type_id' => $financialTypeId));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution1 = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id'], 'return' => 'tax_amount', 'sequential' => 1));
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
      'trxn_id' => '777788888',
      'fee_amount' => '6.00',
    ));
    $contribution2 = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id'], 'return' => array('tax_amount', 'fee_amount', 'net_amount'), 'sequential' => 1));
    $this->assertEquals($contribution1['values'][0]['tax_amount'], $contribution2['values'][0]['tax_amount']);
    $this->assertEquals('6.00', $contribution2['values'][0]['fee_amount']);
    $this->assertEquals('99.00', $contribution2['values'][0]['net_amount']);
  }

  /**
   * Test repeat contribution successfully creates line item.
   */
  public function testRepeatTransaction() {
    $originalContribution = $this->setUpRepeatTransaction($recurParams = array(), 'single');
    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));
    $lineItemParams = array(
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => array(
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ),
    );
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'],
    )));
    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'] + 1,
    )));
    unset($lineItem1['values'][0]['id'], $lineItem1['values'][0]['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($lineItem1['values'][0], $lineItem2['values'][0]);
    $this->_checkFinancialRecords(array(
      'id' => $originalContribution['id'] + 1,
      'payment_instrument_id' => $this->callAPISuccessGetValue('PaymentProcessor', array(
        'id' => $originalContribution['payment_processor_id'],
        'return' => 'payment_instrument_id',
      )),
    ), 'online');
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Test repeat contribution successfully creates line items (plural).
   */
  public function testRepeatTransactionLineItems() {
    // CRM-19309
    $originalContribution = $this->setUpRepeatTransaction($recurParams = array(), 'multiple');
    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));

    $lineItemParams = array(
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => array(
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ),
    );
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'],
    )));
    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'] + 1,
    )));

    // unset id and entity_id for all of them to be able to compare the lineItems:
    unset($lineItem1['values'][0]['id'], $lineItem1['values'][0]['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($lineItem1['values'][0], $lineItem2['values'][0]);

    unset($lineItem1['values'][1]['id'], $lineItem1['values'][1]['entity_id']);
    unset($lineItem2['values'][1]['id'], $lineItem2['values'][1]['entity_id']);
    $this->assertEquals($lineItem1['values'][1], $lineItem2['values'][1]);

    // CRM-19309 so in future we also want to:
    // check that financial_line_items have been created for entity_id 3 and 4;

    $this->callAPISuccessGetCount('FinancialItem', array('description' => 'Sales Tax', 'amount' => 0), 0);
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Test repeat contribution successfully creates is_test transaction.
   */
  public function testRepeatTransactionIsTest() {
    $this->_params['is_test'] = 1;
    $originalContribution = $this->setUpRepeatTransaction(array('is_test' => 1), 'single');

    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));
    $this->callAPISuccessGetCount('Contribution', array('contribution_test' => 1), 2);
  }

  /**
   * Test repeat contribution passed in status.
   */
  public function testRepeatTransactionPassedInStatus() {
    $originalContribution = $this->setUpRepeatTransaction($recurParams = array(), 'single');

    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Pending',
      'trxn_id' => uniqid(),
    ));
    $this->callAPISuccessGetCount('Contribution', array('contribution_status_id' => 2), 1);
  }

  /**
   * Test repeat contribution accepts recur_id instead of original_contribution_id.
   */
  public function testRepeatTransactionAcceptRecurID() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array(
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
    ));
    $this->callAPISuccess('contribution', 'create', array_merge(
        $this->_params,
        array('contribution_recur_id' => $contributionRecur['id']))
    );

    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_recur_id' => $contributionRecur['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));

    $this->quickCleanUpFinancialEntities();
  }

  /**
   * CRM-19873 Test repattransaction if contribution_recur_id is a test.
   */
  public function testRepeatTransactionTestRecurId() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array(
      'contact_id' => $this->_individualId,
      'frequency_interval' => '1',
      'amount' => '1.00',
      'contribution_status_id' => 1,
      'start_date' => '2017-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
      'is_test' => 1,
    ));
    $this->callAPISuccess('contribution', 'create', array_merge(
        $this->_params,
        array(
          'contribution_recur_id' => $contributionRecur['id'],
          'is_test' => 1,
        ))
    );

    $repeatedContribution = $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_recur_id' => $contributionRecur['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));

    $this->assertEquals($contributionRecur['values'][1]['is_test'], $repeatedContribution['values'][2]['is_test']);
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * CRM-19945 Tests that Contribute.repeattransaction renews a membership when contribution status=Completed
   *
   */
  public function testRepeatTransactionMembershipRenewCompletedContribution() {
    list($originalContribution, $membership) = $this->setUpAutoRenewMembership();

    $this->callAPISuccess('contribution', 'create', array(
      'contact_id' => $originalContribution['values'][1]['contact_id'],
      'financial_type_id' => $originalContribution['values'][1]['financial_type_id'],
      'total_amount' => $originalContribution['values'][1]['total_amount'],
      'contribution_recur_id' => $originalContribution['values'][1]['contribution_recur_id'],
      'contribution_status_id' => "Failed",
    ));

    $this->callAPISuccess('membership', 'create', array(
      'id' => $membership['id'],
      'end_date' => 'yesterday',
      'status_id' => 'Expired',
    ));

    $contribution = $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_recur_id' => $originalContribution['values'][1]['contribution_recur_id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'bobsled',
    ));

    $membershipStatusId = $this->callAPISuccess('membership', 'getvalue', array(
      'id' => $membership['id'],
      'return' => 'status_id',
    ));

    $membership = $this->callAPISuccess('membership', 'get', array(
      'id' => $membership['id'],
    ));

    $this->assertEquals('New', CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membershipStatusId));

    $lineItem = $this->callAPISuccessGetSingle('LineItem', array('contribution_id' => $contribution['id']));
    $this->assertEquals('civicrm_membership', $lineItem['entity_table']);
    $this->callAPISuccessGetCount('MembershipPayment', array('membership_id' => $membership['id']));
    $this->quickCleanUpFinancialEntities();
    $this->contactDelete($originalContribution['values'][1]['contact_id']);
  }

  /**
   * CRM-19945 Tests that Contribute.repeattransaction DOES NOT renew a membership when contribution status=Failed
   *
   * @dataProvider contributionStatusProvider
   */
  public function testRepeatTransactionMembershipRenewContributionNotCompleted($contributionStatus) {
    // Completed status should renew so we don't test that here
    // In Progress status is only for recurring contributions so we don't test that here
    if (in_array($contributionStatus['name'], ['Completed', 'In Progress'])) {
      return;
    }
    list($originalContribution, $membership) = $this->setUpAutoRenewMembership();

    $this->callAPISuccess('contribution', 'create', array(
      'contact_id' => $originalContribution['values'][1]['contact_id'],
      'financial_type_id' => $originalContribution['values'][1]['financial_type_id'],
      'total_amount' => $originalContribution['values'][1]['total_amount'],
      'contribution_recur_id' => $originalContribution['values'][1]['contribution_recur_id'],
      'contribution_status_id' => "Completed",
    ));

    $this->callAPISuccess('membership', 'create', array(
      'id' => $membership['id'],
      'end_date' => 'yesterday',
      'status_id' => 'Expired',
    ));

    $contribution = $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_recur_id' => $originalContribution['values'][1]['contribution_recur_id'],
      'contribution_status_id' => $contributionStatus['name'],
      'trxn_id' => 'bobsled',
    ));

    $updatedMembership = $this->callAPISuccess('membership', 'getsingle', array(
      'id' => $membership['id'],
    ));

    $dateTime = new DateTime('yesterday');
    $this->assertEquals($dateTime->format('Y-m-d'), $updatedMembership['end_date']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Expired'), $updatedMembership['status_id']);

    $lineItem = $this->callAPISuccessGetSingle('LineItem', array('contribution_id' => $contribution['id']));
    $this->assertEquals('civicrm_membership', $lineItem['entity_table']);
    $this->callAPISuccessGetCount('MembershipPayment', array('membership_id' => $membership['id']));
    $this->quickCleanUpFinancialEntities();
    $this->contactDelete($originalContribution['values'][1]['contact_id']);
  }

  /**
   * Dataprovider provides contribution status as [optionvalue=>contribution_status_name]
   * FIXME: buildOptions seems to die in CRM_Core_Config::_construct when in test mode.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function contributionStatusProvider() {
    $contributionStatuses = civicrm_api3('OptionValue', 'get', [
      'return' => ["id", "name"],
      'option_group_id' => "contribution_status",
    ]);
    foreach ($contributionStatuses['values'] as $statusName) {
      $statuses[] = [$statusName];
    }
    return $statuses;
  }

  /**
   * CRM-16397 test appropriate action if total amount has changed for single line items.
   */
  public function testRepeatTransactionAlteredAmount() {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array(
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ));
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
        $this->_params,
        array(
          'contribution_recur_id' => $contributionRecur['id'],
        ))
    );

    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
      'total_amount' => '400',
      'fee_amount' => 50,
    ));

    $lineItemParams = array(
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => array(
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ),
    );
    $this->callAPISuccessGetSingle('contribution', array(
      'total_amount' => 400,
      'fee_amount' => 50,
      'net_amount' => 350,
    ));
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'],
    )));
    $expectedLineItem = array_merge(
      $lineItem1['values'][0], array(
        'line_total' => '400.00',
        'unit_price' => '400.00',
      )
    );

    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'] + 1,
    )));

    unset($expectedLineItem['id'], $expectedLineItem['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($expectedLineItem, $lineItem2['values'][0]);
  }

  /**
   * CRM-17718 test appropriate action if financial type has changed for single line items.
   */
  public function testRepeatTransactionPassedInFinancialType() {
    $originalContribution = $this->setUpRecurringContribution();

    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
      'financial_type_id' => 2,
    ));
    $lineItemParams = array(
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => array(
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ),
    );

    $this->callAPISuccessGetSingle('contribution', array(
      'total_amount' => 100,
      'financial_type_id' => 2,
    ));
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'],
    )));
    $expectedLineItem = array_merge(
      $lineItem1['values'][0], array(
        'line_total' => '100.00',
        'unit_price' => '100.00',
        'financial_type_id' => 2,
        'contribution_type_id' => 2,
      )
    );
    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'] + 1,
    )));
    unset($expectedLineItem['id'], $expectedLineItem['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($expectedLineItem, $lineItem2['values'][0]);
  }

  /**
   * CRM-17718 test appropriate action if financial type has changed for single line items.
   */
  public function testRepeatTransactionUpdatedFinancialType() {
    $originalContribution = $this->setUpRecurringContribution(array(), array('financial_type_id' => 2));

    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_recur_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));
    $lineItemParams = array(
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => array(
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ),
    );

    $this->callAPISuccessGetSingle('contribution', array(
      'total_amount' => 100,
      'financial_type_id' => 2,
    ));
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'],
    )));
    $expectedLineItem = array_merge(
      $lineItem1['values'][0], array(
        'line_total' => '100.00',
        'unit_price' => '100.00',
        'financial_type_id' => 2,
        'contribution_type_id' => 2,
      )
    );

    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'] + 1,
    )));
    unset($expectedLineItem['id'], $expectedLineItem['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($expectedLineItem, $lineItem2['values'][0]);
  }

  /**
   * CRM-16397 test appropriate action if campaign has been passed in.
   */
  public function testRepeatTransactionPassedInCampaign() {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $campaignID = $this->campaignCreate();
    $campaignID2 = $this->campaignCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array(
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ));
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      array(
        'contribution_recur_id' => $contributionRecur['id'],
        'campaign_id' => $campaignID,
      ))
    );

    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
      'campaign_id' => $campaignID2,
    ));

    $this->callAPISuccessGetSingle('contribution', array(
      'total_amount' => 100,
      'campaign_id' => $campaignID2,
    ));
  }

  /**
   * CRM-17718 campaign stored on contribution recur gets priority.
   *
   * This reflects the fact we permit people to update them.
   */
  public function testRepeatTransactionUpdatedCampaign() {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $campaignID = $this->campaignCreate();
    $campaignID2 = $this->campaignCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array(
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
      'campaign_id' => $campaignID,
    ));
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      array(
        'contribution_recur_id' => $contributionRecur['id'],
        'campaign_id' => $campaignID2,
      ))
    );

    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));

    $this->callAPISuccessGetSingle('contribution', array(
      'total_amount' => 100,
      'campaign_id' => $campaignID,
    ));
  }

  /**
   * CRM-20685 Repeattransaction produces incorrect Financial Type ID (in specific circumstance) - if number of lineItems = 1.
   *
   * This case happens when the line item & contribution do not have the same type in his initiating transaction.
   */
  public function testRepeatTransactionUpdatedFinancialTypeAndNotEquals() {
    $originalContribution = $this->setUpRecurringContribution(array(), array('financial_type_id' => 2));
    // This will made the trick to get the not equals behaviour.
    $this->callAPISuccess('line_item', 'create', array('id' => 1, 'financial_type_id' => 4));
    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_recur_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));
    $lineItemParams = array(
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => array(
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ),
    );
    $this->callAPISuccessGetSingle('contribution', array(
      'total_amount' => 100,
      'financial_type_id' => 2,
    ));
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'],
    )));
    $expectedLineItem = array_merge(
      $lineItem1['values'][0], array(
        'line_total' => '100.00',
        'unit_price' => '100.00',
        'financial_type_id' => 4,
        'contribution_type_id' => 4,
      )
    );

    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'] + 1,
    )));
    $this->callAPISuccess('line_item', 'create', array('id' => 1, 'financial_type_id' => 1));
    unset($expectedLineItem['id'], $expectedLineItem['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($expectedLineItem, $lineItem2['values'][0]);
  }

  /**
   * Test completing a transaction does not 'mess' with net amount (CRM-15960).
   */
  public function testCompleteTransactionNetAmountOK() {
    $this->createLoggedInUser();
    $params = array_merge($this->_params, array('contribution_status_id' => 2));
    unset($params['net_amount']);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
    ));
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $contribution['id']));
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertTrue(($contribution['total_amount'] - $contribution['net_amount']) == $contribution['fee_amount']);
  }

  /**
   * CRM-14151 - Test completing a transaction via the API.
   */
  public function testCompleteTransactionWithReceiptDateSet() {
    $this->swapMessageTemplateForTestTemplate();
    $mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $params = array_merge($this->_params, array('contribution_status_id' => 2, 'receipt_date' => 'now'));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', array('id' => $contribution['id'], 'trxn_date' => date('Y-m-d')));
    $contribution = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id'], 'sequential' => 1));
    $this->assertEquals('Completed', $contribution['values'][0]['contribution_status']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contribution['values'][0]['receive_date'])));
    $mut->checkMailLog(array(
      'Receipt - Contribution',
      'receipt_date:::' . date('Ymd'),
    ));
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * CRM-1960 - Test to ensure that completetransaction respects the is_email_receipt setting
   */
  public function testCompleteTransactionWithEmailReceiptInput() {
    $contributionPage = $this->createReceiptableContributionPage();

    $this->_params['contribution_page_id'] = $contributionPage['id'];
    $params = array_merge($this->_params, array('contribution_status_id' => 2));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    // Complete the transaction overriding is_email_receipt to = FALSE
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
      'trxn_date' => date('2011-04-09'),
      'trxn_id' => 'kazam',
      'is_email_receipt' => 0,
    ));
    // Check if a receipt was issued
    $receipt_date = $this->callAPISuccess('Contribution', 'getvalue', array('id' => $contribution['id'], 'return' => 'receipt_date'));
    $this->assertEquals('', $receipt_date);
  }

  /**
   * Test that $is_recur is assigned to the receipt.
   */
  public function testCompleteTransactionForRecurring() {

    $this->swapMessageTemplateForTestTemplate();
    $recurring = $this->setUpRecurringContribution();
    $contributionPage = $this->createReceiptableContributionPage(array('is_recur' => TRUE, 'recur_frequency_unit' => 'month', 'recur_interval' => 1));

    $this->_params['contribution_page_id'] = $contributionPage['id'];
    $this->_params['contribution_recur_id'] = $recurring['id'];

    $contribution = $this->setUpForCompleteTransaction();

    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
      'trxn_date' => date('2011-04-09'),
      'trxn_id' => 'kazam',
      'is_email_receipt' => 1,
    ));

    $this->mut->checkMailLog(array(
      'is_recur:::1',
      'cancelSubscriptionUrl:::' . CIVICRM_UF_BASEURL,
    ));
    $this->mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * CRM-19710 - Test to ensure that completetransaction respects the input for is_email_receipt setting.
   *
   * If passed in it will override the default from contribution page.
   */
  public function testCompleteTransactionWithEmailReceiptInputTrue() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    // Create a Contribution Page with is_email_receipt = FALSE
    $contributionPage = $this->callAPISuccess('ContributionPage', 'create', array(
      'receipt_from_name' => 'Mickey Mouse',
      'receipt_from_email' => 'mickey@mouse.com',
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'CAD',
      'is_monetary' => TRUE,
      'is_email_receipt' => 0,
    ));
    $this->_params['contribution_page_id'] = $contributionPage['id'];
    $params = array_merge($this->_params, array('contribution_status_id' => 2, 'receipt_date' => 'now'));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    // Complete the transaction overriding is_email_receipt to = TRUE
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
      'is_email_receipt' => 1,
    ));
    $mut->checkMailLog(array(
      'Please print this receipt for your records.',
    ));
    $mut->stop();
  }

  /**
   * Complete the transaction using the template with all the possible.
   */
  public function testCompleteTransactionWithTestTemplate() {
    $this->swapMessageTemplateForTestTemplate();
    $contribution = $this->setUpForCompleteTransaction();
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
      'trxn_date' => date('2011-04-09'),
      'trxn_id' => 'kazam',
    ));
    $receive_date = $this->callAPISuccess('Contribution', 'getvalue', array('id' => $contribution['id'], 'return' => 'receive_date'));
    $this->mut->checkMailLog(array(
      'email:::anthony_anderson@civicrm.org',
      'is_monetary:::1',
      'amount:::100.00',
      'currency:::USD',
      'receive_date:::' . date('Ymd', strtotime($receive_date)),
      'receipt_date:::' . date('Ymd'),
      'contributeMode:::notify',
      'title:::Contribution',
      'displayName:::Mr. Anthony Anderson II',
      'trxn_id:::kazam',
      'contactID:::' . $this->_params['contact_id'],
      'contributionID:::' . $contribution['id'],
      'financialTypeId:::1',
      'financialTypeName:::Donation',
    ));
    $this->mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Complete the transaction using the template with all the possible.
   */
  public function testCompleteTransactionContributionPageFromAddress() {
    $contributionPage = $this->callAPISuccess('ContributionPage', 'create', array(
      'receipt_from_name' => 'Mickey Mouse',
      'receipt_from_email' => 'mickey@mouse.com',
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => 50,
      'is_pay_later' => 1,
      'is_monetary' => TRUE,
      'is_email_receipt' => TRUE,
    ));
    $this->_params['contribution_page_id'] = $contributionPage['id'];
    $contribution = $this->setUpForCompleteTransaction();
    $this->callAPISuccess('contribution', 'completetransaction', array('id' => $contribution['id']));
    $this->mut->checkMailLog(array(
      'mickey@mouse.com',
      'Mickey Mouse <',
    ));
    $this->mut->stop();
  }

  /**
   * Test completing first transaction in a recurring series.
   *
   * The status should be set to 'in progress' and the next scheduled payment date calculated.
   *
   * @dataProvider getScheduledDateData
   *
   * @param array $dataSet
   *
   * @throws \Exception
   */
  public function testCompleteTransactionSetStatusToInProgress($dataSet) {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge(array(
      'contact_id' => $this->_individualId,
      'installments' => '2',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 'Pending',
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ), $dataSet['data']));
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      array(
        'contribution_recur_id' => $contributionRecur['id'],
        'contribution_status_id' => 'Pending',
        'receive_date' => $dataSet['receive_date'],
      ))
    );
    $this->callAPISuccess('Contribution', 'completetransaction', array(
      'id' => $contribution,
      'receive_date' => $dataSet['receive_date'],
    ));
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', array(
      'id' => $contributionRecur['id'],
      'return' => array('next_sched_contribution_date', 'contribution_status_id'),
    ));
    $this->assertEquals(5, $contributionRecur['contribution_status_id']);
    $this->assertEquals($dataSet['expected'], $contributionRecur['next_sched_contribution_date']);
    $this->callAPISuccess('Contribution', 'create', array_merge(
      $this->_params,
      array(
        'contribution_recur_id' => $contributionRecur['id'],
        'contribution_status_id' => 'Completed',
      )
    ));
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', array(
      'id' => $contributionRecur['id'],
      'return' => array('contribution_status_id'),
    ));
    $this->assertEquals(1, $contributionRecur['contribution_status_id']);
  }

  /**
   * Get dates for testing.
   *
   * @return array
   */
  public function getScheduledDateData() {
    $result = array();
    $result[]['2016-08-31-1-month'] = array(
      'data' => array(
        'start_date' => '2016-08-31',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
      ),
      'receive_date' => '2016-08-31',
      'expected' => '2016-10-01 00:00:00',
    );
    $result[]['2012-01-01-1-month'] = array(
      'data' => array(
        'start_date' => '2012-01-01',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
      ),
      'receive_date' => '2012-01-01',
      'expected' => '2012-02-01 00:00:00',
    );
    $result[]['2012-01-01-1-month'] = array(
      'data' => array(
        'start_date' => '2012-01-01',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
      ),
      'receive_date' => '2012-02-29',
      'expected' => '2012-03-29 00:00:00',
    );
    $result['receive_date_includes_time']['2012-01-01-1-month'] = array(
      'data' => array(
        'start_date' => '2012-01-01',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
        'next_sched_contribution_date' => '2012-02-29',
      ),
      'receive_date' => '2012-02-29 16:00:00',
      'expected' => '2012-03-29 00:00:00',
    );
    return $result;
  }

  /**
   * Test completing a pledge with the completeTransaction api..
   *
   * Note that we are creating a logged in user because email goes out from
   * that person.
   */
  public function testCompleteTransactionUpdatePledgePayment() {
    $this->swapMessageTemplateForTestTemplate();
    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();
    $this->createLoggedInUser();
    $contributionID = $this->createPendingPledgeContribution();
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contributionID,
      'trxn_date' => '1 Feb 2013',
    ));
    $pledge = $this->callAPISuccessGetSingle('Pledge', array(
      'id' => $this->_ids['pledge'],
    ));
    $this->assertEquals('Completed', $pledge['pledge_status']);

    $status = $this->callAPISuccessGetValue('PledgePayment', array(
      'pledge_id' => $this->_ids['pledge'],
      'return' => 'status_id',
    ));
    $this->assertEquals(1, $status);
    $mut->checkMailLog(array(
      'amount:::500.00',
      'receive_date:::20130201000000',
      "receipt_date:::\n",
    ));
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test completing a transaction with an event via the API.
   *
   * Note that we are creating a logged in user because email goes out from
   * that person
   */
  public function testCompleteTransactionWithParticipantRecord() {
    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();
    $this->_individualId = $this->createLoggedInUser();
    $contributionID = $this->createPendingParticipantContribution();
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contributionID,
    )
    );
    $participantStatus = $this->callAPISuccessGetValue('participant', array(
      'id' => $this->_ids['participant'],
      'return' => 'participant_status_id',
    ));
    $this->assertEquals(1, $participantStatus);

    //Assert only three activities are created.
    $activities = CRM_Activity_BAO_Activity::getContactActivity($this->_individualId);
    $this->assertEquals(3, count($activities));
    $activityNames = array_count_values(CRM_Utils_Array::collect('activity_name', $activities));
    // record two activities before and after completing payment for Event registration
    $this->assertEquals(2, $activityNames['Event Registration']);
    // update the original 'Contribution' activity created after completing payment
    $this->assertEquals(1, $activityNames['Contribution']);

    $mut->checkMailLog(array(
      'Annual CiviCRM meet',
      'Event',
      'This letter is a confirmation that your registration has been received and your status has been updated to Registered.',
    ));
    $mut->stop();
  }

  /**
   * Test membership is renewed when transaction completed.
   */
  public function testCompleteTransactionMembershipPriceSet() {
    $this->createPriceSetWithPage('membership');
    $stateOfGrace = $this->callAPISuccess('MembershipStatus', 'getvalue', array(
      'name' => 'Grace',
      'return' => 'id',
    ));
    $this->setUpPendingContribution($this->_ids['price_field_value'][0]);
    $membership = $this->callAPISuccess('membership', 'getsingle', array('id' => $this->_ids['membership']));
    $logs = $this->callAPISuccess('MembershipLog', 'get', array(
      'membership_id' => $this->_ids['membership'],
    ));
    $this->assertEquals(1, $logs['count']);
    $this->assertEquals($stateOfGrace, $membership['status_id']);
    $this->callAPISuccess('contribution', 'completetransaction', array('id' => $this->_ids['contribution']));
    $membership = $this->callAPISuccess('membership', 'getsingle', array('id' => $this->_ids['membership']));
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 1 year')), $membership['end_date']);
    $this->callAPISuccessGetSingle('LineItem', array(
      'entity_id' => $this->_ids['membership'],
      'entity_table' => 'civicrm_membership',
    ));
    $logs = $this->callAPISuccess('MembershipLog', 'get', array(
      'membership_id' => $this->_ids['membership'],
    ));
    $this->assertEquals(2, $logs['count']);
    $this->assertNotEquals($stateOfGrace, $logs['values'][2]['status_id']);
    //Assert only three activities are created.
    $activities = CRM_Activity_BAO_Activity::getContactActivity($this->_ids['contact']);
    $this->assertEquals(3, count($activities));
    $activityNames = array_flip(CRM_Utils_Array::collect('activity_name', $activities));
    $this->assertArrayHasKey('Contribution', $activityNames);
    $this->assertArrayHasKey('Membership Signup', $activityNames);
    $this->assertArrayHasKey('Change Membership Status', $activityNames);
    $this->cleanUpAfterPriceSets();
  }

  /**
   * Test if renewal activity is create after changing Pending contribution to Completed via offline
   */
  public function testPendingToCompleteContribution() {
    $this->createPriceSetWithPage('membership');
    $this->setUpPendingContribution($this->_ids['price_field_value'][0]);
    $this->callAPISuccess('membership', 'getsingle', array('id' => $this->_ids['membership']));
    // Case 1: Assert that Membership Signup Activity is created on Pending to Completed Contribution via backoffice
    $activity = $this->callAPISuccess('Activity', 'get', array(
      'activity_type_id' => 'Membership Signup',
      'source_record_id' => $this->_ids['membership'],
      'status_id' => 'Scheduled',
    ));
    $this->assertEquals(1, $activity['count']);

    // change pending contribution to completed
    $form = new CRM_Contribute_Form_Contribution();

    $form->_params = array(
      'id' => $this->_ids['contribution'],
      'total_amount' => 20,
      'net_amount' => 20,
      'fee_amount' => 0,
      'financial_type_id' => 1,
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 1,
      'billing_middle_name' => '',
      'billing_last_name' => 'Adams',
      'billing_street_address-5' => '790L Lincoln St S',
      'billing_city-5' => 'Maryknoll',
      'billing_state_province_id-5' => 1031,
      'billing_postal_code-5' => 10545,
      'billing_country_id-5' => 1228,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'installments' => '',
      'hidden_AdditionalDetail' => 1,
      'hidden_Premium' => 1,
      'from_email_address' => '"civi45" <civi45@civicrm.com>',
      'receipt_date' => '',
      'receipt_date_time' => '',
      'payment_processor_id' => $this->paymentProcessorID,
      'currency' => 'USD',
      'contribution_page_id' => $this->_ids['contribution_page'],
      'contribution_mode' => 'membership',
      'source' => 'Membership Signup and Renewal',
    );

    $form->testSubmit($form->_params, CRM_Core_Action::UPDATE);

    // Case 2: After successful payment for Pending backoffice there are three activities created
    //  2.a Update status of existing Scheduled Membership Signup (created in step 1) to Completed
    $activity = $this->callAPISuccess('Activity', 'get', array(
      'activity_type_id' => 'Membership Signup',
      'source_record_id' => $this->_ids['membership'],
      'status_id' => 'Completed',
    ));
    $this->assertEquals(1, $activity['count']);
    // 2.b Contribution activity created to record successful payment
    $activity = $this->callAPISuccess('Activity', 'get', array(
      'activity_type_id' => 'Contribution',
      'source_record_id' => $this->_ids['contribution'],
      'status_id' => 'Completed',
    ));
    $this->assertEquals(1, $activity['count']);

    // 2.c 'Change membership type' activity created to record Membership status change from Grace to Current
    $activity = $this->callAPISuccess('Activity', 'get', array(
      'activity_type_id' => 'Change Membership Status',
      'source_record_id' => $this->_ids['membership'],
      'status_id' => 'Completed',
    ));
    $this->assertEquals(1, $activity['count']);
    $this->assertEquals('Status changed from Grace to Current', $activity['values'][$activity['id']]['subject']);

    //Create another pending contribution for renewal
    $contribution = $this->callAPISuccess('contribution', 'create', array(
      'domain_id' => 1,
      'contact_id' => $this->_ids['contact'],
      'receive_date' => date('Ymd'),
      'total_amount' => 20.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 'Credit Card',
      'non_deductible_amount' => 10.00,
      'trxn_id' => 'rdhfi88',
      'invoice_id' => 'dofhiewuyr',
      'source' => 'SSF',
      'contribution_status_id' => 2,
      'contribution_page_id' => $this->_ids['contribution_page'],
      // We can't rely on contribution api to link line items correctly to membership
      'skipLineItem' => TRUE,
      'api.membership_payment.create' => array('membership_id' => $this->_ids['membership']),
    ));

    $this->callAPISuccess('line_item', 'create', array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'contribution_id' => $contribution['id'],
      'price_field_id' => $this->_ids['price_field'][0],
      'qty' => 1,
      'unit_price' => 20,
      'line_total' => 20,
      'financial_type_id' => 1,
      'price_field_value_id' => $this->_ids['price_field_value']['cont'],
    ));
    $this->callAPISuccess('line_item', 'create', array(
      'entity_id' => $this->_ids['membership'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
      'price_field_id' => $this->_ids['price_field'][0],
      'qty' => 1,
      'unit_price' => 20,
      'line_total' => 20,
      'financial_type_id' => 1,
      'price_field_value_id' => $this->_ids['price_field_value'][0],
      'membership_type_id' => $this->_ids['membership_type'],
    ));

    //Update it to Failed.
    $form->_params['id'] = $contribution['id'];
    $form->_params['contribution_status_id'] = 4;

    $form->testSubmit($form->_params, CRM_Core_Action::UPDATE);
    //Existing membership should not get updated to expired.
    $membership = $this->callAPISuccess('membership', 'getsingle', array('id' => $this->_ids['membership']));
    $this->assertNotEquals($membership['status_id'], 4);
  }

  /**
   * Test membership is renewed when transaction completed.
   */
  public function testCompleteTransactionMembershipPriceSetTwoTerms() {
    $this->createPriceSetWithPage('membership');
    $this->setUpPendingContribution($this->_ids['price_field_value'][1]);
    $this->callAPISuccess('contribution', 'completetransaction', array('id' => $this->_ids['contribution']));
    $membership = $this->callAPISuccess('membership', 'getsingle', array('id' => $this->_ids['membership']));
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 2 years')), $membership['end_date']);
    $this->cleanUpAfterPriceSets();
  }

  public function cleanUpAfterPriceSets() {
    $this->quickCleanUpFinancialEntities();
    $this->contactDelete($this->_ids['contact']);
  }

  /**
   * Set up a pending transaction with a specific price field id.
   *
   * @param int $priceFieldValueID
   * @param array $contriParams
   */
  public function setUpPendingContribution($priceFieldValueID, $contriParams = array()) {
    $contactID = $this->individualCreate();
    $membership = $this->callAPISuccess('membership', 'create', array(
      'contact_id' => $contactID,
      'membership_type_id' => $this->_ids['membership_type'],
      'start_date' => 'yesterday - 1 year',
      'end_date' => 'yesterday',
      'join_date' => 'yesterday - 1 year',
    ));
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge(array(
      'domain_id' => 1,
      'contact_id' => $contactID,
      'receive_date' => date('Ymd'),
      'total_amount' => 20.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 'Credit Card',
      'non_deductible_amount' => 10.00,
      'trxn_id' => 'jdhfi' . rand(1, 100),
      'invoice_id' => 'djfhiew' . rand(5, 100),
      'source' => 'SSF',
      'contribution_status_id' => 2,
      'contribution_page_id' => $this->_ids['contribution_page'],
      'api.membership_payment.create' => array('membership_id' => $membership['id']),
    ), $contriParams));

    $this->callAPISuccess('line_item', 'create', array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'contribution_id' => $contribution['id'],
      'price_field_id' => $this->_ids['price_field'][0],
      'qty' => 1,
      'unit_price' => 20,
      'line_total' => 20,
      'financial_type_id' => 1,
      'price_field_value_id' => $priceFieldValueID,
    ));
    $this->_ids['contact'] = $contactID;
    $this->_ids['contribution'] = $contribution['id'];
    $this->_ids['membership'] = $membership['id'];
  }

  /**
   * Test sending a mail via the API.
   */
  public function testSendMail() {
    $mut = new CiviMailUtils($this, TRUE);
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $this->callAPISuccess('contribution', 'sendconfirmation', array(
      'id' => $contribution['id'],
      'receipt_from_email' => 'api@civicrm.org',
    ));
    $mut->checkMailLog(array(
      '$ 100.00',
      'Contribution Information',
      'Please print this confirmation for your records',
    ), array(
      'Event',
    ));

    $this->checkCreditCardDetails($mut, $contribution['id']);
    $mut->stop();
  }

  /**
   * Test sending a mail via the API.
   * This simulates webform_civicrm using pay later contribution page
   */
  public function testSendconfirmationPayLater() {
    $mut = new CiviMailUtils($this, TRUE);

    // Create contribution page
    $pageParams = array(
      'title' => 'Webform Contributions',
      'financial_type_id' => 1,
      'contribution_type_id' => 1,
      'is_confirm_enabled' => 1,
      'is_pay_later' => 1,
      'pay_later_text' => 'I will send payment by cheque',
      'pay_later_receipt' => 'Send your cheque payable to "CiviCRM LLC" to the office',
    );
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', $pageParams);

    // Create pay later contribution
    $contribParams = array(
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'is_pay_later' => 1,
      'contribution_status_id' => 2,
      'contribution_page_id' => $contributionPage['id'],
      'total_amount' => '10.00',
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);

    // Create line item
    $lineItemParams = array(
      'contribution_id' => $contribution['id'],
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'label' => 'My lineitem label',
      'qty' => 1,
      'unit_price' => "10.00",
      'line_total' => "10.00",
    );
    $lineItem = $this->callAPISuccess('lineItem', 'create', $lineItemParams);

    // Create email
    try {
      civicrm_api3('contribution', 'sendconfirmation', array(
        'id' => $contribution['id'],
        'receipt_from_email' => 'api@civicrm.org',
      ));
    }
    catch (Exception $e) {
      // Need to figure out how to stop this some other day
      // We don't care about the Payment Processor because this is Pay Later
      // The point of this test is to check we get the pay_later version of the mail
      if ($e->getMessage() != "Undefined variable: CRM16923AnUnreliableMethodHasBeenUserToDeterminePaymentProcessorFromContributionPage") {
        throw $e;
      }
    }

    // Retrieve mail & check it has the pay_later_receipt info
    $mut->getMostRecentEmail('raw');
    $mut->checkMailLog(array(
      (string) $contribParams['total_amount'],
      $pageParams['pay_later_receipt'],
    ), array(
      'Event',
    ));
    $mut->stop();
  }

  /**
   * Check credit card details in sent mail via API
   *
   * @param $mut obj CiviMailUtils instance
   * @param int $contributionID Contribution ID
   *
   */
  public function checkCreditCardDetails($mut, $contributionID) {
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $this->callAPISuccess('contribution', 'sendconfirmation', array(
      'id' => $contributionID,
      'receipt_from_email' => 'api@civicrm.org',
      'payment_processor_id' => $this->paymentProcessorID,
    ));
    $mut->checkMailLog(array(
      // credit card header
      'Credit Card Information',
      // billing header
      'Billing Name and Address',
      // billing name
      'anthony_anderson@civicrm.org',
    ), array(
      'Event',
    ));
  }

  /**
   * Test sending a mail via the API.
   */
  public function testSendMailEvent() {
    $mut = new CiviMailUtils($this, TRUE);
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $event = $this->eventCreate(array(
      'is_email_confirm' => 1,
      'confirm_from_email' => 'test@civicrm.org',
    ));
    $this->_eventID = $event['id'];
    $participantParams = array(
      'contact_id' => $this->_individualId,
      'event_id' => $this->_eventID,
      'status_id' => 1,
      'role_id' => 1,
      // to ensure it matches later on
      'register_date' => '2007-07-21 00:00:00',
      'source' => 'Online Event Registration: API Testing',

    );
    $participant = $this->callAPISuccess('participant', 'create', $participantParams);
    $this->callAPISuccess('participant_payment', 'create', array(
      'participant_id' => $participant['id'],
      'contribution_id' => $contribution['id'],
    ));
    $this->callAPISuccess('contribution', 'sendconfirmation', array(
      'id' => $contribution['id'],
      'receipt_from_email' => 'api@civicrm.org',
    ));

    $mut->checkMailLog(array(
      'Annual CiviCRM meet',
      'Event',
      'To: "Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
    ), array());
    $mut->stop();
  }

  /**
   * This function does a GET & compares the result against the $params.
   *
   * Use as a double check on Creates.
   *
   * @param array $params
   * @param int $id
   * @param bool $delete
   */
  public function contributionGetnCheck($params, $id, $delete = TRUE) {

    $contribution = $this->callAPISuccess('Contribution', 'Get', array(
      'id' => $id,
    ));

    if ($delete) {
      $this->callAPISuccess('contribution', 'delete', array('id' => $id));
    }
    $this->assertAPISuccess($contribution, 0);
    $values = $contribution['values'][$contribution['id']];
    $params['receive_date'] = date('Y-m-d H:i:s', strtotime($params['receive_date']));
    // this is not returned in id format
    unset($params['payment_instrument_id']);
    $params['contribution_source'] = $params['source'];
    unset($params['source']);
    foreach ($params as $key => $value) {
      $this->assertEquals($value, $values[$key], $key . " value: $value doesn't match " . print_r($values, TRUE));
    }
  }

  /**
   * Create a pending contribution & linked pending pledge record.
   */
  public function createPendingPledgeContribution() {

    $pledgeID = $this->pledgeCreate(array('contact_id' => $this->_individualId, 'installments' => 1, 'amount' => 500));
    $this->_ids['pledge'] = $pledgeID;
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge($this->_params, array(
      'contribution_status_id' => 'Pending',
      'total_amount' => 500,
    )));
    $paymentID = $this->callAPISuccessGetValue('PledgePayment', array(
      'options' => array('limit' => 1),
      'return' => 'id',
    ));
    $this->callAPISuccess('PledgePayment', 'create', array(
      'id' => $paymentID,
      'contribution_id' =>
      $contribution['id'],
      'status_id' => 'Pending',
      'scheduled_amount' => 500,
    ));

    return $contribution['id'];
  }

  /**
   * Create a pending contribution & linked pending participant record (along with an event).
   */
  public function createPendingParticipantContribution() {
    $event = $this->eventCreate(array('is_email_confirm' => 1, 'confirm_from_email' => 'test@civicrm.org'));
    $participantID = $this->participantCreate(array('event_id' => $event['id'], 'status_id' => 6, 'contact_id' => $this->_individualId));
    $this->_ids['participant'] = $participantID;
    $params = array_merge($this->_params, array('contact_id' => $this->_individualId, 'contribution_status_id' => 2, 'financial_type_id' => 'Event Fee'));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('participant_payment', 'create', array(
      'contribution_id' => $contribution['id'],
      'participant_id' => $participantID,
    ));
    $this->callAPISuccess('line_item', 'get', array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'api.line_item.create' => array(
        'entity_id' => $participantID,
        'entity_table' => 'civicrm_participant',
      ),
    ));
    return $contribution['id'];
  }

  /**
   * Get financial transaction amount.
   *
   * @param int $contId
   *
   * @return null|string
   */
  public function _getFinancialTrxnAmount($contId) {
    $query = "SELECT
     SUM( ft.total_amount ) AS total
     FROM civicrm_financial_trxn AS ft
     LEFT JOIN civicrm_entity_financial_trxn AS ceft ON ft.id = ceft.financial_trxn_id
     WHERE ceft.entity_table = 'civicrm_contribution'
     AND ceft.entity_id = {$contId}";

    $result = CRM_Core_DAO::singleValueQuery($query);
    return $result;
  }

  /**
   * @param int $contId
   *
   * @return null|string
   */
  public function _getFinancialItemAmount($contId) {
    $lineItem = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
    $query = "SELECT
     SUM(amount)
     FROM civicrm_financial_item
     WHERE entity_table = 'civicrm_line_item'
     AND entity_id = {$lineItem}";
    $result = CRM_Core_DAO::singleValueQuery($query);
    return $result;
  }

  /**
   * @param int $contId
   * @param $context
   */
  public function _checkFinancialItem($contId, $context) {
    if ($context != 'paylater') {
      $params = array(
        'entity_id' => $contId,
        'entity_table' => 'civicrm_contribution',
      );
      $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($params, TRUE));
      $entityParams = array(
        'financial_trxn_id' => $trxn['financial_trxn_id'],
        'entity_table' => 'civicrm_financial_item',
      );
      $entityTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
      $params = array(
        'id' => $entityTrxn['entity_id'],
      );
    }
    if ($context == 'paylater') {
      $lineItems = CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution');
      foreach ($lineItems as $key => $item) {
        $params = array(
          'entity_id' => $key,
          'entity_table' => 'civicrm_line_item',
        );
        $compareParams = array('status_id' => 1);
        $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $params, $compareParams);
      }
    }
    elseif ($context == 'refund') {
      $compareParams = array(
        'status_id' => 1,
        'financial_account_id' => 1,
        'amount' => -100,
      );
    }
    elseif ($context == 'cancelPending') {
      $compareParams = array(
        'status_id' => 3,
        'financial_account_id' => 1,
        'amount' => -100,
      );
    }
    elseif ($context == 'changeFinancial') {
      $lineKey = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
      $params = array(
        'entity_id' => $lineKey,
        'amount' => -100,
      );
      $compareParams = array(
        'financial_account_id' => 1,
      );
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $params, $compareParams);
      $params = array(
        'financial_account_id' => 3,
        'entity_id' => $lineKey,
      );
      $compareParams = array(
        'amount' => 100,
      );
    }
    if ($context != 'paylater') {
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $params, $compareParams);
    }
  }

  /**
   * Check correct financial transaction entries were created for the change in payment instrument.
   *
   * @param int $contributionID
   * @param int $originalInstrumentID
   * @param int $newInstrumentID
   * @param int $amount
   */
  public function checkFinancialTrxnPaymentInstrumentChange($contributionID, $originalInstrumentID, $newInstrumentID, $amount = 100) {

    $entityFinancialTrxns = $this->getFinancialTransactionsForContribution($contributionID);

    $originalTrxnParams = array(
      'to_financial_account_id' => CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($originalInstrumentID),
      'payment_instrument_id' => $originalInstrumentID,
      'amount' => $amount,
      'status_id' => 1,
    );

    $reversalTrxnParams = array(
      'to_financial_account_id' => CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($originalInstrumentID),
      'payment_instrument_id' => $originalInstrumentID,
      'amount' => -$amount,
      'status_id' => 1,
    );

    $newTrxnParams = array(
      'to_financial_account_id' => CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($newInstrumentID),
      'payment_instrument_id' => $newInstrumentID,
      'amount' => $amount,
      'status_id' => 1,
    );

    foreach (array($originalTrxnParams, $reversalTrxnParams, $newTrxnParams) as $index => $transaction) {
      $entityFinancialTrxn = $entityFinancialTrxns[$index];
      $this->assertEquals($entityFinancialTrxn['amount'], $transaction['amount']);

      $financialTrxn = $this->callAPISuccessGetSingle('FinancialTrxn', array(
        'id' => $entityFinancialTrxn['financial_trxn_id'],
      ));
      $this->assertEquals($transaction['status_id'], $financialTrxn['status_id']);
      $this->assertEquals($transaction['amount'], $financialTrxn['total_amount']);
      $this->assertEquals($transaction['amount'], $financialTrxn['net_amount']);
      $this->assertEquals(0, $financialTrxn['fee_amount']);
      $this->assertEquals($transaction['payment_instrument_id'], $financialTrxn['payment_instrument_id']);
      $this->assertEquals($transaction['to_financial_account_id'], $financialTrxn['to_financial_account_id']);

      // Generic checks.
      $this->assertEquals(1, $financialTrxn['is_payment']);
      $this->assertEquals('USD', $financialTrxn['currency']);
      $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($financialTrxn['trxn_date'])));
    }
  }

  /**
   * Check financial transaction.
   *
   * @todo break this down into sensible functions - most calls to it only use a few lines out of the big if.
   *
   * @param array $contribution
   * @param string $context
   * @param int $instrumentId
   * @param array $extraParams
   */
  public function _checkFinancialTrxn($contribution, $context, $instrumentId = NULL, $extraParams = array()) {
    $financialTrxns = $this->getFinancialTransactionsForContribution($contribution['id']);
    $trxn = array_pop($financialTrxns);

    $params = array(
      'id' => $trxn['financial_trxn_id'],
    );
    if ($context == 'payLater') {
      $compareParams = array(
        'status_id' => 1,
        'from_financial_account_id' => CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($contribution['financial_type_id'], 'Accounts Receivable Account is'),
      );
    }
    elseif ($context == 'refund') {
      $compareParams = array(
        'to_financial_account_id' => 6,
        'total_amount' => -100,
        'status_id' => 7,
        'trxn_date' => '2015-01-01 09:00:00',
        'trxn_id' => 'the refund',
      );
    }
    elseif ($context == 'cancelPending') {
      $compareParams = array(
        'to_financial_account_id' => 7,
        'total_amount' => -100,
        'status_id' => 3,
      );
    }
    elseif ($context == 'changeFinancial' || $context == 'paymentInstrument') {
      // @todo checkFinancialTrxnPaymentInstrumentChange instead for paymentInstrument.
      // It does the same thing with greater readability.
      // @todo remove handling for

      $entityParams = array(
        'entity_id' => $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'amount' => -100,
      );
      $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
      $trxnParams1 = array(
        'id' => $trxn['financial_trxn_id'],
      );
      if (empty($extraParams)) {
        $compareParams = array(
          'total_amount' => -100,
          'status_id' => 1,
        );
      }
      else {
        $compareParams = array(
          'total_amount' => 100,
          'status_id' => 1,
        );
      }
      if ($context == 'paymentInstrument') {
        $compareParams['to_financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($instrumentId);
        $compareParams['payment_instrument_id'] = $instrumentId;
      }
      else {
        $compareParams['to_financial_account_id'] = 12;
      }
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams1, array_merge($compareParams, $extraParams));
      $compareParams['total_amount'] = 100;
    }

    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $params, array_merge($compareParams, $extraParams));
  }

  /**
   * @return mixed
   */
  public function _addPaymentInstrument() {
    $gId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'payment_instrument', 'id', 'name');
    $optionParams = array(
      'option_group_id' => $gId,
      'label' => 'Test Card',
      'name' => 'Test Card',
      'value' => '6',
      'weight' => '6',
      'is_active' => 1,
    );
    $optionValue = $this->callAPISuccess('option_value', 'create', $optionParams);
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
    $financialParams = array(
      'entity_table' => 'civicrm_option_value',
      'entity_id' => $optionValue['id'],
      'account_relationship' => $relationTypeId,
      'financial_account_id' => 7,
    );
    CRM_Financial_BAO_FinancialTypeAccount::add($financialParams, CRM_Core_DAO::$_nullArray);
    $this->assertNotEmpty($optionValue['values'][$optionValue['id']]['value']);
    return $optionValue['values'][$optionValue['id']]['value'];
  }

  public function _deletedAddedPaymentInstrument() {
    $result = $this->callAPISuccess('OptionValue', 'get', array(
      'option_group_id' => 'payment_instrument',
      'name' => 'Test Card',
      'value' => '6',
      'is_active' => 1,
    ));
    if ($id = CRM_Utils_Array::value('id', $result)) {
      $this->callAPISuccess('OptionValue', 'delete', array('id' => $id));
    }
  }

  /**
   * Set up the basic recurring contribution for tests.
   *
   * @param array $generalParams
   *   Parameters that can be merged into the recurring AND the contribution.
   *
   * @param array $recurParams
   *   Parameters to merge into the recur only.
   *
   * @return array|int
   */
  protected function setUpRecurringContribution($generalParams = array(), $recurParams = array()) {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge(array(
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
    ), $generalParams, $recurParams));
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      array(
        'contribution_recur_id' => $contributionRecur['id'],
      ), $generalParams)
    );
    return $originalContribution;
  }

  /**
   * Set up a basic auto-renew membership for tests.
   *
   * @param array $generalParams
   *   Parameters that can be merged into the recurring AND the contribution.
   *
   * @param array $recurParams
   *   Parameters to merge into the recur only.
   *
   * @return array|int
   */
  protected function setUpAutoRenewMembership($generalParams = array(), $recurParams = array()) {
    $newContact = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual',
      'sort_name' => 'McTesterson, Testy',
      'display_name' => 'Testy McTesterson',
      'preferred_language' => 'en_US',
      'preferred_mail_format' => 'Both',
      'first_name' => 'Testy',
      'last_name' => 'McTesterson',
      'contact_is_deleted' => '0',
      'email_id' => '4',
      'email' => 'tmctesterson@example.com',
      'on_hold' => '0',
    ));
    $membershipType = $this->callAPISuccess('MembershipType', 'create', array(
      'domain_id' => "Default Domain Name",
      'member_of_contact_id' => 1,
      'financial_type_id' => "Member Dues",
      'duration_unit' => "month",
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'name' => "Standard Member",
      'minimum_fee' => 100,
    ));
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge(array(
      'contact_id' => $newContact['id'],
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
    ), $generalParams, $recurParams));

    $membership = $this->callAPISuccess('membership', 'create', array(
      'contact_id' => $newContact['id'],
      'contribution_recur_id' => $contributionRecur['id'],
      'financial_type_id' => "Member Dues",
      'membership_type_id' => $membershipType['id'],
      'num_terms' => 1,
      'skipLineItem' => TRUE,
    ));

    CRM_Price_BAO_LineItem::getLineItemArray($this->_params, NULL, 'membership', $membershipType['id']);
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      array(
        'contact_id' => $newContact['id'],
        'contribution_recur_id' => $contributionRecur['id'],
        'financial_type_id' => "Member Dues",
        'contribution_status_id' => 1,
        'invoice_id' => uniqid(),
      ), $generalParams)
    );
    $lineItem = $this->callAPISuccess('LineItem', 'getsingle', array());
    $this->assertEquals('civicrm_membership', $lineItem['entity_table']);
    $membership = $this->callAPISuccess('Membership', 'getsingle', array('id' => $lineItem['entity_id']));
    $this->callAPISuccess('LineItem', 'getsingle', array());
    $this->callAPISuccessGetCount('MembershipPayment', array('membership_id' => $membership['id']), 1);

    return array($originalContribution, $membership);
  }

  /**
   * Set up a repeat transaction.
   *
   * @param array $recurParams
   * @param mixed $flag
   * @param array $contributionParams
   * @return array
   */
  protected function setUpRepeatTransaction($recurParams = array(), $flag, $contributionParams = array()) {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge(array(
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ), $recurParams));

    $originalContribution = '';
    if ($flag == 'multiple') {
      // CRM-19309 create a contribution + also add in line_items (plural):
      $params = array_merge($this->_params, $contributionParams);
      $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
          $params,
          array(
            'contribution_recur_id' => $contributionRecur['id'],
            'skipLineItem' => 1,
            'api.line_item.create' => array(
              array(
                'price_field_id' => 1,
                'qty' => 2,
                'line_total' => '20',
                'unit_price' => '10',
                'financial_type_id' => 1,
              ),
              array(
                'price_field_id' => 1,
                'qty' => 1,
                'line_total' => '80',
                'unit_price' => '80',
                'financial_type_id' => 2,
              ),
            ),
          )
        )
      );
    }
    elseif ($flag == 'single') {
      $params = array_merge($this->_params, array('contribution_recur_id' => $contributionRecur['id']));
      $params = array_merge($params, $contributionParams);
      $originalContribution = $this->callAPISuccess('contribution', 'create', $params);
    }
    $originalContribution['payment_processor_id'] = $paymentProcessorID;
    return $originalContribution;
  }

  /**
   * Common set up routine.
   *
   * @return array
   */
  protected function setUpForCompleteTransaction() {
    $this->mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $params = array_merge($this->_params, array('contribution_status_id' => 2, 'receipt_date' => 'now'));
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    return $contribution;
  }

  /**
   * Test repeat contribution uses the Payment Processor' payment_instrument setting.
   */
  public function testRepeatTransactionWithNonCreditCardDefault() {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array(
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
    ));
    $contribution1 = $this->callAPISuccess('contribution', 'create', array_merge(
        $this->_params,
        array('contribution_recur_id' => $contributionRecur['id'], 'payment_instrument_id' => 2))
    );
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument('name');
    $contribution2 = $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
      'original_contribution_id' => $contribution1,
    ));
    $this->assertEquals(array_search('Debit Card', $paymentInstruments), $contribution2['values'][$contribution2['id']]['payment_instrument_id']);
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * CRM-20008 Tests repeattransaction creates pending membership.
   */
  public function testRepeatTransactionMembershipCreatePendingContribution() {
    list($originalContribution, $membership) = $this->setUpAutoRenewMembership();
    $this->callAPISuccess('membership', 'create', array(
      'id' => $membership['id'],
      'end_date' => 'yesterday',
      'status_id' => 'Expired',
    ));
    $repeatedContribution = $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_recur_id' => $originalContribution['values'][1]['contribution_recur_id'],
      'contribution_status_id' => 'Pending',
      'trxn_id' => uniqid(),
    ));
    $membershipStatusId = $this->callAPISuccess('membership', 'getvalue', array(
      'id' => $membership['id'],
      'return' => 'status_id',
    ));

    // Let's see if the membership payments got created while we're at it.
    $membershipPayments = $this->callAPISuccess('MembershipPayment', 'get', array(
      'membership_id' => $membership['id'],
    ));
    $this->assertEquals(2, $membershipPayments['count']);

    $this->assertEquals('Expired', CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'status_id', $membershipStatusId));
    $this->callAPISuccess('Contribution', 'completetransaction', array('id' => $repeatedContribution['id']));
    $membership = $this->callAPISuccessGetSingle('membership', array(
      'id' => $membership['id'],
      'return' => 'status_id, end_date',
    ));
    $this->assertEquals('New', CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membership['status_id']));
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 1 month')), $membership['end_date']);

    $this->quickCleanUpFinancialEntities();
    $this->contactDelete($originalContribution['values'][1]['contact_id']);
  }

  /**
   * Test sending a mail via the API.
   */
  public function testSendMailWithAPISetFromDetails() {
    $mut = new CiviMailUtils($this, TRUE);
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $this->callAPISuccess('contribution', 'sendconfirmation', array(
      'id' => $contribution['id'],
      'receipt_from_email' => 'api@civicrm.org',
      'receipt_from_name' => 'CiviCRM LLC',
    ));
    $mut->checkMailLog(array(
      'From: CiviCRM LLC <api@civicrm.org>',
      'Contribution Information',
      'Please print this confirmation for your records',
    ), array(
      'Event',
    ));
    $mut->stop();
  }

  /**
   * Test sending a mail via the API.
   */
  public function testSendMailWithNoFromSetFallToDomain() {
    $this->createLoggedInUser();
    $mut = new CiviMailUtils($this, TRUE);
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $this->callAPISuccess('contribution', 'sendconfirmation', array(
      'id' => $contribution['id'],
    ));
    $domain = $this->callAPISuccess('domain', 'getsingle', array('id' => 1));
    $mut->checkMailLog(array(
      'From: ' . $domain['from_name'] . ' <' . $domain['from_email'] . '>',
      'Contribution Information',
      'Please print this confirmation for your records',
    ), array(
      'Event',
    ));
    $mut->stop();
  }

  /**
   * Test sending a mail via the API.
   */
  public function testSendMailWithRepeatTransactionAPIFalltoDomain() {
    $this->createLoggedInUser();
    $mut = new CiviMailUtils($this, TRUE);
    $contribution = $this->setUpRepeatTransaction(array(), 'single');
    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
      'original_contribution_id' => $contribution,
    ));
    $domain = $this->callAPISuccess('domain', 'getsingle', array('id' => 1));
    $mut->checkMailLog(array(
      'From: ' . $domain['from_name'] . ' <' . $domain['from_email'] . '>',
      'Contribution Information',
      'Please print this confirmation for your records',
    ), array(
      'Event',
    )
    );
    $mut->stop();
  }

  /**
   * Test sending a mail via the API.
   */
  public function testSendMailWithRepeatTransactionAPIFalltoContributionPage() {
    $mut = new CiviMailUtils($this, TRUE);
    $contributionPage = $this->contributionPageCreate(array('receipt_from_name' => 'CiviCRM LLC', 'receipt_from_email' => 'contributionpage@civicrm.org', 'is_email_receipt' => 1));
    $paymentProcessorID = $this->paymentProcessorCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array(
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ));
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      array(
        'contribution_recur_id' => $contributionRecur['id'],
        'contribution_page_id' => $contributionPage['id'],
      ))
    );
    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
      'original_contribution_id' => $originalContribution,
    )
    );
    $mut->checkMailLog(array(
      'From: CiviCRM LLC <contributionpage@civicrm.org>',
      'Contribution Information',
      'Please print this confirmation for your records',
    ), array(
      'Event',
    ));
    $mut->stop();
  }

  /**
   * Test sending a mail via the API.
   */
  public function testSendMailWithRepeatTransactionAPIFalltoSystemFromNoDefaultFrom() {
    $mut = new CiviMailUtils($this, TRUE);
    $originalContribution = $contribution = $this->setUpRepeatTransaction(array(), 'single');
    $fromEmail = $this->CallAPISuccess('optionValue', 'get', array('is_default' => 1, 'option_group_id' => 'from_email_address', 'sequential' => 1));
    foreach ($fromEmail['values'] as $from) {
      $this->callAPISuccess('optionValue', 'create', array('is_default' => 0, 'id' => $from['id']));
    }
    $domain = $this->callAPISuccess('domain', 'getsingle', array('id' => CRM_Core_Config::domainID()));
    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
      'original_contribution_id' => $originalContribution,
    ));
    $mut->checkMailLog(array(
      'From: ' . $domain['name'] . ' <' . $domain['domain_email'] . '>',
      'Contribution Information',
      'Please print this confirmation for your records',
    ), array(
      'Event',
    ));
    $mut->stop();
  }

  /**
   * Create a Contribution Page with is_email_receipt = TRUE.
   *
   * @param array $params
   *   Params to overwrite with.
   *
   * @return array|int
   */
  protected function createReceiptableContributionPage($params = array()) {
    $contributionPage = $this->callAPISuccess('ContributionPage', 'create', array_merge(array(
      'receipt_from_name' => 'Mickey Mouse',
      'receipt_from_email' => 'mickey@mouse.com',
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'CAD',
      'is_monetary' => TRUE,
      'is_email_receipt' => TRUE,
    ), $params));
    return $contributionPage;
  }

  /**
   * function to test card_type and pan truncation.
   */
  public function testCardTypeAndPanTruncation() {
    $creditCardTypeIDs = array_flip(CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'));
    $contactId = $this->individualCreate();
    $params = array(
      'contact_id' => $contactId,
      'receive_date' => '2016-01-20',
      'total_amount' => 100,
      'financial_type_id' => 1,
      'payment_instrument' => 'Credit Card',
      'card_type_id' => $creditCardTypeIDs['Visa'],
      'pan_truncation' => 4567,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('card_type_id', 'pan_truncation'),
      )
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id', $financialTrxn), $creditCardTypeIDs['Visa']);
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), 4567);
    $params = array(
      'id' => $contribution['id'],
      'pan_truncation' => 2345,
      'card_type_id' => $creditCardTypeIDs['Amex'],
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('card_type_id', 'pan_truncation'),
      )
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id', $financialTrxn), $creditCardTypeIDs['Amex']);
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), 2345);
  }

  /**
   * Test repeat contribution uses non default currency
   * @see https://issues.civicrm.org/jira/projects/CRM/issues/CRM-20678
   */
  public function testRepeatTransactionWithDifferenceCurrency() {
    $originalContribution = $this->setUpRepeatTransaction(array('currency' => 'AUD'), 'single', array('currency' => 'AUD'));
    $contribution = $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));
    $this->assertEquals('AUD', $contribution['values'][$contribution['id']]['currency']);
  }

  /**
   * Get the financial items for the contribution.
   *
   * @param int $contributionID
   *
   * @return array
   *   Array of associated financial items.
   */
  protected function getFinancialTransactionsForContribution($contributionID) {
    $trxnParams = array(
      'entity_id' => $contributionID,
      'entity_table' => 'civicrm_contribution',
    );
    // @todo the following function has naming errors & has a weird signature & appears to
    // only be called from test classes. Move into test suite & maybe just use api
    // from this function.
    return array_merge(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($trxnParams, FALSE, array()));
  }

  /**
   * Test getunique api call for Contribution entity
   */
  public function testContributionGetUnique() {
    $result = $this->callAPIAndDocument($this->_entity, 'getunique', array(), __FUNCTION__, __FILE__);
    $this->assertEquals(2, $result['count']);
    $this->assertEquals(array('trxn_id'), $result['values']['UI_contrib_trxn_id']);
    $this->assertEquals(array('invoice_id'), $result['values']['UI_contrib_invoice_id']);
  }

  /**
   * Test Repeat Transaction Contribution with Tax amount.
   * https://lab.civicrm.org/dev/core/issues/806
   */
  public function testRepeatContributionWithTaxAmount() {
    $this->enableTaxAndInvoicing();
    $financialType = $this->callAPISuccess('financial_type', 'create', [
      'name' => 'Test taxable financial Type',
      'is_reserved' => 0,
      'is_active' => 1,
    ]);
    $this->relationForFinancialTypeWithFinancialAccount($financialType['id']);
    $contribution = $this->setUpRepeatTransaction(
      [],
      'single',
      [
        'financial_type_id' => $financialType['id'],
      ]
    );
    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $contribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
    ));
    $this->callAPISuccessGetCount('Contribution', [], 2);
  }

}
