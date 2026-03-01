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

use CRM_Pledge_ExtensionUtil as E;
use Civi\Core\Event\GenericHookEvent;

/**
 * @service
 * @internal
 */
class PledgeTasksProvider extends \Civi\Core\Service\AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_searchKitTasks' => ['addTasks', 100],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public function addTasks(GenericHookEvent $event): void {
    if (\CRM_Core_Permission::check('edit pledges') || !$event->checkPermissions) {
      $event->tasks['Pledge']['pledge.cancel'] = [
        'title' => E::ts('Cancel Pledges'),
        'icon' => 'fa-ban',
        'apiBatch' => [
          'action' => 'cancel',
          'params' => NULL,
          'confirmMsg' => E::ts('Are you sure you want to cancel %1 %2 along with any scheduled payments?'),
          'successMsg' => E::ts('%1 %2 cancelled.'),
          'errorMsg' => E::ts('An error occurred while attempting to cancel %1 %2.'),
        ],
        'conditions' => [
          ['status_id:name', 'NOT IN', ['Completed', 'Cancelled']],
        ],
      ];
    }
  }

}
