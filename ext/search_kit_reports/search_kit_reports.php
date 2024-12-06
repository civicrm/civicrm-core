<?php

require_once 'search_kit_reports.civix.php';

use CRM_SearchKitReports_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function search_kit_reports_civicrm_config(&$config): void {
  _search_kit_reports_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function search_kit_reports_civicrm_install(): void {
  _search_kit_reports_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function search_kit_reports_civicrm_enable(): void {
  _search_kit_reports_civix_civicrm_enable();
}
