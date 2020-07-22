<?php

require_once 'csslib.civix.php';
// phpcs:disable
use CRM_Csslib_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function csslib_civicrm_config(&$config) {
  _csslib_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function csslib_civicrm_xmlMenu(&$files) {
  _csslib_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function csslib_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _csslib_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
