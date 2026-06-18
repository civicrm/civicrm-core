<?php

require_once 'postbox.civix.php';

use CRM_Postbox_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function postbox_civicrm_config(&$config): void {
  _postbox_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function postbox_civicrm_install(): void {
  _postbox_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function postbox_civicrm_enable(): void {
  _postbox_civix_civicrm_enable();
}
