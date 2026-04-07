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

use Civi\Api4\Generic\DAOEntity;

/**
 * Track the list if items with CiviCRM processing queues.
 *
 * @searchable secondary
 * @since 5.69
 * @package Civi\Api4
 */
class QueueItem extends DAOEntity {

  use Generic\Traits\ManagedEntity;

  /**
   * @return array
   */
  public static function permissions(): array {
    return [
      'meta' => ['access CiviCRM'],
      // Note that the queue items can have private information in them
      // e.g other users imports.
      // so only users with administer queues should access.
      'default' => ['administer queues'],
    ];
  }

}
