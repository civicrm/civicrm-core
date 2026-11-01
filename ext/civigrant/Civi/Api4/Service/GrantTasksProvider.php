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
use CRM_Grant_ExtensionUtil as E;
use Civi\Core\Event\GenericHookEvent;

class GrantTasksProvider {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  #[CiviEventListener(event: 'hook_civicrm_searchKitTasks', priority: 100)]
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
