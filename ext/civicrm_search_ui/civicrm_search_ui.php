<?php

require_once 'civicrm_search_ui.civix.php';
// phpcs:disable
use CRM_CivicrmSearchUi_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civicrm_search_ui_civicrm_config(&$config): void {
  _civicrm_search_ui_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function civicrm_search_ui_civicrm_install(): void {
  _civicrm_search_ui_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function civicrm_search_ui_civicrm_enable(): void {
  _civicrm_search_ui_civix_civicrm_enable();
}
