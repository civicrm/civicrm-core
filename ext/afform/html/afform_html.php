<?php

require_once 'afform_html.civix.php';
use CRM_AfformHtml_ExtensionUtil as E;

if (!defined('AFFORM_HTML_MONACO')) {
  define('AFFORM_HTML_MONACO', 'node_modules/monaco-editor/min/vs');
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function afform_html_civicrm_config(&$config) {
  _afform_html_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function afform_html_civicrm_xmlMenu(&$files) {
  _afform_html_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function afform_html_civicrm_install() {
  _afform_html_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function afform_html_civicrm_postInstall() {
  _afform_html_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function afform_html_civicrm_uninstall() {
  _afform_html_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function afform_html_civicrm_enable() {
  _afform_html_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function afform_html_civicrm_disable() {
  _afform_html_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function afform_html_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _afform_html_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function afform_html_civicrm_managed(&$entities) {
  _afform_html_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function afform_html_civicrm_caseTypes(&$caseTypes) {
  _afform_html_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function afform_html_civicrm_angularModules(&$angularModules) {
  _afform_html_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function afform_html_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _afform_html_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function afform_html_civicrm_entityTypes(&$entityTypes) {
  _afform_html_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function afform_html_civicrm_themes(&$themes) {
  _afform_html_civix_civicrm_themes($themes);
}

/**
 * Implements hook_civicrm_check().
 */
function afform_html_civicrm_check(&$messages) {
  $dir = E::path(AFFORM_HTML_MONACO);
  if (!file_exists($dir)) {
    $messages[] = new CRM_Utils_Check_Message(
      'afform_html_monaco',
      ts('Afform HTML is missing its "node_modules" folder. Please consult the README.md for current installation instructions.'),
      ts('Afform HTML: Packages are missing'),
      \Psr\Log\LogLevel::CRITICAL,
      'fa-chain-broken'
    );
  }
}
