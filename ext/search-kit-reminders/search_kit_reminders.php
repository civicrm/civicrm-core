<?php

require_once 'search_kit_reminders.civix.php';
// phpcs:disable
use CRM_SearchKitReminders_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function search_kit_reminders_civicrm_config(&$config) {
  \Civi::dispatcher()->addListener('civi.actionSchedule.getMappings', [
    '\Civi\ActionSchedule\SearchKitMapping',
    'onRegisterActionMappings'
  ]);
  _search_kit_reminders_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function search_kit_reminders_civicrm_xmlMenu(&$files) {
  _search_kit_reminders_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function search_kit_reminders_civicrm_install() {
  _search_kit_reminders_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function search_kit_reminders_civicrm_postInstall() {
  _search_kit_reminders_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function search_kit_reminders_civicrm_uninstall() {
  _search_kit_reminders_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function search_kit_reminders_civicrm_enable() {
  _search_kit_reminders_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function search_kit_reminders_civicrm_disable() {
  _search_kit_reminders_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function search_kit_reminders_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _search_kit_reminders_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function search_kit_reminders_civicrm_managed(&$entities) {
  _search_kit_reminders_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function search_kit_reminders_civicrm_caseTypes(&$caseTypes) {
  _search_kit_reminders_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function search_kit_reminders_civicrm_angularModules(&$angularModules) {
  _search_kit_reminders_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function search_kit_reminders_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _search_kit_reminders_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function search_kit_reminders_civicrm_entityTypes(&$entityTypes) {
  _search_kit_reminders_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function search_kit_reminders_civicrm_themes(&$themes) {
  _search_kit_reminders_civix_civicrm_themes($themes);
}


/**
 * Intercept form functions
 */
function search_kit_reminders_civicrm_buildForm($formName, &$form) {
  if ($formName === 'CRM_Admin_Form_ScheduleReminders') {
    $b = 1;
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function search_kit_reminders_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function search_kit_reminders_civicrm_navigationMenu(&$menu) {
//  _search_kit_reminders_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _search_kit_reminders_civix_navigationMenu($menu);
//}
