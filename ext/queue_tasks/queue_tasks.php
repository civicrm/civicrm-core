<?php

require_once 'queue_tasks.civix.php';

use CRM_QueueTasks_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function queue_tasks_civicrm_config(&$config): void {
  _queue_tasks_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function queue_tasks_civicrm_install(): void {
  _queue_tasks_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function queue_tasks_civicrm_enable(): void {
  _queue_tasks_civix_civicrm_enable();
}
