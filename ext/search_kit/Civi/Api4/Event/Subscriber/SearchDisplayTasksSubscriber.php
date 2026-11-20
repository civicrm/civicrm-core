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
use Civi\Core\Event\GenericHookEvent;

/**
 * Event subscriber to filter search display tasks
 */
class SearchDisplayTasksSubscriber {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  #[CiviEventListener(event: 'hook_civicrm_searchKitTasks', priority: -200)]
  public function filterTasksForDisplay(GenericHookEvent $event): void {
    $enabledActions = $event->display['settings']['actions'] ?? NULL;
    if (!is_array($enabledActions)) {
      return;
    }

    // Collect unprefixed task names enabled in actions (strip optional `alias:` prefix)
    $enabledUnprefixed = [];
    foreach ($enabledActions as $actionName) {
      $enabledUnprefixed[] = str_contains($actionName, ':') ? explode(':', $actionName, 2)[1] : $actionName;
    }

    // Collect task names referenced by in-row task links so they are preserved for link execution
    $linkTasks = [];
    foreach ($event->display['settings']['columns'] ?? [] as $column) {
      $links = !empty($column['links']) ? $column['links'] : (!empty($column['link']) ? [$column['link']] : []);
      foreach ($links as $link) {
        if (!empty($link['task']) && empty($link['path'])) {
          $linkTasks[$link['task']] = TRUE;
        }
      }
    }

    foreach ($event->tasks as &$entityTasks) {
      foreach ($entityTasks as $taskName => &$task) {
        $isEnabled = in_array($taskName, $enabledUnprefixed, TRUE);
        if (!$isEnabled && empty($linkTasks[$taskName])) {
          unset($entityTasks[$taskName]);
        }
        else {
          $task['_filteredBySubscriber'] = TRUE;
          if (!$isEnabled) {
            $task['inMenu'] = FALSE;
          }
        }
      }
    }
  }

}
