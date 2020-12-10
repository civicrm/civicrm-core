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
 * GroupContact entity - link between groups and contacts.
 *
 * A contact can either be "Added" "Removed" or "Pending" in a group.
 * CiviCRM only considers them to be "in" a group if their status is "Added".
 *
 * @bridge group_id contact_id
 * @see \Civi\Api4\Group
 * @searchable false
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

}
