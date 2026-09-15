<?php

require_once 'legacyprofiles.civix.php';

use CRM_Legacyprofiles_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function legacyprofiles_civicrm_config(&$config): void {
  _legacyprofiles_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function legacyprofiles_civicrm_install(): void {
  _legacyprofiles_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_ufGroupTypes().
 *
 * A standalone profile can also be exposed as a directory listing while this extension is enabled.
 */
function legacyprofiles_civicrm_ufGroupTypes(array &$ufGroupTypes): void {
  if (isset($ufGroupTypes['Profile'])) {
    $ufGroupTypes['Profile'] = ts('Standalone Form or Directory');
  }
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function legacyprofiles_civicrm_enable(): void {
  _legacyprofiles_civix_civicrm_enable();
}
