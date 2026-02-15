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

/**
 * Implements hook_civicrm_alterSettingsMetaData().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsMetaData
 *
 * @todo if/when Riverlea is required, update the canonical metadata for these settings
 * and remove these overrides
 */
function riverlea_civicrm_alterSettingsMetaData(array &$settings) {
  // use the bespoke theme picker, no need to render these inputs
  $settings['theme_backend']['settings_pages'] = [];
  $settings['theme_frontend']['settings_pages'] = [];
  // move these from Display Settings => Theme Settings
  $settings['menubar_color']['settings_pages'] = ['theme' => ['weight' => 300]];
  $settings['menubar_position']['settings_pages'] = ['theme' => ['weight' => 300]];
}

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *
 * @todo this is transitional for users who are used to theme showing on Display Settings page.
 * remove at some stage?
 */
function riverlea_civicrm_buildForm($formName, &$form) {
  if ($formName === 'CRM_Admin_Form_Preferences_Display') {
    $message = E::ts('For theme settings, see the <a href="%1">new Theme Settings page</a>', [1 => (string) \Civi::url('backend://civicrm/admin/theme')]);

    \CRM_Core_Region::instance('crm-setting-form-display-top')
      ->addMarkup("<div class='status status-info'>{$message}</div>");
  }
}
