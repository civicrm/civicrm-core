<?php

require_once 'payflowpro.civix.php';
// phpcs:disable
use CRM_Payflowpro_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function payflowpro_civicrm_config(&$config) {
  _payflowpro_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function payflowpro_civicrm_install() {
  _payflowpro_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function payflowpro_civicrm_enable() {
  _payflowpro_civix_civicrm_enable();
}
