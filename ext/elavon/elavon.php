<?php

require_once 'elavon.civix.php';
// phpcs:disable
use CRM_Elavon_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function elavon_civicrm_config(&$config) {
  _elavon_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function elavon_civicrm_install() {
  _elavon_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function elavon_civicrm_enable() {
  _elavon_civix_civicrm_enable();
}
