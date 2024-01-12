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


namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetFieldsAction;
use CRM_Standaloneusers_ExtensionUtil as E;

/**
 * RolePermission - Virtual Entity for retrieving the permissions of a user role.
 *
 * @searchable secondary
 * @package standaloneusers
 */
class RolePermission extends Generic\BasicEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\RolePermission\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\RolePermission\Get(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function update($checkPermissions = TRUE) {
    return (new Action\RolePermission\Update(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new BasicGetFieldsAction(static::getEntityName(), __FUNCTION__, function() {
      $permissions = \Civi\Api4\Permission::get(FALSE)
        ->addSelect('*', 'group:label')
        ->addWhere('is_synthetic', '=', FALSE)
        ->addOrderBy('group', 'ASC')
        ->execute();
      $groups = [];
      foreach ($permissions as $permission) {
        if (!isset($groups[$permission['group:label']])) {
          $groups[$permission['group:label']] = $permission['group:label'];
        }
      }
      $roles = \Civi\Api4\Role::get(FALSE)
        ->addWhere('name', '!=', 'admin')
        ->execute();
      $roleOptions = [];
      foreach ($roles as $role) {
        $roleOptions[$role['name']] = $role['label'];
      }

      return [
        'id' => [
          'name' => 'id',
          'data_type' => 'String',
          'label' => E::ts('ID'),
          'input_type' => 'Text',
        ],
        'role_id' => [
          'name' => 'role_id',
          'data_type' => 'Integer',
          'label' => E::ts('Role ID'),
        ],
        'role_name' => [
          'name' => 'role_name',
          'data_type' => 'String',
          'label' => E::ts('Role Name'),
          'input_type' => 'Select',
          'options' => $roleOptions,
        ],
        'role_label' => [
          'name' => 'role_label',
          'data_type' => 'String',
          'label' => E::ts('Role Label'),
          'input_type' => 'Text',
        ],
        'permission_group' => [
          'name' => 'permission_group',
          'data_type' => 'String',
          'label' => E::ts('Permission Group'),
          'input_type' => 'Select',
          'options' => $groups,
        ],
        'permission_name' => [
          'name' => 'permission_name',
          'data_type' => 'String',
          'label' => E::ts('Permission Name'),
          'input_type' => 'Text',
        ],
        'permission_title' => [
          'name' => 'permission_title',
          'data_type' => 'String',
          'label' => E::ts('Permission Title'),
          'input_type' => 'Text',
        ],
        'permission_description' => [
          'name' => 'permission_description',
          'data_type' => 'String',
          'label' => E::ts('Permission Description'),
          'input_type' => 'Text',
        ],
        'permission_granted' => [
          'name' => 'permission_granted',
          'data_type' => 'Boolean',
          'label' => E::ts('Permission Granted'),
          'input_type' => 'CheckBox',
          'entity' => 'RolePermission',
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'default' => ['cms:administer users'],
    ];
  }

}
