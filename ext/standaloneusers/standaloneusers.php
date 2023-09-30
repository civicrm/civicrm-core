<?php

// Define default URL to haveibeenpwned service. Set this empty in settings to disable.
if (!defined('CIVICRM_HIBP_URL')) {
  define('CIVICRM_HIBP_URL', 'https://api.pwnedpasswords.com/range/');
}

require_once 'standaloneusers.civix.php';
// phpcs:disable
use CRM_Standaloneusers_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function standaloneusers_civicrm_config(&$config) {
  _standaloneusers_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function standaloneusers_civicrm_install() {
  _standaloneusers_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function standaloneusers_civicrm_enable() {
  _standaloneusers_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_permission().
 */
function standalone_civicrm_permission(&$permissions) {
  $permissions['access password resets'] = ts('Allow users to access the reset password system');
}
