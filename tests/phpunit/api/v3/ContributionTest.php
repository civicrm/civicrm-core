<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/CiviMailUtils.php';


/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Contribution
 */

class api_v3_ContributionTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data
   */
  protected $_individualId;
  protected $_contribution;
  protected $_financialTypeId = 1;
  protected $_apiversion;
  protected $_entity = 'Contribution';
  public $debug = 0;
  protected $_params;
  protected $_ids = array();
  protected $_pageParams = array();

  function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
    $paymentProcessor = $this->processorCreate();
    $this->_params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id'   => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
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
    $this->_pageParams = array(
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'USD',
      'financial_account_id' => 1,
      'payment_processor' => $paymentProcessor->id,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    );
  }

  function tearDown() {
    $this->quickCleanUpFinancialEntities();
  }

  function testGetContribution() {
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

    $contribution = $this->callAPIAndDocument('contribution', 'get', $params, __FUNCTION__, __FILE__);
    $financialParams['id'] = $this->_financialTypeId;
    $default = null;
    CRM_Financial_BAO_FinancialType::retrieve($financialParams, $default);

    $this->assertEquals(1, $contribution['count']);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    // note there was an assertion converting financial_type_id to 'Donation' which wasn't working.
    // passing back a string rather than an id seems like an error / cruft - & if it is to be introduced we should discuss
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['non_deductible_amount'], 10.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['fee_amount'], 5.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['net_amount'], 95.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 23456);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 78910);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    //create a second contribution - we are testing that 'id' gets the right contribution id (not the contact id)
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
    $this->assertEquals($this->_contribution['id'], $contribution, print_r($contribution,true) . " in line " . __LINE__);
    //test id only format
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'id' => $contribution2['id'],
      'format.only_id' => 1,
    ));
    $this->assertEquals($contribution2['id'], $contribution);
    //test id as field
    $contribution = $this->callAPISuccess('contribution', 'get', array(
      'id' => $this->_contribution['id'],
    ));
    $this->assertEquals(1, $contribution['count']);

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
   * We need to ensure previous tested behaviour still works as part of the api contract
   */
  function testGetContributionLegacyBehaviour() {
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
    $this->_contribution = $this->callAPISuccess('contribution', 'create', $p);

    $params = array(
      'contribution_id' => $this->_contribution['id'],
    );
    $contribution = $this->callAPIAndDocument('contribution', 'get', $params, __FUNCTION__, __FILE__);
    $financialParams['id'] = $this->_financialTypeId;
    $default = null;
    CRM_Financial_BAO_FinancialType::retrieve($financialParams, $default);

    $this->assertEquals(1,$contribution['count']);
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
    //create a second contribution - we are testing that 'id' gets the right contribution id (not the contact id)
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
    $this->assertEquals($this->_contribution['id'], $contribution, print_r($contribution,true) . " in line " . __LINE__);
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
  ///////////////// civicrm_contribution_
  function testCreateEmptyContributionIDUseDonation() {
    $params = array(
      'contribution_id' => FALSE,
      'contact_id' => 1,
      'total_amount' => 1,
      'check_permissions' => false,
      'financial_type_id' => 'Donation',
    );
    $this->callAPISuccess('contribution', 'create', $params);
  }
  /*
   * ensure we continue to support contribution_type_id as part of the api commitment to
   * stability
   *///////////////// civicrm_contribution_

  function testCreateLegacyBehaviour() {
    $params = array(
      'contribution_id' => FALSE,
      'contact_id' => 1,
      'total_amount' => 1,
      'check_permissions' => false,
      'contribution_type_id' => 3,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array( 'id' => $contribution['id']));
    $this->assertEquals(3, $contribution['financial_type_id']);
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPIAndDocument($this->_entity, 'create', $params,  __FUNCTION__, __FILE__);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);
    $check = $this->callAPISuccess($this->_entity, 'get', array(
      'return.custom_' . $ids['custom_field_id'] => 1,
      'id' => $result['id'],
    ));
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testCreateGetFieldsWithCustom() {
    $ids        = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $idsContact = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTest.php');
    $result     = $this->callAPISuccess('Contribution', 'getfields', array());
    $this->assertArrayHasKey('custom_' . $ids['custom_field_id'], $result['values']);
    $this->assertArrayNotHasKey('custom_' . $idsContact['custom_field_id'], $result['values']);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->customFieldDelete($idsContact['custom_field_id']);
    $this->customGroupDelete($idsContact['custom_group_id']);
  }

  function testCreateContributionNoLineItems() {

    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id'      => $this->_financialTypeId,
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
    $lineItems = $this->callAPISuccess('line_item','get',array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(0, $lineItems['count']);
  }
  /*
   * Test checks that passing in line items suppresses the create mechanism
   */
  function testCreateContributionChainedLineItems() {

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

    $description = "Create Contribution with Nested Line Items";
    $subfile = "CreateWithNestedLineItems";
    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__,__FILE__, $description, $subfile);

    $lineItems = $this->callAPISuccess('line_item','get',array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(2, $lineItems['count']);
  }

  function testCreateContributionOffline() {
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

    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1 );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1);
    $lineItems = $this->callAPISuccess('line_item','get',array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(1, $lineItems['count']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $lineItems = $this->callAPISuccess('line_item','get',array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(1, $lineItems['count']);
    $this->_checkFinancialRecords($contribution, 'offline');
    $this->contributionGetnCheck($params, $contribution['id']);
  }
  /**
   * test create with valid payment instument
   */
  function testCreateContributionWithPaymentInstrument() {
    $params = $this->_params + array('payment_instrument' => 'EFT');
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->callAPISuccess('contribution','get', array(
      'sequential' => 1,
      'id' => $contribution['id']
    ));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT', $contribution['values'][0]['payment_instrument']);

    $this->callAPISuccess('contribution', 'create', array('id' => $contribution['id'], 'payment_instrument' => 'Credit Card'));
    $contribution = $this->callAPISuccess('contribution','get', array(
      'sequential' => 1,
      'id' => $contribution['id']
    ));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Credit Card', $contribution['values'][0]['payment_instrument']);
  }

  function testGetContributionByPaymentInstrument() {
    $params = $this->_params + array('payment_instrument' => 'EFT');
    $params2 = $this->_params + array('payment_instrument' => 'Cash');
    $this->callAPISuccess('contribution','create',$params);
    $this->callAPISuccess('contribution','create',$params2);
    $contribution = $this->callAPISuccess('contribution','get',array( 'sequential' => 1, 'contribution_payment_instrument_id' => 'Cash'));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Cash',$contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1,$contribution['count']);
    $contribution = $this->callAPISuccess('contribution','get',array('sequential' => 1, 'payment_instrument_id' => 'EFT'));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT',$contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('contribution','get',array( 'sequential' => 1, 'payment_instrument_id' => 5));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT',$contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1,$contribution['count']);
    $contribution = $this->callAPISuccess('contribution','get',array( 'sequential' => 1, 'payment_instrument' => 'EFT'));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('contribution', 'create', array('id' => $contribution['id'], 'payment_instrument' => 'Credit Card'));
    $contribution = $this->callAPISuccess('contribution','get',array( 'sequential' => 1, 'id' => $contribution['id'], ));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Credit Card',$contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1,$contribution['count']);
  }

  /*
     * Create test with unique field name on source
     */
  function testCreateContributionSource() {

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

  /*
   * Create test with unique field name on source
  */
  function testCreateDefaultNow() {

    $params = $this->_params;
    unset($params['receive_date']);

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->callAPISuccessGetSingle('contribution', array('id' => $contribution['id']));
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contribution['receive_date'])));
  }

  /*
     * Create test with unique field name on source
     */
  function testCreateContributionSourceInvalidContac() {

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

  function testCreateContributionSourceInvalidContContact() {

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

    $this->callAPIFailure('contribution', 'create', $params, 'contact_id is not valid : 999');
  }

  /**
   * test note created correctly
   */
  function testCreateContributionWithNote() {
    $description = "Demonstrates creating contribution with Note Entity";
    $subfile     = "ContributionCreateWithNote";
    $params      = array(
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
    $result = $this->callAPISuccess('note', 'get', array('entity_table' => 'civicrm_contribution', 'entity_id' => $contribution['id'], 'sequential' => 1));
    $this->assertEquals('my contribution note', $result['values'][0]['note']);
    $this->callAPISuccess('contribution', 'delete', array('id' => $contribution['id']));
  }

  function testCreateContributionWithNoteUniqueNameAliases() {
    $params      = array(
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
    $result = $this->callAPISuccess('note', 'get', array('entity_table' => 'civicrm_contribution', 'entity_id' => $contribution['id'], 'sequential' => 1));
    $this->assertEquals('my contribution note', $result['values'][0]['note']);
    $this->callAPISuccess('contribution', 'delete', array('id' => $contribution['id']));
  }
  /*
     * This is the test for creating soft credits - however a 'get' is not yet possible via API
     * as the current BAO functions are contact-centric (from what I can find)
     *
     */
  function testCreateContributionWithSoftCredt() {
    $description = "Demonstrates creating contribution with SoftCredit";
    $subfile     = "ContributionCreateWithSoftCredit";
    $contact2    = $this->callAPISuccess('Contact', 'create', array('display_name' => 'superman', 'contact_type' => 'Individual'));
    $params      = $this->_params + array(
      'soft_credit_to' => $contact2['id'],

    );

    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    //     $result = $this->callAPISuccess('contribution','get', array('return'=> 'soft_credit_to', 'sequential' => 1));
    //     $this->assertEquals($contact2['id'], $result['values'][$result['id']]['soft_credit_to']) ;
    //    well - the above doesn't work yet so lets do SQL
    $query = "SELECT count(*) FROM civicrm_contribution_soft WHERE contact_id = " . $contact2['id'];

    $count = CRM_Core_DAO::singleValueQuery($query);
    $this->assertEquals(1, $count);

    $this->callAPISuccess('contribution', 'delete', array('id' => $contribution['id']));
    $this->callAPISuccess('contact', 'delete', array('id' => $contact2['id']));
  }

  /**
   *  Test  using example code
   */
  function testContributionCreateExample() {
    //make sure at least on page exists since there is a truncate in tear down
    $page = $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    $this->assertAPISuccess($page);
    require_once 'api/v3/examples/Contribution/Create.php';
    $result         = contribution_create_example();
    $this->assertAPISuccess($result);
    $contributionId = $result['id'];
    $expectedResult = contribution_create_expectedresult();
    $this->checkArrayEquals($expectedResult, $result);
    $this->contributionDelete($contributionId);
  }

  /*
   * Function tests that additional financial records are created when fee amount is recorded
   */
  function testCreateContributionWithFee() {
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

    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1 );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1);

    $lineItems = $this->callAPISuccess('line_item','get',array(

      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(1, $lineItems['count']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $lineItems = $this->callAPISuccess('line_item','get',array(

      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(1, $lineItems['count']);
    $this->_checkFinancialRecords($contribution, 'feeAmount');
  }


  /**
   * Function tests that additional financial records are created when online contribution is created
   */
  function testCreateContributionOnline() {
    CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $contributionPage = $this->callAPISuccess( 'contribution_page','create',  $this->_pageParams );
    $this->assertAPISuccess($contributionPage);
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'contribution_page_id' => $contributionPage['id'],
      'payment_processor' => 1,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,

    );

    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1 );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1);
    $this->_checkFinancialRecords($contribution, 'online');
  }

  /**
   * in the interests of removing financial type / contribution type checks from
   * legacy format function lets test that the api is doing this for us
   */
  function testCreateInvalidFinancialType() {
    $params = $this->_params;
    $params['financial_type_id'] = 99999;
    $this->callAPIFailure($this->_entity, 'create', $params, "'99999' is not a valid option for field financial_type_id");
  }

  /**
   * in the interests of removing financial type / contribution type checks from
   * legacy format function lets test that the api is doing this for us
   */
  function testValidNamedFinancialType() {
    $params = $this->_params;
    $params['financial_type_id'] = 'Donation';
    $this->callAPISuccess($this->_entity, 'create', $params);
  }

  /**
   * Function tests that additional financial records are created when online contribution with pay later option
   * is created
   */
  function testCreateContributionPayLaterOnline() {
    CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $this->_pageParams['is_pay_later'] = 1;
    $contributionPage = $this->callAPISuccess( 'contribution_page','create',$this->_pageParams );
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
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1 );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 2);
    $this->_checkFinancialRecords($contribution, 'payLater');
  }

  /*
   * Function tests that additional financial records are created when online contribution with pending option
   * is created
   */
  function testCreateContributionPendingOnline() {
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $contributionPage = $this->callAPISuccess( 'contribution_page', 'create', $this->_pageParams );
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

    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1 );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 2);
    $this->_checkFinancialRecords($contribution, 'pending');
  }

  /*
   * Function tests that line items, financial records are updated when contribution amount is changed
   */
  function testCreateUpdateContributionChangeTotal() {
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $lineItems = $this->callAPISuccess('line_item','getvalue', array(

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
      'total_amount' => '125');
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);

    $lineItems = $this->callAPISuccess('line_item','getvalue',array(

      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => 'line_total',
    ));

    $this->assertEquals('125.00', $lineItems);
    $trxnAmount = $this->_getFinancialTrxnAmount($contribution['id']);
    $fitemAmount = $this->_getFinancialItemAmount($contribution['id']);
    // Financial trxn SUM = 125 + 5 (fee)
    $this->assertEquals('130.00', $trxnAmount);
    $this->assertEquals('125.00', $fitemAmount);
  }

  /*
   * Function tests that line items, financial records are updated when pay later contribution is received
   */
  function testCreateUpdateContributionPayLater() {
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
        'contribution_status_id' => 1,)
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $contribution = $contribution['values'][$contribution['id']];
    $this->assertEquals($contribution['contribution_status_id'],'1');
    $this->_checkFinancialItem($contribution['id'], 'paylater');
    $this->_checkFinancialTrxn($contribution, 'payLater');
  }

  /*
   * Function tests that financial records are updated when Payment Instrument is changed
   */
  function testCreateUpdateContributionPaymentInstrument() {
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
        'payment_instrument_id' => $instrumentId,)
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->assertAPISuccess($contribution);
    $this->_checkFinancialTrxn($contribution, 'paymentInstrument', $instrumentId);
  }

  /*
   * Function tests that financial records are added when Contribution is Refunded
   */
  function testCreateUpdateContributionRefund() {
    $contribParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,

    );
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, array(
        'id' => $contribution['id'],
        'contribution_status_id' => 7,
      )
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'refund');
    $this->_checkFinancialItem($contribution['id'], 'refund');
  }

  /*
   * Function tests invalid contribution status change
   */
  function testCreateUpdateContributionInValidStatusChange() {
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
      )
    );
    $this->callAPIFailure('contribution', 'create', $newParams,ts('Cannot change contribution status from Completed to Pending.'));

  }

  /*
   * Function tests that financial records are added when Pending Contribution is Canceled
   */
  function testCreateUpdateContributionCancelPending() {
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
      )
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'cancelPending');
    $this->_checkFinancialItem($contribution['id'], 'cancelPending');
  }

  /*
   * Function tests that financial records are added when Financial Type is Changed
   */
  function testCreateUpdateContributionChangeFinancialType() {
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
      )
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'changeFinancial');
    $this->_checkFinancialItem($contribution['id'], 'changeFinancial');
  }

  /**
   * test that update does not change status id CRM-15105
   */
  function testCreateUpdateWithoutChangingPendingStatus() {
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge($this->_params, array('contribution_status_id' => 2)));
    $this->callAPISuccess('contribution', 'create', array('id' => $contribution['id'], 'source' => 'new source'));
    $contribution = $this->callAPISuccess('contribution', 'getsingle', array('id' => $contribution['id'], 'api.contribution.delete' => 1));
    $this->assertEquals(2, $contribution['contribution_status_id']);
  }
  //To Update Contribution
  //CHANGE: we require the API to do an incremental update
  function testCreateUpdateContribution() {

    $contributionID = $this->contributionCreate($this->_individualId, $this->_financialTypeId, 'idofsh', 212355);
    $old_params = array(
      'contribution_id' => $contributionID,

    );
    $original = $this->callAPISuccess('contribution', 'get', $old_params);
    //Make sure it came back
    $this->assertAPISuccess($original);
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
    $this->assertEquals($old_invoice_id, 'idofsh');
    $params = array(
      'id' => $contributionID,
      'contact_id' => $this->_individualId,
      'total_amount' => 110.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'net_amount' => 100.00,
      'contribution_status_id' => 1,
      'note' => 'Donating for Nobel Cause',

    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $new_params = array(
      'contribution_id' => $contribution['id'],

    );
    $contribution = $this->callAPISuccess('contribution', 'get', $new_params);

    $this->assertEquals($contribution['values'][$contributionID]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contributionID]['total_amount'], 110.00);
    $this->assertEquals($contribution['values'][$contributionID]['financial_type_id'],$this->_financialTypeId );
    $this->assertEquals($contribution['values'][$contributionID]['instrument_id'], $old_payment_instrument);
    $this->assertEquals($contribution['values'][$contributionID]['non_deductible_amount'], 10.00);
    $this->assertEquals($contribution['values'][$contributionID]['fee_amount'], $old_fee_amount);
    $this->assertEquals($contribution['values'][$contributionID]['net_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contributionID]['trxn_id'], $old_trxn_id);
    $this->assertEquals($contribution['values'][$contributionID]['invoice_id'], $old_invoice_id);
    $this->assertEquals($contribution['values'][$contributionID]['contribution_source'], $old_source);
    $this->assertEquals($contribution['values'][$contributionID]['contribution_status'], 'Completed');
    $params = array(
      'contribution_id' => $contributionID,

    );
    $result = $this->callAPISuccess('contribution', 'delete', $params);
    $this->assertAPISuccess($result, 'in line' . __LINE__);
  }

  ///////////////// civicrm_contribution_delete methods
  function testDeleteEmptyParamsContribution() {
    $params = array();
    $this->callAPIFailure('contribution', 'delete', $params);
  }

  function testDeleteParamsNotArrayContribution() {
    $params = 'contribution_id= 1';
    $contribution = $this->callAPIFailure('contribution', 'delete', $params);
    $this->assertEquals($contribution['error_message'], 'Input variable `params` is not an array');
  }

  function testDeleteWrongParamContribution() {
    $params = array(
      'contribution_source' => 'SSF',

    );
    $this->callAPIFailure('contribution', 'delete', $params);
  }

  function testDeleteContribution() {

    $contributionID = $this->contributionCreate($this->_individualId, $this->_financialTypeId, 'dfsdf', 12389);
    $params = array(
      'id' => $contributionID,
    );
    $this->callAPIAndDocument('contribution', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   *  Test civicrm_contribution_search with empty params.
   *  All available contributions expected.
   */
  function testSearchEmptyParams() {
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
    $this->assertEquals($p['financial_type_id'], $res['financial_type_id'] );
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
   *  Test civicrm_contribution_search. Success expected.
   */
  function testSearch() {
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
    $this->assertEquals($p2['financial_type_id'], $res['financial_type_id'] );
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
   * Test completing a transaction via the API
   *
   * Note that we are creating a logged in user because email goes out from
   * that person
   */
  function testCompleteTransaction() {
    $mut = new CiviMailUtils( $this, true );
    $this->createLoggedInUser();
    $params = array_merge($this->_params, array('contribution_status_id' => 2,));
    $contribution = $this->callAPISuccess('contribution','create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', array(
      'id' => $contribution['id'],
    ));
    $contribution = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id'], 'sequential' => 1,));
    $this->assertEquals('Completed', $contribution['values'][0]['contribution_status']);
    $mut->checkMailLog(array(
      'Receipt - Contribution',
      'Please print this confirmation for your records.',
    ));
    $mut->stop();
  }

  /**
   * Test repeat contribution successfully creates line items.
   */
  function testRepeatTransaction() {
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
      array('contribution_recur_id' => $contributionRecur['id']))
    );

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

    $this->quickCleanUpFinancialEntities();
  }

  /**
   * CRM-16397 test appropriate action if total amount has changed for single line items.
   */
  public function testRepeatTransactionAlteredAmount() {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $campaignID = $this->campaignCreate();
    $campaignID2 = $this->campaignCreate();
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
          'campaign_id' => $campaignID,
        ))
    );

    $this->callAPISuccess('contribution', 'repeattransaction', array(
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => uniqid(),
      'total_amount' => '400',
      'fee_amount' => 50,
      'campaign_id' => $campaignID2,
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
      'campaign_id' => $campaignID2,
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
      ));

    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, array(
      'entity_id' => $originalContribution['id'] + 1,
    )));
    unset($expectedLineItem['id'], $expectedLineItem['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($expectedLineItem, $lineItem2['values'][0]);

    $this->quickCleanUpFinancialEntities();
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
   * CRM-14151
   * Test completing a transaction via the API
   *
   * tests.
   */
  function testCompleteTransactionWithReceiptDateSet() {
    $mut = new CiviMailUtils( $this, true );
    $this->createLoggedInUser();
    $params = array_merge($this->_params, array('contribution_status_id' => 2,'receipt_date' => 'now'));
    $contribution = $this->callAPISuccess('contribution','create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', array('id' => $contribution['id'],));
    $contribution = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id'], 'sequential' => 1,));
    $this->assertEquals('Completed', $contribution['values'][0]['contribution_status']);
    $mut->checkMailLog(array(
      'Receipt - Contribution',
      'Please print this confirmation for your records.',
    ));
    $mut->stop();
  }

  /**
   * Test completing a transaction with an event via the API
   *
   * Note that we are creating a logged in user because email goes out from
   * that person
   */
  function testCompleteTransactionWithParticipantRecord() {
    $mut = new CiviMailUtils( $this, true );
    $mut->clearMessages();
    $this->createLoggedInUser();
    $contributionID = $this->createPendingParticipantContribution();
    $this->callAPISuccess('contribution', 'completetransaction', array(
        'id' => $contributionID,)
    );
    $participantStatus = $this->callAPISuccessGetValue('participant', array('id' => $this->_ids['participant'], 'return' => 'participant_status_id'));
    $this->assertEquals(1, $participantStatus);
    $mut->checkMailLog(array(
      'Annual CiviCRM meet',
      'Event',
      'This letter is a confirmation that your registration has been received and your status has been updated to Registered.',
    ));
    $mut->stop();
  }

  /**
   * test membership is renewed when transaction completed
   */
  function testCompleteTransactionMembershipPriceSet() {
    $this->createPriceSetWithPage('membership');
    $this->setUpPendingContribution($this->_ids['price_field_value'][0]);
    $this->callAPISuccess('contribution', 'completetransaction', array('id' =>  $this->_ids['contribution']));
    $membership = $this->callAPISuccess('membership', 'getsingle', array('id' => $this->_ids['membership']));
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 1 year')), $membership['end_date']);
    $this->cleanUpAfterPriceSets();
  }

  /**
   * test membership is renewed when transaction completed
   */
  function testCompleteTransactionMembershipPriceSetTwoTerms() {
    $this->createPriceSetWithPage('membership');
    $this->setUpPendingContribution($this->_ids['price_field_value'][1]);
    $this->callAPISuccess('contribution', 'completetransaction', array('id' =>  $this->_ids['contribution']));
    $membership = $this->callAPISuccess('membership', 'getsingle', array('id' => $this->_ids['membership']));
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 2 years')), $membership['end_date']);
    $this->cleanUpAfterPriceSets();
  }

  function cleanUpAfterPriceSets() {
    $this->quickCleanUpFinancialEntities();
    $this->contactDelete($this->_ids['contact']);
    $this->callAPISuccess('price_set', 'delete', array('id' => $this->_ids['price_set']));
  }


  /**
   * this could be merged with 4.5 function setup in api_v3_ContributionPageTest::setUpContributionPage
   * on parent class at some point (fn is not in 4.4)
   * @param $entity
   * @param array $params
   */
  function createPriceSetWithPage($entity, $params = array()) {
    $membershipTypeID = $this->membershipTypeCreate();
    $contributionPageResult = $this->callAPISuccess('contribution_page', 'create', array(
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => 50,
      'is_pay_later' => 1,
      'is_monetary' => TRUE,
      'is_email_receipt' => FALSE,
    ));
    $priceSet = $this->callAPISuccess('price_set', 'create', array(
      'is_quick_config' => 0,
      'extends' => 'CiviMember',
      'financial_type_id' => 1,
      'title' => 'my Page'
    ));
    $priceSetID = $priceSet['id'];

    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageResult['id'], $priceSetID );
    $priceField = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => $priceSetID ,
      'label' => 'Goat Breed',
      'html_type' => 'Radio',
    ));
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID ,
        'price_field_id' => $priceField['id'],
        'label' => 'Long Haired Goat',
        'amount' => 20,
        'membership_type_id' => $membershipTypeID,
        'membership_num_terms' => 1,
      )
    );
    $this->_ids['price_field_value'] = array($priceFieldValue['id']);
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID ,
        'price_field_id' => $priceField['id'],
        'label' => 'Shoe-eating Goat',
        'amount' => 10,
        'membership_type_id' => $membershipTypeID,
        'membership_num_terms' => 2,
      )
    );
    $this->_ids['price_field_value'][] = $priceFieldValue['id'];
    $this->_ids['price_set'] = $priceSetID;
    $this->_ids['contribution_page'] = $contributionPageResult['id'];
    $this->_ids['price_field'] = array($priceField['id']);

    $this->_ids['membership_type'] = $membershipTypeID;
  }

  /**
   * Set up a pending transaction with a specific price field id
   * @param $priceFieldValueID
   */
  function setUpPendingContribution($priceFieldValueID){
    $contactID = $this->individualCreate();
    $membership = $this->callAPISuccess('membership', 'create', array(
      'contact_id' => $contactID,
      'membership_type_id' => $this->_ids['membership_type'],
      'start_date' => 'yesterday - 1 year',
      'end_date' => 'yesterday',
    ));
    $contribution = $this->callAPISuccess('contribution', 'create', array(
      'domain_id' => 1,
      'contact_id' => $contactID,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 'Credit Card',
      'non_deductible_amount' => 10.00,
      'trxn_id' => 'jdhfi88',
      'invoice_id' => 'djfhiewuyr',
      'source' => 'SSF',
      'contribution_status_id' => 2,
      'contribution_page_id' => $this->_ids['contribution_page'],
      'api.membership_payment.create' => array('membership_id' => $membership['id']),
    ));

    $this->callAPISuccess('line_item', 'create', array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
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
   * Test sending a mail via the API
   */
  function testSendMail() {
    $mut = new CiviMailUtils( $this, true );
    $contribution = $this->callAPISuccess('contribution','create',$this->_params);
    $this->callAPISuccess('contribution', 'sendconfirmation', array(
        'id' => $contribution['id'],
        'receipt_from_email' => 'api@civicrm.org',
      )
    );
    $mut->checkMailLog(array(
        '$ 100.00',
        'Contribution Information',
        'Please print this confirmation for your records',
      ), array(
        'Event'
      )
    );
    $mut->stop();
  }

  /**
   * Test sending a mail via the API
   */
  function testSendMailEvent() {
    $mut = new CiviMailUtils( $this, true );
    $contribution = $this->callAPISuccess('contribution','create',$this->_params);
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
      )
    );

    $mut->checkMailLog(array(
        'Annual CiviCRM meet',
        'Event',
        'To: "Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
      ), array(

      )
    );
    $mut->stop();
  }

  /**
   * This function does a GET & compares the result against the $params
   * Use as a double check on Creates
   */
  function contributionGetnCheck($params, $id, $delete = 1) {

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
      $this->assertEquals($value, $values[$key], $key . " value: $value doesn't match " . print_r($values, TRUE) . 'in line' . __LINE__);
    }
  }

  /**
   * Create a pending contribution & linked pending participant record
   * (along with an event)
   */
  function createPendingParticipantContribution(){
    $event = $this->eventCreate(array('is_email_confirm' => 1, 'confirm_from_email' => 'test@civicrm.org',));
    $participantID = $this->participantCreate(array('event_id' => $event['id'], 'status_id' => 6));
    $this->_ids['participant']  = $participantID;
    $params = array_merge($this->_params, array('contribution_status_id' => 2, 'financial_type_id' => 'Event Fee'));
    $contribution = $this->callAPISuccess('contribution','create', $params);
    $this->callAPISuccess('participant_payment', 'create', array('contribution_id' => $contribution['id'], 'participant_id' => $participantID));
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
   * @param $contId
   *
   * @return null|string
   */function _getFinancialTrxnAmount($contId) {
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
   * @param $contId
   *
   * @return null|string
   */function _getFinancialItemAmount($contId) {
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
   * @param $contId
   * @param $context
   */
  function _checkFinancialItem($contId, $context) {
    if ($context != 'paylater') {
      $params = array (
        'entity_id' =>   $contId,
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
      foreach ($lineItems as $key=>$item) {
        $params = array(
          'entity_id' => $key,
          'entity_table' => 'civicrm_line_item',
        );
        $compareParams = array ('status_id' => 1);
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
   * @param $contribution
   * @param $context
   * @param null $instrumentId
   */
  function _checkFinancialTrxn($contribution, $context, $instrumentId = NULL) {
    $trxnParams = array(
      'entity_id' =>   $contribution['id'],
      'entity_table' => 'civicrm_contribution',
    );
    $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($trxnParams, TRUE));
    $params = array(
      'id' => $trxn['financial_trxn_id'],
    );
    if ($context == 'payLater') {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
      $compareParams = array(
        'status_id' => 1,
        'from_financial_account_id' => CRM_Contribute_PseudoConstant::financialAccountType($contribution['financial_type_id'], $relationTypeId),
      );
    }
    elseif ($context == 'refund') {
      $compareParams = array(
        'to_financial_account_id' => 6,
        'total_amount' => -100,
        'status_id' => 7,
      );
    }
    elseif ($context == 'cancelPending') {
      $compareParams = array(
        'from_financial_account_id' => 7,
        'total_amount' => -100,
        'status_id' => 3,
      );
    }
    elseif ($context == 'changeFinancial' || $context == 'paymentInstrument') {
      $entityParams = array(
        'entity_id' =>   $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'amount' => -100,
      );
      $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
      $trxnParams1 = array(
        'id' => $trxn['financial_trxn_id'],
      );
      $compareParams = array(
        'total_amount' => -100,
        'status_id' => 1,
      );
      if ($context == 'paymentInstrument') {
        $compareParams += array(
          'to_financial_account_id' => CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount(4),
          'payment_instrument_id' => 4,
        );
      }
      else {
        $compareParams['to_financial_account_id'] = 12;
      }
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams1, $compareParams);
      $compareParams['total_amount'] = 100;
      if ($context == 'paymentInstrument') {
        $compareParams['to_financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($instrumentId);
        $compareParams['payment_instrument_id'] = $instrumentId;
      }
      else {
        $compareParams['to_financial_account_id'] = 12;
      }
    }

    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $params, $compareParams);
  }

  /**
   * @return mixed
   */
  function _addPaymentInstrument () {
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

  /**
   * @param $params
   * @param $context
   */
  function _checkFinancialRecords($params,$context) {
    $entityParams = array(
      'entity_id' => $params['id'],
      'entity_table' => 'civicrm_contribution',
    );
    if ($context == 'pending') {
      $trxn = CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams);
      $this->assertNull($trxn, 'No Trxn to be created until IPN callback');
      return;
    }
    $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $trxnParams = array(
      'id' => $trxn['financial_trxn_id'],
    );
    if ($context != 'online' && $context != 'payLater') {
      $compareParams = array(
        'to_financial_account_id' => 6,
        'total_amount' => 100,
        'status_id' => 1,
      );
    }
    if ($context == 'feeAmount') {
      $compareParams['fee_amount'] = 50;
    }
    elseif ($context == 'online') {
      $compareParams = array(
        'to_financial_account_id' => 12,
        'total_amount' => 100,
        'status_id' => 1,
      );
    }
    elseif ($context == 'payLater') {
      $compareParams = array(
        'to_financial_account_id' => 7,
        'total_amount' => 100,
        'status_id' => 2,
      );
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn',$trxnParams, $compareParams);
    $entityParams = array(
      'financial_trxn_id' => $trxn['financial_trxn_id'],
      'entity_table' => 'civicrm_financial_item',
    );
    $entityTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $fitemParams = array(
      'id' => $entityTrxn['entity_id'],
    );
    $compareParams = array(
      'amount' => 100,
      'status_id' => 1,
      'financial_account_id' => 1,
    );
    if ($context == 'payLater') {
      $compareParams = array(
        'amount' => 100,
        'status_id' => 3,
        'financial_account_id' => 1,
      );
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $fitemParams, $compareParams);
    if ($context == 'feeAmount') {
      $maxParams = array(
        'entity_id' => $params['id'],
        'entity_table' => 'civicrm_contribution',
      );
      $maxTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($maxParams, TRUE));
      $trxnParams = array(
        'id' => $maxTrxn['financial_trxn_id'],
      );
      $compareParams = array(
        'to_financial_account_id' => 5,
        'from_financial_account_id' => 6,
        'total_amount' => 50,
        'status_id' => 1,
      );
      $trxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($params['id'], 'DESC');
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams, $compareParams);
      $fitemParams = array(
        'entity_id' => $trxnId['financialTrxnId'],
        'entity_table' => 'civicrm_financial_trxn',
      );
      $compareParams = array(
        'amount' => 50,
        'status_id' => 1,
        'financial_account_id' => 5,
      );
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $fitemParams, $compareParams);
    }
  }
}

