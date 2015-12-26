<?php
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Group_Page_AjaxTest
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
      'sEcho' => '1',
      'page' => 1,
      'rp' => 50,
      'offset' => 0,
      'rowCount' => 50,
      'sort' => NULL,
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
    CRM_Core_Config::singleton()->userPermissionClass->permissions = (array) $permission;
    CRM_Contact_BAO_Group::getPermissionClause(TRUE);
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
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContacts() {
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">not-me-active</span>', $groups[4]['group_name']);
  }

  /**
   * Check Group Edit w/o 'edit groups' permission.
   */
  public function testGroupEditWithAndWithoutPermission() {
    $this->setPermissionAndRequest('view all contacts');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
    $this->assertEquals('not-me-active', $groups[4]['group_name']);

    // as per changes made in PR-6822
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">not-me-active</span>', $groups[4]['group_name']);
  }

  /**
   * Retrieve groups as 'view all contacts' permissioned user
   * Without setting params the default is both enabled & disabled
   * (if you do set default it is enabled only)
   */
  public function testGroupListViewAllContactsFoundTitle() {
    $this->_params['title'] = 'p';
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsNotFoundTitle() {
    $this->_params['title'] = 'z';
    $this->setPermissionAndRequest('view all contacts');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, $total);
  }

  /**
   * Retrieve groups as 'edit all contacts'
   */
  public function testGroupListEditAllContacts() {
    $this->setPermissionAndRequest(array('edit all contacts', 'edit groups'));
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">not-me-active</span>', $groups[4]['group_name']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsEnabled() {
    $this->_params['status'] = 1;
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">not-me-active</span>', $groups[4]['group_name']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsDisabled() {
    $this->_params['status'] = 2;
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">not-me-disabled</span>', $groups[3]['group_name']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsDisabledNotFoundTitle() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'n';
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, $total);
    $this->assertEquals('<span class="crm-editable crmf-title">not-me-disabled</span>', $groups[3]['group_name']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsDisabledFoundTitle() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'p';
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, $total);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListViewAllContactsAll() {
    $this->_params['status'] = 3;
    $this->setPermissionAndRequest(array('view all contacts', 'edit groups'));
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(4, $total);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">not-me-disabled</span>', $groups[3]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">not-me-active</span>', $groups[4]['group_name']);
  }


  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRM() {
    $this->setPermissionAndRequest('access CiviCRM');
    $permissionClause = CRM_Contact_BAO_Group::getPermissionClause(TRUE);
    $this->assertEquals('1 = 0', $permissionClause);
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMEnabled() {
    $this->_params['status'] = 1;
    $this->setPermissionAndRequest('access CiviCRM');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMDisabled() {
    $this->_params['status'] = 2;
    $this->setPermissionAndRequest('access CiviCRM');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMAll() {
    $this->_params['status'] = 2;
    $this->setPermissionAndRequest('access CiviCRM');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMFound() {
    $this->_params['title'] = 'p';
    $this->setPermissionAndRequest('access CiviCRM');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }

  /**
   * Retrieve groups as 'view all contacts'
   */
  public function testGroupListAccessCiviCRMNotFound() {
    $this->_params['title'] = 'z';
    $this->setPermissionAndRequest('access CiviCRM');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }

  public function testTraditionalACL() {
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
  }

  public function testTraditionalACLNotFoundTitle() {
    $this->_params['title'] = 'n';
    $this->setupACL();
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(0, $total, 'Total needs to be set correctly');
  }

  public function testTraditionalACLFoundTitle() {
    $this->_params['title'] = 'p';
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  public function testTraditionalACLDisabled() {
    $this->_params['status'] = 2;
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  public function testTraditionalACLDisabledFoundTitle() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'p';
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  public function testTraditionalACLDisabledNotFoundTitle() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'n';
    $this->setupACL();
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(0, $total, 'Total needs to be set correctly');
  }

  public function testTraditionalACLEnabled() {
    $this->_params['status'] = 1;
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
  }

  public function testTraditionalACLAll() {
    $this->_params['status'] = 3;
    $this->setupACL();
    $this->setPermissionAndRequest('edit groups');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookDisabled() {
    $this->_params['status'] = 2;
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookDisabledFound() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'p';
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookDisabledNotFound() {
    $this->_params['status'] = 2;
    $this->_params['title'] = 'n';
    $this->setHookAndRequest('access CiviCRM', 'hook_civicrm_aclGroup');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(0, $total, 'Total needs to be set correctly');
  }


  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHook() {
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookTitleNotFound() {
    $this->_params['title'] = 'n';
    $this->setHookAndRequest('access CiviCRM', 'hook_civicrm_aclGroup');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, $total, 'Total needs to be set correctly');
    $this->assertEquals(0, count($groups), 'Returned groups should exclude disabled by default');
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookTitleFound() {
    $this->_params['title'] = 'p';
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookAll() {
    $this->_params['status'] = 3;
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-disabled</span>', $groups[1]['group_name']);
  }

  /**
   * ACL Group hook.
   */
  public function testGroupListAclGroupHookEnabled() {
    $this->_params['status'] = 1;
    $this->setHookAndRequest(array('access CiviCRM', 'edit groups'), 'hook_civicrm_aclGroup');
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('<span class="crm-editable crmf-title">pick-me-active</span>', $groups[2]['group_name']);
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
