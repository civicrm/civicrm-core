<?php

require_once 'riverlea.civix.php';
use CRM_riverlea_ExtensionUtil as E;

/**
 * Check if current active theme is a Riverlea theme
 * @deprecated
 * @return bool
 */
function _riverlea_is_active() {
  return \Civi::service('riverlea.style_loader')->isActive();
}

function riverlea_civicrm_config(&$config) {
  _riverlea_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function riverlea_civicrm_install() {
  _riverlea_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function riverlea_civicrm_enable() {
  _riverlea_civix_civicrm_enable();
}
