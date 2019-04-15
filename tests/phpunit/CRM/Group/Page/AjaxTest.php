<?php

/**
 * Class CRM_Group_Page_AjaxTest
 * @group headless
 */
class CRM_Group_Page_AjaxTest extends CiviUnitTestCase {
  /**
   * Permissioned group is used both as an active group the contact can see and as a group that allows
   * logged in user to see contacts
   * @var integer
   */
  protected $_permissionedGroup;
  /**
   * AS disabled group the contact has permission to.
   * @var int
   */
  protected $_permissionedDisabledGroup;

  /**
   * @var CRM_Utils_Hook_UnitTests
   */
  public $hookClass;

  protected $_params = array();

  public function setUp() {
    parent::setUp();
    $this->_params = array(
      'page' => 1,
      'rp' => 50,
      'offset' => 0,
      'rowCount' => 50,
      'sort' => NULL,
      'parentsOnly' => FALSE,
      'is_unit_test' => TRUE,
    );
    $this->hookClass = CRM_Utils_Hook::singleton();
    $this->createLoggedInUser();
    $this->_permissionedDisabledGroup = $this->groupCreate(array(
      'title' => 'pick-me-disabled',
      'is_active' => 0,
      'name' => 'pick-me-disabled',
    ));
    $this->_permissionedGroup = $this->groupCreate(array(
      'title' => 'pick-me-active',
      'is_active' => 1,
      'name' => 'pick-me-active',
    ));
    $this->groupCreate(array('title' => 'not-me-disabled', 'is_active' => 0, 'name' => 'not-me-disabled'));
    $this->groupCreate(array('title' => 'not-me-active', 'is_active' => 1, 'name' => 'not-me-active'));
  }

  public function tearDown() {
    CRM_Utils_Hook::singleton()->reset();
    $this->quickCleanup(array('civicrm_group'));
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
    $this->hookClass->setHook('civicrm_aclGroup', array($this, $hook));
    global $_REQUEST;
    $_REQUEST = $this->_params;
  }

  /**
   * CRM-18528 - Retrieve groups with filter
   */
  public function testGroupListWithFilter() {
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));

    $_GET = $this->_params;
    $obj = new CRM_Group_Page_AJAX();

    //filter with title
    $_GET['title'] = "not-me-active";
    $groups = $obj->getGroupList();
    $this->assertEquals(1, $groups['recordsTotal']);
    $this->assertEquals('not-me-active', $groups['data'][0]['title']);
    unset($_GET['title']);

    // check on status
    $_GET['status'] = 2;
    $groups = $obj->getGroupList();
    foreach ($groups['data'] as $key => $val) {
      $this->assertEquals('crm-entity disabled', $val['DT_RowClass']);
    }
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContacts() {
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
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
  public function testGroupEditWithAndWithoutPermission() {
    $this->setPermissionAndRequest('view all contacts');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('<span><a href="/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=4&amp;component_mode=1" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a></span>', $groups['data'][0]['links']);
    $this->assertEquals('<span><a href="/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=2&amp;component_mode=1" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a></span>', $groups['data'][1]['links']);

    // as per changes made in PR-6822
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('<span><a href="/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=4&amp;component_mode=1" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a><a href="/index.php?q=civicrm/group&amp;reset=1&amp;action=update&amp;id=4" class="action-item crm-hover-button" title=\'Edit Group\' >Settings</a></span><span class=\'btn-slide crm-hover-button\'>more<ul class=\'panel\'><li><a href="#" class="action-item crm-hover-button crm-enable-disable" title=\'Disable Group\' >Disable</a></li><li><a href="/index.php?q=civicrm/group&amp;reset=1&amp;action=delete&amp;id=4" class="action-item crm-hover-button small-popup" title=\'Delete Group\' >Delete</a></li></ul></span>', $groups['data'][0]['links']);
    $this->assertEquals('<span><a href="/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=2&amp;component_mode=1" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a><a href="/index.php?q=civicrm/group&amp;reset=1&amp;action=update&amp;id=2" class="action-item crm-hover-button" title=\'Edit Group\' >Settings</a></span><span class=\'btn-slide crm-hover-button\'>more<ul class=\'panel\'><li><a href="#" class="action-item crm-hover-button crm-enable-disable" title=\'Disable Group\' >Disable</a></li><li><a href="/index.php?q=civicrm/group&amp;reset=1&amp;action=delete&amp;id=2" class="action-item crm-hover-button small-popup" title=\'Delete Group\' >Delete</a></li></ul></span>', $groups['data'][1]['links']);
  }

  /**
   * Retrieve groups as 'view all contacts' permissioned user
   * Without setting params the default is both enabled & disabled
   * (if you do set default it is enabled only)
   */
  public function testGroupListViewAllContactsFoundTitle() {
    $this->_params['title'] = 'p';
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-disabled', $groups['data'][1]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsNotFoundTitle() {
    $this->_params['title'] = 'z';
    $this->setPermissionAndRequest('view all contacts');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, $groups['recordsTotal']);
  }

  /**
   * Retrieve groups as 'edit all contacts'
   */
  public function testGroupListEditAllContacts() {
    $this->setPermissionAndRequest(array('edit all contacts', 'edit groups'));
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('not-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-active', $groups['data'][1]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsEnabled() {
    $this->_params['status'] = 1;
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('not-me-active', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-active', $groups['data'][1]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsDisabled() {
    $this->_params['status'] = 2;
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('not-me-disabled', $groups['data'][0]['title']);
    $this->assertEquals('pick-me-disabled', $groups['data'][1]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsDisabledNotFoundTitle() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'n';
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, $groups['recordsTotal']);
    $this->assertEquals('not-me-disabled', $groups['data'][0]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsDisabledFoundTitle() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'p';
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, $groups['recordsTotal']);
    $this->assertEquals('pick-me-disabled', $groups['data'][0]['title']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsAll() {
    $this->_params['status'] = 3;
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
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
  public function testGroupListAccessCiviCRM() {
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
  public function testGroupListAccessCiviCRMEnabled() {
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
  public function testGroupListAccessCiviCRMDisabled() {
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
  public function testGroupListAccessCiviCRMAll() {
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
  public function testGroupListAccessCiviCRMFound() {
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
  public function testGroupListAccessCiviCRMNotFound() {
    $this->_params['title'] = 'z';
    $this->setPermissionAndRequest('access CiviCRM');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']));
    $this->assertEquals(0, $groups['recordsTotal'], 'Total returned should be accurate based on permissions');
  }

  public function testTraditionalACL() {
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
  }

  public function testTraditionalACLNotFoundTitle() {
    $this->_params['title'] = 'n';
    $this->setupACL();
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(0, $groups['recordsTotal'], 'Total needs to be set correctly');
  }

  public function testTraditionalACLFoundTitle() {
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

  public function testTraditionalACLDisabled() {
    $this->_params['status'] = 2;
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-disabled', $groups['data'][0]['title']);
  }

  public function testTraditionalACLDisabledFoundTitle() {
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

  public function testTraditionalACLDisabledNotFoundTitle() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'n';
    $this->setupACL();
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(0, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(0, $groups['recordsTotal'], 'Total needs to be set correctly');
  }

  public function testTraditionalACLEnabled() {
    $this->_params['status'] = 1;
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
  }

  public function testTraditionalACLAll() {
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
  public function testGroupListAclGroupHookDisabled() {
    $this->_params['status'] = 2;
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-disabled', $groups['data'][0]['title']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookDisabledFound() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'p';
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-disabled', $groups['data'][0]['title']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookDisabledNotFound() {
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
  public function testGroupListAclGroupHook() {
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookTitleNotFound() {
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
  public function testGroupListAclGroupHookTitleFound() {
    $this->_params['title'] = 'p';
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
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
  public function testGroupListAclGroupHookAll() {
    $this->_params['status'] = 3;
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
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
  public function testGroupListAclGroupHookEnabled() {
    $this->_params['status'] = 1;
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(1, count($groups['data']), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $groups['recordsTotal'], 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups['data'][0]['title']);
  }

  /**
   * Don't populate smart group cache when building Group list.
   *
   * It takes forever, especially if you have lots of smart groups.
   */
  public function testGroupDontRegenerateSmartGroups() {
    // Create a contact.
    $firstName = 'Tweak';
    $lastName = 'Octonaut';
    $params = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
    );
    $contact = CRM_Contact_BAO_Contact::add($params);

    // Create a smart group.
    $searchParams = array(
      'last_name' => $lastName,
    );
    $groupParams = array('title' => 'Find all Octonauts', 'formValues' => $searchParams, 'is_active' => 1);
    $group = CRM_Contact_BAO_Group::createSmartGroup($groupParams);

    // Ensure the smart group is created.
    $this->assertTrue(is_int($group->id), "Smart group created successfully.");
    CRM_Contact_BAO_GroupContactCache::load($group, TRUE);

    // Ensure it is populating the cache when loaded.
    $sql = 'SELECT contact_id FROM civicrm_group_contact_cache WHERE group_id = %1';
    $params = array(1 => array($group->id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $this->assertEquals($dao->N, 1, '1 record should be found in smart group');

    // Load the Manage Group page code and we should get a count from our
    // group because the cache is fresh.
    $_GET = $this->_params;
    $obj = new CRM_Group_Page_AJAX();
    $groups = $obj->getGroupList();

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

    // Purge the group contact cache.
    CRM_Contact_BAO_GroupContactCache::clearGroupContactCache($group->id);

    // Load the Manage Group page code.
    $_GET = $this->_params;
    $obj = new CRM_Group_Page_AJAX();
    $groups = $obj->getGroupList();

    // Make sure the smart group reports unknown count.
    $count_is_unknown = FALSE;
    foreach ($groups['data'] as $returned_group) {
      if ($returned_group['group_id'] == $group->id) {
        if ($returned_group['count'] == ts('unknown')) {
          $count_is_unknown = TRUE;
        }
      }
    }
    $this->assertTrue($count_is_unknown, 'Smart group shows up as unknown when cache is expired.');

    // Ensure we did not populate the cache.
    $sql = 'SELECT contact_id FROM civicrm_group_contact_cache WHERE group_id = %1';
    $params = array(1 => array($group->id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $test = 'Group contact cache should not be populated on Manage Groups ' .
      'when cache_date is null';
    $this->assertEquals($dao->N, 0, $test);

    // Do it again, but this time don't clear group contact cache. Instead,
    // set it to expire.
    CRM_Contact_BAO_GroupContactCache::load($group, TRUE);
    $params['name'] = 'smartGroupCacheTimeout';
    $timeout = civicrm_api3('Setting', 'getvalue', $params);
    $timeout = intval($timeout) * 60;
    // Reset the cache_date to $timeout seconds ago minus another 60
    // seconds for good measure.
    $cache_date = date('YmdHis', time() - $timeout - 60);

    $sql = "UPDATE civicrm_group SET cache_date = %1 WHERE id = %2";
    $update_params = array(
      1 => array($cache_date, 'Timestamp'),
      2 => array($group->id, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($sql, $update_params);

    // Load the Manage Group page code.
    $_GET = $this->_params;
    $obj = new CRM_Group_Page_AJAX();
    $groups = $obj->getGroupList();

    // Ensure we did not regenerate the cache.
    $sql = 'SELECT DATE_FORMAT(cache_date, "%Y%m%d%H%i%s") AS cache_date ' .
      'FROM civicrm_group WHERE id = %1';
    $params = array(1 => array($group->id, 'Integer'));
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
    //don't use api - you will get a loop
    $sql = " SELECT * FROM civicrm_group WHERE name LIKE '%pick%'";
    $groups = array();
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

  public function testEditAllGroupsACL() {
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

    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $optionGroupID = $this->callAPISuccessGetValue('option_group', array('return' => 'id', 'name' => 'acl_role'));
    $ov = new CRM_Core_DAO_OptionValue();
    $ov->option_group_id = $optionGroupID;
    $ov->value = 55;
    if ($ov->find(TRUE)) {
      CRM_Core_DAO::executeQuery("DELETE FROM civicrm_option_value WHERE id = {$ov->id}");
    }
    $optionValue = $this->callAPISuccess('option_value', 'create', array(
      'option_group_id' => $optionGroupID,
      'label' => 'groupmaster',
      'value' => 55,
    ));
    $groupId = $this->groupCreate(['name' => 'groupmaster group']);
    // Assign groupmaster to groupmaster group in civicrm_acl_entity_role
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_acl_entity_role (
      `acl_role_id`, `entity_table`, `entity_id`, `is_active`
      ) VALUES (55, 'civicrm_group', $groupId, 1);
    ");
    // Put the user into this group
    $this->_loggedInUser = CRM_Core_Session::singleton()->get('userID');
    $this->callAPISuccess('group_contact', 'create', array(
      'group_id' => $groupId,
      'contact_id' => $this->_loggedInUser,
    ));
    // Add the ACL
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_acl (
      `name`, `entity_table`, `entity_id`, `operation`, `object_table`, `object_id`, `is_active`
      )
      VALUES (
      'core-580', 'civicrm_acl_role', 55, 'Edit', 'civicrm_saved_search', 0, 1
      );
      ");

  }

}
