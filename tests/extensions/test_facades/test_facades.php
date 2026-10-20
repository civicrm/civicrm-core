<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'test_facades.civix.php';
// phpcs:enable

use CRM_TestFacades_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function test_facades_civicrm_config(\CRM_Core_Config $config): void {
  _test_facades_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function test_facades_civicrm_install(): void {
  _test_facades_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function test_facades_civicrm_enable(): void {
  _test_facades_civix_civicrm_enable();
}
