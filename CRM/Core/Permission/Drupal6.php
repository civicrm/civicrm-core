<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_Permission_Drupal6 extends CRM_Core_Permission_DrupalBase {

  /**
   * Is this user someone with access for the entire system.
   *
   * @var boolean
   */
  protected $_viewAdminUser = FALSE;
  protected $_editAdminUser = FALSE;

  /**
   * Am in in view permission or edit permission?
   * @var boolean
   */
  protected $_viewPermission = FALSE;
  protected $_editPermission = FALSE;

  /**
   * The current set of permissioned groups for the user.
   *
   * @var array
   */
  protected $_viewPermissionedGroups;
  protected $_editPermissionedGroups;

  /**
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   *
   * @param int $contactID
   *
   * @return bool
   *   true if yes, else false
   */
  public function check($str, $contactID = NULL) {
    $str = $this->translatePermission($str, 'Drupal6', array(
      'view user account' => 'access user profiles',
      'administer users' => 'administer users',
    ));
    if ($str == CRM_Core_Permission::ALWAYS_DENY_PERMISSION) {
      return FALSE;
    }
    if ($str == CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return TRUE;
    }
    if (function_exists('user_access')) {
      return user_access($str) ? TRUE : FALSE;
    }
    return TRUE;
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
    if (function_exists('user_load') && isset($array)) {
      $user = user_load(array('uid' => $GLOBALS['user']->uid));
      //if giver roles found in user roles - return true
      foreach ($array as $key => $value) {
        if (in_array($value, $user->roles)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Get all the contact emails for users that have a specific role.
   *
   * @param string $roleName
   *   Name of the role we are interested in.
   *
   * @return string
   *   a comma separated list of email addresses
   */
  public function roleEmails($roleName) {
    static $_cache = array();

    if (isset($_cache[$roleName])) {
      return $_cache[$roleName];
    }

    $uids = array();
    $sql = "
    SELECT     {users}.uid
    FROM       {users}
    LEFT  JOIN {users_roles} ON {users}.uid = {users_roles}.uid
    INNER JOIN {role}        ON ( {role}.rid = {users_roles}.rid OR {role}.rid = 2 )
    WHERE      {role}. name LIKE '%%{$roleName}%%'
    AND        {users}.status = 1
    ";

    $query = db_query($sql);
    while ($result = db_fetch_object($query)) {
      $uids[] = $result->uid;
    }

    $_cache[$roleName] = self::getContactEmails($uids);
    return $_cache[$roleName];
  }

  /**
   * Get all the contact emails for users that have a specific permission.
   *
   * @param string $permissionName
   *   Name of the permission we are interested in.
   *
   * @return string
   *   a comma separated list of email addresses
   */
  public function permissionEmails($permissionName) {
    static $_cache = array();

    if (isset($_cache[$permissionName])) {
      return $_cache[$permissionName];
    }

    $uids = array();
    $sql = "
    SELECT     {users}.uid, {permission}.perm
    FROM       {users}
    LEFT  JOIN {users_roles} ON {users}.uid = {users_roles}.uid
    INNER JOIN {permission}  ON ( {permission}.rid = {users_roles}.rid OR {permission}.rid = 2 )
    WHERE      {permission}.perm LIKE '%%{$permissionName}%%'
    AND        {users}.status = 1
    ";

    $query = db_query($sql);
    while ($result = db_fetch_object($query)) {
      $uids[] = $result->uid;
    }

    $_cache[$permissionName] = self::getContactEmails($uids);
    return $_cache[$permissionName];
  }

  /**
   * @inheritDoc
   */
  public function isModulePermissionSupported() {
    return TRUE;
  }

  /**
   * @inheritDoc
   *
   * Does nothing in Drupal 6.
   */
  public function upgradePermissions($permissions) {
    // D6 allows us to be really lazy... things get cleaned up when the admin form is next submitted...
  }

  /**
   * Get the permissions defined in the hook_civicrm_permission implementation
   * of the given module.
   *
   * @param $module
   *
   * @return array
   *   Array of permissions, in the same format as CRM_Core_Permission::getCorePermissions().
   */
  public static function getModulePermissions($module) {
    $return_permissions = array();
    $fn_name = "{$module}_civicrm_permission";
    if (function_exists($fn_name)) {
      $fn_name($return_permissions);
    }
    return $return_permissions;
  }

}
