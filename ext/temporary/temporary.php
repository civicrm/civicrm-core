<?php

require_once 'temporary.civix.php';
// phpcs:disable
use CRM_Temporary_ExtensionUtil as E;
// phpcs:enable

/**
 * Define the list of tables for which we manage temporal fields.
 */
// define('TEMPORARY_TIMESTAMP_TABLES', '/^civicrm_/');
define('TEMPORARY_TIMESTAMP_TABLES', '/^civicrm_(mailing|note|entity|group|action)/');
// define('TEMPORARY_TIMESTAMP_TABLES', '/^civicrm_note/');
// define('TEMPORARY_TIMESTAMP_TABLES', '/^alskdjfasdf/');

/**
 * If the sysadmin has not set a timestamp, then which mode should we assume?
 */
define('TEMPORARY_TIMESTAMP_AUTO', 'ts');

/**
 * What is the current timestamp mode?
 */
function temporary_timestamps(): string {
  $v = Civi::settings()->get('temporary_timestamps');
  return ($v && $v !== 'auto') ? $v : TEMPORARY_TIMESTAMP_AUTO;
}

/**
 * Implements hook_civicrm_triggerInfo().
 *
 * @see CRM_Utils_Hook::triggerInfo()
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_triggerInfo/
 */
function temporary_civicrm_triggerInfo(&$info, $tableName = NULL) {
  CRM_Temporary_Schema::createSqlTriggers($info, $tableName);
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function temporary_civicrm_config(&$config) {
  _temporary_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function temporary_civicrm_xmlMenu(&$files) {
  _temporary_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function temporary_civicrm_install() {
  _temporary_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function temporary_civicrm_postInstall() {
  _temporary_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function temporary_civicrm_uninstall() {
  _temporary_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function temporary_civicrm_enable() {
  _temporary_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function temporary_civicrm_disable() {
  _temporary_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function temporary_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _temporary_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function temporary_civicrm_managed(&$entities) {
  _temporary_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Add CiviCase types provided by this extension.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function temporary_civicrm_caseTypes(&$caseTypes) {
  _temporary_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Add Angular modules provided by this extension.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function temporary_civicrm_angularModules(&$angularModules) {
  // Auto-add module files from ./ang/*.ang.php
  _temporary_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function temporary_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _temporary_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Modify all DAO's - identify TIMESTAMPs and configure alternative schemas.
 *
 * Note: hook_entityTypes is one of those ~2 wonky hooks that runs very-early during boot.
 * This means, eg, autoloading and Symfony priorities are unreliable for this hook.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function temporary_civicrm_entityTypes(&$entityTypes) {
  // _temporary_civix_civicrm_entityTypes($entityTypes);

  $mode = temporary_timestamps();
  $callback = ['CRM_Temporary_DaoFilter_' . ucfirst($mode), 'filter'];
  require_once __DIR__ . '/CRM/Temporary/DaoFilter/' . ucfirst($mode) . '.php';
  foreach ($entityTypes as &$entity) {
    $entity['fields_callback'][] = $callback;
  }
}

/**
 * Implements hook_civicrm_themes().
 */
function temporary_civicrm_themes(&$themes) {
  _temporary_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function temporary_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function temporary_civicrm_navigationMenu(&$menu) {
//  _temporary_civix_insert_navigation_menu($menu, 'Mailings', [
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ]);
//  _temporary_civix_navigationMenu($menu);
//}
