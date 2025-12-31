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

use CRM_Grant_ExtensionUtil as E;
use Civi\Core\Event\GenericHookEvent;

/**
 * @service
 * @internal
 */
class GrantTasksProvider extends \Civi\Core\Service\AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_searchKitTasks' => ['addTasks', 100],
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public function addTasks(GenericHookEvent $event): void {
    if (\CRM_Core_Permission::check('access CiviGrant') || !$event->checkPermissions) {
      $event->tasks['Contact']['contact.addGrant'] = [
        'title' => E::ts('Grant - Add New Grant'),
        'uiDialog' => ['templateUrl' => '~/civiGrantTasks/civiGrantTaskAddGrant.html'],
        'icon' => 'fa-money',
        'module' => 'civiGrantTasks',
        // Default values can be set via `hook_civicrm_searchKitTasks`
        'values' => [],
      ];
    }
  }

}
