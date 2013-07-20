<?php

/**
 *  File for the TestActivityType class
 *
 *  (PHP 5)
 *
 *   @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 *  Test APIv3 civicrm_activity_* functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_Activity
 */

class api_v3_ActivityTypeTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;

  function get_info() {
    return array(
      'name' => 'Activity Type',
      'description' => 'Test all ActivityType Get/Create/Delete methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    $this->_apiversion = 3;
    CRM_Core_PseudoConstant::activityType(TRUE, TRUE, TRUE, 'name');
    parent::setUp();
  }

  /**
   *  Test civicrm_activity_type_get()
   */
  function testActivityTypeGet() {
    $params = array('version' => $this->_apiversion);
    $result = civicrm_api('activity_type', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values']['1'], 'Meeting', 'In line ' . __LINE__);
    $this->assertEquals($result['values']['13'], 'Open Case', 'In line ' . __LINE__);
  }

  /**
   *  Test civicrm_activity_type_create()
   */
  function testActivityTypeCreate() {

    $params = array(
      'weight' => '2',
      'label' => 'send out letters',
      'version' => $this->_apiversion,
      'filter' => 0,
      'is_active' => 1,
      'is_optgroup' => 1,
      'is_default' => 0,
    );
    $result = civicrm_api('activity_type', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
  }

  /**
   *  Test civicrm_activity_type_create - check id
   */
  function testActivityTypecreatecheckId() {

    $params = array(
      'label' => 'type_create',
      'weight' => '2',
      'version' => $this->_apiversion,
    );
    $activitycreate = civicrm_api('activity_type', 'create', $params);
    $activityID = $activitycreate['id'];
    $this->assertAPISuccess($activitycreate, "in line " . __LINE__);
    $this->assertArrayHasKey('id', $activitycreate);
    $this->assertArrayHasKey('option_group_id', $activitycreate['values'][$activitycreate['id']]);
  }

  /**
   *  Test civicrm_activity_type_delete()
   */
  function testActivityTypeDelete() {

    $params = array(
      'label' => 'type_create_delete',
      'weight' => '2',
      'version' => $this->_apiversion,
    );
    $activitycreate = civicrm_api('activity_type', 'create', $params);
    $params = array(
      'activity_type_id' => $activitycreate['id'],
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('activity_type', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPIFailure($result, 'In line ' . __LINE__);
  }
}

