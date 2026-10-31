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

namespace Civi\Core\Attribute;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Declare an event listener on a public method in CiviCRM.
 *
 * Classes using this attribute in core or in extensions (with scan-classes) are
 * automatically discovered and registered with the CiviCRM event dispatcher.
 *
 * - Standalone service classes are defined as container services and subscribed.
 * - DAO/BAO classes have their static methods registered via addListenerMap.
 * - For DAO/BAO entity classes, `{self}` in the event name is automatically
 *   replaced with the entity name (e.g. `hook_civicrm_pre::{self}`).
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class CiviEventListener extends AsEventListener {

  /**
   * @param string $event
   *   The name of the event to listen to (e.g. 'civi.api.prepare', 'hook_civicrm_pre::{self}').
   * @param int $priority
   *   The listener priority (higher values execute earlier).
   */
  public function __construct(
    string $event,
    int $priority = 0
  ) {
    parent::__construct(event: $event, priority: $priority);
  }

}
