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

use Civi\Api4\Role;

trait RolePermissionSaveTrait {

  protected function updateRecords(array $items): array {
    $rolesToUpdate = [];
    foreach ($items as $item) {
      if (!empty($item['name'])) {
        foreach ($item as $key => $grant) {
          if (str_starts_with($key, 'granted_')) {
            [, $roleName] = explode('_', $key, 2);
            $op = $grant ? 'add' : 'remove';
            $rolesToUpdate[$roleName][$op][] = $item['name'];
          }
        }
      }
    }
    if (!$rolesToUpdate) {
      return $items;
    }
    $roles = Role::get(FALSE)
      ->addSelect('name', 'permissions')
      ->addWhere('name', 'IN', array_keys($rolesToUpdate))
      ->execute()
      ->column('permissions', 'name');
    foreach ($rolesToUpdate as $roleName => $rolePermissions) {
      $roles[$roleName] = array_diff($roles[$roleName], $rolePermissions['remove'] ?? []);
      $roles[$roleName] = array_unique(array_merge($roles[$roleName], $rolePermissions['add'] ?? []));

      Role::update($this->getCheckPermissions())
        ->addWhere('name', '=', $roleName)
        ->addValue('permissions', $roles[$roleName])
        ->execute();
    }
    return $items;
  }

}
