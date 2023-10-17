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
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function temporary_civicrm_install() {
  _temporary_civix_civicrm_install();
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
