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
    CRM_Contact_BAO_Group::getPermissionClause(TRUE);
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
    $this->assertEquals('<span><a href="' . CIVICRM_UF_BASEURL . '/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=4" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a></span>', $groups['data'][0]['links']);
    $this->assertEquals('<span><a href="' . CIVICRM_UF_BASEURL . '/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=2" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a></span>', $groups['data'][1]['links']);

    // as per changes made in PR-6822
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    $params = $this->_params;
    $groups = CRM_Contact_BAO_Group::getGroupListSelector($params);
    $this->assertEquals(2, $groups['recordsTotal']);
    $this->assertEquals('<span><a href="'
      . CIVICRM_UF_BASEURL . '/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=4" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a><a href="'
      . CIVICRM_UF_BASEURL . '/index.php?q=civicrm/group&amp;reset=1&amp;action=update&amp;id=4" class="action-item crm-hover-button" title=\'Edit Group\' >Settings</a></span><span class=\'btn-slide crm-hover-button\'>more<ul class=\'panel\'><li><a href="#" class="action-item crm-hover-button crm-enable-disable" title=\'Disable Group\' >Disable</a></li><li><a href="'
      . CIVICRM_UF_BASEURL . '/index.php?q=civicrm/group&amp;reset=1&amp;action=delete&amp;id=4" class="action-item crm-hover-button small-popup" title=\'Delete Group\' >Delete</a></li></ul></span>', $groups['data'][0]['links']);
    $this->assertEquals('<span><a href="'
      . CIVICRM_UF_BASEURL . '/index.php?q=civicrm/group/search&amp;reset=1&amp;force=1&amp;context=smog&amp;gid=2" class="action-item crm-hover-button" title=\'Group Contacts\' >Contacts</a><a href="'
      . CIVICRM_UF_BASEURL . '/index.php?q=civicrm/group&amp;reset=1&amp;action=update&amp;id=2" class="action-item crm-hover-button" title=\'Edit Group\' >Settings</a></span><span class=\'btn-slide crm-hover-button\'>more<ul class=\'panel\'><li><a href="#" class="action-item crm-hover-button crm-enable-disable" title=\'Disable Group\' >Disable</a></li><li><a href="'
      . CIVICRM_UF_BASEURL . '/index.php?q=civicrm/group&amp;reset=1&amp;action=delete&amp;id=2" class="action-item crm-hover-button small-popup" title=\'Delete Group\' >Delete</a></li></ul></span>', $groups['data'][1]['links']);
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
    $permissionClause = CRM_Contact_BAO_Group::getPermissionClause(TRUE);
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

}
