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
 * Class api_v3_GroupContactTest
 * @group headless
 */
class api_v3_GroupContactTest extends CiviUnitTestCase {

  protected $_contactId;
  protected $_contactId1;
  protected $_apiversion = 3;

  /**
   * @var int
   */
  protected $_groupId1;

  /**
   * @var int
   */
  protected $_groupId2;

  /**
   * Set up for group contact tests.
   *
   * @todo set up calls function that doesn't work @ the moment
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_contactId = $this->individualCreate();

    $this->_groupId1 = $this->groupCreate();

    $this->callAPISuccess('group_contact', 'create', array(
      'contact_id' => $this->_contactId,
      'group_id' => $this->_groupId1,
    ));

    $this->_groupId2 = $this->groupCreate(array(
      'name' => 'Test Group 2',
      'domain_id' => 1,
      'title' => 'New Test Group2 Created',
      'description' => 'New Test Group2 Created',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
    ));

    $this->_group = array(
      $this->_groupId1 => array(
        'title' => 'New Test Group Created',
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

  ///////////////// civicrm_group_contact_get methods

  /**
   * Test GroupContact.get by ID.
   */
  public function testGet() {
    $params = array(
      'contact_id' => $this->_contactId,
    );
    $result = $this->callAPIAndDocument('group_contact', 'get', $params, __FUNCTION__, __FILE__);
    foreach ($result['values'] as $v) {
      $this->assertEquals($v['title'], $this->_group[$v['group_id']]['title']);
      $this->assertEquals($v['visibility'], $this->_group[$v['group_id']]['visibility']);
      $this->assertEquals($v['in_method'], $this->_group[$v['group_id']]['in_method']);
    }
  }

  public function testGetGroupID() {
    $description = "Get all from group and display contacts.";
    $subfile = "GetWithGroupID";
    $params = array(
      'group_id' => $this->_groupId1,
      'api.group.get' => 1,
      'sequential' => 1,
    );
    $result = $this->callAPIAndDocument('group_contact', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    foreach ($result['values'][0]['api.group.get']['values'] as $values) {
      $key = $values['id'];
      $this->assertEquals($values['title'], $this->_group[$key]['title']);
      $this->assertEquals($values['visibility'], $this->_group[$key]['visibility']);
    }
  }

  public function testCreateWithEmptyParams() {
    $params = array();
    $groups = $this->callAPIFailure('group_contact', 'create', $params);
    $this->assertEquals($groups['error_message'],
      'Mandatory key(s) missing from params array: group_id, contact_id'
    );
  }

  public function testCreateWithoutGroupIdParams() {
    $params = array(
      'contact_id' => $this->_contactId,
    );

    $groups = $this->callAPIFailure('group_contact', 'create', $params);
    $this->assertEquals($groups['error_message'], 'Mandatory key(s) missing from params array: group_id');
  }

  public function testCreateWithoutContactIdParams() {
    $params = array(
      'group_id' => $this->_groupId1,
    );
    $groups = $this->callAPIFailure('group_contact', 'create', $params);
    $this->assertEquals($groups['error_message'], 'Mandatory key(s) missing from params array: contact_id');
  }

  public function testCreate() {
    $cont = array(
      'first_name' => 'Amiteshwar',
      'middle_name' => 'L.',
      'last_name' => 'Prasad',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'amiteshwar.prasad@civicrm.org',
      'contact_type' => 'Individual',
    );

    $this->_contactId1 = $this->individualCreate($cont);
    $params = array(
      'contact_id' => $this->_contactId,
      'contact_id.2' => $this->_contactId1,
      'group_id' => $this->_groupId1,
    );

    $result = $this->callAPIAndDocument('group_contact', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['not_added'], 1);
    $this->assertEquals($result['added'], 1);
    $this->assertEquals($result['total_count'], 2);
  }

  ///////////////// civicrm_group_contact_remove methods

  /**
   * Test GroupContact.delete by contact+group ID.
   */
  public function testDelete() {
    $params = array(
      'contact_id' => $this->_contactId,
      'group_id' => $this->_groupId1,
    );

    $result = $this->callAPIAndDocument('group_contact', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['total_count'], 1);
  }

  public function testDeletePermanent() {
    $result = $this->callAPISuccess('group_contact', 'get', array('contact_id' => $this->_contactId));
    $params = array(
      'id' => $result['id'],
      'skip_undelete' => TRUE,
    );
    $this->callAPIAndDocument('group_contact', 'delete', $params, __FUNCTION__, __FILE__);
    $result = $this->callAPISuccess('group_contact', 'get', $params);
    $this->assertEquals(0, $result['count']);
    $this->assertArrayNotHasKey('id', $result);
  }

  /**
   * CRM-16945 duplicate groups are showing up when contacts are hard-added to child groups or smart groups.
   *
   * Fix documented in
   *
   * Test illustrates this (& ensures once fixed it will stay fixed).
   */
  public function testAccurateCountWithSmartGroups() {
    $childGroupID = $this->groupCreate(array(
      'name' => 'Child group',
      'domain_id' => 1,
      'title' => 'Child group',
      'description' => 'Child group',
      'is_active' => 1,
      'parents' => $this->_groupId1,
      'visibility' => 'User and User Admin Only',
    ));

    $params = array(
      'name' => 'Individuals',
      'title' => 'Individuals',
      'is_active' => 1,
      'parents' => $this->_groupId1,
      'formValues' => array('contact_type' => 'Goat'),
    );
    $smartGroup2 = CRM_Contact_BAO_Group::createSmartGroup($params);

    $this->callAPISuccess('GroupContact', 'create', array('contact_id' => $this->_contactId, 'status' => 'Added', 'group_id' => $this->_groupId2));
    $this->callAPISuccess('GroupContact', 'create', array('contact_id' => $this->_contactId, 'status' => 'Added', 'group_id' => $smartGroup2->id));
    $this->callAPISuccess('GroupContact', 'create', array('contact_id' => $this->_contactId, 'status' => 'Added', 'group_id' => $childGroupID));
    $groups = $this->callAPISuccess('GroupContact', 'get', array('contact_id' => $this->_contactId));

    // Although the contact is actually hard-added to 4 groups the smart groups are conventionally not returned by the api or displayed
    // on the main part of the groups tab on the contact (which calls the same function. So, 3 groups is an OK number to return.
    // However, as of writing this test 4 groups are returned (indexed by group_contact_id, but more seriously 3/4 of those have the group id 1
    // so 2 on them have group ids that do not match the group contact id they have been keyed by.
    foreach ($groups['values'] as $groupContactID => $groupContactRecord) {
      $this->assertEquals($groupContactRecord['group_id'], CRM_Core_DAO::singleValueQuery("SELECT group_id FROM civicrm_group_contact WHERE id = $groupContactID"), 'Group contact record mis-returned for id ' . $groupContactID);
    }
    $this->assertEquals(3, $groups['count']);

  }

}
