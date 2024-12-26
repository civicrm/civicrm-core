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
class CRM_Core_Permission_Backdrop extends CRM_Core_Permission_DrupalBase {

  /**
   * Is this user someone with access for the entire system.
   *
   * @var bool
   */
  protected $_viewAdminUser = FALSE;
  protected $_editAdminUser = FALSE;

  /**
   * Am in in view permission or edit permission?
   *
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
    $str = $this->translatePermission($str, 'Drupal', [
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
      if ($userId || $userId === 0) {
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
      $user = user_load($GLOBALS['user']->uid);
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
   * @inheritDoc
   */
  public function getAvailablePermissions() {
    // We want to list *only* Backdrop perms, so we'll *skip* Civi perms.
    $allCorePerms = \CRM_Core_Permission::basicPermissions(TRUE);

    $permissions = parent::getAvailablePermissions();
    $modules = system_get_info('module');
    foreach ($modules as $moduleName => $module) {
      $prefix = isset($module['name']) ? ($module['name'] . ': ') : '';
      foreach (module_invoke($moduleName, 'permission') ?? [] as $permName => $perm) {
        if (isset($allCorePerms[$permName])) {
          continue;
        }

        $permissions["Drupal:$permName"] = [
          'title' => $prefix . strip_tags($perm['title']),
          'description' => $perm['description'] ?? NULL,
        ];
      }
    }
    return $permissions;
  }

  /**
   * @inheritDoc
   */
  public function isModulePermissionSupported() {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function upgradePermissions($permissions) {
    if (empty($permissions)) {
      throw new CRM_Core_Exception("Cannot upgrade permissions: permission list missing");
    }
    // FIXME!!!!
    /*
    $query = db_delete('role_permission')
    ->condition('module', 'civicrm')
    ->condition('permission', array_keys($permissions), 'NOT IN');
    $query->execute();
     */
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

    // FIXME!!!!
    /**
     * $uids = array();
     * $sql = "
     * SELECT {users}.uid, {role_permission}.permission
     * FROM {users}
     * JOIN {users_roles}
     * ON {users}.uid = {users_roles}.uid
     * JOIN {role_permission}
     * ON {role_permission}.rid = {users_roles}.rid
     * WHERE {role_permission}.permission = '{$permissionName}'
     * AND {users}.status = 1
     * ";
     *
     * $result = db_query($sql);
     * foreach ($result as $record) {
     * $uids[] = $record->uid;
     * }
     *
     * $_cache[$permissionName] = self::getContactEmails($uids);
     * return $_cache[$permissionName];
    */
    return [];
  }

}
