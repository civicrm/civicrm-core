<?php

require_once 'afform_gui.civix.php';
use CRM_AfformGui_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function afform_gui_civicrm_config(&$config) {
  _afform_gui_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function afform_gui_civicrm_xmlMenu(&$files) {
  _afform_gui_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function afform_gui_civicrm_install() {
  _afform_gui_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function afform_gui_civicrm_postInstall() {
  _afform_gui_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function afform_gui_civicrm_uninstall() {
  _afform_gui_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function afform_gui_civicrm_enable() {
  _afform_gui_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function afform_gui_civicrm_disable() {
  _afform_gui_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function afform_gui_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _afform_gui_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function afform_gui_civicrm_managed(&$entities) {
  _afform_gui_civix_civicrm_managed($entities);
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
function afform_gui_civicrm_caseTypes(&$caseTypes) {
  _afform_gui_civix_civicrm_caseTypes($caseTypes);
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
function afform_gui_civicrm_angularModules(&$angularModules) {
  _afform_gui_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function afform_gui_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _afform_gui_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function afform_gui_civicrm_entityTypes(&$entityTypes) {
  _afform_gui_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function afform_gui_civicrm_themes(&$themes) {
  _afform_gui_civix_civicrm_themes($themes);
}

/**
 * Implements hook_civicrm_pageRun().
 */
function afform_gui_civicrm_pageRun(&$page) {
  if (get_class($page) == 'CRM_Afform_Page_AfformBase' && $page->get('afModule') == 'afGuiAdmin') {
    Civi::resources()->addScriptUrl(Civi::service('asset_builder')->getUrl('af-gui-vars.js'));
  }
}

/**
 * Implements hook_civicrm_buildAsset().
 *
 * Loads metadata to send to the gui editor.
 */
function afform_gui_civicrm_buildAsset($asset, $params, &$mimeType, &$content) {
  if ($asset !== 'af-gui-vars.js') {
    return;
  }

  // Things that can't be handled by afform. TODO: Need a better way to do this. Maybe add core metadata about what each entity is for, and filter on that.
  $entityBlacklist = [
    'ACL',
    'ActionSchedule',
    'ActivityContact',
    'Afform%',
    'CaseContact',
    'EntityTag',
    'GroupContact',
    'GroupNesting',
    'GroupOrganization',
    'Setting',
    'System',
    'UF%',
  ];
  $entityApi = Civi\Api4\Entity::get()
    ->setCheckPermissions(FALSE)
    ->setSelect(['name', 'description']);
  foreach ($entityBlacklist as $nono) {
    $entityApi->addWhere('name', 'NOT LIKE', $nono);
  }

  $contacts = Civi\Api4\Contact::getFields()
    ->setCheckPermissions(FALSE)
    ->setIncludeCustom(TRUE)
    ->setLoadOptions(TRUE)
    ->setAction('Create')
    ->execute();

  $mimeType = 'text/javascript';
  $content = "CRM.afformAdminData={";
  $content .= 'entities:' . json_encode((array) $entityApi->execute(), JSON_UNESCAPED_SLASHES) . ',';
  $content .= 'fields:' . json_encode(['Contact' => (array) $contacts], JSON_UNESCAPED_SLASHES);
  $content .= '}';
}
