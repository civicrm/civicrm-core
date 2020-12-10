<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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

    $this->callAPISuccess('group_contact', 'create', [
      'contact_id' => $this->_contactId,
      'group_id' => $this->_groupId1,
    ]);

    $this->_groupId2 = $this->groupCreate([
      'name' => 'Test Group 2',
      'domain_id' => 1,
      'title' => 'New Test Group2 Created',
      'description' => 'New Test Group2 Created',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
    ]);

    $this->_group = [
      $this->_groupId1 => [
        'title' => 'New Test Group Created',
        'visibility' => 'Public Pages',
        'in_method' => 'API',
      ],
      $this->_groupId2 => [
        'title' => 'New Test Group2 Created',
        'visibility' => 'User and User Admin Only',
        'in_method' => 'API',
      ],
    ];
  }

  /**
   * Test GroupContact.get by ID.
   */
  public function testGet() {
    $params = [
      'contact_id' => $this->_contactId,
    ];
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
    $params = [
      'group_id' => $this->_groupId1,
      'api.group.get' => 1,
      'sequential' => 1,
    ];
    $result = $this->callAPIAndDocument('group_contact', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    foreach ($result['values'][0]['api.group.get']['values'] as $values) {
      $key = $values['id'];
      $this->assertEquals($values['title'], $this->_group[$key]['title']);
      $this->assertEquals($values['visibility'], $this->_group[$key]['visibility']);
    }
  }

  public function testCreateWithEmptyParams() {
    $params = [];
    $groups = $this->callAPIFailure('group_contact', 'create', $params);
    $this->assertEquals($groups['error_message'],
      'Mandatory key(s) missing from params array: group_id, contact_id'
    );
  }

  public function testCreateWithoutGroupIdParams() {
    $params = [
      'contact_id' => $this->_contactId,
    ];

    $groups = $this->callAPIFailure('group_contact', 'create', $params);
    $this->assertEquals($groups['error_message'], 'Mandatory key(s) missing from params array: group_id');
  }

  public function testCreateWithoutContactIdParams() {
    $params = [
      'group_id' => $this->_groupId1,
    ];
    $groups = $this->callAPIFailure('group_contact', 'create', $params);
    $this->assertEquals($groups['error_message'], 'Mandatory key(s) missing from params array: contact_id');
  }

  public function testCreate() {
    $cont = [
      'first_name' => 'Amiteshwar',
      'middle_name' => 'L.',
      'last_name' => 'Prasad',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'amiteshwar.prasad@civicrm.org',
      'contact_type' => 'Individual',
    ];

    $this->_contactId1 = $this->individualCreate($cont);
    $params = [
      'contact_id' => $this->_contactId,
      'contact_id.2' => $this->_contactId1,
      'group_id' => $this->_groupId1,
    ];

    $result = $this->callAPIAndDocument('group_contact', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['not_added'], 1);
    $this->assertEquals($result['added'], 1);
    $this->assertEquals($result['total_count'], 2);
  }

  /**
   * Test GroupContact.delete by contact+group ID.
   */
  public function testDelete() {
    $params = [
      'contact_id' => $this->_contactId,
      'group_id' => $this->_groupId1,
    ];

    $result = $this->callAPIAndDocument('group_contact', 'delete', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['total_count'], 1);
  }

  public function testDeletePermanent() {
    $result = $this->callAPISuccess('group_contact', 'get', ['contact_id' => $this->_contactId]);
    $params = [
      'id' => $result['id'],
      'skip_undelete' => TRUE,
    ];
    $this->callAPIAndDocument('group_contact', 'delete', $params, __FUNCTION__, __FILE__);
    $result = $this->callAPISuccess('group_contact', 'get', $params);
    $this->assertEquals(0, $result['count']);
    $this->assertArrayNotHasKey('id', $result);
  }

  /**
   * CRM-19496 When id is used rather than contact_id and group_id ensure that remove function still works.
   *
   */
  public function testDeleteWithId() {
    $groupContactParams = [
      'contact_id' => $this->_contactId,
      'group_id' => $this->_groupId1,
    ];
    $groupContact = $this->callAPISuccess('group_contact', 'get', $groupContactParams);
    $params = [
      'id' => $groupContact['id'],
      'status' => 'Removed',
    ];
    $result = $this->callAPISuccess('group_contact', 'delete', $params);
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['total_count'], 1);
  }

  /**
   * CRM-19496 When id is used rather than contact_id and group_id ensure that remove function still works.
   *
   */
  public function testDeleteAndReAddWithId() {
    $groupContactParams = [
      'contact_id' => $this->_contactId,
      'group_id' => $this->_groupId1,
    ];
    $groupContact = $this->callAPISuccess('group_contact', 'get', $groupContactParams);
    $params = [
      'id' => $groupContact['id'],
      'status' => 'Removed',
    ];
    $result = $this->callAPISuccess('group_contact', 'delete', $params);
    $this->assertEquals($result['removed'], 1);
    $this->assertEquals($result['total_count'], 1);
    $params = array_merge($params, ['status' => 'Added']);
    $result2 = $this->callAPISuccess('group_contact', 'delete', $params);
    $this->assertEquals($result2['added'], 1);
    $this->assertEquals($result2['total_count'], 1);
  }

  /**
   * CRM-19979 test that group cotnact delete action works when contact is in status of pendin.
   */
  public function testDeleteWithPending() {
    $groupId3 = $this->groupCreate([
      'name' => 'Test Group 3',
      'domain_id' => 1,
      'title' => 'New Test Group3 Created',
      'description' => 'New Test Group3 Created',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
    ]);
    $groupContactCreateParams = [
      'contact_id' => $this->_contactId,
      'group_id' => $groupId3,
      'status' => 'Pending',
    ];
    $groupContact = $this->callAPISuccess('groupContact', 'create', $groupContactCreateParams);
    $groupGetContact = $this->CallAPISuccess('groupContact', 'get', $groupContactCreateParams);
    $this->callAPISuccess('groupContact', 'delete', ['id' => $groupGetContact['id'], 'status' => 'Removed']);
    $this->callAPISuccess('groupContact', 'delete', ['id' => $groupGetContact['id'], 'skip_undelete' => TRUE]);
    $this->callAPISuccess('group', 'delete', ['id' => $groupId3]);
  }

  /**
   * CRM-19979 test that group cotnact delete action works when contact is in status of pendin and is a permanent delete.
   */
  public function testPermanentDeleteWithPending() {
    $groupId3 = $this->groupCreate([
      'name' => 'Test Group 3',
      'domain_id' => 1,
      'title' => 'New Test Group3 Created',
      'description' => 'New Test Group3 Created',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
    ]);
    $groupContactCreateParams = [
      'contact_id' => $this->_contactId,
      'group_id' => $groupId3,
      'status' => 'Pending',
    ];
    $groupContact = $this->callAPISuccess('groupContact', 'create', $groupContactCreateParams);
    $groupGetContact = $this->CallAPISuccess('groupContact', 'get', $groupContactCreateParams);
    $this->callAPISuccess('groupContact', 'delete', ['id' => $groupGetContact['id'], 'skip_undelete' => TRUE]);
    $this->callAPISuccess('group', 'delete', ['id' => $groupId3]);
  }

  /**
   * CRM-16945 duplicate groups are showing up when contacts are hard-added to child groups or smart groups.
   *
   * Fix documented in
   *
   * Test illustrates this (& ensures once fixed it will stay fixed).
   */
  public function testAccurateCountWithSmartGroups() {
    $childGroupID = $this->groupCreate([
      'name' => 'Child group',
      'domain_id' => 1,
      'title' => 'Child group',
      'description' => 'Child group',
      'is_active' => 1,
      'parents' => $this->_groupId1,
      'visibility' => 'User and User Admin Only',
    ]);

    $params = [
      'name' => 'Individuals',
      'title' => 'Individuals',
      'is_active' => 1,
      'parents' => $this->_groupId1,
      'formValues' => ['contact_type' => 'Goat'],
    ];
    $smartGroup2 = CRM_Contact_BAO_Group::createSmartGroup($params);

    $this->callAPISuccess('GroupContact', 'create', ['contact_id' => $this->_contactId, 'status' => 'Added', 'group_id' => $this->_groupId2]);
    $this->callAPISuccess('GroupContact', 'create', ['contact_id' => $this->_contactId, 'status' => 'Added', 'group_id' => $smartGroup2->id]);
    $this->callAPISuccess('GroupContact', 'create', ['contact_id' => $this->_contactId, 'status' => 'Added', 'group_id' => $childGroupID]);
    $groups = $this->callAPISuccess('GroupContact', 'get', ['contact_id' => $this->_contactId]);

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
