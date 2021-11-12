<?php

require_once 'civigrant.civix.php';
use CRM_Grant_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civigrant_civicrm_config(&$config) {
  _civigrant_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function civigrant_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _civigrant_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function civigrant_civicrm_entityTypes(&$entityTypes) {
  _civigrant_civix_civicrm_entityTypes($entityTypes);
}
