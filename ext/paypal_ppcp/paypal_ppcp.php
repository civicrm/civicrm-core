<?php

require_once 'paypal_ppcp.civix.php';

use CRM_PaypalPpcp_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function paypal_ppcp_civicrm_config(&$config): void {
  _paypal_ppcp_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function paypal_ppcp_civicrm_install(): void {
  _paypal_ppcp_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function paypal_ppcp_civicrm_enable(): void {
  _paypal_ppcp_civix_civicrm_enable();
}
