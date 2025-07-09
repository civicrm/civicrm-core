<?php

require_once 'batch_entry.civix.php';

use CRM_BatchEntry_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function batch_entry_civicrm_config(&$config): void {
  _batch_entry_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function batch_entry_civicrm_install(): void {
  _batch_entry_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function batch_entry_civicrm_enable(): void {
  _batch_entry_civix_civicrm_enable();
}
