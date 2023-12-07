<?php

require_once 'ewaysingle.civix.php';
// phpcs:disable
use CRM_Ewaysingle_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function ewaysingle_civicrm_config(&$config) {
  _ewaysingle_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function ewaysingle_civicrm_install() {
  _ewaysingle_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function ewaysingle_civicrm_enable() {
  _ewaysingle_civix_civicrm_enable();
}
