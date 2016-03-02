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
 * Test class for Group API - civicrm_group_*
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_GroupTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_groupID;

  public function setUp() {
    $this->_apiversion = 3;

    parent::setUp();
    $this->_groupID = $this->groupCreate();
  }

  public function tearDown() {

    $this->groupDelete($this->_groupID);
  }

  public function testgroupCreateNoTitle() {
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

    $group = $this->callAPIFailure('group', 'create', $params, 'Mandatory key(s) missing from params array: title');
  }


  public function testGetGroupWithEmptyParams() {
    $group = $this->callAPISuccess('group', 'get', $params = array());

    $group = $group["values"];
    $this->assertNotNull(count($group));
    $this->assertEquals($group[$this->_groupID]['name'], "Test Group 1");
    $this->assertEquals($group[$this->_groupID]['is_active'], 1);
    $this->assertEquals($group[$this->_groupID]['visibility'], 'Public Pages');
  }

  public function testGetGroupParamsWithGroupId() {
    $params = array('id' => $this->_groupID);
    $group = $this->callAPISuccess('group', 'get', $params);

    foreach ($group['values'] as $v) {
      $this->assertEquals($v['name'], "Test Group 1");
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  public function testGetGroupParamsWithGroupName() {
    $params = array(
      'name' => "Test Group 1",
    );
    $group = $this->callAPIAndDocument('group', 'get', $params, __FUNCTION__, __FILE__);
    $group = $group['values'];

    foreach ($group as $v) {
      $this->assertEquals($v['id'], $this->_groupID);
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  public function testGetGroupParamsWithReturnName() {
    $params = array();
    $params['id'] = $this->_groupID;
    $params['return.name'] = 1;
    $group = $this->callAPISuccess('group', 'get', $params);
    $this->assertEquals($group['values'][$this->_groupID]['name'],
      "Test Group 1"
    );
  }

  public function testGetGroupParamsWithGroupTitle() {
    $params = array();
    $params['title'] = 'New Test Group Created';
    $group = $this->callAPISuccess('group', 'get', $params);

    foreach ($group['values'] as $v) {
      $this->assertEquals($v['id'], $this->_groupID);
      $this->assertEquals($v['name'], "Test Group 1");
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  public function testGetNonExistingGroup() {
    $params = array();
    $params['title'] = 'No such group Exist';
    $group = $this->callAPISuccess('group', 'get', $params);
    $this->assertEquals(0, $group['count']);
  }

  public function testgroupdeleteParamsnoId() {
    $group = $this->callAPIFailure('group', 'delete', array(), 'Mandatory key(s) missing from params array: id');
  }

  public function testgetfields() {
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = array('action' => 'create');
    $result = $this->callAPIAndDocument('group', 'getfields', $params, __FUNCTION__, __FILE__, $description);
    $this->assertEquals(1, $result['values']['is_active']['api.default']);
  }

}
