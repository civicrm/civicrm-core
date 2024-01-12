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
