<?php

require_once 'legacybatchentry.civix.php';

use CRM_LegacyBatchEntry_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function legacybatchentry_civicrm_config(&$config): void {
  _legacybatchentry_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function legacybatchentry_civicrm_install(): void {
  _legacybatchentry_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function legacybatchentry_civicrm_enable(): void {
  _legacybatchentry_civix_civicrm_enable();
}
