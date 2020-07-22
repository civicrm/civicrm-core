<?php

require_once 'greenwich.civix.php';
// phpcs:disable
use CRM_Greenwich_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function greenwich_civicrm_config(&$config) {
  _greenwich_civix_civicrm_config($config);
}

///**
// * Implements hook_civicrm_xmlMenu().
// *
// * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
// */
//function greenwich_civicrm_xmlMenu(&$files) {
//  _greenwich_civix_civicrm_xmlMenu($files);
//}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function greenwich_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _greenwich_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_thems().
 */
function greenwich_civicrm_themes(&$themes) {
  _greenwich_civix_civicrm_themes($themes);
}
