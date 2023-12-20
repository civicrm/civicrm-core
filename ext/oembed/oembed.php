<?php

require_once 'oembed.civix.php';

use CRM_Oembed_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function oembed_civicrm_config(&$config): void {
  _oembed_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function oembed_civicrm_install(): void {
  _oembed_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function oembed_civicrm_enable(): void {
  _oembed_civix_civicrm_enable();
}
