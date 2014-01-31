<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Group_Page_AjaxTest extends CiviUnitTestCase {
  /**
   * Permissioned group is used both as an active group the contact can see and as a group that allows
   * logged in user to see contacts
   * @var integer
   */
  protected $_permissionedGroup;
  /**
   * AS disabled group the contact has permission to
   * @var unknown
   */
  protected $_permissionedDisabledGroup;

  function get_info() {
    return array(
      'name' => 'Contact BAOs',
      'description' => 'Test all Contact_BAO_Contact methods.',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
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
    $this->_permissionedDisabledGroup = $this->groupCreate(array('title' => 'pick-me-disabled', 'is_active' => 0, 'name' => 'pick-me-disabled'));
    $this->_permissionedGroup = $this->groupCreate(array('title' => 'pick-me-active', 'is_active' => 1, 'name' => 'pick-me-active'));
    $this->groupCreate(array('title' => 'not-me-disabled', 'is_active' => 0, 'name' => 'not-me-disabled'));
    $this->groupCreate(array('title' => 'not-me-active', 'is_active' => 1, 'name' => 'not-me-active'));
  }

  function tearDown() {
    CRM_Utils_Hook::singleton()->reset();
    $this->quickCleanup(array('civicrm_group'));
    $config = CRM_Core_Config::singleton();
    unset($config->userPermissionClass->permissions);
    parent::tearDown();
  }

  /**
   * retrieve groups as 'view all contacts'
   */
  function testGroupListViewAllContacts() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('view all contacts');
    global $_REQUEST;
    $_REQUEST = $this->_params;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
    $this->assertEquals('not-me-active', $groups[4]['group_name']);
  }

  /**
   * retrieve groups as 'view all contacts'
   */
  function testGroupListViewAllContactsEnabled() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('view all contacts');
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 1;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
    $this->assertEquals('not-me-active', $groups[4]['group_name']);
  }

  /**
   * retrieve groups as 'view all contacts'
   */
  function testGroupListViewAllContactsDisabled() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('view all contacts');
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 2;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, $total);
    $this->assertEquals('pick-me-disabled', $groups[1]['group_name']);
    $this->assertEquals('not-me-disabled', $groups[3]['group_name']);
  }

  /**
   * retrieve groups as 'view all contacts'
   */
  function testGroupListViewAllContactsAll() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('view all contacts');
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 3;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(4, $total);
    $this->assertEquals('pick-me-disabled', $groups[1]['group_name']);
    $this->assertEquals('not-me-disabled', $groups[3]['group_name']);
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
    $this->assertEquals('not-me-active', $groups[4]['group_name']);
  }


  /**
   * retrieve groups as 'view all contacts'
   */
  function testGroupListAccessCiviCRM() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    global $_REQUEST;
    $_REQUEST = $this->_params;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }
  /**
   * retrieve groups as 'view all contacts'
   */
  function testGroupListAccessCiviCRMEnabled() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 1;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }
  /**
   * retrieve groups as 'view all contacts'
   */
  function testGroupListAccessCiviCRMDisabled() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 2;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }

  /**
   * retrieve groups as 'view all contacts'
   */
  function testGroupListAccessCiviCRMAll() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 2;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(0, count($groups));
    $this->assertEquals(0, $total, 'Total returned should be accurate based on permissions');
  }

  function testTraditionalACL () {
    $this->setupACL();
    global $_REQUEST;
    $_REQUEST = $this->_params;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
  }

  function testTraditionalACLDisabled () {
    $this->setupACL();
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 2;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('pick-me-disabled', $groups[1]['group_name']);
  }

  function testTraditionalACLEnabled () {
    $this->setupACL();
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 1;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
  }

  function testTraditionalACLAll () {
    $this->setupACL();
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 3;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $total, 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
    $this->assertEquals('pick-me-disabled', $groups[1]['group_name']);
  }


  /**
   * ACL Group hook
   */
  function testGroupListAclGroupHookDisabled() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $this->hookClass->setHook('civicrm_aclGroup', array($this, 'hook_civicrm_aclGroup'));
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 2;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('pick-me-disabled', $groups[1]['group_name']);
  }

  /**
   * ACL Group hook
   */
  function testGroupListAclGroupHook() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $this->hookClass->setHook('civicrm_aclGroup', array($this, 'hook_civicrm_aclGroup'));
    global $_REQUEST;
    $_REQUEST = $this->_params;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
  }
  /**
   * ACL Group hook
   */
  function testGroupListAclGroupHookAll() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $this->hookClass->setHook('civicrm_aclGroup', array($this, 'hook_civicrm_aclGroup'));
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 3;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(2, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(2, $total, 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
    $this->assertEquals('pick-me-disabled', $groups[1]['group_name']);
  }

  /**
   * ACL Group hook
   */
  function testGroupListAclGroupHookEnabled() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM');
    $this->hookClass->setHook('civicrm_aclGroup', array($this, 'hook_civicrm_aclGroup'));
    global $_REQUEST;
    $_REQUEST = $this->_params;
    $_REQUEST['status'] = 1;
    list($groups, $total) = CRM_Group_Page_AJAX::getGroupList();
    $this->assertEquals(1, count($groups), 'Returned groups should exclude disabled by default');
    $this->assertEquals(1, $total, 'Total needs to be set correctly');
    $this->assertEquals('pick-me-active', $groups[2]['group_name']);
  }

  /**
  * Implements ACLGroup hook
  * aclGroup function returns a list of permitted groups
  * @param string $type
  * @param integer $contactID
  * @param string $tableName
  * @param array $allGroups
  * @param array $currentGroups
  */
  function hook_civicrm_aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups) {
    //dont use api - you will get a loop
    $sql = " SELECT * FROM civicrm_group WHERE name LIKE '%pick%'";
    $groups = array();
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $groups[] = $dao->id;
    }

    if(!empty($allGroups)) {
      //all groups is empty if we really mean all groups but if a filter like 'is_disabled' is already applied
      // it is populated, ajax calls from Manage Groups will leave empty but calls from New Mailing pass in a filtered list
      $currentGroups = array_intersect($groups, array_flip($allGroups));
    }
    else {
      $currentGroups = $groups;
    }
  }
}
