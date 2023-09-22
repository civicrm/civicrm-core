<?php

/**
 * Class CRM_Group_Page_AjaxTest
 * @group headless
 */
class CRM_Group_Page_AjaxTest extends CiviUnitTestCase {
  /**
   * Permissioned group is used both as an active group the contact can see and as a group that allows
   * logged in user to see contacts.
   *
   * @var int
   */
  protected $_permissionedGroup;
  /**
   * AS disabled group the contact has permission to.
   * @var int
   */
  protected $_permissionedDisabledGroup;

  protected $_params = [];

  public function setUp(): void {
    parent::setUp();
    $this->_params = [
      'page' => 1,
      'rp' => 50,
      'offset' => 0,
      'rowCount' => 50,
      'sort' => NULL,
      'parentsOnly' => FALSE,
    ];
    $this->hookClass = CRM_Utils_Hook::singleton();
    $this->createLoggedInUser();
    $this->_permissionedDisabledGroup = $this->groupCreate([
      'title' => 'pick-me-disabled',
      'is_active' => 0,
      'name' => 'pick-me-disabled',
    ]);
    $this->_permissionedGroup = $this->groupCreate([
      'title' => 'pick-me-active',
      'is_active' => 1,
      'name' => 'pick-me-active',
    ]);
    $this->groupCreate(['title' => 'not-me-disabled', 'is_active' => 0, 'name' => 'not-me-disabled']);
    $this->groupCreate(['title' => 'not-me-active', 'is_active' => 1, 'name' => 'not-me-active']);
  }

  public function tearDown(): void {
    CRM_Utils_Hook::singleton()->reset();
    $this->quickCleanup(['civicrm_group']);
    $config = CRM_Core_Config::singleton();
    unset($config->userPermissionClass->permissions);
    parent::tearDown();
  }

  /**
   * @param $permission
   */
  public function setPermissionAndRequest($permission) {
    $this->setPermissions((array) $permission);
    global $_REQUEST;
    $_REQUEST = $this->_params;
  }

  /**
   * @param $permission
   * @param $hook
   */
  public function setHookAndRequest($permission, $hook) {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = (array) $permission;
    $this->hookClass->setHook('civicrm_aclGroup', [$this, $hook]);
    global $_REQUEST;
    $_REQUEST = $this->_params;
  }

  /**
   * CRM-18528 - Retrieve groups with filter.
   */
  public function testGroupListWithFilter(): void {
    $this->setPermissionAndRequest(['view all contacts', 'edit groups']);

    $_GET = $this->_params;

    // Filter with title.
    $_GET['title'] = 'not-me-active';
    try {
      CRM_Group_Page_AJAX::getGroupList();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $groups = $e->errorData;
    }
    $this->assertEquals(1, $groups['recordsTotal']);
    $this->assertEquals('not-me-active', $groups['data'][0]['title']);
    // Search on just smart groups keeping the title filter
    $_GET['savedSearch'] = 1;
    try {
      CRM_Group_Page_AJAX::getGroupList();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $groups = $e->errorData;
    }
    $this->assertEquals(0, $groups['recordsTotal']);
    // Now search on just normal groups keeping the title filter
    $_GET['savedSearch'] = 2;
    try {
      CRM_Group_Page_AJAX::getGroupList();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $groups = $e->errorData;
    }
    $this->assertEquals(1, $groups['recordsTotal']);
    $this->assertEquals('not-me-active', $groups['data'][0]['title']);
    unset($_GET['title'], $_GET['savedSearch']);

    // Check on status.
    $_GET['status'] = 2;
    try {
      CRM_Group_Page_AJAX::getGroupList();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $groups = $e->errorData;
    }
    foreach ($groups['data'] as $val) {
      $this->assertEquals('crm-entity disabled', $val['DT_RowClass']);
    }
  }

  /**
   * Retrieve groups as 'view all contacts'.
   */
  public function testGroupListViewAllContacts(): void {
    $this->setPermissionAndRequest(['view all contacts', 'edit groups']);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('not-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-active', $groups['data'][1]['title']);
  }

  /**
   * Check Group Edit w/o 'edit groups' permission.
   *
   * FIXME permissions to edit groups can only be determined by the links, which is ridiculously long
   */
  public function testGroupEditWithAndWithoutPermission(): void {
    $this->setPermissionAndRequest('view all contacts');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('<span><a href="/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=4&amp;component_mode=1" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a></span>', $groups['data'][0]['links']);
    $this->assertEquals('<span><a href="/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=2&amp;component_mode=1" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a></span>', $groups['data'][1]['links']);

    // as per changes made in PR-6822
    $this->setPermissionAndRequest(['view all contacts', 'edit groups']);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('<span><a href="/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=4&amp;component_mode=1" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a><a href="/index.php?q=civicrm/group/edit&amp;reset=1&amp;action=update&amp;id=4" class="action-item crm-hover-button" title=\'Edit Group\' >Settings</a></span><span class=\'btn-slide crm-hover-button\'>more<ul class=\'panel\'><li><a href="#" class="action-item crm-hover-button crm-enable-disable" title=\'Disable Group\' >Disable</a></li><li><a href="/index.php?q=civicrm/group/edit&amp;reset=1&amp;action=delete&amp;id=4" class="action-item crm-hover-button small-popup" title=\'Delete Group\' >Delete</a></li></ul></span>', $groups['data'][0]['links']);
    $this->assertEquals('<span><a href="/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=2&amp;component_mode=1" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a><a href="/index.php?q=civicrm/group/edit&amp;reset=1&amp;action=update&amp;id=2" class="action-item crm-hover-button" title=\'Edit Group\' >Settings</a></span><span class=\'btn-slide crm-hover-button\'>more<ul class=\'panel\'><li><a href="#" class="action-item crm-hover-button crm-enable-disable" title=\'Disable Group\' >Disable</a></li><li><a href="/index.php?q=civicrm/group/edit&amp;reset=1&amp;action=delete&amp;id=2" class="action-item crm-hover-button small-popup" title=\'Delete Group\' >Delete</a></li></ul></span>', $groups['data'][1]['links']);
  }

  /**
   * Retrieve groups as 'view all contacts' permissioned user
   * Without setting params the default is both enabled & disabled
   * (if you do set default it is enabled only)
   */
  public function testGroupListViewAllContactsFoundTitle(): void {
    $this->_params['title'] = 'p';
    $this->setPermissionAndRequest(['view all contacts', 'edit groups']);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-disabled', $groups['data'][1]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsNotFoundTitle(): void {
    $this->_params['title'] = 'z';
    $this->setPermissionAndRequest('view all contacts');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, $groups['recordsTotal']);
  }

  /**
   * Retrieve groups as 'edit all contacts'
   */
  public function testGroupListEditAllContacts(): void {
    $this->setPermissionAndRequest(['edit all contacts', 'edit groups']);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('not-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-active', $groups['data'][1]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsEnabled(): void {
    $this->_params['status'] = 1;
    $this->setPermissionAndRequest(['view all contacts', 'edit groups']);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('not-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-active', $groups['data'][1]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsDisabled(): void {
    $this->_params['status'] = 2;
    $this->setPermissionAndRequest(['view all contacts', 'edit groups']);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('not-me-disabled', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-disabled', $groups['data'][1]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsDisabledNotFoundTitle(): void {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'n';
    $this->setPermissionAndRequest(['view all contacts', 'edit groups']);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, $groups['recordsTotal']);
    $this->assertEquals('not-me-disabled', $groups['data'][0]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsDisabledFoundTitle(): void {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'p';
    $this->setPermissionAndRequest(['view all contacts', 'edit groups']);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, $groups['recordsTotal']);
    $this->assertEquals('pick-me-disabled', $groups['data'][0]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsAll(): void {
    $this->_params['status'] = 3;
    $this->setPermissionAndRequest(['view all contacts', 'edit groups']);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(4, $groups['recordsTotal']);
    $this->assertEquals('not-me-active', $groups['data'][0]['title']);
    $this->assertEquals('not-me-disabled', $groups['data'][1]['title']);
    $this->assertEquals('pick-me-active', $groups['data'][2]['title']);
    $this->assertEquals('pick-me-disabled', $groups['data'][3]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRM(): void {
    $this->setPermissionAndRequest('access CiviCRM');
    $permissionClause = CRM_Contact_BAO_Group::getPermissionClause();
    $this->assertEquals('1 = 0', $permissionClause);
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']));
    $this->assertEquals(0, $groups['recordsTotal'], 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMEnabled(): void {
    $this->_params['status'] = 1;
    $this->setPermissionAndRequest('access CiviCRM');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']));
    $this->assertEquals(0, $groups['recordsTotal'], 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMDisabled(): void {
    $this->_params['status'] = 2;
    $this->setPermissionAndRequest('access CiviCRM');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']));
    $this->assertEquals(0, $groups['recordsTotal'], 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMAll(): void {
    $this->_params['status'] = 2;
    $this->setPermissionAndRequest('access CiviCRM');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']));
    $this->assertEquals(0, $groups['recordsTotal'], 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMFound(): void {
    $this->_params['title'] = 'p';
    $this->setPermissionAndRequest('access CiviCRM');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']));
    $this->assertEquals(0, $groups['recordsTotal'], 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMNotFound(): void {
    $this->_params['title'] = 'z';
    $this->setPermissionAndRequest('access CiviCRM');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']));
    $this->assertEquals(0, $groups['recordsTotal'], 'Total returned should be accurate based on permissions');
  }

  public function testTraditionalACL(): void {
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
  }

  public function testTraditionalACLNotFoundTitle(): void {
    $this->_params['title'] = 'n';
    $this->setupACL();
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(0, $groups['recordsTotal'], 'Total needs to be set correctly');
  }

  public function testTraditionalACLFoundTitle(): void {
    $this->_params['title'] = 'p';
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-disabled', $groups['data'][1]['title']);
  }

  public function testTraditionalACLDisabled(): void {
    $this->_params['status'] = 2;
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-disabled', $groups['data'][0]['title']);
  }

  public function testTraditionalACLDisabledFoundTitle(): void {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'p';
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-disabled', $groups['data'][0]['title']);
  }

  public function testTraditionalACLDisabledNotFoundTitle(): void {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'n';
    $this->setupACL();
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(0, $groups['recordsTotal'], 'Total needs to be set correctly');
  }

  public function testTraditionalACLEnabled(): void {
    $this->_params['status'] = 1;
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
  }

  public function testTraditionalACLAll(): void {
    $this->_params['status'] = 3;
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-disabled', $groups['data'][1]['title']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookDisabled(): void {
    $this->_params['status'] = 2;
    $this->setHookAndRequest(['access CiviCRM', 'edit groups'], 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-disabled', $groups['data'][0]['title']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookDisabledFound(): void {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'p';
    $this->setHookAndRequest(['access CiviCRM', 'edit groups'], 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-disabled', $groups['data'][0]['title']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookDisabledNotFound(): void {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'n';
    $this->setHookAndRequest('access CiviCRM', 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(0, $groups['recordsTotal'], 'Total needs to be set correctly');
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHook(): void {
    $this->setHookAndRequest(['access CiviCRM', 'edit groups'], 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookTitleNotFound(): void {
    $this->_params['title'] = 'n';
    $this->setHookAndRequest('access CiviCRM', 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(0, $groups['recordsTotal'], 'Total needs to be set correctly');
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookTitleFound(): void {
    $this->_params['title'] = 'p';
    $this->setHookAndRequest(['access CiviCRM', 'edit groups'], 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-disabled', $groups['data'][1]['title']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookAll(): void {
    $this->_params['status'] = 3;
    $this->setHookAndRequest(['access CiviCRM', 'edit groups'], 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-disabled', $groups['data'][1]['title']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookEnabled(): void {
    $this->_params['status'] = 1;
    $this->setHookAndRequest(['access CiviCRM', 'edit groups'], 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
  }

  /**
   * Test sorting, including with smart group.
   */
  public function testSmartGroupSort(): void {
    $this->smartGroupCreate();
    $this->_params['sort'] = 'count asc';
    CRM_Contact_BAO_Group::getGroupList($this->_params);
  }

  /**
   * Don't populate smart group cache when building Group list.
   *
   * It takes forever, especially if you have lots of smart groups.
   */
  public function testGroupDontRegenerateSmartGroups(): void {
    // Create a contact.
    $firstName = 'Tweak';
    $lastName = 'Octonaut';
    $params = [
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);

    // Create a smart group.
    $searchParams = [
      'last_name' => $lastName,
    ];
    $groupParams = ['title' => 'Find all Octonauts', 'formValues' => $searchParams, 'is_active' => 1];
    $group = CRM_Contact_BAO_Group::createSmartGroup($groupParams);

    // Ensure the smart group is created.
    $this->assertIsInt($group->id, "Smart group created successfully.");
    CRM_Contact_BAO_GroupContactCache::load($group);

    // Ensure it is populating the cache when loaded.
    $sql = 'SELECT contact_id FROM civicrm_group_contact_cache WHERE group_id = %1';
    $params = [1 => [$group->id, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $this->assertEquals($dao->N, 1, '1 record should be found in smart group');

    // Load the Manage Group page code and we should get a count from our
    // group because the cache is fresh.
    $_GET = $this->_params;
    // look for Smart Group only
    $_GET['savedSearch'] = 1;
    try {
      CRM_Group_Page_AJAX::getGroupList();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $groups = $e->errorData;
    }

    // Make sure we returned our smart group and ensure the count is accurate.
    $found = FALSE;
    $right_count = FALSE;
    foreach ($groups['data'] as $returned_group) {
      if ($returned_group['group_id'] == $group->id) {
        $found = TRUE;
        if ($returned_group['count'] == 1) {
          $right_count = TRUE;
        }
      }
    }
    $this->assertTrue($found, 'Smart group shows up on Manage Group page.');
    $this->assertTrue($right_count, 'Smart group displays proper count when cache is loaded.');

    // Invalidate the group contact cache.
    CRM_Contact_BAO_GroupContactCache::invalidateGroupContactCache($group->id);

    // Load the Manage Group page code.
    $_GET = $this->_params;
    try {
      CRM_Group_Page_AJAX::getGroupList();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $groups = $e->errorData;
    }

    // Make sure the smart group reports unknown count.
    $count_is_unknown = FALSE;
    foreach ($groups['data'] as $returned_group) {
      if ($returned_group['group_id'] == $group->id) {
        if ($returned_group['count'] === ts('unknown')) {
          $count_is_unknown = TRUE;
        }
      }
    }
    $this->assertTrue($count_is_unknown, 'Smart group shows up as unknown when cache is expired.');

    // Do it again, but this time don't clear group contact cache. Instead,
    // set it to expire.
    CRM_Contact_BAO_GroupContactCache::load($group);
    $params['name'] = 'smartGroupCacheTimeout';
    $timeout = civicrm_api3('Setting', 'getvalue', $params);
    $timeout = (int) $timeout * 60;
    // Reset the cache_date to $timeout seconds ago minus another 60
    // seconds for good measure.
    $cache_date = date('YmdHis', time() - $timeout - 60);

    $sql = 'UPDATE civicrm_group SET cache_date = %1 WHERE id = %2';
    $update_params = [
      1 => [$cache_date, 'Timestamp'],
      2 => [$group->id, 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($sql, $update_params);

    // Load the Manage Group page code.
    $_GET = $this->_params;
    try {
      CRM_Group_Page_AJAX::getGroupList();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // Perhaps this was just testing valid sql...
    }

    // Ensure we did not regenerate the cache.
    $sql = 'SELECT DATE_FORMAT(cache_date, "%Y%m%d%H%i%s") AS cache_date ' .
      'FROM civicrm_group WHERE id = %1';
    $params = [1 => [$group->id, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    $test = 'Group contact cache should not be re-populated on Manage Groups ' .
     'when cache_date has expired';
    $this->assertEquals($dao->cache_date, $cache_date, $test);
  }

  /**
   * Implements ACLGroup hook.
   * aclGroup function returns a list of permitted groups
   * @param string $type
   * @param int $contactID
   * @param string $tableName
   * @param array $allGroups
   * @param array $currentGroups
   */
  public function hook_civicrm_aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups) {
    if ($tableName !== 'civicrm_group') {
      return;
    }
    //don't use api - you will get a loop
    $sql = " SELECT * FROM civicrm_group WHERE name LIKE '%pick%'";
    $groups = [];
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $groups[] = $dao->id;
    }

    if (!empty($allGroups)) {
      //all groups is empty if we really mean all groups but if a filter like 'is_disabled' is already applied
      // it is populated, ajax calls from Manage Groups will leave empty but calls from New Mailing pass in a filtered list
      $currentGroups = array_intersect($groups, array_flip($allGroups));
    }
    else {
      $currentGroups = $groups;
    }
  }

  public function testEditAllGroupsACL(): void {
    $this->setupEditAllGroupsACL();
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertNotEmpty($groups, 'If Edit All Groups is granted, at least one group should be visible');
  }

  /**
   * Set up an acl allowing Authenticated contacts to Edit All Groups
   *
   *  You need to have pre-created these groups & created the user e.g
   *  $this->createLoggedInUser();
   *
   */
  public function setupEditAllGroupsACL() {
    global $_REQUEST;
    $_REQUEST = $this->_params;

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $optionGroupID = $this->callAPISuccessGetValue('option_group', ['return' => 'id', 'name' => 'acl_role']);
    $ov = new CRM_Core_DAO_OptionValue();
    $ov->option_group_id = $optionGroupID;
    $ov->value = 55;
    if ($ov->find(TRUE)) {
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_option_value WHERE id = {$ov->id}");
    }
    $optionValue = $this->callAPISuccess('option_value', 'create', [
      'option_group_id' => $optionGroupID,
      'label' => 'groupmaster',
      'value' => 55,
    ]);
    $groupId = $this->groupCreate(['name' => 'groupmaster group']);
    // Assign groupmaster to groupmaster group in civicrm_acl_entity_role
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_acl_entity_role (
      `acl_role_id`, `entity_table`, `entity_id`, `is_active`
      ) VALUES (55, 'civicrm_group', $groupId, 1);
    ");
    // Put the user into this group
    $loggedInUser = CRM_Core_Session::singleton()->get('userID');
    $this->callAPISuccess('group_contact', 'create', [
      'group_id' => $groupId,
      'contact_id' => $loggedInUser,
    ]);
    // Add the ACL
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_acl (
      `name`, `entity_table`, `entity_id`, `operation`, `object_table`, `object_id`, `is_active`
      )
      VALUES (
      'core-580', 'civicrm_acl_role', 55, 'Edit', 'civicrm_group', 0, 1
      );
      ");

  }

}
