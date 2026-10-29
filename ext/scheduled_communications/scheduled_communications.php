<?php

require_once 'scheduled_communications.civix.php';
use CRM_ScheduledCommunications_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function scheduled_communications_civicrm_config(&$config): void {
  _scheduled_communications_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function scheduled_communications_civicrm_install(): void {
  _scheduled_communications_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function scheduled_communications_civicrm_enable(): void {
  _scheduled_communications_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * Job.send_reminder is provided by this extension, so its scheduled job would fail on every cron run.
 */
function scheduled_communications_civicrm_disable(): void {
  \Civi\Api4\Job::update(FALSE)
    ->addWhere('api_entity', '=', 'Job')
    ->addWhere('api_action', '=', 'send_reminder')
    ->addValue('is_active', FALSE)
    ->execute();
}

/**
 * Implements hook_civicrm_tabset().
 *
 * Adds "Schedule Reminders" to the Manage Event tabs (with an event_id) and to the event "Configure" links (without).
 */
function scheduled_communications_civicrm_tabset($tabsetName, &$tabs, $context): void {
  if ($tabsetName !== 'civicrm/event/manage') {
    return;
  }
  $permissions = CRM_Event_BAO_Event::getAllPermissions();
  if (!CRM_Core_Permission::check('administer CiviCRM data') && empty($permissions[CRM_Core_Permission::EDIT])) {
    return;
  }
  if (!empty($context['event_id'])) {
    $tab = [
      'title' => E::ts('Schedule Reminders'),
      'link' => NULL,
      'valid' => (bool) CRM_Core_BAO_ActionSchedule::isConfigured($context['event_id'], CRM_Event_ActionMapping::EVENT_NAME_MAPPING_ID),
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
  $position = array_search('registration', array_keys($tabs), TRUE);
  $position = $position === FALSE ? count($tabs) : $position + 1;
  $tabs = array_slice($tabs, 0, $position, TRUE) + ['reminder' => $tab] + array_slice($tabs, $position, NULL, TRUE);
}

/**
 * Implements hook_civicrm_alterAngular().
 */
function scheduled_communications_civicrm_alterAngular(\Civi\Angular\Manager $angular): void {
  $changeSet = \Civi\Angular\ChangeSet::create('scheduled_reminders_membership_types')
    ->alterHtml('~/afsearchAdministerMembershipTypes/afsearchAdministerMembershipTypes.aff.html', function(\phpQueryObject $doc) {
      $doc->find('.help')->append('<p>' . E::ts('Configure membership renewal reminders using <a %1>Schedule Reminders</a>.', [
        1 => 'ng-href="{{:: crmUrl(\'civicrm/admin/scheduleReminders?reset=1\') }}"',
      ]) . '</p>');
    });
  $angular->add($changeSet);
}

/**
 * @implements CRM_Utils_Hook::permission().
 */
function scheduled_communications_civicrm_permission(&$permissions) {
  $permissions['schedule communications'] = [
    'label' => E::ts('SearchKit: schedule communications'),
    'description' => E::ts('Create, update & delete search-based communications'),
  ];
}

/**
 * Implements hook_civicrm_pre().
 */
function scheduled_communications_civicrm_pre($op, $entity, $id, $params): void {
  // Do not allow users without 'schedule communications' permission to edit a saved search used for communications.
  if ($entity === 'SavedSearch' && $id && !empty($params['check_permissions']) && !CRM_Core_Permission::check('schedule communications')) {
    $relatedCommunications = civicrm_api4('ActionSchedule', 'get', [
      'checkPermissions' => FALSE,
      'select' => ['ROW_COUNT'],
      'where' => [
        ['mapping_id', '=', 'saved_search'],
        ['entity_value', '=', $id],
      ],
    ])->count();
    if ($relatedCommunications) {
      throw new CRM_Core_Exception(E::ts('Permission denied to modify saved search that controls a scheduled communication.'));
    }
  }
  // Do not allow search-based scheduled communications to be created/edited/deleted without permission
  if ($entity === 'ActionSchedule' && !empty($params['check_permissions']) && !CRM_Core_Permission::check('schedule communications')) {
    $mappingType = $params['mapping_id'] ?? CRM_Core_DAO_ActionSchedule::getDbVal('mapping_id', $id);
    if ($mappingType === 'saved_search') {
      throw new CRM_Core_Exception(E::ts('Permission denied: search-based communications require the "schedule communications" permission.'));
    }
  }
}

/**
 * Implements hook_civicrm_post().
 */
function scheduled_communications_civicrm_post($op, $entity, $id, $object): void {
  // Delete scheduled communications linked to a deleted saved search
  if ($entity === 'SavedSearch' && $op === 'delete' && $id) {
    civicrm_api4('ActionSchedule', 'delete', [
      'checkPermissions' => FALSE,
      'where' => [
        ['mapping_id', '=', 'saved_search'],
        ['entity_value', '=', $id],
      ],
    ]);
  }
}
