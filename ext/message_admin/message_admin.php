<?php

require_once 'message_admin.civix.php';
// phpcs:disable
use CRM_MessageAdmin_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function message_admin_civicrm_config(&$config) {
  _message_admin_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function message_admin_civicrm_install() {
  _message_admin_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function message_admin_civicrm_enable() {
  _message_admin_civix_civicrm_enable();
}
