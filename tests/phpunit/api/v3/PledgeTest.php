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
 * Test class for Pledge API - civicrm_pledge_*
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_PledgeTest extends CiviUnitTestCase {

  protected $_individualId;
  protected $_pledge;
  protected $_apiversion;
  protected $_params;
  protected $_entity;
  protected $scheduled_date;
  public $DBResetRequired = TRUE;

  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->quickCleanup(array('civicrm_pledge', 'civicrm_pledge_payment'));
    //need to set scheduled payment in advance we are running test @ midnight & it becomes unexpectedly overdue
    //due to timezone issues
    $this->scheduled_date = date('Ymd', mktime(0, 0, 0, date("m"), date("d") + 2, date("y")));
    $this->_entity = 'Pledge';
    $this->_individualId = $this->individualCreate();
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
    );
  }

  public function tearDown() {
    $this->contactDelete($this->_individualId);
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

    $result = $this->callAPISuccess($this->_entity, 'create', $params);
    $this->assertAPISuccess($result, " testCreateWithCustom ");
    $this->assertAPISuccess($result);
    $getParams = array('id' => $result['id'], 'return.custom_' . $ids['custom_field_id'] => 1);
    $check = $this->callAPISuccess($this->_entity, 'get', $getParams);
    $this->callAPISuccess('pledge', 'delete', array('id' => $check['id']));
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']]);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test getfields function for pledge.
   */
  public function testGetfieldsPledge() {
    $result = $this->callAPISuccess('pledge', 'getfields', array('action' => 'get'));
    $this->assertEquals(1, $result['values']['next_pay_date']['api.return']);
  }

  /**
   * Test get pledge api.
   */
  public function testGetPledge() {

    $this->_pledge = $this->callAPISuccess('pledge', 'create', $this->_params);
    $params = array(
      'pledge_id' => $this->_pledge['id'],
    );
    $result = $this->callAPIAndDocument('pledge', 'get', $params, __FUNCTION__, __FILE__);
    $pledge = $result['values'][$this->_pledge['id']];
    $this->assertEquals($this->_individualId, $pledge['contact_id']);
    $this->assertEquals($this->_pledge['id'], $pledge['pledge_id']);
    $this->assertEquals(date('Y-m-d') . ' 00:00:00', $pledge['pledge_create_date']);
    $this->assertEquals(100.00, $pledge['pledge_amount']);
    $this->assertEquals('Pending', $pledge['pledge_status']);
    $this->assertEquals(5, $pledge['pledge_frequency_interval']);
    $this->assertEquals('year', $pledge['pledge_frequency_unit']);
    $this->assertEquals(date('Y-m-d', strtotime($this->scheduled_date)) . ' 00:00:00', $pledge['pledge_next_pay_date']);
    $this->assertEquals($pledge['pledge_next_pay_amount'], 20.00);

    $params2 = array(
      'pledge_id' => $this->_pledge['id'],
    );
    $pledge = $this->callAPISuccess('pledge', 'delete', $params2);
  }

  /**
   * Test 'return.pledge_financial_type' => 1 works.
   */
  public function testGetPledgeWithReturn() {

    $this->_pledge = $this->callAPISuccess('pledge', 'create', $this->_params);
    $params = array(
      'pledge_id' => $this->_pledge['id'],
      'return.pledge_financial_type' => 1,
    );
    $result = $this->callAPISuccess('pledge', 'get', $params);
    $pledge = $result['values'][$this->_pledge['id']];
    $this->callAPISuccess('pledge', 'delete', $pledge);
    $this->assertEquals('Donation', $pledge['pledge_financial_type']);
  }

  /**
   * Test 'return.pledge_contribution_type' => 1 works.
   *
   * This is for legacy compatibility
   */
  public function testGetPledgeWithReturnLegacy() {

    $this->_pledge = $this->callAPISuccess('pledge', 'create', $this->_params);
    $params = array(
      'pledge_id' => $this->_pledge['id'],
      'return.pledge_financial_type' => 1,
    );
    $result = $this->callAPISuccess('pledge', 'get', $params);
    $pledge = $result['values'][$this->_pledge['id']];
    $this->callAPISuccess('pledge', 'delete', $pledge);
    $this->assertEquals('Donation', $pledge['pledge_financial_type']);
  }

  /**
   * Test date legacy date filters like pledge_start_date_high.
   */
  public function testPledgeGetReturnFilters() {
    $this->callAPISuccess('pledge', 'create', $this->_params);

    $overdueParams = array(
      'scheduled_date' => 'first saturday of march last year',
      'start_date' => 'first saturday of march last year',
    );
    $oldPledge = $this->callAPISuccess('pledge', 'create', array_merge($this->_params, $overdueParams));

    $pledgeGetParams = array();
    $allPledges = $this->callAPISuccess('pledge', 'getcount', $pledgeGetParams);

    $this->assertEquals(2, $allPledges, 'Check we have 2 pledges to place with in line ' . __LINE__);
    $pledgeGetParams['pledge_start_date_high'] = date('YmdHis', strtotime('2 days ago'));
    $earlyPledge = $this->callAPIAndDocument('pledge', 'get', $pledgeGetParams, __FUNCTION__, __FILE__, "demonstrates high date filter", "GetFilterHighDate");
    $this->assertEquals(1, $earlyPledge['count'], ' check only one returned with start date filter in line ' . __LINE__);
    $this->assertEquals($oldPledge['id'], $earlyPledge['id'], ' check correct pledge returned ' . __LINE__);
  }

  /**
   * Create 2 pledges - see if we can get by status id.
   */
  public function testGetOverduePledge() {
    $overdueParams = array(
      'scheduled_date' => 'first saturday of march last year',
      'start_date' => 'first saturday of march last year',
    );
    $this->_pledge = $this->callAPISuccess('pledge', 'create', array_merge($this->_params, $overdueParams));

    $result = $this->callAPISuccess('pledge', 'get', array('status_id' => 'Overdue'));
    $emptyResult = $this->callAPISuccess('pledge', 'get', array(
      'pledge_status_id' => '1',
    ));
    $pledge = $result['values'][$this->_pledge['id']];
    $this->callAPISuccess('pledge', 'delete', $pledge);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(0, $emptyResult['count']);
  }

  /**
   * Test pledge_status option group
   */
  public function testOptionGroupForPledgeStatus() {
    $pledgeOg = $this->callAPISuccess('OptionGroup', 'get', array(
      'name' => "pledge_status",
    ));
    $this->assertEquals(1, $pledgeOg['count']);

    $pledgeOv = $this->callAPISuccess('OptionValue', 'get', array(
      'sequential' => 1,
      'option_group_id' => "pledge_status",
    ));
    $this->assertEquals(5, $pledgeOv['count']);
    $pledgeStatus = CRM_Utils_Array::collect('name', $pledgeOv['values']);
    $expected = array('Completed', 'Pending', 'Cancelled', 'In Progress', 'Overdue');
    $this->assertEquals($expected, $pledgeStatus);
  }

  /**
   * Create 2 pledges - see if we can get by status id.
   */
  public function testSortParamPledge() {
    $pledge1 = $this->callAPISuccess('pledge', 'create', $this->_params);
    $overdueParams = array(
      'scheduled_date' => 'first saturday of march last year',
      'start_date' => 'first saturday of march last year',
      'create_date' => 'first saturday of march last year',
    );
    $pledge2 = $this->callAPISuccess('pledge', 'create', array_merge($this->_params, $overdueParams));
    $params = array(
      'pledge_is_test' => 0,
      'rowCount' => 1,
    );
    $result = $this->callAPISuccess('pledge', 'get', $params);

    $resultSortedAsc = $this->callAPISuccess('pledge', 'get', array(
      'rowCount' => 1,
      'sort' => 'start_date ASC',
    ));
    $resultSortedDesc = $this->callAPISuccess('pledge', 'get', array(
      'rowCount' => 1,
      'sort' => 'start_date DESC',
    ));

    $this->assertEquals($pledge1['id'], $result['id'], 'pledge get gets first created pledge in line ' . __LINE__);
    $this->assertEquals($pledge2['id'], $resultSortedAsc['id'], 'Ascending pledge sort works');
    $this->assertEquals($pledge1['id'], $resultSortedDesc['id'], 'Decending pledge sort works');
    $this->callAPISuccess('pledge', 'delete', array('id' => $pledge1['id']));
    $this->callAPISuccess('pledge', 'delete', array('id' => $pledge2['id']));
  }

  public function testCreatePledge() {

    $result = $this->callAPIAndDocument('pledge', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][0]['amount'], 100.00);
    $this->assertEquals($result['values'][0]['installments'], 5);
    $this->assertEquals($result['values'][0]['frequency_unit'], 'year');
    $this->assertEquals($result['values'][0]['frequency_interval'], 5);
    $this->assertEquals($result['values'][0]['frequency_day'], 15);
    $this->assertEquals($result['values'][0]['original_installment_amount'], 20);
    $this->assertEquals($result['values'][0]['status_id'], 2);
    $this->assertEquals($result['values'][0]['create_date'], date('Ymd') . '000000');
    $this->assertEquals($result['values'][0]['start_date'], date('Ymd') . '000000');
    $this->assertAPISuccess($result);
    $payments = $this->callAPISuccess('PledgePayment', 'Get', array('pledge_id' => $result['id'], 'sequential' => 1));
    $this->assertAPISuccess($payments);
    $this->assertEquals($payments['count'], 5);
    $shouldBeDate = CRM_Utils_Date::format(CRM_Utils_Date::intervalAdd('year', 5 * 4, $this->scheduled_date), "-");
    $this->assertEquals(substr($shouldBeDate, 0, 10), substr($payments['values'][4]['scheduled_date'], 0, 10));

    $pledgeID = array('id' => $result['id']);
    $pledge = $this->callAPISuccess('pledge', 'delete', $pledgeID);
  }

  /**
   * Test that pledge with weekly schedule calculates dates correctly.
   */
  public function testCreatePledgeWeeklySchedule() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'week',
      'frequency_day' => 3,
      'frequency_interval' => 2,
    );
    $params = array_merge($this->_params, $params);
    $pledge = $this->callAPISuccess('Pledge', 'Create', $params);
    //ensure that correct number of payments created & last payment has the right date
    $payments = $this->callAPISuccess('PledgePayment', 'Get', array(
      'pledge_id' => $pledge['id'],
      'sequential' => 1,
    ));
    $this->assertEquals($payments['count'], 5);
    $this->assertEquals('2011-07-06 00:00:00', $payments['values'][4]['scheduled_date']);

    $this->callAPISuccess('pledge', 'delete', array('pledge_id' => $pledge['id']));
  }

  /**
   * Test that pledge with weekly schedule calculates dates correctly.
   */
  public function testCreatePledgeMontlySchedule() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'Month',
      'frequency_day' => 3,
      'frequency_interval' => 2,
    );
    $params = array_merge($this->_params, $params);
    $apiResult = $this->callAPISuccess('pledge', 'create', $params);
  }

  /**
   * Test creation of pledge with only one payment.
   *
   * Pledge status id left empty as it is not a required field
   * http://issues.civicrm.org/jira/browse/CRM-8551
   */
  public function testCreatePledgeSinglePayment() {
    $params = array(
      'scheduled_date' => '20110510',
      'frequency_unit' => 'week',
      'frequency_day' => 3,
      'frequency_interval' => 2,
      'installments' => 1,
    );

    $params = array_merge($this->_params, $params);
    unset($params['pledge_status_id']);
    $pledge = $this->callAPISuccess('Pledge', 'Create', $params);
    //ensure that correct number of payments created & last payment has the right date
    $payments = $this->callAPISuccess('PledgePayment', 'Get', array(
      'pledge_id' => $pledge['id'],
      'sequential' => 1,
    ));
    $this->assertEquals(1, $payments['count']);
    $this->assertEquals(2, $payments['values'][0]['status_id']);
    $pledgeID = array('id' => $pledge['id']);
    $pledge = $this->callAPISuccess('pledge', 'delete', $pledgeID);
  }

  /**
   * Test that using original_installment_amount rather than pledge_original_installment_amount works.
   *
   * Pledge field behaviour is a bit random & so pledge has come to try to handle both unique & non -unique fields.
   */
  public function testCreatePledgeWithNonUnique() {
    $params = $this->_params;
    $params['original_installment_amount'] = $params['pledge_original_installment_amount'];

    unset($params['pledge_original_installment_amount']);
    $result = $this->callAPISuccess('pledge', 'create', $params);
    $pledgeDetails = $this->callAPISuccess('Pledge', 'Get', array('id' => $result['id'], 'sequential' => 1));
    $pledge = $pledgeDetails['values'][0];
    $this->assertEquals(100.00, $pledge['pledge_amount']);
    $this->assertEquals('year', $pledge['pledge_frequency_unit']);
    $this->assertEquals(5, $pledge['pledge_frequency_interval']);
    $this->assertEquals(20, $pledge['pledge_next_pay_amount']);

    $pledgeID = array('id' => $result['id']);
    $pledge = $this->callAPISuccess('pledge', 'delete', $pledgeID);
  }

  /**
   * Test cancelling a pledge.
   */
  public function testCreateCancelPledge() {

    $result = $this->callAPISuccess('pledge', 'create', $this->_params);
    $this->assertEquals(2, $result['values'][0]['status_id']);
    $cancelParams = array(
      'sequential' => 1,
      'id' => $result['id'],
      'pledge_status_id' => 3,
    );
    $result = $this->callAPISuccess('pledge', 'create', $cancelParams);
    $this->assertEquals(3, $result['values'][0]['status_id']);
    $pledgeID = array('id' => $result['id']);
    $this->callAPISuccess('pledge', 'delete', $pledgeID);
  }

  /**
   * Test that status is set to pending.
   */
  public function testCreatePledgeNoStatus() {

    $params = $this->_params;
    unset($params['status_id']);
    unset($params['pledge_status_id']);
    $result = $this->callAPISuccess('pledge', 'create', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals(2, $result['values'][0]['status_id']);
    $pledgeID = array('pledge_id' => $result['id']);
    $pledge = $this->callAPISuccess('pledge', 'delete', $pledgeID);
  }

  /**
   * Update Pledge.
   */
  public function testCreateUpdatePledge() {
    // we test 'sequential' param here too
    $pledgeID = $this->pledgeCreate(array('contact_id' => $this->_individualId));
    $old_params = array(
      'id' => $pledgeID,
      'sequential' => 1,
    );
    $original = $this->callAPISuccess('pledge', 'get', $old_params);
    //Make sure it came back
    $this->assertEquals($original['values'][0]['pledge_id'], $pledgeID);
    //set up list of old params, verify
    $old_contact_id = $original['values'][0]['contact_id'];
    $old_frequency_unit = $original['values'][0]['pledge_frequency_unit'];
    $old_frequency_interval = $original['values'][0]['pledge_frequency_interval'];
    $old_status_id = $original['values'][0]['pledge_status'];

    //check against values in CiviUnitTestCase::createPledge()
    $this->assertEquals($old_contact_id, $this->_individualId);
    $this->assertEquals($old_frequency_unit, 'year');
    $this->assertEquals($old_frequency_interval, 5);
    $this->assertEquals($old_status_id, 'Pending');
    $params = array(
      'id' => $pledgeID,
      'contact_id' => $this->_individualId,
      'pledge_status_id' => 3,
      'amount' => 100,
      'financial_type_id' => 1,
      'start_date' => date('Ymd'),
      'installments' => 10,
    );

    $pledge = $this->callAPISuccess('pledge', 'create', $params);
    $new_params = array(
      'id' => $pledge['id'],
    );
    $pledge = $this->callAPISuccess('pledge', 'get', $new_params);
    $this->assertEquals($pledge['values'][$pledgeID]['contact_id'], $this->_individualId);
    $this->assertEquals($pledge['values'][$pledgeID]['pledge_status'], 'Cancelled');
    $pledge = $this->callAPISuccess('pledge', 'delete', $new_params);
  }

  /**
   * Here we ensure we are maintaining our 'contract' & supporting previously working syntax.
   *
   * ie contribution_type_id.
   *
   * We test 'sequential' param here too.
   */
  public function testCreateUpdatePledgeLegacy() {
    $pledgeID = $this->pledgeCreate(array('contact_id' => $this->_individualId));
    $old_params = array(
      'id' => $pledgeID,
      'sequential' => 1,
    );
    $original = $this->callAPISuccess('pledge', 'get', $old_params);
    // Make sure it came back.
    $this->assertEquals($original['values'][0]['pledge_id'], $pledgeID);
    // Set up list of old params, verify.
    $old_contact_id = $original['values'][0]['contact_id'];
    $old_frequency_unit = $original['values'][0]['pledge_frequency_unit'];
    $old_frequency_interval = $original['values'][0]['pledge_frequency_interval'];
    $old_status_id = $original['values'][0]['pledge_status'];

    // Check against values in CiviUnitTestCase::createPledge().
    $this->assertEquals($old_contact_id, $this->_individualId);
    $this->assertEquals($old_frequency_unit, 'year');
    $this->assertEquals($old_frequency_interval, 5);
    $this->assertEquals($old_status_id, 'Pending');
    $params = array(
      'id' => $pledgeID,
      'contact_id' => $this->_individualId,
      'pledge_status_id' => 3,
      'amount' => 100,
      'contribution_type_id' => 1,
      'start_date' => date('Ymd'),
      'installments' => 10,
    );

    $pledge = $this->callAPISuccess('pledge', 'create', $params);
    $new_params = array(
      'id' => $pledge['id'],
    );
    $pledge = $this->callAPISuccess('pledge', 'get', $new_params);
    $this->assertEquals($pledge['values'][$pledgeID]['contact_id'], $this->_individualId);
    $this->assertEquals($pledge['values'][$pledgeID]['pledge_status'], 'Cancelled');
    $this->callAPISuccess('pledge', 'delete', $new_params);
  }

  /**
   * Failure test for delete without id.
   */
  public function testDeleteEmptyParamsPledge() {
    $this->callAPIFailure('pledge', 'delete', array(), 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Failure test for invalid pledge id.
   */
  public function testDeleteWrongParamPledge() {
    $params = array(
      'pledge_source' => 'SSF',
    );
    $this->callAPIFailure('pledge', 'delete', $params, 'Mandatory key(s) missing from params array: id');
  }

  /**
   * Legacy support for pledge_id.
   */
  public function testDeletePledge() {

    $pledgeID = $this->pledgeCreate(array('contact_id' => $this->_individualId));
    $params = array(
      'pledge_id' => $pledgeID,
    );
    $this->callAPIAndDocument('pledge', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Standard is to accept id.
   */
  public function testDeletePledgeUseID() {

    $pledgeID = $this->pledgeCreate(array('contact_id' => $this->_individualId));
    $params = array(
      'id' => $pledgeID,
    );
    $this->callAPIAndDocument('pledge', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Test to make sure empty get returns nothing.
   *
   * Note that the function gives incorrect results if no pledges exist as it does a
   * contact search instead - test only checks that the get finds the one existing
   */
  public function testGetEmpty() {
    $this->callAPISuccess('pledge', 'create', $this->_params);
    $result = $this->callAPISuccess('pledge', 'get', array());
    $this->assertAPISuccess($result, "This test is failing because it's acting like a contact get when no params set. Not sure the fix");
    $this->assertEquals(1, $result['count']);
    $pledgeID = array('id' => $result['id']);
    $this->callAPISuccess('pledge', 'delete', $pledgeID);
  }

}
