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

use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to filter search display tasks
 * @service
 * @internal
 */
class SearchDisplayTasksSubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * Listen for hook_civicrm_searchKitTasks with a low priority so that most other hooks have gone first.
   * Setting the priority to -200 because:
   *  - The default for symfony-style hooks is W_MIDDLE = 0.
   *  - The default for CMS-style hooks is DEFAULT_HOOK_PRIORITY = -100.
   *
   * Generally speaking, the configuration settings enforced by `filterTasksForDisplay` should be respected,
   * but if an extension needs to override them it can do so by listening to this event with an even lower priority.
   *
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_searchKitTasks' => [
        ['filterTasksForDisplay', -200],
      ],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
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
