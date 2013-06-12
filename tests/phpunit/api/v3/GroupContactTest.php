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
class api_v3_GroupContactTest extends CiviUnitTestCase {

  protected $_contactId;
  protected $_contactId1;
  protected $_apiversion;
  protected $_groupId1;
  public $_eNoticeCompliant = True;

  function get_info() {
    return array(
      'name' => 'Group Contact Create',
      'description' => 'Test all Group Contact Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  /*
     * Set up for group contact tests
     *
     * @todo set up calls function that doesn't work @ the moment
     */
  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();

    $this->_contactId = $this->individualCreate(NULL);

    $this->_groupId1 = $this->groupCreate(NULL);
    $params = array(
      'contact_id' => $this->_contactId,
      'group_id' => $this->_groupId1,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('group_contact', 'create', $params);

    $group = array(
      'name' => 'Test Group 2',
      'domain_id' => 1,
      'title' => 'New Test Group2 Created',
      'description' => 'New Test Group2 Created',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
      'version' => $this->_apiversion,
    );

    $this->_groupId2 = $this->groupCreate($group, 3);
    $params = array(
      'contact_id.1' => $this->_contactId,
      'group_id' => $this->_groupId2,
      'version' => $this->_apiversion,
    );
    //@todo uncomment me when I work
    //civicrm_group_contact_create( $params );

    $this->_group = array(
      $this->_groupId1 => array('title' => 'New Test Group Created',
        'visibility' => 'Public Pages',
        'in_method' => 'API',
      ),
      $this->_groupId2 => array(
        'title' => 'New Test Group2 Created',
        'visibility' => 'User and User Admin Only',
        'in_method' => 'API',
      ),
    );
  }

  function tearDown() {
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_group',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  ///////////////// civicrm_group_contact_get methods
  function testGet() {
    $params = array(
      'contact_id' => $this->_contactId,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('group_contact', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    foreach ($result['values'] as $v) {
      $this->assertEquals($v['title'], $this->_group[$v['group_id']]['title']);
      $this->assertEquals($v['visibility'], $this->_group[$v['group_id']]['visibility']);
      $this->assertEquals($v['in_method'], $this->_group[$v['group_id']]['in_method']);
    }
  }

  function testGetGroupID() {
    $description = "Get all from group and display contacts";
    $subfile     = "GetWithGroupID";
    $params      = array(
      'group_id' => $this->_groupId1,
      'version' => $this->_apiversion,
      'api.group.get' => 1,
      'sequential' => 1,
    );
    $result = civicrm_api('group_contact', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    foreach ($result['values'][0]['api.group.get']['values'] as $values) {
      $key = $values['id'];
      $this->assertEquals($values['title'], $this->_group[$key]['title']);
      $this->assertEquals($values['visibility'], $this->_group[$key]['visibility']);
    }
  }


  ///////////////// civicrm_group_contact_add methods
  function testCreateWithWrongParamsType() {
    $params = 1;
    $groups = civicrm_api('group_contact', 'create', $params);

    $this->assertEquals($groups['is_error'], 1);
    $this->assertEquals($groups['error_message'], 'Input variable `params` is not an array');
  }

  function testCreateWithEmptyParams() {
    $params = array();
    $groups = civicrm_api('group_contact', 'create', $params);

    $this->assertEquals($groups['is_error'], 1);
    $this->assertEquals($groups['error_message'],
      'Mandatory key(s) missing from params array: version, group_id, contact_id'
    );
  }

  function testCreateWithoutGroupIdParams() {
    $params = array(
      'contact_id' => $this->_contactId,
      'version' => $this->_apiversion,
    );

    $groups = civicrm_api('group_contact', 'create', $params);

    $this->assertEquals($groups['is_error'], 1);
    $this->assertEquals($groups['error_message'], 'Mandatory key(s) missing from params array: group_id');
  }

  function testCreateWithoutContactIdParams() {
    $params = array(
      'group_id' => $this->_groupId1,
      'version' => $this->_apiversion,
    );
    $groups = civicrm_api('group_contact', 'create', $params);

    $this->assertEquals($groups['is_error'], 1);
    $this->assertEquals($groups['error_message'], 'Mandatory key(s) missing from params array: contact_id');
  }

  function testCreate() {
    $cont = array(
      'first_name' => 'Amiteshwar',
      'middle_name' => 'L.',
      'last_name' => 'Prasad',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'amiteshwar.prasad@civicrm.org',
      'contact_type' => 'Individual',
      'version' => $this->_apiversion,
    );

    $this->_contactId1 = $this->individualCreate($cont);
    $params = array(
      'contact_id' => $this->_contactId,
      'contact_id.2' => $this->_contactId1,
      'group_id' => $this->_groupId1,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('group_contact', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals($result['not_added'], 1, "in line " . __LINE__);
    $this->assertEquals($result['added'], 1, "in line " . __LINE__);
    $this->assertEquals($result['total_count'], 2, "in line " . __LINE__);
  }

  ///////////////// civicrm_group_contact_remove methods
  function testDelete() {
    $params = array(
      'contact_id' => $this->_contactId,
      'group_id' => 1,
      'version' => $this->_apiversion,
    );

    $result = civicrm_api('group_contact', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, "in line " . __LINE__);
    $this->assertEquals($result['removed'], 1, "in line " . __LINE__);
    $this->assertEquals($result['total_count'], 1, "in line " . __LINE__);
  }
}

