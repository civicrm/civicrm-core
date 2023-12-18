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

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Generic\BasicUpdateAction;
use Civi\Api4\Role;
use CRM_Core_Exception;

class Update extends BasicUpdateAction {

  /**
   * This Basic Update class can be used in one of two ways:
   *
   * 1. Use this class directly by passing a callable ($setter) to the constructor.
   * 2. Extend this class and override this function.
   *
   * Either way, this function should return an array representing the one modified object.
   *
   * @param array $item
   * @return array
   * @throws CRM_Core_Exception
   *
   */
  protected function writeRecord($item) {
    if (preg_match("/^(\\d*)_(.*)$/", $item['id'], $matches)) {
      try {
        $roleId = $matches[1];
        $permission = $matches[2];
        $role = Role::get()
          ->addWhere('id', '=', $roleId)
          ->execute()
          ->single();
        if ($item['permission_granted'] && !in_array($permission, $role['permissions'])) {
          $role['permissions'][] = $permission;
        }
        elseif (!$item['permission_granted'] && ($key = array_search($permission, $role['permissions']))) {
          unset($role['permissions'][$key]);
        }
        Role::update()
          ->addWhere('id', '=', $roleId)
          ->addValue('permissions', $role['permissions'])
          ->execute();
      }
      catch (UnauthorizedException | CRM_Core_Exception $e) {
        throw new CRM_Core_Exception('Could not update permission with ID: ' . $item['id'] . ' and grant permission: ' . $item['permission_granted']);
      }
    }
    else {
      throw new CRM_Core_Exception('Invalid ID: ' . $item['id'] . '. ID should consist of a number followed by an underscore and then the name of the permission. E.g. 123_administer CiviCRM');
    }
    return $item;
  }

}
