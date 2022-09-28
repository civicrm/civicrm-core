<?php

require_once 'civicrm_admin_ui.civix.php';
// phpcs:disable
use CRM_CivicrmAdminUi_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civicrm_admin_ui_civicrm_config(&$config) {
  _civicrm_admin_ui_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function civicrm_admin_ui_civicrm_entityTypes(&$entityTypes) {
  _civicrm_admin_ui_civix_civicrm_entityTypes($entityTypes);
}
