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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 *
 */
class CRM_Core_Permission_Base {

  /**
   * permission mapping to stub check() calls
   * @var array
   */
  public $permissions = NULL;

  /**
   * Translate permission.
   *
   * @param string $perm
   *   Permission string e.g "administer CiviCRM", "cms:access user record", "Drupal:administer content",
   *   "Joomla:action:com_asset"
   *
   * @param string $nativePrefix
   * @param array $map
   *   Array($portableName => $nativeName).
   *
   * @return NULL|string
   *   a permission name
   */
  public function translatePermission($perm, $nativePrefix, $map) {
    list ($civiPrefix, $name) = CRM_Utils_String::parsePrefix(':', $perm, NULL);
    switch ($civiPrefix) {
      case $nativePrefix:
        return $name;

      // pass through
      case 'cms':
        return CRM_Utils_Array::value($name, $map, CRM_Core_Permission::ALWAYS_DENY_PERMISSION);

      case NULL:
        return $name;

      default:
        return CRM_Core_Permission::ALWAYS_DENY_PERMISSION;
    }
  }

  /**
   * Get the current permission of this user.
   *
   * @return string
   *   the permission of the user (edit or view or null)
   */
  public function getPermission() {
    return CRM_Core_Permission::EDIT;
  }

  /**
   * Get the permissioned where clause for the user.
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   *
   * @return string
   *   the group where clause for this user
   */
  public function whereClause($type, &$tables, &$whereTables) {
    return '( 1 )';
  }

  /**
   * Get the permissioned where clause for the user when trying to see groups.
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   *
   * @return string
   *   the group where clause for this user
   */
  public function getPermissionedStaticGroupClause($type, &$tables, &$whereTables) {
    $this->group();
    return $this->groupClause($type, $tables, $whereTables);
  }

  /**
   * Get all groups from database, filtered by permissions
   * for this user
   *
   * @param string $groupType
   *   Type of group(Access/Mailing).
   * @param bool $excludeHidden
   *   exclude hidden groups.
   *
   *
   * @return array
   *   array reference of all groups.
   */
  public function group($groupType = NULL, $excludeHidden = TRUE) {
    return CRM_Core_PseudoConstant::allGroup($groupType, $excludeHidden);
  }

  /**
   * Get group clause for this user.
   *
   * @param int $type
   *   The type of permission needed.
   * @param array $tables
   *   (reference ) add the tables that are needed for the select clause.
   * @param array $whereTables
   *   (reference ) add the tables that are needed for the where clause.
   *
   * @return string
   *   the group where clause for this user
   */
  public function groupClause($type, &$tables, &$whereTables) {
    return ' (1) ';
  }

  /**
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   * @param int $userId
   *
   */
  public function check($str, $userId = NULL) {
    //no default behaviour
  }

  /**
   * Given a roles array, check for access requirements
   *
   * @param array $array
   *   The roles to check.
   *
   * @return bool
   *   true if yes, else false
   */
  public function checkGroupRole($array) {
    return FALSE;
  }

  /**
   * Get all the contact emails for users that have a specific permission.
   *
   * @param string $permissionName
   *   Name of the permission we are interested in.
   *
   * @throws CRM_Core_Exception.
   */
  public function permissionEmails($permissionName) {
    throw new CRM_Core_Exception("this function only works in Drupal 6 at the moment");
  }

  /**
   * Get all the contact emails for users that have a specific role.
   *
   * @param string $roleName
   *   Name of the role we are interested in.
   *
   * @throws CRM_Core_Exception.
   */
  public function roleEmails($roleName) {
    throw new CRM_Core_Exception("this function only works in Drupal 6 at the moment");
  }

  /**
   * Determine whether the permission store allows us to store
   * a list of permissions generated dynamically (eg by
   * hook_civicrm_permissions.)
   *
   * @return bool
   */
  public function isModulePermissionSupported() {
    return FALSE;
  }

  /**
   * Ensure that the CMS supports all the permissions defined by CiviCRM
   * and its extensions. If there are stale permissions, they should be
   * deleted. This is useful during module upgrade when the newer module
   * version has removed permission that were defined in the older version.
   *
   * @param array $permissions
   *   Same format as CRM_Core_Permission::getCorePermissions().
   *
   * @throws CRM_Core_Exception
   * @see CRM_Core_Permission::getCorePermissions
   */
  public function upgradePermissions($permissions) {
    throw new CRM_Core_Exception("Unimplemented method: CRM_Core_Permission_*::upgradePermissions");
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * of the given module.
   *
   * Note: At time of writing, this is only used with native extension-modules, so
   * there's one, predictable calling convention (regardless of CMS).
   *
   * @param $module
   *
   * @return array
   *   Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   * @see CRM_Core_Permission::getCorePermissions
   */
  public static function getModulePermissions($module) {
    $return_permissions = [];
    $fn_name = "{$module}_civicrm_permission";
    if (function_exists($fn_name)) {
      $module_permissions = [];
      $fn_name($module_permissions);
      $return_permissions = $module_permissions;
    }
    return $return_permissions;
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * in all enabled CiviCRM module extensions.
   *
   * @param bool $descriptions
   *
   * @return array
   *   Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   */
  public function getAllModulePermissions($descriptions = FALSE) {
    $permissions = [];
    CRM_Utils_Hook::permission($permissions);

    if ($descriptions) {
      foreach ($permissions as $permission => $label) {
        $permissions[$permission] = (is_array($label)) ? $label : [$label];
      }
    }
    else {
      foreach ($permissions as $permission => $label) {
        $permissions[$permission] = (is_array($label)) ? array_shift($label) : $label;
      }
    }
    return $permissions;
  }

}
