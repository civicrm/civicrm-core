<?php

require_once 'legacycustomsearches.civix.php';
// phpcs:disable
use CRM_Legacycustomsearches_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function legacycustomsearches_civicrm_config(&$config) {
  _legacycustomsearches_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function legacycustomsearches_civicrm_install() {
  _legacycustomsearches_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function legacycustomsearches_civicrm_enable() {
  _legacycustomsearches_civix_civicrm_enable();
}
