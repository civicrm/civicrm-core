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

namespace Civi\ScheduledCommunications;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Core\Event\GenericHookEvent;
use CRM_ScheduledCommunications_ExtensionUtil as E;

/**
 * Adds Scheduled Reminders to screens owned by core.
 */
class ScheduledReminders {

  /**
   * Adds "Schedule Reminders" to the Manage Event tabs (with an event_id) and to the event "Configure" links (without).
   */
  #[CiviEventListener(event: 'hook_civicrm_tabset')]
  public function addEventTab(GenericHookEvent $event): void {
    if ($event->tabsetName !== 'civicrm/event/manage') {
      return;
    }
    $permissions = \CRM_Event_BAO_Event::getAllPermissions();
    if (!\CRM_Core_Permission::check('administer CiviCRM data') && empty($permissions[\CRM_Core_Permission::EDIT])) {
      return;
    }
    if (!empty($event->context['event_id'])) {
      $tab = [
        'title' => E::ts('Schedule Reminders'),
        'link' => NULL,
        'valid' => (bool) \CRM_Core_BAO_ActionSchedule::isConfigured($event->context['event_id'], \CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID),
        'active' => TRUE,
        'current' => FALSE,
        'class' => 'livePage',
        'icon' => 'crm-i fa-envelope',
      ];
    }
    else {
      $tab = [
        'title' => E::ts('Schedule Reminders'),
        'url' => 'civicrm/event/manage/reminder',
        'field' => 'reminder',
      ];
    }
    $tabs = $event->tabs;
    $position = array_search('registration', array_keys($tabs), TRUE);
    $position = $position === FALSE ? count($tabs) : $position + 1;
    $event->tabs = array_slice($tabs, 0, $position, TRUE) + ['reminder' => $tab] + array_slice($tabs, $position, NULL, TRUE);
  }

  /**
   * Links the Membership Types listing to renewal reminders.
   */
  #[CiviEventListener(event: 'hook_civicrm_alterAngular')]
  public function alterMembershipTypes(GenericHookEvent $event): void {
    $changeSet = \Civi\Angular\ChangeSet::create('scheduled_reminders_membership_types')
      ->alterHtml('~/afsearchAdministerMembershipTypes/afsearchAdministerMembershipTypes.aff.html', function(\phpQueryObject $doc) {
        $doc->find('.help')->append('<p>' . E::ts('Configure membership renewal reminders using <a %1>Schedule Reminders</a>.', [
          1 => 'ng-href="{{:: crmUrl(\'civicrm/admin/scheduleReminders?reset=1\') }}"',
        ]) . '</p>');
      });
    $event->angular->add($changeSet);
  }

}
