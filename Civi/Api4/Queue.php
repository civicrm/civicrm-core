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
    ];
  }

}
