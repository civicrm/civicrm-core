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

/**
 * Test class for Pledge API - civicrm_pledge_*
 *
 *  @package CiviCRM_APIv3
 */
class api_v3_PledgeTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data
   */
  protected $_individualId;
  protected $_pledge;
  protected $_apiversion;
  protected $_params;
  protected $_entity;
  protected $scheduled_date;
  public $DBResetRequired = True;
  public $_eNoticeCompliant = FALSE;

  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->quickCleanup(array('civicrm_pledge', 'civicrm_pledge_payment'));
    //need to set scheduled payment in advance we are running test @ midnight & it becomes unexpectedly overdue
    //due to timezone issues
    $this->scheduled_date = date('Ymd', mktime(0, 0, 0, date("m"), date("d") + 2, date("y")));
    $this->_entity = 'Pledge';
    $this->_individualId = $this->individualCreate(NULL);
    $this->_params = array(
      'contact_id' => $this->_individualId,
      'pledge_create_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'scheduled_date' => $this->scheduled_date,
      'amount' => 100.00,
      'pledge_status_id' => '2',
      'pledge_financial_type_id' => '1',
      'pledge_original_installment_amount' => 20,
      'frequency_interval' => 5,
      'frequency_unit' => 'year',
      'frequency_day' => 15,
      'installments' => 5,
      'sequential' => 1,
      'version' => $this->_apiversion,
    );
  }

  function tearDown() {
    $this->contactDelete($this->_individualId);
  }

  ///////////////// civicrm_pledge_get methods

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
    $this->assertAPISuccess($result, " testCreateWithCustom ");
    $this->assertAPISuccess($result,  ' in line ' . __LINE__);
    $getparams = array('version' => 3, 'id' => $result['id'], 'return.custom_' . $ids['custom_field_id'] => 1);
    $check = civicrm_api($this->_entity, 'get', $getparams);
    civicrm_api('pledge', 'delete', array('id' => $check['values'][$check['id']], 'version' => 3));
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /*
   *
   */
  function testgetfieldspledge() {
    $result = civicrm_api('pledge', 'getfields', array('version' => 3, 'action' => 'get'));
    $this->assertEquals(1, $result['values']['next_pay_date']['api.return']);
  }

  function testGetPledge() {


    $this->_pledge = civicrm_api('pledge', 'create', $this->_params);
    $params = array(
      'pledge_id' => $this->_pledge['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('pledge', 'get', $params);
    $pledge = $result['values'][$this->_pledge['id']];
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($this->_individualId, $pledge['contact_id'], 'in line' . __LINE__);
    $this->assertEquals($this->_pledge['id'], $pledge['pledge_id'], 'in line' . __LINE__);
    $this->assertEquals(date('Y-m-d') . ' 00:00:00', $pledge['pledge_create_date'], 'in line' . __LINE__);
    $this->assertEquals(100.00, $pledge['pledge_amount'], 'in line' . __LINE__);
    $this->assertEquals('Pending', $pledge['pledge_status'], 'in line' . __LINE__);
    $this->assertEquals(5, $pledge['pledge_frequency_interval'], 'in line' . __LINE__);
    $this->assertEquals('year', $pledge['pledge_frequency_unit'], 'in line' . __LINE__);
    $this->assertEquals(date('Y-m-d', strtotime($this->scheduled_date)) . ' 00:00:00', $pledge['pledge_next_pay_date'], 'in line' . __LINE__);
    $this->assertEquals($pledge['pledge_next_pay_amount'], 20.00, 'in line' . __LINE__);

    $params2 = array(
      'pledge_id' => $this->_pledge['id'],
      'version' => $this->_apiversion,
    );
    $pledge = civicrm_api('pledge', 'delete', $params2);
  }
  /**
   * test  'return.pledge_financial_type' => 1 works
   */
  function testGetPledgewithReturn() {

    $this->_pledge = civicrm_api('pledge', 'create', $this->_params);
    $params = array(
      'pledge_id' => $this->_pledge['id'],
      'version' => $this->_apiversion,
      'return.pledge_financial_type' => 1,
    );
    $result = civicrm_api('pledge', 'get', $params);
    $pledge = $result['values'][$this->_pledge['id']];
    civicrm_api('pledge', 'delete', $pledge);
    $this->assertEquals('Donation', $pledge['pledge_financial_type']);
  }
  /**
   * test  'return.pledge_contribution_type' => 1 works
   * This is for legacy compatibility
  */
  function testGetPledgewithReturnLegacy() {

    $this->_pledge = civicrm_api('pledge', 'create', $this->_params);
    $params = array(
      'pledge_id' => $this->_pledge['id'],
      'version' => $this->_apiversion,
      'return.pledge_financial_type' => 1,
    );
    $result = civicrm_api('pledge', 'get', $params);
    $pledge = $result['values'][$this->_pledge['id']];
    civicrm_api('pledge', 'delete', $pledge);
    $this->assertEquals('Donation', $pledge['pledge_financial_type']);
  }

  function testPledgeGetReturnFilters() {
    $oldPledge = civicrm_api('pledge', 'create', $this->_params);

    $overdueParams = array(
      'scheduled_date' => 'first saturday of march last year',
      'start_date' => 'first saturday of march last year',
    );
    $oldPledge = civicrm_api('pledge', 'create', array_merge($this->_params, $overdueParams));

    $pledgeGetParams = array('version' => 3);
    $allPledges = civicrm_api('pledge', 'getcount', $pledgeGetParams);

    $this->assertEquals(2, $allPledges, 'Check we have 2 pledges to place with in line ' . __LINE__);
    $pledgeGetParams['pledge_start_date_high'] = date('YmdHis', strtotime('2 days ago'));
    $earlyPledge = civicrm_api('pledge', 'get', $pledgeGetParams);
    $this->documentMe($pledgeGetParams, $earlyPledge, __FUNCTION__, __FILE__, "demonstrates high date filter", "GetFilterHighDate");
    $this->assertEquals(1, $earlyPledge['count'], ' check only one returned with start date filter in line ' . __LINE__);
    $this->assertEquals($oldPledge['id'], $earlyPledge['id'], ' check correct pledge returned ' . __LINE__);
  }
  /*
   * create 2 pledges - see if we can get by status id
   */
  function testGetOverduePledge() {
    $overdueParams = array(
      'scheduled_date' => 'first saturday of march last year',
      'start_date' => 'first saturday of march last year',
    );
    $this->_pledge = civicrm_api('pledge', 'create', array_merge($this->_params, $overdueParams));
    $params = array(
      'version' => $this->_apiversion,
      'pledge_status_id' => '6',
    );
    $result = civicrm_api('pledge', 'get', $params);
    $emptyResult = civicrm_api('pledge', 'get', array(
      'version' => $this->_apiversion,
        'pledge_status_id' => '1',
      ));
    $pledge = $result['values'][$this->_pledge['id']];
    civicrm_api('pledge', 'delete', $pledge);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(0, $emptyResult['count']);
  }


  /*
   * create 2 pledges - see if we can get by status id
   */
  function testSortParamPledge() {
    $pledge1 = civicrm_api('pledge', 'create', $this->_params);
    $overdueParams = array(
      'scheduled_date' => 'first saturday of march last year',
      'start_date' => 'first saturday of march last year',
      'create_date' => 'first saturday of march last year',
    );
    $pledge2 = civicrm_api('pledge', 'create', array_merge($this->_params, $overdueParams));
    $params = array(
      'version' => $this->_apiversion,
      'pledge_is_test' => 0,
      'rowCount' => 1,
    );
    $result = civicrm_api('pledge', 'get', $params);

    $resultSortedAsc = civicrm_api('pledge', 'get', array(
      'version' => $this->_apiversion,
      'rowCount' => 1,
      'sort' => 'start_date ASC',
    ));
    $resultSortedDesc = civicrm_api('pledge', 'get', array(
      'version' => $this->_apiversion,
      'rowCount' => 1,
      'sort' => 'start_date DESC',
    ));

    $this->assertEquals($pledge1['id'], $result['id'], 'pledge get gets first created pledge in line ' . __LINE__);
    $this->assertEquals($pledge2['id'], $resultSortedAsc['id'], 'Ascending pledge sort works');
    $this->assertEquals($pledge1['id'], $resultSortedDesc['id'], 'Decending pledge sort works');
    civicrm_api('pledge', 'delete', array('version' => 3, 'id' => $pledge1['id']));
    civicrm_api('pledge', 'delete', array('version' => 3, 'id' => $pledge2['id']));
  }

  function testCreatePledge() {

    $result = civicrm_api('pledge', 'create', $this->_params);
    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals($result['values'][0]['amount'], 100.00, 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['installments'], 5, 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['frequency_unit'], 'year', 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['frequency_interval'], 5, 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['frequency_day'], 15, 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['original_installment_amount'], 20, 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['status_id'], 2, 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['create_date'], date('Ymd') . '000000', 'In line ' . __LINE__);
    $this->assertEquals($result['values'][0]['start_date'], date('Ymd') . '000000', 'In line ' . __LINE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $payments = civicrm_api('PledgePayment', 'Get', array('version' => 3, 'pledge_id' => $result['id'], 'sequential' => 1));
    $this->assertAPISuccess($payments, 'In line ' . __LINE__);
    $this->assertEquals($payments['count'], 5, 'In line ' . __LINE__);
    require_once 'CRM/Utils/Date.php';
    $shouldBeDate = CRM_Utils_Date::format(CRM_Utils_Date::intervalAdd('year', 5 * 4, $this->scheduled_date), "-");
    $this->assertEquals(substr($shouldBeDate, 0, 10), substr($payments['values'][4]['scheduled_date'], 0, 10), 'In line ' . __LINE__);

    $pledgeID = array('id' => $result['id'], 'version' => 3);
    $pledge = civicrm_api('pledge', 'delete', $pledgeID);
  }

  /*
   * Test that pledge with weekly schedule calculates dates correctly
   */
  function testCreatePledgeWeeklySchedule() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'week',
      'frequency_day' => 3,
      'frequency_interval' => 2,
    );
    $params = array_merge($this->_params, $params);
    $pledge = civicrm_api('Pledge', 'Create', $params);
    //ensure that correct number of payments created & last payment has the right date
    $payments = civicrm_api('PledgePayment', 'Get', array(
      'version' => 3,
      'pledge_id' => $pledge['id'],
      'sequential' => 1));
    $this->assertEquals($payments['is_error'], 0, 'In line ' . __LINE__);
    $this->assertEquals($payments['count'], 5, 'In line ' . __LINE__);
    require_once 'CRM/Utils/Date.php';
    $this->assertEquals('2011-07-06 00:00:00', $payments['values'][4]['scheduled_date'], 'In line ' . __LINE__);

    $pledgeID = array('pledge_id' => $pledge['id'], 'version' => 3);
    $pledge = civicrm_api('pledge', 'delete', $pledgeID);
  }
  /*
   * Test that pledge with weekly schedule calculates dates correctly
  */
  function testCreatePledgeMontlySchedule() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'Month',
      'frequency_day' => 3,
      'frequency_interval' => 2,
    );
    $params = array_merge($this->_params, $params);
    $apiResult = civicrm_api('pledge', 'create', $params);
    $this->assertAPISuccess($apiResult);
  }


  /*
     * Test creation of pledge with only one payment.
     *
     * Pledge status id left empty as it is not a required field
     * http://issues.civicrm.org/jira/browse/CRM-8551
     *
     */
  function testCreatePledgeSinglePayment() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'week',
      'frequency_day' => 3,
      'frequency_interval' => 2,
      'installments' => 1,
    );

    $params = array_merge($this->_params, $params);
    unset($params['pledge_status_id']);
    $pledge = civicrm_api('Pledge', 'Create', $params);
    //ensure that correct number of payments created & last payment has the right date
    $payments = civicrm_api('PledgePayment', 'Get', array(
      'version' => 3,
      'pledge_id' => $pledge['id'],
      'sequential' => 1
    ));
    $this->assertEquals($payments['is_error'], 0, 'In line ' . __LINE__);
    $this->assertEquals(1, $payments['count'], 'In line ' . __LINE__);
    $this->assertEquals(2, $payments['values'][0]['status_id'], 'In line ' . __LINE__);
    $pledgeID = array('id' => $pledge['id'], 'version' => 3);
    $pledge = civicrm_api('pledge', 'delete', $pledgeID);
  }

  /*
 * test that using original_installment_amount rather than pledge_original_installment_amount works
 * Pledge field behaviour is a bit random & so pledge has come to try to handle both unique & non -unique fields
 */
  function testCreatePledgeWithNonUnique() {
    $params = $this->_params;
    $params['original_installment_amount'] = $params['pledge_original_installment_amount'];

    unset($params['pledge_original_installment_amount']);
    $result        = civicrm_api('pledge', 'create', $params);
    $pledgeDetails = civicrm_api('Pledge', 'Get', array('version' => 3, 'id' => $result['id'], 'sequential' => 1));
    $pledge        = $pledgeDetails['values'][0];
    $this->assertEquals(0, $result['is_error'], "in line " . __LINE__);
    $this->assertEquals(100.00, $pledge['pledge_amount'], 'In line ' . __LINE__);
    $this->assertEquals('year', $pledge['pledge_frequency_unit'], 'In line ' . __LINE__);
    $this->assertEquals(5, $pledge['pledge_frequency_interval'], 'In line ' . __LINE__);
    $this->assertEquals(20, $pledge['pledge_next_pay_amount'], 'In line ' . __LINE__);

    $pledgeID = array('id' => $result['id'], 'version' => 3);
    $pledge = civicrm_api('pledge', 'delete', $pledgeID);
  }

  function testCreateCancelPledge() {


    $result = civicrm_api('pledge', 'create', $this->_params);
    $this->assertEquals(0, $result['is_error'], "in line " . __LINE__);
    $this->assertEquals(2, $result['values'][0]['status_id'], "in line " . __LINE__);
    $cancelparams = array('sequential' => 1, 'version' => $this->_apiversion, 'id' => $result['id'], 'pledge_status_id' => 3);
    $result = civicrm_api('pledge', 'create', $cancelparams);
    $this->assertEquals(3, $result['values'][0]['status_id'], "in line " . __LINE__);
    $pledgeID = array('id' => $result['id'], 'version' => 3);
    $pledge = civicrm_api('pledge', 'delete', $pledgeID);
  }

  /*
     * test that status is set to pending
     */
  function testCreatePledgeNoStatus() {

    $params = $this->_params;
    unset($params['status_id']);
    unset($params['pledge_status_id']);
    $result = civicrm_api('pledge', 'create', $params);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals(2, $result['values'][0]['status_id'], "in line " . __LINE__);
    $pledgeID = array('pledge_id' => $result['id'], 'version' => 3);
    $pledge = civicrm_api('pledge', 'delete', $pledgeID);
  }

  //To Update Pledge
  function testCreateUpdatePledge() {

    // we test 'sequential' param here too
    $pledgeID = $this->pledgeCreate($this->_individualId);
    $old_params = array(
      'id' => $pledgeID,
      'sequential' => 1,
      'version' => $this->_apiversion,
    );
    $original = civicrm_api('pledge', 'get', $old_params);
    //Make sure it came back
    $this->assertEquals($original['values'][0]['pledge_id'], $pledgeID, 'In line ' . __LINE__);
    //set up list of old params, verify
    $old_contact_id = $original['values'][0]['contact_id'];
    $old_frequency_unit = $original['values'][0]['pledge_frequency_unit'];
    $old_frequency_interval = $original['values'][0]['pledge_frequency_interval'];
    $old_status_id = $original['values'][0]['pledge_status'];


    //check against values in CiviUnitTestCase::createPledge()
    $this->assertEquals($old_contact_id, $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($old_frequency_unit, 'year', 'In line ' . __LINE__);
    $this->assertEquals($old_frequency_interval, 5, 'In line ' . __LINE__);
    $this->assertEquals($old_status_id, 'Pending', 'In line ' . __LINE__);
    $params = array(
      'id' => $pledgeID,
      'contact_id' => $this->_individualId,
      'pledge_status_id' => 3,
      'amount' => 100,
      'financial_type_id' => 1,
      'start_date' => date('Ymd'),
      'installments' => 10,
      'version' => $this->_apiversion,
    );

    $pledge = civicrm_api('pledge', 'create', $params);
    $this->assertEquals($pledge['is_error'], 0);
    $new_params = array(
      'id' => $pledge['id'],
      'version' => $this->_apiversion,
    );
    $pledge = civicrm_api('pledge', 'get', $new_params);
    $this->assertEquals($pledge['values'][$pledgeID]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($pledge['values'][$pledgeID]['pledge_status'], 'Cancelled', 'In line ' . __LINE__);
    $pledge = civicrm_api('pledge', 'delete', $new_params);
    $this->assertEquals($pledge['is_error'], 0, 'In line ' . __LINE__);
  }
/**
 *  Here we ensure we are maintaining our 'contract' & supporting previously working syntax
 *  ie contribution_type_id
 *
 */
  function testCreateUpdatePledgeLegacy() {

    // we test 'sequential' param here too
    $pledgeID = $this->pledgeCreate($this->_individualId);
    $old_params = array(
      'id' => $pledgeID,
      'sequential' => 1,
      'version' => $this->_apiversion,
    );
    $original = civicrm_api('pledge', 'get', $old_params);
    //Make sure it came back
    $this->assertEquals($original['values'][0]['pledge_id'], $pledgeID, 'In line ' . __LINE__);
    //set up list of old params, verify
    $old_contact_id = $original['values'][0]['contact_id'];
    $old_frequency_unit = $original['values'][0]['pledge_frequency_unit'];
    $old_frequency_interval = $original['values'][0]['pledge_frequency_interval'];
    $old_status_id = $original['values'][0]['pledge_status'];


    //check against values in CiviUnitTestCase::createPledge()
    $this->assertEquals($old_contact_id, $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($old_frequency_unit, 'year', 'In line ' . __LINE__);
    $this->assertEquals($old_frequency_interval, 5, 'In line ' . __LINE__);
    $this->assertEquals($old_status_id, 'Pending', 'In line ' . __LINE__);
    $params = array(
      'id' => $pledgeID,
      'contact_id' => $this->_individualId,
      'pledge_status_id' => 3,
      'amount' => 100,
      'contribution_type_id' => 1,
      'start_date' => date('Ymd'),
      'installments' => 10,
      'version' => $this->_apiversion,
    );

    $pledge = civicrm_api('pledge', 'create', $params);
    $this->assertEquals($pledge['is_error'], 0);
    $new_params = array(
      'id' => $pledge['id'],
      'version' => $this->_apiversion,
    );
    $pledge = civicrm_api('pledge', 'get', $new_params);
    $this->assertEquals($pledge['values'][$pledgeID]['contact_id'], $this->_individualId, 'In line ' . __LINE__);
    $this->assertEquals($pledge['values'][$pledgeID]['pledge_status'], 'Cancelled', 'In line ' . __LINE__);
    $pledge = civicrm_api('pledge', 'delete', $new_params);
    $this->assertEquals($pledge['is_error'], 0, 'In line ' . __LINE__);
  }

  ///////////////// civicrm_pledge_delete methods
  function testDeleteEmptyParamsPledge() {

    $params = array('version' => $this->_apiversion);
    $pledge = civicrm_api('pledge', 'delete', $params);
    $this->assertEquals($pledge['is_error'], 1);
    $this->assertEquals($pledge['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  function testDeleteParamsNotArrayPledge() {
    $params = 'pledge_id= 1';
    $pledge = civicrm_api('pledge', 'delete', $params);
    $this->assertEquals($pledge['is_error'], 1);
    $this->assertEquals($pledge['error_message'], 'Input variable `params` is not an array');
  }

  function testDeleteWrongParamPledge() {
    $params = array(
      'pledge_source' => 'SSF',
      'version' => $this->_apiversion,
    );
    $pledge = civicrm_api('pledge', 'delete', $params);
    $this->assertEquals($pledge['is_error'], 1);
    $this->assertEquals($pledge['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /*
     * legacy support for pledge_id
     */
  function testDeletePledge() {

    $pledgeID = $this->pledgeCreate($this->_individualId);
    $params = array(
      'pledge_id' => $pledgeID,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('pledge', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
  }

  /*
     * std is to accept id
     */
  function testDeletePledgeUseID() {

    $pledgeID = $this->pledgeCreate($this->_individualId);
    $params = array(
      'id' => $pledgeID,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('pledge', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
  }
  /*
     * test to make sure empty get returns nothing
     * Note that the function gives incorrect results if no pledges exist as it does a
     * contact search instead - test only checks that the get finds the one existing
     */
  function testGetEmpty() {
    $result = civicrm_api('pledge', 'create', $this->_params);
    $result = civicrm_api('pledge', 'get', array('version' => 3));
    $this->assertAPISuccess($result, "This test is failing because it's acting like a contact get when no params set. Not sure the fix");
    $this->assertEquals(1, $result['count'], 'in line ' . __LINE__);
    $pledgeID = array('id' => $result['id'], 'version' => 3);
    $pledge = civicrm_api('pledge', 'delete', $pledgeID);
  }
}

