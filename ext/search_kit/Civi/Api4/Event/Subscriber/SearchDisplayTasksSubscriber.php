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

use Civi\Core\Attribute\CiviEventListener;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;

/**
 * Event subscriber to filter search display tasks
 * @service
 * @internal
 */
class SearchDisplayTasksSubscriber extends \Civi\Core\Service\AutoService {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  #[CiviEventListener(event: 'hook_civicrm_searchKitTasks', priority: -200)]
  public function filterTasksForDisplay(GenericHookEvent $event): void {
    $enabledActions = $event->display['settings']['actions'] ?? NULL;
    $entityName = $event->search['api_entity'] ?? NULL;
    // Hack to support relationships
    $entityName = ($entityName === 'RelationshipCache') ? 'Relationship' : $entityName;
    if (is_array($enabledActions)) {
      if ($entityName) {
        $event->tasks[$entityName] = array_intersect_key($event->tasks[$entityName] ?? [], array_flip($enabledActions));
      }
      if (CoreUtil::isContact($entityName)) {
        $event->tasks['Contact'] = array_intersect_key($event->tasks['Contact'] ?? [], array_flip($enabledActions));
      }
    }

  }

}
