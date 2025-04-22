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
 * Recent Items API.
 *
 * Lists the most recently viewed entities by the current user.
 *
 * The list is stored in the user's session.
 * The number of items stored is determined by the setting `recentItemsMaxCount`.
 *
 * @searchable secondary
 * @primaryKey entity_id,entity_type
 * @since 5.49
 * @package Civi\Api4
 */
class RecentItem extends Generic\BasicEntity {

  protected static $getter = ['CRM_Utils_Recent', 'get'];
  protected static $setter = ['CRM_Utils_Recent', 'create'];
  protected static $deleter = ['CRM_Utils_Recent', 'del'];

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction('RecentItem', __FUNCTION__, function() {
      return [
        [
          'name' => 'entity_id',
          'data_type' => 'Integer',
          'required' => TRUE,
        ],
        [
          'name' => 'entity_type',
          'title' => 'Entity Type',
          'options' => \CRM_Utils_Recent::getProviders(),
          'required' => TRUE,
        ],
        [
          'name' => 'title',
        ],
        [
          'name' => 'is_deleted',
          'data_type' => 'Boolean',
        ],
        [
          'name' => 'icon',
        ],
        [
          'name' => 'view_url',
          'title' => 'View URL',
        ],
        [
          'name' => 'edit_url',
          'title' => 'Edit URL',
        ],
        [
          'name' => 'delete_url',
          'title' => 'Delete URL',
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'default' => ['access CiviCRM'],
    ];
  }

}
