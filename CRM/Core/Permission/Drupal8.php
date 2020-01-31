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
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_Permission_Drupal8 extends CRM_Core_Permission_DrupalBase {

  /**
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   *
   * @param int $userId
   *
   * @return bool
   */
  public function check($str, $userId = NULL) {
    $str = $this->translatePermission($str, 'Drupal', [
      'view user account' => 'access user profiles',
    ]);

    if ($str == CRM_Core_Permission::ALWAYS_DENY_PERMISSION) {
      return FALSE;
    }
    if ($str == CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return TRUE;
    }
    $acct = $userId ? \Drupal\user\Entity\User::load($userId) : \Drupal::currentUser();
    return $acct->hasPermission($str);
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

    $role_ids = array_map(
      function (\Drupal\user\RoleInterface $role) {
        return $role->id();
      }, user_roles(TRUE, $permissionName)
    );
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['roles' => $role_ids]);
    $uids = array_keys($users);

    $_cache[$permissionName] = self::getContactEmails($uids);
    return $_cache[$permissionName];
  }

  /**
   * @inheritDoc
   */
  public function upgradePermissions($permissions) {
    $civicrm_perms = array_keys(CRM_Core_Permission::getCorePermissions());
    if (empty($civicrm_perms)) {
      throw new CRM_Core_Exception("Cannot upgrade permissions: permission list missing");
    }

    $roles = user_roles(TRUE);
    foreach ($roles as $role) {
      foreach ($civicrm_perms as $permission) {
        $role->revokePermission($permission);
      }
    }
  }

  /**
   * Given a roles array, check user has at least one of those roles
   *
   * @param array $roles_to_check
   *   The roles to check. An array indexed starting at 0, e.g. [0 => 'administrator']
   *
   * @return bool
   *   true if user has at least one of the roles, else false
   */
  public function checkGroupRole($roles_to_check) {
    if (isset($roles_to_check)) {

      // This returns an array indexed starting at 0 of role machine names, e.g.
      // [
      //   0 => 'authenticated',
      //   1 => 'administrator',
      // ]
      // or
      // [ 0 => 'anonymous' ]
      $user_roles = \Drupal::currentUser()->getRoles();

      $roles_in_both = array_intersect($user_roles, $roles_to_check);
      return !empty($roles_in_both);
    }
    return FALSE;
  }

}
