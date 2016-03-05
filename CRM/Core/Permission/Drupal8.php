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
class CRM_Core_Permission_Drupal8 extends CRM_Core_Permission_DrupalBase {
  /**
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   *
   * @param null $contactID
   *
   * @return bool
   */
  public function check($str, $contactID = NULL) {
    $str = $this->translatePermission($str, 'Drupal', array(
      'view user account' => 'access user profiles',
    ));

    if ($str == CRM_Core_Permission::ALWAYS_DENY_PERMISSION) {
      return FALSE;
    }
    if ($str == CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return TRUE;
    }
    return \Drupal::currentUser()->hasPermission($str);
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

    $role_ids = array_map(
      function (\Drupal\user\RoleInterface $role) {
        return $role->id();
      }, user_roles(TRUE, $permissionName)
    );
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(array('roles' => $role_ids));
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

}
