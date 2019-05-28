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
class api_v3_ContributionRecurTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $ids = array();
  protected $_entity = 'contribution_recur';

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->ids['contact'][0] = $this->individualCreate();
    $this->params = array(
      'contact_id' => $this->ids['contact'][0],
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'day',
    );
  }

  public function testCreateContributionRecur() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetContributionRecur() {
    $this->callAPISuccess($this->_entity, 'create', $this->params);
    $getParams = array(
      'amount' => '500',
    );
    $result = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
  }

  public function testCreateContributionRecurWithToken() {
    // create token
    $this->createLoggedInUser();
    $token = $this->callAPISuccess('PaymentToken', 'create', array(
      'payment_processor_id' => $this->processorCreate(),
      'token' => 'hhh',
      'contact_id' => $this->individualCreate(),
    ));
    $params['payment_token_id'] = $token['id'];
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testDeleteContributionRecur() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $deleteParams = array('id' => $result['id']);
    $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', array());
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testGetFieldsContributionRecur() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', array('action' => 'create'));
    $this->assertEquals(12, $result['values']['start_date']['type']);
  }

  /**
   * Test that we can cancel a contribution and add a cancel_reason via the api.
   */
  public function testContributionRecurCancel() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->callAPISuccess('ContributionRecur', 'cancel', ['id' => $result['id'], 'cancel_reason' => 'just cos', 'processor_message' => 'big fail']);
    $cancelled = $this->callAPISuccess('ContributionRecur', 'getsingle', ['id' => $result['id']]);
    $this->assertEquals('just cos', $cancelled['cancel_reason']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', 'Cancelled'), $cancelled['contribution_status_id']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($cancelled['cancel_date'])));
    $activity = $this->callAPISuccessGetSingle('Activity', ['activity_type_id' => 'Cancel Recurring Contribution', 'record_type_id' => $result['id']]);
    $this->assertEquals('Recurring contribution cancelled', $activity['subject']);
    $this->assertEquals('big fail<br/>The recurring contribution of 500.00, every 1 day has been cancelled.', $activity['details']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($activity['activity_date_time'])));
    $this->assertEquals($this->params['contact_id'], $activity['source_contact_id']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'), $activity['status_id']);
  }

}
