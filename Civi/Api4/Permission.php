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

namespace Civi\Api4;

/**
 * (Read-only) Available permissions
 *
 * NOTE: This is a high-level API intended for introspective use by administrative tools.
 * It may be poorly suited to recursive usage (e.g. permissions defined dynamically
 * on top of permissions!) or during install/uninstall processes.
 *
 * @searchable false
 * @package Civi\Api4
 */
class Permission extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\Permission\Get(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions);
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
          'required' => TRUE,
          'data_type' => 'String',
        ],
        [
          'name' => 'name',
          'title' => 'Name',
          'required' => TRUE,
          'data_type' => 'String',
        ],
        [
          'name' => 'title',
          'title' => 'Title',
          'required' => TRUE,
          'data_type' => 'String',
        ],
        [
          'name' => 'description',
          'title' => 'Description',
          'required' => FALSE,
          'data_type' => 'String',
        ],
        [
          'name' => 'is_synthetic',
          'title' => 'Is Synthetic',
          'required' => FALSE,
          'data_type' => 'Boolean',
        ],
        [
          'name' => 'is_active',
          'title' => 'Is Active',
          'description' => '',
          'default' => TRUE,
          'required' => FALSE,
          'data_type' => 'Boolean',
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
