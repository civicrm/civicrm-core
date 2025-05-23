<?php

// require_once 'event_check.civix.php';
// use CRM_EventCheck_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function event_check_civicrm_config(&$config, ?array $flags = NULL): void {
  if (isset($flags['civicrm'])) {
    \Civi::$statics['event_checker'] = $checker = new \Civi\Test\EventChecker();
    $checker->start(NULL);
    $checker->addListeners();
  }

  // _event_check_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
// function event_check_civicrm_install(): void {
//   _event_check_civix_civicrm_install();
// }

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
// function event_check_civicrm_enable(): void {
//   _event_check_civix_civicrm_enable();
// }
