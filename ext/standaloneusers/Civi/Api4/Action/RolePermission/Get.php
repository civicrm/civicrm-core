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

namespace Civi\Api4\Action\RolePermission;

use Civi\Api4\Generic\BasicGetAction;
use CRM_Standaloneusers_BAO_Role;

class Get extends BasicGetAction {

  protected function getRecords() {
    $permissions = (array) \Civi\Api4\Permission::get(FALSE)
      ->addWhere('is_synthetic', '=', FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->indexBy('name');
    $roles = \Civi\Api4\Role::get(FALSE)
      ->addSelect('name', 'permissions')
      ->addWhere('name', '!=', CRM_Standaloneusers_BAO_Role::SUPERADMIN_ROLE_NAME)
      ->execute()->column('permissions', 'name');
    $result = [];
    foreach ($permissions as $permissionName => $permission) {
      $row = $permission;
      foreach ($roles as $role => $rolePermissions) {
        $row["granted_$role"] = in_array($permissionName, $rolePermissions);
        $row["implied_$role"] = FALSE;
      }
      $result[$permissionName] = $row;
    }
    // Add implied permissions
    foreach ($permissions as $permissionName => $permission) {
      if ($permission['implies']) {
        self::addImpliedPermissions($result, $permissions, $roles, $permissionName, $permission['implies']);
      }
    }
    return $result;
  }

  private static function addImpliedPermissions(array &$result, array $permissions, array $roles, string $permissionName, array $impliedPermissions) {
    foreach ($impliedPermissions as $impliedName) {
      foreach ($roles as $role => $rolePermissions) {
        if (in_array($permissionName, $rolePermissions) && isset($result[$impliedName])) {
          $result[$impliedName]["implied_$role"] = TRUE;
        }
      }
      if (!empty($permissions[$impliedName]['implies'])) {
        self::addImpliedPermissions($result, $permissions, $roles, $permissionName, $permissions[$impliedName]['implies']);
      }
    }
  }

}
