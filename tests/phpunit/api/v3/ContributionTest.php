<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
  protected $_contributionTypeId = 1;
  protected $_apiversion;
  protected $_entity = 'Contribution';
  public $debug = 0;
  protected $_params;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
    $paymentProcessor = $this->processorCreate();
    $this->_params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id'   => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
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
      'version' => 3,
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

    $this->contributionTypeDelete();
    $this->quickCleanup(array(
      'civicrm_contribution',
      'civicrm_contribution_soft',
      'civicrm_event',
      'civicrm_contribution_page',
      'civicrm_participant',
      'civicrm_participant_payment',
      'civicrm_line_item',
      'civicrm_financial_trxn',
      'civicrm_financial_item',
      'civicrm_entity_financial_trxn',
      'civicrm_contact',
    ));
  }

  ///////////////// civicrm_contribution_get methods

  function testGetParamsNotArrayContribution() {
    $params = 'contact_id= 1';
    $contribution = $this->callAPIFailure('contribution', 'get', $params);
    $this->assertEquals($contribution['error_message'], 'Input variable `params` is not an array');
  }

  function testGetContribution() {
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );
    $this->_contribution = civicrm_api('contribution', 'create', $p);
    $this->assertAPISuccess($this->_contribution, 0, 'In line ' . __LINE__);

    $params = array(
      'contribution_id' => $this->_contribution['id'],
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'get', $params);
    $financialParams['id'] = $this->_contributionTypeId;
    $default = null;
    $financialType  =  CRM_Financial_BAO_FinancialType::retrieve($financialParams,$default);
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $this->assertEquals(1,$contribution['count']);
    $this->documentMe($params, $contribution, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    // note there was an assertion converting financial_type_id to 'Donation' which wasn't working.
    // passing back a string rather than an id seems like an error / cruft - & if it is to be introduced we should discuss
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['non_deductible_amount'], 10.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['fee_amount'], 5.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['net_amount'], 95.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 23456, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 78910, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_source'], 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed', 'In line ' . __LINE__);
    //create a second contribution - we are testing that 'id' gets the right contribution id (not the contact id)
    $p['trxn_id'] = '3847';
    $p['invoice_id'] = '3847';

    $contribution2 = civicrm_api('contribution', 'create', $p);
    $this->assertAPISuccess($contribution2, 'In line ' . __LINE__);

    $params = array(
      'version' => $this->_apiversion,
    );
    // now we have 2 - test getcount
    $contribution = civicrm_api('contribution', 'getcount', array(
      'version' => $this->_apiversion,
      ));
    $this->assertEquals(2, $contribution);
    //test id only format
    $contribution = civicrm_api('contribution', 'get', array
      ('version' => $this->_apiversion,
        'id' => $this->_contribution['id'],
        'format.only_id' => 1,
      )
    );
    $this->assertEquals($this->_contribution['id'], $contribution, print_r($contribution,true) . " in line " . __LINE__);
    //test id only format
    $contribution = civicrm_api('contribution', 'get', array
      ('version' => $this->_apiversion,
        'id' => $contribution2['id'],
        'format.only_id' => 1,
      )
    );
    $this->assertEquals($contribution2['id'], $contribution);
    $contribution = civicrm_api('contribution', 'get', array(
      'version' => $this->_apiversion,
        'id' => $this->_contribution['id'],
      ));
    //test id as field
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $this->assertEquals(1, $contribution['count'], 'In line ' . __LINE__);
    // $this->assertEquals($this->_contribution['id'], $contribution['id'] )  ;
    //test get by contact id works
    $contribution = civicrm_api('contribution', 'get', array('version' => $this->_apiversion, 'contact_id' => $this->_individualId));
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__ . "get with contact_id" . print_r(array('version' => $this->_apiversion, 'contact_id' => $this->_individualId), TRUE));

    $this->assertEquals(2, $contribution['count'], 'In line ' . __LINE__);
    civicrm_api('Contribution', 'Delete', array(
      'id' => $this->_contribution['id'],
        'version' => $this->_apiversion,
      ));
    civicrm_api('Contribution', 'Delete', array(
      'id' => $contribution2['id'],
        'version' => $this->_apiversion,
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
      'contribution_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );
    $this->_contribution = civicrm_api('contribution', 'create', $p);
    $this->assertAPISuccess($this->_contribution, 'In line ' . __LINE__);

    $params = array(
      'contribution_id' => $this->_contribution['id'],
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'get', $params);
    $financialParams['id'] = $this->_contributionTypeId;
    $default = null;
    $financialType  =  CRM_Financial_BAO_FinancialType::retrieve($financialParams,$default);
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $this->assertEquals(1,$contribution['count']);
    $this->documentMe($params, $contribution, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], $this->_contributionTypeId);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_type_id'], $this->_contributionTypeId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['non_deductible_amount'], 10.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['fee_amount'], 5.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['net_amount'], 95.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 23456, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 78910, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_source'], 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed', 'In line ' . __LINE__);
    //create a second contribution - we are testing that 'id' gets the right contribution id (not the contact id)
    $p['trxn_id'] = '3847';
    $p['invoice_id'] = '3847';

    $contribution2 = civicrm_api('contribution', 'create', $p);
    $this->assertAPISuccess($contribution2, 'In line ' . __LINE__);

    $params = array(
      'version' => $this->_apiversion,
    );
    // now we have 2 - test getcount
    $contribution = civicrm_api('contribution', 'getcount', array(
      'version' => $this->_apiversion,
    ));
    $this->assertEquals(2, $contribution);
    //test id only format
    $contribution = civicrm_api('contribution', 'get', array
      ('version' => $this->_apiversion,
        'id' => $this->_contribution['id'],
        'format.only_id' => 1,
      )
    );
    $this->assertEquals($this->_contribution['id'], $contribution, print_r($contribution,true) . " in line " . __LINE__);
    //test id only format
    $contribution = civicrm_api('contribution', 'get', array
      ('version' => $this->_apiversion,
        'id' => $contribution2['id'],
        'format.only_id' => 1,
      )
    );
    $this->assertEquals($contribution2['id'], $contribution);
    $contribution = civicrm_api('contribution', 'get', array(
      'version' => $this->_apiversion,
      'id' => $this->_contribution['id'],
    ));
    //test id as field
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $this->assertEquals(1, $contribution['count'], 'In line ' . __LINE__);
    // $this->assertEquals($this->_contribution['id'], $contribution['id'] )  ;
    //test get by contact id works
    $contribution = civicrm_api('contribution', 'get', array('version' => $this->_apiversion, 'contact_id' => $this->_individualId));
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__ . "get with contact_id" . print_r(array('version' => $this->_apiversion, 'contact_id' => $this->_individualId), TRUE));

    $this->assertEquals(2, $contribution['count'], 'In line ' . __LINE__);
    civicrm_api('Contribution', 'Delete', array(
    'id' => $this->_contribution['id'],
    'version' => $this->_apiversion,
    ));
    civicrm_api('Contribution', 'Delete', array(
    'id' => $contribution2['id'],
    'version' => $this->_apiversion,
    ));
  }
  ///////////////// civicrm_contribution_
  function testCreateEmptyContributionIDUseDonation() {
    $params = array(
      'contribution_id' => FALSE,
      'contact_id' => 1,
      'total_amount' => 1,
      'version' => 3,
      'check_permissions' => false,
      'financial_type_id' => 'Donation',
    );
    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
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
      'version' => 3,
      'check_permissions' => false,
      'contribution_type_id' => 3,
    );
    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $contribution = civicrm_api('contribution', 'getsingle', array('version' => $this->_apiversion, 'id' => $contribution['id']));
    $this->assertEquals(3, $contribution['financial_type_id']);
  }

  ///////////////// civicrm_contribution_
  function testCreateEmptyParamsContribution() {


    $params = array('version' => $this->_apiversion);
    $contribution = $this->callAPIFailure('contribution', 'create', $params);
    $this->assertEquals($contribution['error_message'], 'Mandatory key(s) missing from params array: financial_type_id, total_amount, contact_id', 'In line ' . __LINE__);
  }

  function testCreateParamsNotArrayContribution() {

    $params = 'contact_id= 1';
    $contribution = $this->callAPIFailure('contribution', 'create', $params);
    $this->assertEquals($contribution['error_message'], 'Input variable `params` is not an array');
  }

  function testCreateParamsWithoutRequiredKeys() {
    $params = array('version' => 3);
    $contribution = $this->callAPIFailure('contribution', 'create', $params);
    $this->assertEquals($contribution['error_message'], 'Mandatory key(s) missing from params array: financial_type_id, total_amount, contact_id');
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

    $result = civicrm_api($this->_entity, 'create', $params);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $check = civicrm_api($this->_entity, 'get', array(
        'return.custom_' . $ids['custom_field_id'] => 1,
        'version' => 3,
        'id' => $result['id'],
      )
    );
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
    $result     = civicrm_api('Contribution', 'getfields', array('version' => 3));
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
      'financial_type_id'      => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
      'skipLineItem' => 1,
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $lineItems = civicrm_api('line_item','get',array(
      'version' => $this->_apiversion,
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
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
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

    $contribution = civicrm_api('contribution', 'create', $params);
    $description = "Create Contribution with Nested Line Items";
    $subfile = "CreateWithNestedLineItems";
    $this->documentMe($params, $contribution, __FUNCTION__,__FILE__, $description, $subfile);
    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $lineItems = civicrm_api('line_item','get',array(
      'version' => $this->_apiversion,
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
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->documentMe($params, $contribution, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1, 'In line ' . __LINE__ );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1, 'In line ' . __LINE__);
    $lineItems = civicrm_api('line_item','get',array(
      'version' => $this->_apiversion,
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      ));
    $this->assertEquals(1, $lineItems['count']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $lineItems = civicrm_api('line_item','get',array(
        'version' => $this->_apiversion,
        'entity_id' => $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'sequential' => 1,
    ));
    $this->assertEquals(1, $lineItems['count']);
    $this->_checkFinancialRecords($contribution, 'offline');
    $this->contributionGetnCheck($params, $contribution['id']);
  }
  /*
   *
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
    civicrm_api('contribution','create',$params);
    $contribution = civicrm_api('contribution','create',$params2);
    $this->assertAPISuccess($contribution);
    $contribution = civicrm_api('contribution','get',array('version'=> 3, 'sequential' => 1, 'contribution_payment_instrument_id' => 'Cash'));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Cash',$contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1,$contribution['count']);
    $contribution = civicrm_api('contribution','get',array('version'=> 3, 'sequential' => 1, 'payment_instrument_id' => 'EFT'));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT',$contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = civicrm_api('contribution','get',array('version'=> 3, 'sequential' => 1, 'payment_instrument_id' => 5));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT',$contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1,$contribution['count']);
    $contribution = civicrm_api('contribution','get',array('version'=> 3, 'sequential' => 1, 'payment_instrument' => 'EFT'));
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = civicrm_api('contribution', 'update', array('id' => $contribution['id'], 'version' => $this->_apiversion, 'payment_instrument' => 'Credit Card'));
    $this->assertAPISuccess($contribution);
    $contribution = civicrm_api('contribution','get',array('version'=> 3, 'sequential' => 1, 'id' => $contribution['id'], ));
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
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'contribution_source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);

    $this->assertAPISuccess($contribution, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF', 'In line ' . __LINE__);
  }
  /*
     * Create test with unique field name on source
     */
  function testCreateContributionSourceInvalidContac() {

    $params = array(
      'contact_id' => 999,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'contribution_source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertEquals($contribution['error_message'], 'contact_id is not valid : 999', 'In line ' . __LINE__);
  }

  function testCreateContributionSourceInvalidContContac() {

    $params = array(
      'contribution_contact_id' => 999,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'contribution_source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertEquals($contribution['error_message'], 'contact_id is not valid : 999', 'In line ' . __LINE__);
  }

  function testCreateContributionWithNote() {
    $description = "Demonstrates creating contribution with Note Entity";
    $subfile     = "ContributionCreateWithNote";
    $params      = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
      'note' => 'my contribution note',
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->documentMe($params, $contribution, __FUNCTION__, __FILE__, $description, $subfile);
    $result = civicrm_api('note', 'get', array('version' => 3, 'entity_table' => 'civicrm_contribution', 'entity_id' => $contribution['id'], 'sequential' => 1));
    $this->assertAPISuccess($result);
    $this->assertEquals('my contribution note', $result['values'][0]['note']);
    civicrm_api('contribution', 'delete', array('version' => 3, 'id' => $contribution['id']));
  }

  function testCreateContributionWithNoteUniqueNameAliases() {
    $params      = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
      'contribution_note' => 'my contribution note',
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $result = civicrm_api('note', 'get', array('version' => 3, 'entity_table' => 'civicrm_contribution', 'entity_id' => $contribution['id'], 'sequential' => 1));
    $this->assertAPISuccess($result);
    $this->assertEquals('my contribution note', $result['values'][0]['note']);
    civicrm_api('contribution', 'delete', array('version' => 3, 'id' => $contribution['id']));
  }
  /*
     * This is the test for creating soft credits - however a 'get' is not yet possible via API
     * as the current BAO functions are contact-centric (from what I can find)
     *
     */
  function testCreateContributionWithSoftCredt() {
    $description = "Demonstrates creating contribution with SoftCredit";
    $subfile     = "ContributionCreateWithSoftCredit";
    $contact2    = civicrm_api('Contact', 'create', array('version' => 3, 'display_name' => 'superman', 'version' => 3, 'contact_type' => 'Individual'));
    $params      = $this->_params + array(
      'soft_credit_to' => $contact2['id'],

    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->assertAPISuccess($contribution);
    $this->documentMe($params, $contribution, __FUNCTION__, __FILE__, $description, $subfile);
    //     $result = civicrm_api('contribution','get', array('version' => 3,'return'=> 'soft_credit_to', 'sequential' => 1));
    //     $this->assertAPISuccess($result);
    //     $this->assertEquals($contact2['id'], $result['values'][$result['id']]['soft_credit_to']) ;
    //    well - the above doesn't work yet so lets do SQL
    $query = "SELECT count(*) FROM civicrm_contribution_soft WHERE contact_id = " . $contact2['id'];

    $count = CRM_Core_DAO::singleValueQuery($query);
    $this->assertEquals(1, $count);

    civicrm_api('contribution', 'delete', array('version' => 3, 'id' => $contribution['id']));
    civicrm_api('contact', 'delete', array('version' => 3, 'id' => $contact2['id']));
  }

  /**
   *  Test  using example code
   */
  function testContributionCreateExample() {
    //make sure at least on page exists since there is a truncate in tear down
    $page = civicrm_api('contribution_page', 'create', $this->_pageParams);
    $this->assertAPISuccess($page);
    require_once 'api/v3/examples/ContributionCreate.php';
    $result         = contribution_create_example();
    $this->assertAPISuccess($result);
    $contributionId = $result['id'];
    $expectedResult = contribution_create_expectedresult();
    $this->checkArrayEquals($result, $expectedResult);
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
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->documentMe($params, $contribution, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1, 'In line ' . __LINE__ );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1, 'In line ' . __LINE__);
    $lineItems = civicrm_api('line_item','get',array(
      'version' => $this->_apiversion,
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      ));
    $this->assertEquals(1, $lineItems['count']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $lineItems = civicrm_api('line_item','get',array(
        'version' => $this->_apiversion,
        'entity_id' => $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'sequential' => 1,
    ));
    $this->assertEquals(1, $lineItems['count']);
    $this->_checkFinancialRecords($contribution, 'feeAmount');
  }


  /*
   * Function tests that additional financial records are created when online contribution is created
   */
  function testCreateContributionOnline() {
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $contributionPage = civicrm_api( 'contribution_page','create',  $this->_pageParams );
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
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->documentMe($params, $contribution, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1, 'In line ' . __LINE__ );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1, 'In line ' . __LINE__);
    $this->_checkFinancialRecords($contribution, 'online');
  }

  /*
   * Function tests that additional financial records are created when online contribution with pay later option
   * is created
   */
  function testCreateContributionPayLaterOnline() {
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $this->_pageParams['is_pay_later'] = 1;
    $contributionPage = civicrm_api( 'contribution_page','create',$this->_pageParams );
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
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->documentMe($params, $contribution, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1, 'In line ' . __LINE__ );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 2, 'In line ' . __LINE__);
    $this->_checkFinancialRecords($contribution, 'payLater');
  }

  /*
   * Function tests that additional financial records are created when online contribution with pending option
   * is created
   */
  function testCreateContributionPendingOnline() {
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $contributionPage = civicrm_api( 'contribution_page', 'create', $this->_pageParams );
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
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);
    $this->documentMe($params, $contribution, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'],1, 'In line ' . __LINE__ );
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 2, 'In line ' . __LINE__);
    $this->_checkFinancialRecords($contribution, 'pending');
  }

  /*
   * Function tests that line items, financial records are updated when contribution amount is changed
   */
  function testCreateUpdateContributionChangeTotal() {
    $contribution = civicrm_api('contribution', 'create', $this->_params);
    $lineItems = civicrm_api('line_item','getvalue', array(
      'version' => $this->_apiversion,
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
      'version' => $this->_apiversion,
      'id' => $contribution['id'],
      'total_amount' => '125');
    $contribution = civicrm_api('contribution', 'update', $newParams);

    $lineItems = civicrm_api('line_item','getvalue',array(
        'version' => $this->_apiversion,
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
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 2,
      'is_pay_later' => 1,
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'create', $contribParams);

    $newParams = array_merge($contribParams, array(
      'id' => $contribution['id'],
      'contribution_status_id' => 1,)
    );
    $contribution = civicrm_api('contribution', 'update', $newParams);
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
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'create', $contribParams);

    $newParams = array_merge($contribParams, array(
     'id' => $contribution['id'],
     'payment_instrument_id' => $instrumentId,)
    );
    $contribution = civicrm_api('contribution', 'update', $newParams);
    $this->assertAPISuccess($contribution);
    $this->_checkFinancialTrxn($contribution, 'paymentInstrument');
  }

  /*
   * Function tests that financial records are added when Contribution is Refunded
   */
  function testCreateUpdateContributionRefund() {
    $contribParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, array(
     'id' => $contribution['id'],
     'contribution_status_id' => 7,
      )
    );

    $contribution = civicrm_api('contribution', 'update', $newParams);
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
      'version' => 3,
    );
    $contribution = civicrm_api('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, array(
     'id' => $contribution['id'],
     'contribution_status_id' => 2,
      )
    );
    $contribution = $this->callAPIFailure('contribution', 'update', $newParams);
    $this->assertEquals($contribution['error_message'], ts('Cannot change contribution status from Completed to Pending.'), 'In line ' . __LINE__);

  }

  /*
   * Function tests that financial records are added when Pending Contribution is Canceled
   */
  function testCreateUpdateContributionCancelPending() {
    $contribParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 2,
      'is_pay_later' => 1,
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, array(
     'id' => $contribution['id'],
     'contribution_status_id' => 3,
      )
    );
    $contribution = civicrm_api('contribution', 'update', $newParams);
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
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, array(
     'id' => $contribution['id'],
     'financial_type_id' => 3,
      )
    );
    $contribution = civicrm_api('contribution', 'update', $newParams);
    $this->_checkFinancialTrxn($contribution, 'changeFinancial');
    $this->_checkFinancialItem($contribution['id'], 'changeFinancial');
  }

  //To Update Contribution
  //CHANGE: we require the API to do an incremental update
  function testCreateUpdateContribution() {

    $contributionID = $this->contributionCreate($this->_individualId, $this->_contributionTypeId, 'idofsh', 212355);
    $old_params = array(
      'contribution_id' => $contributionID,
      'version' => $this->_apiversion,
    );
    $original = civicrm_api('contribution', 'get', $old_params);
    //Make sure it came back
    $this->assertAPISuccess($original, 'In line ' . __LINE__);
    $this->assertEquals($original['id'], $contributionID, 'In line ' . __LINE__);
    //set up list of old params, verify

    //This should not be required on update:
    $old_contact_id = $original['values'][$contributionID]['contact_id'];
    $old_payment_instrument = $original['values'][$contributionID]['instrument_id'];
    $old_fee_amount = $original['values'][$contributionID]['fee_amount'];
    $old_source = $original['values'][$contributionID]['contribution_source'];

    //note: current behavior is to return ISO.  Is this
    //documented behavior?  Is this correct
    $old_receive_date = date('Ymd', strtotime($original['values'][$contributionID]['receive_date']));

    $old_trxn_id = $original['values'][$contributionID]['trxn_id'];
    $old_invoice_id = $original['values'][$contributionID]['invoice_id'];

    //check against values in CiviUnitTestCase::createContribution()
    $this->assertEquals($old_contact_id, $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($old_fee_amount, 5.00, 'In line ' . __LINE__);
    $this->assertEquals($old_source, 'SSF', 'In line ' . __LINE__);
    $this->assertEquals($old_trxn_id, 212355, 'In line ' . __LINE__);
    $this->assertEquals($old_invoice_id, 'idofsh', 'In line ' . __LINE__);
    $params = array(
      'id' => $contributionID,
      'contact_id' => $this->_individualId,
      'total_amount' => 110.00,
      'financial_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'net_amount' => 100.00,
      'contribution_status_id' => 1,
      'note' => 'Donating for Nobel Cause',
      'version' => $this->_apiversion,
    );

    $contribution = civicrm_api('contribution', 'create', $params);

    $new_params = array(
      'contribution_id' => $contribution['id'],
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'get', $new_params);

    $this->assertEquals($contribution['values'][$contributionID]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['total_amount'], 110.00, 'In line ' . __LINE__);
        $this->assertEquals($contribution['values'][$contributionID]['financial_type_id'],$this->_contributionTypeId, 'In line ' . __LINE__ );
    $this->assertEquals($contribution['values'][$contributionID]['instrument_id'], $old_payment_instrument, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['non_deductible_amount'], 10.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['fee_amount'], $old_fee_amount, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['net_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['trxn_id'], $old_trxn_id, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['invoice_id'], $old_invoice_id, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['contribution_source'], $old_source, 'In line ' . __LINE__);
    $this->assertEquals($contribution['values'][$contributionID]['contribution_status'], 'Completed', 'In line ' . __LINE__);
    $params = array(
      'contribution_id' => $contributionID,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('contribution', 'delete', $params);
    $this->assertAPISuccess($result, 'in line' . __LINE__);
  }

  ///////////////// civicrm_contribution_delete methods
  function testDeleteEmptyParamsContribution() {
    $params = array('version' => $this->_apiversion);
    $contribution = $this->callAPIFailure('contribution', 'delete', $params);
  }

  function testDeleteParamsNotArrayContribution() {
    $params = 'contribution_id= 1';
    $contribution = $this->callAPIFailure('contribution', 'delete', $params);
    $this->assertEquals($contribution['error_message'], 'Input variable `params` is not an array');
  }

  function testDeleteWrongParamContribution() {
    $params = array(
      'contribution_source' => 'SSF',
      'version' => $this->_apiversion,
    );
    $contribution = $this->callAPIFailure('contribution', 'delete', $params);
  }

  function testDeleteContribution() {

    $contributionID = $this->contributionCreate($this->_individualId, $this->_contributionTypeId, 'dfsdf', 12389);
    $params = array(
      'id' => $contributionID,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('contribution', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 0, 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_contribution_search with empty params.
   *  All available contributions expected.
   */
  function testSearchEmptyParams() {
    $params = array('version' => $this->_apiversion);

    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution', 'create', $p);

    $result = civicrm_api('contribution', 'get', $params);
    // We're taking the first element.
    $res = $result['values'][$contribution['id']];

    $this->assertEquals($p['contact_id'], $res['contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['total_amount'], $res['total_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['financial_type_id'], $res['financial_type_id'], 'In line ' . __LINE__ );
    $this->assertEquals($p['net_amount'], $res['net_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['non_deductible_amount'], $res['non_deductible_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['fee_amount'], $res['fee_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p['trxn_id'], $res['trxn_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['invoice_id'], $res['invoice_id'], 'In line ' . __LINE__);
    $this->assertEquals($p['source'], $res['contribution_source'], 'In line ' . __LINE__);
    // contribution_status_id = 1 => Completed
    $this->assertEquals('Completed', $res['contribution_status'], 'In line ' . __LINE__);

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
      'financial_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );
    $contribution1 = civicrm_api('contribution', 'create', $p1);

    $p2 = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 200.00,
      'financial_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 20.00,
      'trxn_id' => 5454565,
      'invoice_id' => 1212124,
      'fee_amount' => 50.00,
      'net_amount' => 60.00,
      'contribution_status_id' => 2,
      'version' => $this->_apiversion,
    );
    $contribution2 = civicrm_api('contribution', 'create', $p2);

    $params = array(
      'contribution_id' => $contribution2['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('contribution', 'get', $params);
    $res = $result['values'][$contribution2['id']];

    $this->assertEquals($p2['contact_id'], $res['contact_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['total_amount'], $res['total_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['financial_type_id'], $res['financial_type_id'], 'In line ' . __LINE__ );
    $this->assertEquals($p2['net_amount'], $res['net_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['non_deductible_amount'], $res['non_deductible_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['fee_amount'], $res['fee_amount'], 'In line ' . __LINE__);
    $this->assertEquals($p2['trxn_id'], $res['trxn_id'], 'In line ' . __LINE__);
    $this->assertEquals($p2['invoice_id'], $res['invoice_id'], 'In line ' . __LINE__);
    // contribution_status_id = 2 => Pending
    $this->assertEquals('Pending', $res['contribution_status'], 'In line ' . __LINE__);

    $this->contributionDelete($contribution1['id']);
    $this->contributionDelete($contribution2['id']);
  }
  /*
   * Test sending a mail via the API
   */
  function testSendMail() {
    $mut = new CiviMailUtils( $this, true );
    $contribution = civicrm_api('contribution','create',$this->_params);
    $this->assertAPISuccess($contribution);
    $apiResult = civicrm_api('contribution', 'sendconfirmation', array(
      'version' => $this->_apiversion,
      'id' => $contribution['id'],
      'receipt_from_email' => 'api@civicrm.org',
      )
    );
    $this->assertAPISuccess($apiResult);
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

  /*
   * Test sending a mail via the API
   */
  function testSendMailEvent() {
    $mut = new CiviMailUtils( $this, true );
    $contribution = civicrm_api('contribution','create',$this->_params);
    $event          = $this->eventCreate(array(
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
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'create', $participantParams);
    $this->assertAPISuccess($participant, "participant created in line " . __LINE__);
    $this->assertAPISuccess(civicrm_api('participant_payment', 'create', array(
      'version' => 3,
      'participant_id' => $participant['id'],
      'contribution_id' => $contribution['id'],
    )), " in line " . __LINE__);
    $apiResult = civicrm_api('contribution', 'sendconfirmation', array(
      'version' => $this->_apiversion,
      'id' => $contribution['id'],
      'receipt_from_email' => 'api@civicrm.org',
      )
    );

    $this->assertAPISuccess($apiResult);
    $mut->checkMailLog(array(
        'Annual CiviCRM meet',
        'Event',
        'To: "Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
      ), array(

      )
    );
    $mut->stop();
  }

  ///////////////  _civicrm_contribute_format_params for $create
  function testFormatParams() {
    require_once 'CRM/Contribute/DAO/Contribution.php';
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'contribution_status_id' => 1,
      'financial_type' => null,
      'note' => 'note',
      'contribution_source' => 'test',
    );

    $values = array();
    $result = _civicrm_api3_contribute_format_params($params, $values, TRUE);
    $this->assertEquals($values['total_amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($values['contribution_status_id'], 1, 'In line ' . __LINE__);
  }
  /*
     * This function does a GET & compares the result against the $params
     * Use as a double check on Creates
     */
  function contributionGetnCheck($params, $id, $delete = 1) {

    $contribution = civicrm_api('Contribution', 'Get', array(
      'id' => $id,
        'version' => $this->_apiversion,
      ));

    if ($delete) {
      civicrm_api('contribution', 'delete', array(
        'id' => $id,
          'version' => $this->_apiversion,
        ));
    }
    $this->assertAPISuccess($contribution, 0, 'In line ' . __LINE__);
    $values = $contribution['values'][$contribution['id']];
    $params['receive_date'] = date('Y-m-d H:i:s', strtotime($params['receive_date']));
    // this is not returned in id format
    unset($params['payment_instrument_id']);
    $params['contribution_source'] = $params['source'];
    unset($params['source']);
    foreach ($params as $key => $value) {
      if ($key == 'version') {
        continue;
      }
      $this->assertEquals($value, $values[$key], $key . " value: $value doesn't match " . print_r($values, TRUE) . 'in line' . __LINE__);
    }
  }

 function _getFinancialTrxnAmount($contId) {
   $query = "SELECT
     SUM( ft.total_amount ) AS total
     FROM civicrm_financial_trxn AS ft
     LEFT JOIN civicrm_entity_financial_trxn AS ceft ON ft.id = ceft.financial_trxn_id
     WHERE ceft.entity_table = 'civicrm_contribution'
     AND ceft.entity_id = {$contId}";

   $result = CRM_Core_DAO::singleValueQuery($query);
   return $result;
 }

 function _getFinancialItemAmount($contId) {
   $lineItem = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
   $query = "SELECT
     SUM(amount)
     FROM civicrm_financial_item
     WHERE entity_table = 'civicrm_line_item'
     AND entity_id = {$lineItem}";
   $result = CRM_Core_DAO::singleValueQuery($query);
   return $result;
 }

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

 function _checkFinancialTrxn($contribution, $context) {
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
   elseif ($context == 'paymentInstrument') {
     $compareParams = array(
       'from_financial_account_id' => 6,
       'to_financial_account_id'  =>  7,
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
   elseif ($context == 'changeFinancial') {
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
       'to_financial_account_id' => 12,
       'total_amount' => -100,
       'status_id' => 1,
     );
     $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams1, $compareParams);
     $compareParams = array(
       'to_financial_account_id' => 12,
       'total_amount' => 100,
       'status_id' => 1,
     );
   }

   $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $params, $compareParams);
 }

 function _addPaymentInstrument () {
   $gId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'payment_instrument', 'id', 'name');
   $optionParams = array(
     'option_group_id' => $gId,
     'label' => 'Test Card',
     'name' => 'Test Card',
     'value' => '6',
     'weight' => '6',
     'is_active' => 1,
     'version' => 3,
);
   $optionValue = civicrm_api('option_value', 'create', $optionParams);
   $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
   $financialParams = array(
     'entity_table' => 'civicrm_option_value',
     'entity_id' => $optionValue['id'],
     'account_relationship' => $relationTypeId,
     'financial_account_id' => 7,
   );
   $financialType = CRM_Financial_BAO_FinancialTypeAccount::add($financialParams, CRM_Core_DAO::$_nullArray);
   $this->assertNotEmpty($optionValue['values'][$optionValue['id']]['value']);
   return $optionValue['values'][$optionValue['id']]['value'];
 }

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
   $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn',$trxnParams,$compareParams);
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

