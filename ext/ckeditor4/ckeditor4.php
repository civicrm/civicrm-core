<?php

require_once 'ckeditor4.civix.php';
// phpcs:disable
use CRM_Ckeditor4_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function ckeditor4_civicrm_config(&$config) {
  _ckeditor4_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function ckeditor4_civicrm_xmlMenu(&$files) {
  _ckeditor4_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function ckeditor4_civicrm_install() {
  _ckeditor4_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function ckeditor4_civicrm_postInstall() {
  _ckeditor4_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function ckeditor4_civicrm_uninstall() {
  _ckeditor4_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function ckeditor4_civicrm_enable() {
  _ckeditor4_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function ckeditor4_civicrm_disable() {
  _ckeditor4_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function ckeditor4_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _ckeditor4_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function ckeditor4_civicrm_managed(&$entities) {
  _ckeditor4_civix_civicrm_managed($entities);
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
function ckeditor4_civicrm_caseTypes(&$caseTypes) {
  _ckeditor4_civix_civicrm_caseTypes($caseTypes);
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
function ckeditor4_civicrm_angularModules(&$angularModules) {
  _ckeditor4_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function ckeditor4_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _ckeditor4_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function ckeditor4_civicrm_entityTypes(&$entityTypes) {
  _ckeditor4_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function ckeditor4_civicrm_themes(&$themes) {
  _ckeditor4_civix_civicrm_themes($themes);
}

function ckeditor4_civicrm_buildForm($formName, $form) {
  if ($formName === 'CRM_Admin_Form_Preferences_Display') {
    $form->addElement(
      'xbutton',
      'ckeditor_config',
      CRM_Core_Page::crmIcon('fa-wrench') . ' ' . E::ts('Configure CKEditor 4'),
      [
        'type' => 'submit',
        'class' => 'crm-button',
        'style' => 'display:inline-block;vertical-align:middle;float:none!important;',
        'value' => 1,
      ]
    );
    CRM_Core_Region::instance('form-bottom')->add([
      'template' => 'CRM/Admin/Form/Preferences/Ckeditor.tpl',
    ]);
  }
}

function ckeditor4_civicrm_coreResourceList(&$list, $region) {
  // add wysiwyg editor
  $editor = \Civi::settings()->get('editor_id');
  if ($editor == "CKEditor") {
    CRM_Ckeditor4_Form_CKEditorConfig::setConfigDefault();
    $list[] = [
      'config' => [
        'wysisygScriptLocation' => E::url('js/crm.ckeditor.js'),
        'CKEditorCustomConfig' => CRM_Ckeditor4_Form_CKEditorConfig::getConfigUrl(),
      ],
    ];
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function ckeditor4_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function ckeditor4_civicrm_navigationMenu(&$menu) {
//  _ckeditor4_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _ckeditor4_civix_navigationMenu($menu);
//}
