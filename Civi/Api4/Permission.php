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

use Civi\Api4\Generic\Traits\HierarchicalEntity;

/**
 * (Read-only) Available permissions
 *
 * NOTE: This is a high-level API intended for introspective use by administrative tools.
 * It may be poorly suited to recursive usage (e.g. permissions defined dynamically
 * on top of permissions!) or during install/uninstall processes.
 *
 * @searchable none
 * @primaryKey name
 * @parentField parent
 * @since 5.34
 * @package Civi\Api4
 */
class Permission extends Generic\AbstractEntity {
  use HierarchicalEntity;

  /**
   * @param bool $checkPermissions
   * @return Action\Permission\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Permission\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [
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
            'label' => ts('Group'),
          ],
        ],
        [
          'name' => 'name',
          'title' => 'Name',
          'data_type' => 'String',
          'input_type' => 'Text',
          'readonly' => TRUE,
          'input_attrs' => [
            'label' => ts('Machine name'),
          ],
        ],
        [
          'name' => 'title',
          'title' => 'Title',
          'data_type' => 'String',
          'input_type' => 'Text',
          'readonly' => TRUE,
          'input_attrs' => [
            'label' => ts('Title'),
          ],
        ],
        [
          'name' => 'description',
          'title' => 'Description',
          'data_type' => 'String',
          'input_type' => 'Text',
          'readonly' => TRUE,
          'input_attrs' => [
            'label' => ts('Description'),
          ],
        ],
        [
          'name' => 'is_synthetic',
          'title' => 'Is Synthetic',
          'data_type' => 'Boolean',
          'input_type' => 'CheckBox',
          'readonly' => TRUE,
        ],
        [
          'name' => 'is_active',
          'title' => 'Enabled',
          'description' => '',
          'default_value' => TRUE,
          'data_type' => 'Boolean',
          'input_type' => 'CheckBox',
          'readonly' => TRUE,
          'input_attrs' => [
            'label' => ts('Enabled'),
          ],
        ],
        [
          'name' => 'implies',
          'title' => 'Implies',
          'description' => 'List of sub-permissions automatically granted by this one',
          'data_type' => 'Array',
          'readonly' => TRUE,
        ],
        [
          'name' => 'parent',
          'title' => 'Parent',
          'description' => 'Higher permission that implies this one',
          'data_type' => 'String',
          'fk_entity' => 'Permission',
          'fk_column' => 'name',
          'readonly' => TRUE,
        ],
        [
          'name' => '_depth',
          'type' => 'Extra',
          'readonly' => TRUE,
          'title' => ts('Depth'),
          'description' => ts('Depth in the nested hierarchy'),
          'data_type' => 'Integer',
          'default_value' => 0,
          'label' => ts('Depth'),
          'input_type' => 'Number',
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      "meta" => ["access CiviCRM"],
      "default" => ["access CiviCRM"],
    ];
  }

}
