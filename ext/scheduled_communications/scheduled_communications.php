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
