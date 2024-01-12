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
      ->addSelect('*', 'group:label')
      ->addWhere('is_synthetic', '=', FALSE)
      ->addOrderBy('group', 'ASC')
      ->execute();
    $roles = \Civi\Api4\Role::get(FALSE)
      ->addWhere('name', '!=', 'admin')
      ->execute();
    $result = [];
    foreach ($permissions as $permission) {
      foreach ($roles as $role) {
        $row = [
          'id' => $role['id'] . '_' . $permission['name'],
          'role_id' => $role['id'],
          'role_name' => $role['name'],
          'role_label' => $role['label'],
          'permission_group' => $permission['group:label'],
          'permission_name' => $permission['name'],
          'permission_title' => $permission['title'],
          'permission_description' => $permission['description'],
          'permission_granted' => in_array($permission['name'], $role['permissions']),
        ];
        $result[] = $row;
      }
    }
    return $result;
  }

}
