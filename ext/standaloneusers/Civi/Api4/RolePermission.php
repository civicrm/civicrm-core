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
use Civi\Api4\Generic\Traits\HierarchicalEntity;
use CRM_Standaloneusers_ExtensionUtil as E;

/**
 * RolePermission - Virtual Entity for retrieving the permissions of a user role.
 *
 * @searchable secondary
 * @labelField title
 * @primaryKey name
 * @parentField parent
 * @package standaloneusers
 */
class RolePermission extends Generic\AbstractEntity {
  use HierarchicalEntity;

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

  public static function save($checkPermissions = TRUE) {
    return (new Action\RolePermission\Save(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new BasicGetFieldsAction(static::getEntityName(), __FUNCTION__, function($getFields) {
      $roles = \Civi\Api4\Role::get(FALSE)
        ->addSelect('name', 'label')
        ->addWhere('name', '!=', 'admin')
        ->execute()
        ->column('label', 'name');

      $fields = [
        [
          'name' => 'group',
          'title' => 'Group',
          'data_type' => 'String',
          'input_type' => 'Select',
          'readonly' => TRUE,
          'options' => [
            'civicrm' => 'civicrm',
            'cms' => 'cms',
            'const' => 'const',
            'afform' => 'afform',
            'afformGeneric' => 'afformGeneric',
            'unknown' => 'unknown',
          ],
          'input_attrs' => [
            'label' => E::ts('Group'),
          ],
        ],
        [
          'name' => 'name',
          'title' => 'Name',
          'data_type' => 'String',
          'input_type' => 'Text',
          'readonly' => TRUE,
          'input_attrs' => [
            'label' => E::ts('Machine name'),
          ],
        ],
        [
          'name' => 'title',
          'title' => 'Permission Title',
          'data_type' => 'String',
          'input_type' => 'Text',
          'readonly' => TRUE,
          'input_attrs' => [
            'label' => E::ts('Permission'),
          ],
        ],
        [
          'name' => 'description',
          'title' => 'Description',
          'data_type' => 'String',
          'input_type' => 'Text',
          'readonly' => TRUE,
          'input_attrs' => [
            'label' => E::ts('Description'),
          ],
        ],
        [
          'name' => 'parent',
          'title' => 'Parent',
          'description' => 'Permission that implies this one',
          'data_type' => 'String',
          'fk_entity' => 'RolePermission',
          'fk_column' => 'name',
          'readonly' => TRUE,
        ],
        [
          'name' => '_depth',
          'type' => 'Extra',
          'readonly' => TRUE,
          'title' => E::ts('Depth'),
          'description' => E::ts('Depth in the nested hierarchy'),
          'data_type' => 'Integer',
          'default_value' => 0,
          'label' => E::ts('Depth'),
          'input_type' => 'Number',
        ],
      ];
      foreach ($roles as $roleName => $roleLabel) {
        $fields[] = [
          'name' => 'granted_' . $roleName,
          'title' => $roleLabel,
          'data_type' => 'Boolean',
          'input_type' => 'CheckBox',
          'description' => E::ts('Permission explicitly granted to the "%1" role.', [1 => $roleLabel]),
        ];
        $fields[] = [
          'name' => 'implied_' . $roleName,
          'title' => $roleLabel . ' (' . E::ts('implied') . ')',
          'data_type' => 'Boolean',
          'input_type' => 'CheckBox',
          'readonly' => TRUE,
          'description' => E::ts('Permission implied to the "%1" role.', [1 => $roleLabel]),
        ];
      }
      return $fields;
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

  protected static function getEntityTitle(bool $plural = FALSE): string {
    return $plural ? E::ts('User Permissions') : E::ts('User Permission');
  }

}
