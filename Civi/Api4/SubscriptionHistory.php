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
 * SubscriptionHistory entity.
 *
 * @searchable secondary
 * @since 5.47
 * @package Civi\Api4
 */
class SubscriptionHistory extends Generic\DAOEntity {

  /**
   * @see \Civi\Api4\Generic\AbstractEntity::permissions()
   * @return array
   */
  public static function permissions() {
    // get permission is managed by ACLs
    return [
      'get' => [],
    ];
  }

}
