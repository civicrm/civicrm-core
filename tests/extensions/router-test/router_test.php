<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'router_test.civix.php';
// phpcs:enable

use CRM_RouterTest_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function router_test_civicrm_config(\CRM_Core_Config $config): void {
  _router_test_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function router_test_civicrm_install(): void {
  _router_test_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function router_test_civicrm_enable(): void {
  _router_test_civix_civicrm_enable();
}
