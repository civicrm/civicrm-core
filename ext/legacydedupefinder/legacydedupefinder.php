<?php

require_once 'legacydedupefinder.civix.php';

use CRM_Legacydedupefinder_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function legacydedupefinder_civicrm_config(&$config): void {
  _legacydedupefinder_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function legacydedupefinder_civicrm_install(): void {
  _legacydedupefinder_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function legacydedupefinder_civicrm_enable(): void {
  _legacydedupefinder_civix_civicrm_enable();
}
