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
 * Test class for Group API - civicrm_group_*
 *
 *  @package CiviCRM_APIv3
 */

class api_v3_GroupTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_groupID;
  public $_eNoticeCompliant = True;

  function get_info() {
    return array(
      'name' => 'Group Get',
      'description' => 'Test all Group Get API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    $this->_apiversion = 3;

    parent::setUp();
    $this->_groupID = $this->groupCreate(NULL, 3);
  }

  function tearDown() {

    $this->groupDelete($this->_groupID);
  }

  function testgroupCreateNoTitle() {
    $params = array(
      'name' => 'Test Group No title ',
      'domain_id' => 1,
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'visibility' => 'Public Pages',
      'group_type' => array(
        '1' => 1,
        '2' => 1,
      ),
    );

    $group = $this->callAPIFailure('group', 'create', $params);
    $this->assertEquals($group['error_message'], 'Mandatory key(s) missing from params array: title');
  }

  function testGetGroupEmptyParams() {
    $params = '';
    $group = civicrm_api('group', 'get', $params);

    $this->assertEquals($group['error_message'], 'Input variable `params` is not an array');
  }

  function testGetGroupWithEmptyParams() {
    $params = array('version' => $this->_apiversion);

    $group = civicrm_api('group', 'get', $params);

    $group = $group["values"];
    $this->assertNotNull(count($group));
    $this->assertEquals($group[$this->_groupID]['name'], "Test Group 1_{$this->_groupID}");
    $this->assertEquals($group[$this->_groupID]['is_active'], 1);
    $this->assertEquals($group[$this->_groupID]['visibility'], 'Public Pages');
  }

  function testGetGroupParamsWithGroupId() {
    $params       = array('version' => $this->_apiversion);
    $params['id'] = $this->_groupID;
    $group        = civicrm_api('group', 'get', $params);

    foreach ($group['values'] as $v) {
      $this->assertEquals($v['name'], "Test Group 1_{$this->_groupID}");
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  function testGetGroupParamsWithGroupName() {
    $params         = array('version' => $this->_apiversion);
    $params['name'] = "Test Group 1_{$this->_groupID}";
    $group          = civicrm_api('group', 'get', $params);
    $this->documentMe($params, $group, __FUNCTION__, __FILE__);
    $group = $group['values'];

    foreach ($group as $v) {
      $this->assertEquals($v['id'], $this->_groupID);
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  function testGetGroupParamsWithReturnName() {
    $params = array('version' => $this->_apiversion);
    $params['id'] = $this->_groupID;
    $params['return.name'] = 1;
    $group = civicrm_api('group', 'get', $params);
    $this->assertEquals($group['values'][$this->_groupID]['name'],
      "Test Group 1_{$this->_groupID}"
    );
  }

  function testGetGroupParamsWithGroupTitle() {
    $params          = array('version' => $this->_apiversion);
    $params['title'] = 'New Test Group Created';
    $group           = civicrm_api('group', 'get', $params);

    foreach ($group['values'] as $v) {
      $this->assertEquals($v['id'], $this->_groupID);
      $this->assertEquals($v['name'], "Test Group 1_{$this->_groupID}");
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  function testGetNonExistingGroup() {
    $params          = array('version' => $this->_apiversion);
    $params['title'] = 'No such group Exist';
    $group           = civicrm_api('group', 'get', $params);
    $this->assertEquals(0, $group['count']);
  }

  function testgroupdeleteParamsnoId() {
    $group = $this->callAPIFailure('group', 'delete', array());
    $this->assertEquals($group['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  function testgetfields() {
    $description = "demonstrate use of getfields to interogate api";
    $params      = array('version' => 3, 'action' => 'create');
    $result      = civicrm_api('group', 'getfields', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, 'getfields', 'getfields');
    $this->assertEquals(1, $result['values']['is_active']['api.default']);
  }
}

