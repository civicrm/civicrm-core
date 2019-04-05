<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_Permission_Backdrop extends CRM_Core_Permission_DrupalBase {

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
    $uids = array();
    $sql = "
      SELECT {users}.uid, {role_permission}.permission
      FROM {users}
      JOIN {users_roles}
        ON {users}.uid = {users_roles}.uid
      JOIN {role_permission}
        ON {role_permission}.rid = {users_roles}.rid
      WHERE {role_permission}.permission = '{$permissionName}'
        AND {users}.status = 1
    ";

    $result = db_query($sql);
    foreach ($result as $record) {
      $uids[] = $record->uid;
    }

    $_cache[$permissionName] = self::getContactEmails($uids);
    return $_cache[$permissionName];
    */
    return [];
  }

}
