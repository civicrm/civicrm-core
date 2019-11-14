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

  $entityWhitelist = $data = [];

  // First scan the entityDefaults directory for our list of supported entities
  // FIXME: Need a way to load this from other extensions too
  foreach (glob(__DIR__ . '/ang/afGuiEditor/entityDefaults/*.json') as $file) {
    $matches = [];
    preg_match('/([-a-z_A-Z0-9]*).json/', $file, $matches);
    $entityWhitelist[] = $entity = $matches[1];
    // No json_decode, the files are not strict json and will go through angular.$parse clientside
    $data['defaults'][$entity] = trim(CRM_Utils_JS::stripComments(file_get_contents($file)));
  }

  $data['entities'] = (array) Civi\Api4\Entity::get()
    ->setCheckPermissions(FALSE)
    ->setSelect(['name', 'description'])
    ->addWhere('name', 'IN', $entityWhitelist)
    ->execute();

  foreach ($entityWhitelist as $entityName) {
    $api = 'Civi\\Api4\\' . $entityName;
    $data['fields'][$entityName] = (array) $api::getFields()
      ->setCheckPermissions(FALSE)
      ->setIncludeCustom(TRUE)
      ->setLoadOptions(TRUE)
      ->setAction('create')
      ->setSelect(['name', 'title', 'input_type', 'input_attrs', 'required', 'options', 'help_pre', 'help_post', 'serialize', 'data_type'])
      ->addWhere('input_type', 'IS NOT NULL')
      ->execute()
      ->indexBy('name');

    // TODO: Teach the api to return options in this format
    foreach ($data['fields'][$entityName] as $name => $field) {
      if (!empty($field['options'])) {
        $data['fields'][$entityName][$name]['options'] = CRM_Utils_Array::makeNonAssociative($field['options'], 'key', 'label');
      }
      else {
        unset($data['fields'][$entityName][$name]['options']);
      }
    }
  }

  // Now adjust the field metadata
  // FIXME: This should probably be a callback event or something to allow extensions to tweak the metadata for their entities
  $data['fields']['Contact']['contact_type']['required_data'] = TRUE;

  // Scan for input types
  // FIXME: Need a way to load this from other extensions too
  foreach (glob(__DIR__ . '/ang/afGuiEditor/inputType/*.html') as $file) {
    $matches = [];
    preg_match('/([-a-z_A-Z0-9]*).html/', $file, $matches);
    $data['inputType'][$matches[1]] = $matches[1];
  }

  $mimeType = 'text/javascript';
  $content = "CRM.afformAdminData=" . json_encode($data, JSON_UNESCAPED_SLASHES) . ';';
}
