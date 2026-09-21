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
 * LineItem entity.
 *
 * @searchable secondary
 * @since 5.31
 * @package Civi\Api4
 */
class LineItem extends Generic\DAOEntity {

  /**
   * @return array
   */
  public static function permissions() {
    $permissions = parent::permissions();
    $permissions['save'] = $permissions['update'] = $permissions['delete'] = \CRM_Core_Permission::ALWAYS_DENY_PERMISSION;
    return $permissions;
  }

}
