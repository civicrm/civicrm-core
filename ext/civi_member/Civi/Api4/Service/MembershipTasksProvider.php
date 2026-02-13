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

namespace Civi\Api4\Service;

use CRM_Member_ExtensionUtil as E;
use Civi\Core\Event\GenericHookEvent;

/**
 * @service
 * @internal
 */
class MembershipTasksProvider extends \Civi\Core\Service\AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_searchKitTasks' => 'addMembershipTasks',
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public function addMembershipTasks(GenericHookEvent $event): void {
    foreach (\CRM_Member_Task::tasks() as $id => $task) {
      if (!empty($task['url'])) {
        $path = explode('?', $task['url'], 2)[0];
        $menu = \CRM_Core_Menu::get($path);
        $key = $menu ? \CRM_Core_Key::get($menu['page_callback'], TRUE) : '';

        $event->tasks['Membership']['membership.' . $id] = [
          'title' => $task['title'],
          'icon' => $task['icon'] ?? 'fa-gear',
          'crmPopup' => [
            'path' => "'{$task['url']}'",
            'query' => "{reset: 1}",
            'data' => "{ids: ids.join(','), qfKey: '$key'}",
            'mode' => $task['mode'] ?? 'back',
          ],
        ];
      }
    }
  }

}
