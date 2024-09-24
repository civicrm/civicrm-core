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

class Get extends BasicGetAction {

  protected function getRecords() {
    $permissions = \Civi\Api4\Permission::get(FALSE)
      ->addWhere('is_synthetic', '=', FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->execute();
    $roles = \Civi\Api4\Role::get(FALSE)
      ->addSelect('name', 'permissions')
      ->addWhere('name', '!=', 'admin')
      ->execute()->column('permissions', 'name');
    $result = [];
    foreach ($permissions as $permission) {
      $row = $permission;
      foreach ($roles as $role => $rolePermissions) {
        $row["granted_$role"] = in_array($permission['name'], $rolePermissions);
        $row["implied_$role"] = FALSE;
      }
      $result[$permission['name']] = $row;
    }
    // Add implied permissions
    foreach ($permissions as $permission) {
      foreach ($permission['implies'] ?? [] as $impliedName) {
        foreach ($roles as $role => $rolePermissions) {
          if (in_array($permission['name'], $rolePermissions) && isset($result[$impliedName])) {
            $result[$impliedName]["implied_$role"] = TRUE;
          }
        }
      }
    }
    return $result;
  }

}
