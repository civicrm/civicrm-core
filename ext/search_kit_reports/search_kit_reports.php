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

/**
 * Implements hook_civicrm_permissions().
 *
 * Add a new permission 'access Reports' which is implied by access CiviReport, but does
 * not require you to have the component enabled
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_permission
 */
function search_kit_reports_civicrm_permission(&$permissions): void {
  $permissions['access Reports'] = [
    'label' => ts('access Reports'),
    'description' => ts('Access new-style SearchKit Reports'),
    'implied_by' => ['access CiviReport'],
  ];
}
