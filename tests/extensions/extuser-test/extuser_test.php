<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'extuser_test.civix.php';
// phpcs:enable

use CRM_ExtuserTest_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function extuser_test_civicrm_config(\CRM_Core_Config $config): void {
  _extuser_test_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function extuser_test_civicrm_install(): void {
  _extuser_test_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function extuser_test_civicrm_enable(): void {
  _extuser_test_civix_civicrm_enable();
}
