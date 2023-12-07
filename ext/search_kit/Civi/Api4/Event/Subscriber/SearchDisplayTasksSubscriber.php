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

namespace Civi\Api4\Event\Subscriber;

use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to filter search display tasks
 * @service
 * @internal
 */
class SearchDisplayTasksSubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * Filter tasks with a priority of -50, which allows W_MIDDLE & W_EARLY to go first, but W_LATE to go after.
   *
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_searchKitTasks' => [
        ['filterTasksForDisplay', -50],
      ],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public function filterTasksForDisplay(GenericHookEvent $event): void {
    $enabledActions = $event->display['settings']['actions'] ?? NULL;
    $entityName = $event->search['api_entity'] ?? NULL;
    if ($entityName && is_array($enabledActions)) {
      $event->tasks[$entityName] = array_intersect_key($event->tasks[$entityName], array_flip($enabledActions));
    }
  }

}
