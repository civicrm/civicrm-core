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

use Civi\Api4\Action\Queue\ClaimItems;
use Civi\Api4\Action\Queue\RunItems;

/**
 * Track a list of durable/scannable queues.
 *
 * Registering a queue in this table (and setting `is_auto=1`) can
 * allow it to execute tasks automatically in the background.
 *
 * @searchable none
 * @since 5.47
 * @package Civi\Api4
 */
class Queue extends \Civi\Api4\Generic\DAOEntity {

  use Generic\Traits\ManagedEntity;

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer queues'],
      'get' => ['access CiviCRM'],
      'runItem' => [\CRM_Core_Permission::ALWAYS_DENY_PERMISSION],
    ];
  }

  /**
   * Claim an item from the queue. Returns zero or one items.
   *
   * Note: This is appropriate for persistent, auto-run queues.
   *
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Queue\ClaimItems
   */
  public static function claimItems($checkPermissions = TRUE) {
    return (new ClaimItems(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Run an item from the queue.
   *
   * Note: This is appropriate for persistent, auto-run queues.
   *
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Queue\RunItems
   */
  public static function runItems($checkPermissions = TRUE) {
    return (new RunItems(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
