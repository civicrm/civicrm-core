<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'civi_event_afform.civix.php';
// phpcs:enable

use CRM_CiviEventAfform_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civi_event_afform_civicrm_config(\CRM_Core_Config $config): void {
  _civi_event_afform_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function civi_event_afform_civicrm_install(): void {
  _civi_event_afform_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function civi_event_afform_civicrm_enable(): void {
  _civi_event_afform_civix_civicrm_enable();
}
