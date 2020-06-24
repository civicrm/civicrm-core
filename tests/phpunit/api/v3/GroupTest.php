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
 * Test class for Group API - civicrm_group_*
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class api_v3_GroupTest extends CiviUnitTestCase {

  protected $_groupID;

  /**
   * Set up for tests.
   */
  public function setUp() {
    parent::setUp();
    $this->_groupID = $this->groupCreate();
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [];
  }

  /**
   * Clean up after test.
   *
   * @throws \Exception
   */
  public function tearDown() {
    CRM_Utils_Hook::singleton()->reset();
    $config = CRM_Core_Config::singleton();
    unset($config->userPermissionClass->permissions);
    $this->quickCleanup(['civicrm_group', 'civicrm_group_contact']);
    parent::tearDown();
  }

  /**
   * Test missing required title parameter results in an error.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGroupCreateNoTitle($version) {
    $this->_apiversion = $version;
    $params = [
      'name' => 'Test Group No title ',
      'domain_id' => 1,
      'description' => 'New Test Group Created',
      'is_active' => 1,
      'visibility' => 'Public Pages',
      'group_type' => [
        '1' => 1,
        '2' => 1,
      ],
    ];

    $this->callAPIFailure('group', 'create', $params, 'title');
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetGroupWithEmptyParams($version) {
    $this->_apiversion = $version;
    $group = $this->callAPISuccess('group', 'get', []);

    $group = $group["values"];
    $this->assertNotNull(count($group));
    $this->assertEquals($group[$this->_groupID]['name'], "Test Group 1");
    $this->assertEquals($group[$this->_groupID]['is_active'], 1);
    $this->assertEquals($group[$this->_groupID]['visibility'], 'Public Pages');
  }

  /**
   * Test ability to get active, inactive and both.
   *
   * Default is active only.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetGroupActiveAndInactive($version) {
    $this->_apiversion = $version;
    $this->groupCreate(['is_active' => 0, 'name' => 'group_2', 'title' => 2]);
    $group1 = $this->callAPISuccessGetSingle('Group', ['is_active' => 1]);
    $this->callAPISuccessGetCount('Group', [], 2);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetGroupParamsWithGroupId($version) {
    $this->_apiversion = $version;
    $params = ['id' => $this->_groupID];
    $group = $this->callAPISuccess('group', 'get', $params);

    foreach ($group['values'] as $v) {
      $this->assertEquals($v['name'], "Test Group 1");
      $this->assertEquals($v['title'], 'New Test Group Created');
      $this->assertEquals($v['description'], 'New Test Group Created');
      $this->assertEquals($v['is_active'], 1);
      $this->assertEquals($v['visibility'], 'Public Pages');
    }
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetGroupParamsWithGroupName($version) {
    $this->_apiversion = $version;
    $params = [
      'name' => "Test Group 1",
    ];
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

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetGroupParamsWithReturnName($version) {
    $this->_apiversion = $version;
    $params = [];
    $params['id'] = $this->_groupID;
    $params['return.name'] = 1;
    $group = $this->callAPISuccess('group', 'get', $params);
    $this->assertEquals($group['values'][$this->_groupID]['name'],
      "Test Group 1"
    );
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetGroupParamsWithGroupTitle($version) {
    $this->_apiversion = $version;
    $params = [];
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

  /**
   * Test Group create with Group Type and Parent
   * FIXME: Api4
   */
  public function testGroupCreateWithTypeAndParent() {
    $params = [
      'name' => 'Test Group type',
      'title' => 'Test Group Type',
      'description' => 'Test Group with Group Type',
      'is_active' => 1,
      //check for empty parent
      'parents' => "",
      'visibility' => 'Public Pages',
      'group_type' => [1, 2],
    ];

    $result = $this->callAPISuccess('Group', 'create', $params);
    $group = $result['values'][$result['id']];
    $this->assertEquals($group['name'], "Test Group type");
    $this->assertEquals($group['is_active'], 1);
    $this->assertEquals($group['parents'], "");
    $this->assertEquals($group['group_type'], $params['group_type']);

    //Pass group_type param in checkbox format.
    $params = array_merge($params, [
      'name' => 'Test Checkbox Format',
      'title' => 'Test Checkbox Format',
      'group_type' => [2 => 1],
    ]);
    $result = $this->callAPISuccess('Group', 'create', $params);
    $group = $result['values'][$result['id']];
    $this->assertEquals($group['name'], "Test Checkbox Format");
    $this->assertEquals($group['group_type'], array_keys($params['group_type']));

    //assert single value for group_type and parent
    $params = array_merge($params, [
      'name' => 'Test Group 2',
      'title' => 'Test Group 2',
      'group_type' => 2,
      'parents' => $result['id'],
      'sequential' => 1,
    ]);
    $group2 = $this->callAPISuccess('Group', 'create', $params)['values'][0];

    $this->assertEquals($group2['group_type'], [$params['group_type']]);
    $this->assertEquals($params['parents'], $group2['parents']);

    // Test array format for parents.
    $params = array_merge($params, [
      'name' => 'Test Group 3',
      'title' => 'Test Group 3',
      'parents' => [$result['id'], $group2['id']],
    ]);
    $group3 = $this->callAPISuccess('Group', 'create', $params)['values'][0];
    $parents = $this->callAPISuccess('Group', 'getvalue', ['return' => 'parents', 'id' => $group3['id']]);

    $this->assertAPIArrayComparison("{$result['id']},{$group2['id']}", $parents);

    $groupNesting = $this->callAPISuccess('GroupNesting', 'get', ['child_group_id' => $group3['id']]);
    // 2 Group nesting entries - one for direct parent & one for grandparent.
    $this->assertEquals(2, $groupNesting['count']);
    $this->groupDelete($group2['id']);
    $this->groupDelete($group3['id']);
  }

  /**
   * Test that an array of valid values works for group_type field.
   * FIXME: Api4
   */
  public function testGroupTypeWithPseudoconstantArray() {
    $params = [
      'name' => 'Test Group 2',
      'title' => 'Test Group 2',
      'group_type' => ['Mailing List', 'Access Control'],
      'sequential' => 1,
    ];
    $group = $this->callAPISuccess('Group', 'create', $params);
    $groupType = $this->callAPISuccess('Group', 'getvalue', ['return' => 'group_type', 'id' => $group['id']]);

    $this->assertAPIArrayComparison([2, 1], $groupType);
  }

  /**
   * Test / demonstrate behaviour when attempting to filter by group_type.
   *
   * Per https://lab.civicrm.org/dev/core/issues/1321 the group_type filter is deceptive
   * - it only filters on exact match not 'is one of'.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGroupWithGroupTypeFilter() {
    $this->groupCreate(['group_type' => ['Access Control'], 'name' => 'access_list', 'title' => 'access list']);
    $this->groupCreate(['group_type' => ['Mailing List'], 'name' => 'mailing_list', 'title' => 'mailing list']);
    $this->groupCreate(['group_type' => ['Access Control', 'Mailing List'], 'name' => 'group', 'title' => 'group']);
    $group = $this->callAPISuccessGetSingle('Group', ['return' => 'id,title,group_type', 'group_type' => 'Mailing List']);
    $this->assertEquals('mailing list', $group['title']);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetNonExistingGroup($version) {
    $this->_apiversion = $version;
    $params = [];
    $params['title'] = 'No such group Exist';
    $group = $this->callAPISuccess('group', 'get', $params);
    $this->assertEquals(0, $group['count']);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testgroupdeleteParamsnoId($version) {
    $this->_apiversion = $version;
    $group = $this->callAPIFailure('group', 'delete', []);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testgetfields($version) {
    $this->_apiversion = $version;
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = ['action' => 'create'];
    $result = $this->callAPIAndDocument('group', 'getfields', $params, __FUNCTION__, __FILE__, $description);
    $this->assertEquals('is_active', $result['values']['is_active']['name']);
  }

  public function testIllegalParentsParams() {
    $params = [
      'title' => 'Test illegal Group',
      'domain_id' => 1,
      'description' => 'Testing illegal Parents params',
      'is_active' => 1,
      'parents' => "(SELECT api_key FROM civicrm_contact where id = 1)",
    ];
    $this->callAPIFailure('group', 'create', $params);
    unset($params['parents']);
    $this->callAPISuccess('group', 'create', $params);
    $group1 = $this->callAPISuccess('group', 'get', [
      'title' => 'Test illegal Group',
      'parents' => ['IS NOT NULL' => 1],
    ]);
    $this->assertEquals(0, $group1['count']);
    $params['title'] = 'Test illegal Group 2';
    $params['parents'] = [];
    $params['parents'][$this->_groupID] = 'test Group';
    $params['parents']["(SELECT api_key FROM civicrm_contact where id = 1)"] = "Test";
    $this->callAPIFailure('group', 'create', $params);
    unset($params['parents']["(SELECT api_key FROM civicrm_contact where id = 1)"]);
    $this->callAPIFailure('group', 'create', $params, '\'test Group\' is not a valid option for field parents');
  }

  /**
   * Test that ACLs are applied to group.get calls.
   * FIXME: Api4
   */
  public function testGroupGetACLs() {
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->callAPISuccessGetCount('Group', ['check_permissions' => 1], 0);
    $this->hookClass->setHook('civicrm_aclGroup', [$this, 'aclGroupAllGroups']);
    unset(Civi::$statics['CRM_ACL_API']['group_permission']);
    $this->callAPISuccessGetCount('Group', ['check_permissions' => 1], 1);
  }

  /**
   * Implement hook to restrict to test group 1.
   *
   * @param string $type
   * @param int $contactID
   * @param string $tableName
   * @param array $allGroups
   * @param array $ids
   */
  public function aclGroupAllGroups($type, $contactID, $tableName, $allGroups, &$ids) {
    $group = $this->callAPISuccess('Group', 'get', ['name' => 'Test Group 1']);
    $ids = array_keys($group['values']);
  }

}
