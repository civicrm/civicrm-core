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

/**
 * (Read-only) Available permissions
 *
 * NOTE: This is a high-level API intended for introspective use by administrative tools.
 * It may be poorly suited to recursive usage (e.g. permissions defined dynamically
 * on top of permissions!) or during install/uninstall processes.
 *
 * @searchable none
 * @since 5.34
 * @package Civi\Api4
 */
class Permission extends Generic\AbstractEntity {

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
          'options' => [
            'civicrm' => 'civicrm',
            'cms' => 'cms',
            'const' => 'const',
            'afform' => 'afform',
            'afformGeneric' => 'afformGeneric',
            'unknown' => 'unknown',
          ],
        ],
        [
          'name' => 'name',
          'title' => 'Name',
          'data_type' => 'String',
        ],
        [
          'name' => 'title',
          'title' => 'Title',
          'data_type' => 'String',
        ],
        [
          'name' => 'description',
          'title' => 'Description',
          'data_type' => 'String',
        ],
        [
          'name' => 'is_synthetic',
          'title' => 'Is Synthetic',
          'data_type' => 'Boolean',
        ],
        [
          'name' => 'is_active',
          'title' => 'Is Active',
          'description' => '',
          'default' => TRUE,
          'data_type' => 'Boolean',
        ],
        [
          'name' => 'implies',
          'title' => 'Implies',
          'description' => 'List of sub-permissions automatically granted by this one',
          'data_type' => 'Array',
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
