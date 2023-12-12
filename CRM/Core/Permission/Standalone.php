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

use Civi\Api4\Role;

/**
 * Permissions class for Standalone.
 *
 * Note that CRM_Core_Permission_Base is unrelated to CRM_Core_Permission
 * This class, and the _Base class, is to do with CMS permissions, whereas
 * the CRM_Core_Permission class deals with Civi-specific permissioning.
 *
 */
class CRM_Core_Permission_Standalone extends CRM_Core_Permission_Base {

  /**
   * permission mapping to stub check() calls
   * @var array
   */
  public $permissions = NULL;

  /**
   * Given a permission string, check for access requirements.
   *
   * Note this differs from CRM_Core_Permission::check() which handles
   * composite permissions (ORs etc), implied permission hierarchies,
   * and Contacts.
   *
   * @param string $str
   *   The permission to check.
   * @param int $userId
   *
   * @return bool
   *   true if yes, else false
   */
  public function check($str, $userId = NULL) {
    // These core-defined synthetic permissions (which cannot be applied by our Role UI):
    // cms:administer users
    // cms:view user account
    // need mapping to our concrete permissions (which can be applied to Roles) with the same names:
    $str = $this->translatePermission($str, 'Standalone', [
      'view user account' => 'view user account',
      'administer users' => 'administer users',
    ]);
    return \Civi\Standalone\Security::singleton()->checkPermission($str, $userId);
  }

  /**
   * Determine whether the permission store allows us to store
   * a list of permissions generated dynamically (eg by
   * hook_civicrm_permissions.)
   *
   * @return bool
   */
  public function isModulePermissionSupported() {
    return TRUE;
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
    if (empty($permissions)) {
      throw new CRM_Core_Exception("Cannot upgrade permissions: permission list missing");
    }
    if (class_exists(Role::class)) {
      $roles = Role::get(FALSE)->addSelect('permissions')->execute();
      $records = [];
      $definedPermissions = array_keys($permissions);
      foreach ($roles as $role) {
        $newPermissions = array_intersect($role['permissions'], $definedPermissions);
        if (count($newPermissions) < count($role['permissions'])) {
          $records[] = ['id' => $role['id'], 'permissions' => $newPermissions];
        }
      }
      if ($records) {
        Role::save(FALSE)->setRecords($records)->execute();
      }
    }
  }

}
