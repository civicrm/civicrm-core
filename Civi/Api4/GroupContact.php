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
 * GroupContact entity - link between groups and contacts.
 *
 * A contact can either be "Added" "Removed" or "Pending" in a group.
 * CiviCRM only considers them to be "in" a group if their status is "Added".
 *
 * @ui_join_filters status
 *
 * @searchable bridge
 * @see \Civi\Api4\Group
 * @since 5.19
 * @package Civi\Api4
 */
class GroupContact extends Generic\DAOEntity {
  use Generic\Traits\EntityBridge;

  /**
   * @param bool $checkPermissions
   * @return Action\GroupContact\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\GroupContact\Create(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\GroupContact\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\GroupContact\Save(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\GroupContact\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\GroupContact\Update(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['bridge'] = [
      'group_id' => [
        'to' => 'contact_id',
        'description' => ts('Static (non-smart) group contacts'),
      ],
      'contact_id' => [
        'to' => 'group_id',
        'description' => ts('Static (non-smart) group contacts'),
      ],
    ];
    return $info;
  }

  /**
   * Returns a list of permissions needed to access the various actions in this api.
   *
   * @return array
   */
  public static function permissions() {
    // Override CRM_Core_Permission::getEntityActionPermissions() because the v3 API is nonstandard
    return [
      'default' => ['access CiviCRM'],
    ];
  }

}
