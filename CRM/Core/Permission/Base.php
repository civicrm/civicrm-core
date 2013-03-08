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
 *
 */
class CRM_Core_Permission_Base {

  /**
   * get the current permission of this user
   *
   * @return string the permission of the user (edit or view or null)
   */
  public function getPermission() {
    return CRM_Core_Permission::EDIT;
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
  public function whereClause($type, &$tables, &$whereTables) {
    return '( 1 )';
  }
  /**
   * Get the permissioned where clause for the user when trying to see groups
   *
   * @param int $type the type of permission needed
   * @param  array $tables (reference ) add the tables that are needed for the select clause
   * @param  array $whereTables (reference ) add the tables that are needed for the where clause
   *
   * @return string the group where clause for this user
   * @access public
   */
  public function getPermissionedStaticGroupClause($type, &$tables, &$whereTables) {
    $this->group();
    return $this->groupClause($type, $tables, $whereTables);
  }
  /**
   * Get all groups from database, filtered by permissions
   * for this user
   *
   * @param string $groupType     type of group(Access/Mailing)
   * @param boolen $excludeHidden exclude hidden groups.
   *
   * @access public
   *
   * @return array - array reference of all groups.
   *
   */
  public function group($groupType = NULL, $excludeHidden = TRUE) {
    return CRM_Core_PseudoConstant::allGroup($groupType, $excludeHidden);
  }

  /**
   * Get group clause for this user
   *
   * @param int $type the type of permission needed
   * @param  array $tables (reference ) add the tables that are needed for the select clause
   * @param  array $whereTables (reference ) add the tables that are needed for the where clause
   *
   * @return string the group where clause for this user
   * @access public
   */
  public function groupClause($type, &$tables, &$whereTables) {
    return ' (1) ';
  }

  /**
   * given a permission string, check for access requirements
   *
   * @param string $str the permission to check
   *
   * @return boolean true if yes, else false
   * @access public
   */

  function check($str) {
    //no default behaviour
  }

  /**
   * Given a roles array, check for access requirements
   *
   * @param array $array the roles to check
   *
   * @return boolean true if yes, else false
   * @access public
   */

  function checkGroupRole($array) {
    return FALSE;
  }

  /**
   * Get all the contact emails for users that have a specific permission
   *
   * @param string $permissionName name of the permission we are interested in
   *
   * @return string a comma separated list of email addresses
   */
  public function permissionEmails($permissionName) {
    CRM_Core_Error::fatal("this function only works in Drupal 6 at the moment");
  }

  /**
   * Get all the contact emails for users that have a specific role
   *
   * @param string $roleName name of the role we are interested in
   *
   * @return string a comma separated list of email addresses
   */
  public function roleEmails($roleName) {
    CRM_Core_Error::fatal("this function only works in Drupal 6 at the moment");
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
   * @param array $permissions same format as CRM_Core_Permission::getCorePermissions().
   * @see CRM_Core_Permission::getCorePermissions
   */
  function upgradePermissions($permissions) {
    throw new CRM_Core_Exception("Unimplemented method: CRM_Core_Permission_*::upgradePermissions");
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * of the given module.
   *
   * Note: At time of writing, this is only used with native extension-modules, so
   * there's one, predictable calling convention (regardless of CMS).
   *
   * @return Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   * @see CRM_Core_Permission::getCorePermissions
   */
  static function getModulePermissions($module) {
    $return_permissions = array();
    $fn_name = "{$module}_civicrm_permission";
    if (function_exists($fn_name)) {
      $module_permissions = array();
      $fn_name($module_permissions);
      $return_permissions = $module_permissions;
    }
    return $return_permissions;
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * in all enabled CiviCRM module extensions.
   *
   * @return Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   */
  function getAllModulePermissions() {
    $permissions = array();
    CRM_Utils_Hook::permission($permissions);
    return $permissions;
  }
}

