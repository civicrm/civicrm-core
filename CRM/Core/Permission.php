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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This is the basic permission class wrapper
 */
class CRM_Core_Permission {

  /**
   * Static strings used to compose permissions
   *
   * @const
   * @var string
   */
  CONST EDIT_GROUPS = 'edit contacts in ', VIEW_GROUPS = 'view contacts in ';

  /**
   * The various type of permissions
   *
   * @var int
   */
  CONST EDIT = 1, VIEW = 2, DELETE = 3, CREATE = 4, SEARCH = 5, ALL = 6, ADMIN = 7;

  /**
   * get the current permission of this user
   *
   * @return string the permission of the user (edit or view or null)
   */
  public static function getPermission() {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->getPermission( );
  }

  /**
   * given a permission string, check for access requirements
   *
   * @param string $str the permission to check
   *
   * @return boolean true if yes, else false
   * @static
   * @access public
   */
  static function check($str) {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->check( $str );
  }

  /**
   * Determine if any one of the permissions strings applies to current user
   *
   * @param array $perms
   * @return bool
   */
  public static function checkAnyPerm($perms) {
    foreach ($perms as $perm) {
      if (CRM_Core_Permission::check($perm)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Given a group/role array, check for access requirements
   *
   * @param array $array the group/role to check
   *
   * @return boolean true if yes, else false
   * @static
   * @access public
   */
  static function checkGroupRole($array) {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->checkGroupRole( $array );
  }

  /**
   * Get the permissioned where clause for the user
   *
   * @param int $type the type of permission needed
   * @param  array $tables (reference ) add the tables that are needed for the select clause
   * @param  array $whereTables (reference ) add the tables that are needed for the where clause
   *
   * @return string the group where clause for this user
   * @access public
   */
  public static function getPermissionedStaticGroupClause($type, &$tables, &$whereTables) {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->getPermissionedStaticGroupClause( $type, $tables, $whereTables );
  }

  /**
   * Get all groups from database, filtered by permissions
   * for this user
   *
   * @param string $groupType     type of group(Access/Mailing)
   * @param boolen $excludeHidden exclude hidden groups.
   *
   * @access public
   * @static
   *
   * @return array - array reference of all groups.
   *
   */
  public static function group($groupType, $excludeHidden = TRUE) {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->group( $groupType, $excludeHidden );
  }

  public static function customGroupAdmin() {
    $admin = FALSE;

    // check if user has all powerful permission
    // or administer civicrm permission (CRM-1905)
    if (self::check('access all custom data')) {
      return TRUE;
    }

    if (self::check('administer Multiple Organizations') &&
      self::isMultisiteEnabled()
    ) {
      return TRUE;
    }

    if (self::check('administer CiviCRM')) {
      return TRUE;
    }

    return FALSE;
  }

  public static function customGroup($type = CRM_Core_Permission::VIEW, $reset = FALSE) {
    $customGroups = CRM_Core_PseudoConstant::customGroup($reset);
    $defaultGroups = array();

    // check if user has all powerful permission
    // or administer civicrm permission (CRM-1905)
    if (self::customGroupAdmin()) {
      $defaultGroups = array_keys($customGroups);
    }

    return CRM_ACL_API::group($type, NULL, 'civicrm_custom_group', $customGroups, $defaultGroups);
  }

  static function customGroupClause($type = CRM_Core_Permission::VIEW, $prefix = NULL, $reset = FALSE) {
    if (self::customGroupAdmin()) {
      return ' ( 1 ) ';
    }

    $groups = self::customGroup($type, $reset);
    if (empty($groups)) {
      return ' ( 0 ) ';
    }
    else {
      return "{$prefix}id IN ( " . implode(',', $groups) . ' ) ';
    }
  }

  public static function ufGroupValid($gid, $type = CRM_Core_Permission::VIEW) {
    if (empty($gid)) {
      return TRUE;
    }

    $groups = self::ufGroup($type);
    return in_array($gid, $groups) ? TRUE : FALSE;
  }

  public static function ufGroup($type = CRM_Core_Permission::VIEW) {
    $ufGroups = CRM_Core_PseudoConstant::ufGroup();

    $allGroups = array_keys($ufGroups);

    // check if user has all powerful permission
    if (self::check('profile listings and forms')) {
      return $allGroups;
    }

    switch ($type) {
      case CRM_Core_Permission::VIEW:
        if (self::check('profile view')) {
          return $allGroups;
        }
        break;

      case CRM_Core_Permission::CREATE:
        if (self::check('profile create')) {
          return $allGroups;
        }
        break;

      case CRM_Core_Permission::EDIT:
        if (self::check('profile edit')) {
          return $allGroups;
        }
        break;

      case CRM_Core_Permission::SEARCH:
        if (self::check('profile listings')) {
          return $allGroups;
        }
        break;
    }

    return CRM_ACL_API::group($type, NULL, 'civicrm_uf_group', $ufGroups);
  }

  static function ufGroupClause($type = CRM_Core_Permission::VIEW, $prefix = NULL, $returnUFGroupIds = FALSE) {
    $groups = self::ufGroup($type);
    if ($returnUFGroupIds) {
      return $groups;
    }
    elseif (empty($groups)) {
      return ' ( 0 ) ';
    }
    else {
      return "{$prefix}id IN ( " . implode(',', $groups) . ' ) ';
    }
  }

  public static function event($type = CRM_Core_Permission::VIEW, $eventID = NULL) {
    $events = CRM_Event_PseudoConstant::event(NULL, TRUE);
    $includeEvents = array();

    // check if user has all powerful permission
    if (self::check('register for events')) {
      $includeEvents = array_keys($events);
    }

    if ($type == CRM_Core_Permission::VIEW &&
      self::check('view event info')
    ) {
      $includeEvents = array_keys($events);
    }

    $permissionedEvents = CRM_ACL_API::group($type, NULL, 'civicrm_event', $events, $includeEvents);
    if (!$eventID) {
      return $permissionedEvents;
    }
    return array_search($eventID, $permissionedEvents) === FALSE ? NULL : $eventID;
  }

  static function eventClause($type = CRM_Core_Permission::VIEW, $prefix = NULL) {
    $events = self::event($type);
    if (empty($events)) {
      return ' ( 0 ) ';
    }
    else {
      return "{$prefix}id IN ( " . implode(',', $events) . ' ) ';
    }
  }

  static function access($module, $checkPermission = TRUE) {
    $config = CRM_Core_Config::singleton();

    if (!in_array($module, $config->enableComponents)) {
      return FALSE;
    }

    if ($checkPermission) {
      if ($module == 'CiviCase') {
        return CRM_Case_BAO_Case::accessCiviCase();
      }
      else {
        return CRM_Core_Permission::check("access $module");
      }
    }

    return TRUE;
  }

  /**
   * check permissions for delete and edit actions
   *
   * @param string  $module component name.
   * @param $action action to be check across component
   *
   **/
  static function checkActionPermission($module, $action) {
    //check delete related permissions.
    if ($action & CRM_Core_Action::DELETE) {
      $permissionName = "delete in $module";
    }
    else {
      $editPermissions = array(
        'CiviEvent' => 'edit event participants',
        'CiviMember' => 'edit memberships',
        'CiviPledge' => 'edit pledges',
        'CiviContribute' => 'edit contributions',
        'CiviGrant' => 'edit grants',
        'CiviMail' => 'access CiviMail',
        'CiviAuction' => 'add auction items',
      );
      $permissionName = CRM_Utils_Array::value($module, $editPermissions);
    }

    if ($module == 'CiviCase' && !$permissionName) {
      return CRM_Case_BAO_Case::accessCiviCase();
    }
    else {
      //check for permission.
      return CRM_Core_Permission::check($permissionName);
    }
  }

  static function checkMenu(&$args, $op = 'and') {
    if (!is_array($args)) {
      return $args;
    }
    foreach ($args as $str) {
      $res = CRM_Core_Permission::check($str);
      if ($op == 'or' && $res) {
        return TRUE;
      }
      elseif ($op == 'and' && !$res) {
        return FALSE;
      }
    }
    return ($op == 'or') ? FALSE : TRUE;
  }

  static function checkMenuItem(&$item) {
    if (!array_key_exists('access_callback', $item)) {
      CRM_Core_Error::backtrace();
      CRM_Core_Error::fatal();
    }

    // if component_id is present, ensure it is enabled
    if (isset($item['component_id']) &&
      $item['component_id']
    ) {
      $config = CRM_Core_Config::singleton();
      if (is_array($config->enableComponentIDs) &&
        in_array($item['component_id'], $config->enableComponentIDs)
      ) {
        // continue with process
      }
      else {
        return FALSE;
      }
    }

    // the following is imitating drupal 6 code in includes/menu.inc
    if (empty($item['access_callback']) ||
      is_numeric($item['access_callback'])
    ) {
      return (boolean ) $item['access_callback'];
    }

    // check whether the following Ajax requests submitted the right key
    // FIXME: this should be integrated into ACLs proper
    if (CRM_Utils_Array::value('page_type', $item) == 3) {
      if (!CRM_Core_Key::validate($_REQUEST['key'], $item['path'])) {
        return FALSE;
      }
    }

    // check if callback is for checkMenu, if so optimize it
    if (is_array($item['access_callback']) &&
      $item['access_callback'][0] == 'CRM_Core_Permission' &&
      $item['access_callback'][1] == 'checkMenu'
    ) {
      $op = CRM_Utils_Array::value(1, $item['access_arguments'], 'and');
      return self::checkMenu($item['access_arguments'][0], $op);
    }
    else {
      return call_user_func_array($item['access_callback'], $item['access_arguments']);
    }
  }

  static function &basicPermissions($all = FALSE) {
    static $permissions = NULL;

    if (!$permissions) {
      $prefix = ts('CiviCRM') . ': ';
      $permissions = self::getCorePermissions();

      if (self::isMultisiteEnabled()) {
        $permissions['administer Multiple Organizations'] = $prefix . ts('administer Multiple Organizations');
      }

      $config = CRM_Core_Config::singleton();

      if (!$all) {
        $components = CRM_Core_Component::getEnabledComponents();
      }
      else {
        $components = CRM_Core_Component::getComponents();
      }

      foreach ($components as $comp) {
        $perm = $comp->getPermissions();
        if ($perm) {
          $info = $comp->getInfo();
          foreach ($perm as $p) {
            $permissions[$p] = $info['translatedName'] . ': ' . $p;
          }
        }
      }

      // Add any permissions defined in hook_civicrm_permission implementations.
      $config = CRM_Core_Config::singleton();
      $module_permissions = $config->userPermissionClass->getAllModulePermissions();
      $permissions = array_merge($permissions, $module_permissions);
    }

    return $permissions;
  }

  static function getCorePermissions() {
    $prefix = ts('CiviCRM') . ': ';
    $permissions = array(
      'add contacts' => $prefix . ts('add contacts'),
      'view all contacts' => $prefix . ts('view all contacts'),
      'edit all contacts' => $prefix . ts('edit all contacts'),
      'delete contacts' => $prefix . ts('delete contacts'),
      'access deleted contacts' => $prefix . ts('access deleted contacts'),
      'import contacts' => $prefix . ts('import contacts'),
      'edit groups' => $prefix . ts('edit groups'),
      'administer CiviCRM' => $prefix . ts('administer CiviCRM'),
      'access uploaded files' => $prefix . ts('access uploaded files'),
      'profile listings and forms' => $prefix . ts('profile listings and forms'),
      'profile listings' => $prefix . ts('profile listings'),
      'profile create' => $prefix . ts('profile create'),
      'profile edit' => $prefix . ts('profile edit'),
      'profile view' => $prefix . ts('profile view'),
      'access all custom data' => $prefix . ts('access all custom data'),
      'view all activities' => $prefix . ts('view all activities'),
      'delete activities' => $prefix . ts('delete activities'),
      'access CiviCRM' => $prefix . ts('access CiviCRM'),
      'access Contact Dashboard' => $prefix . ts('access Contact Dashboard'),
      'translate CiviCRM' => $prefix . ts('translate CiviCRM'),
      'administer reserved groups' => $prefix . ts('administer reserved groups'),
      'administer Tagsets' => $prefix . ts('administer Tagsets'),
      'administer reserved tags' => $prefix . ts('administer reserved tags'),
      'administer dedupe rules' => $prefix . ts('administer dedupe rules'),
      'merge duplicate contacts' => $prefix . ts('merge duplicate contacts'),
      'view debug output' => $prefix . ts('view debug output'),
      'view all notes' => $prefix . ts('view all notes'),
      'access AJAX API' => $prefix . ts('access AJAX API'),
      'access contact reference fields' => $prefix . ts('access contact reference fields'),
      'create manual batch' => $prefix . ts('create manual batch'),
      'edit own manual batches' => $prefix . ts('edit own manual batches'),
      'edit all manual batches' => $prefix . ts('edit all manual batches'),
      'view own manual batches' => $prefix . ts('view own manual batches'),
      'view all manual batches' => $prefix . ts('view all manual batches'),
      'delete own manual batches' => $prefix . ts('delete own manual batches'),
      'delete all manual batches' => $prefix . ts('delete all manual batches'),
      'export own manual batches' => $prefix . ts('export own manual batches'),
      'export all manual batches' => $prefix . ts('export all manual batches'),
    );

    return $permissions;
  }

  /**
   * Validate user permission across
   * edit or view or with supportable acls.
   *
   * return boolean true/false.
   **/
  static function giveMeAllACLs() {
    if (CRM_Core_Permission::check('view all contacts') ||
      CRM_Core_Permission::check('edit all contacts')
    ) {
      return TRUE;
    }

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    //check for acl.
    $aclPermission = self::getPermission();
    if (in_array($aclPermission, array(
      CRM_Core_Permission::EDIT,
          CRM_Core_Permission::VIEW,
        ))) {
      return TRUE;
    }

    // run acl where hook and see if the user is supplying an ACL clause
    // that is not false
    $tables = $whereTables = array();
    $where = NULL;

    CRM_Utils_Hook::aclWhereClause(CRM_Core_Permission::VIEW,
      $tables, $whereTables,
      $contactID, $where
    );
    return empty($whereTables) ? FALSE : TRUE;
  }

  /**
   * Function to get component name from given permission.
   *
   * @param string  $permission
   *
   * return string $componentName the name of component.
   * @static
   */
  static function getComponentName($permission) {
    $componentName = NULL;
    $permission = trim($permission);
    if (empty($permission)) {
      return $componentName;
    }

    static $allCompPermissions = array();
    if (empty($allCompPermissions)) {
      $components = CRM_Core_Component::getComponents();
      foreach ($components as $name => $comp) {
        //get all permissions of each components unconditionally
        $allCompPermissions[$name] = $comp->getPermissions(TRUE);
      }
    }

    if (is_array($allCompPermissions)) {
      foreach ($allCompPermissions as $name => $permissions) {
        if (in_array($permission, $permissions)) {
          $componentName = $name;
          break;
        }
      }
    }

    return $componentName;
  }

  /**
   * Get all the contact emails for users that have a specific permission
   *
   * @param string $permissionName name of the permission we are interested in
   *
   * @return string a comma separated list of email addresses
   */
  public static function permissionEmails($permissionName) {
    $config = CRM_Core_Config::singleton();
    return $config->userPermissionClass->permissionEmails( $permissionName );
  }

  /**
   * Get all the contact emails for users that have a specific role
   *
   * @param string $roleName name of the role we are interested in
   *
   * @return string a comma separated list of email addresses
   */
  public static function roleEmails($roleName) {
    $config = CRM_Core_Config::singleton();
    return $config->userRoleClass->roleEmails( $roleName );
  }

  static function isMultisiteEnabled() {
    return CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME,
      'is_enabled'
    ) ? TRUE : FALSE;
  }
}

