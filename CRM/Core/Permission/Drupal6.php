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
class CRM_Core_Permission_Drupal6 extends CRM_Core_Permission_DrupalBase {

  /**
   * Is this user someone with access for the entire system.
   *
   * @var bool
   */
  protected $_viewAdminUser = FALSE;
  protected $_editAdminUser = FALSE;

  /**
   * Am in in view permission or edit permission?
   * @var bool
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
   * @param int $userId
   *
   * @return bool
   *   true if yes, else false
   */
  public function check($str, $userId = NULL) {
    $str = $this->translatePermission($str, 'Drupal6', [
      'view user account' => 'access user profiles',
      'administer users' => 'administer users',
    ]);
    if ($str == CRM_Core_Permission::ALWAYS_DENY_PERMISSION) {
      return FALSE;
    }
    if ($str == CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return TRUE;
    }
    if (function_exists('user_access')) {
      $account = NULL;
      if ($userId) {
        $account = user_load($userId);
      }
      return user_access($str, $account);
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
      $user = user_load(['uid' => $GLOBALS['user']->uid]);
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
    static $_cache = [];

    if (isset($_cache[$roleName])) {
      return $_cache[$roleName];
    }

    $uids = [];
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
    static $_cache = [];

    if (isset($_cache[$permissionName])) {
      return $_cache[$permissionName];
    }

    $uids = [];
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
    $return_permissions = [];
    $fn_name = "{$module}_civicrm_permission";
    if (function_exists($fn_name)) {
      $fn_name($return_permissions);
    }
    return $return_permissions;
  }

}
