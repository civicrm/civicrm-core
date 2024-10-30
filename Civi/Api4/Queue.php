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
use Civi\Api4\Action\Queue\Run;

/**
 * Track a list of durable/scannable queues.
 *
 * Registering a queue in this table (and setting `is_auto=1`) can
 * allow it to execute tasks automatically in the background.
 *
 * @searchable secondary
 * @since 5.47
 * @package Civi\Api4
 */
class Queue extends Generic\DAOEntity {

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
   * Claim some items from the queue. Returns zero or more items.
   *
   * Note: This is appropriate for persistent, auto-run queues.
   *
   * The number of items depends on the specific queue. Most notably, batch sizes are
   * influenced by queue-driver support (`BatchQueueInterface`) and queue-configuration
   * (`civicrm_queue.batch_limit`).
   *
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Queue\ClaimItems
   */
  public static function claimItems($checkPermissions = TRUE) {
    return (new ClaimItems(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Run some items from the queue.
   *
   * Note: This is appropriate for persistent, auto-run queues.
   *
   * The number of items depends on the specific queue. Most notably, batch sizes are
   * influenced by queue-driver support (`BatchQueueInterface`) and queue-configuration
   * (`civicrm_queue.batch_limit`).
   *
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Queue\RunItems
   */
  public static function runItems($checkPermissions = TRUE) {
    return (new RunItems(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Run a series of items from a queue.
   *
   * This is a lightweight main-loop for development/testing. It may have some limited utility for
   * sysadmins who want to fine-tune runners on a specific queue. See the class docblock for
   * more information.
   *
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\Queue\Run
   */
  public static function run($checkPermissions = TRUE) {
    return (new Run(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
