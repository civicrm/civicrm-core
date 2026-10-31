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

use Civi\Core\Attribute\CiviEventListener;
use CRM_Contribute_ExtensionUtil as E;
use Civi\Core\Event\GenericHookEvent;

class ContributionTasksProvider {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  #[CiviEventListener(event: 'hook_civicrm_searchKitTasks')]
  public function addContributionTasks(GenericHookEvent $event): void {
    // FIXME: CRM_Contribute_Task::tasks() should respect `$this->checkPermissions`
    foreach (\CRM_Contribute_Task::tasks() as $id => $task) {
      if (!empty($task['url'])) {
        $path = explode('?', $task['url'], 2)[0];
        $menu = \Civi::router()->get($path);
        $key = $menu ? \CRM_Core_Key::get($menu['page_callback'], TRUE) : '';

        $event->tasks['Contribution']['contribution.' . $id] = [
          'title' => $task['title'],
          'icon' => $task['icon'] ?? 'fa-gear',
          'crmPopup' => [
            'path' => "'{$task['url']}'",
            'data' => "{id: ids.join(','), qfKey: '$key'}",
          ],
        ];
      }
    }
  }

}
