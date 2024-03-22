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

use CRM_Mailing_ExtensionUtil as E;
use Civi\Core\Event\GenericHookEvent;

/**
 * @service
 * @internal
 */
class MailTasksProvider extends \Civi\Core\Service\AutoSubscriber {

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_searchKitTasks' => 'addMailTasks',
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public function addMailTasks(GenericHookEvent $event): void {
    if (
        \CRM_Core_Permission::access('CiviMail') || !$event->checkPermissions ||
        (\CRM_Mailing_Info::workflowEnabled() && \CRM_Core_Permission::check('create mailings'))
      ) {
      $event->tasks['Contact']['contact.mailing'] = [
        'title' => E::ts('Email - schedule/send via CiviMail'),
        'uiDialog' => ['templateUrl' => '~/crmSearchTasks/crmSearchTaskMailing.html'],
        'icon' => 'fa-paper-plane',
      ];
    }
  }

}
