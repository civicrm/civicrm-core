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
