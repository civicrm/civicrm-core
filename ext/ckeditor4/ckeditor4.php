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
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function ckeditor4_civicrm_install() {
  _ckeditor4_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function ckeditor4_civicrm_enable() {
  _ckeditor4_civix_civicrm_enable();
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

function ckeditor4_civicrm_postProcess($formName, $form) {
  if ($formName === 'CRM_Admin_Form_Preferences_Display') {
    // If "Configure CKEditor" button was clicked
    if (!empty($form->_params['ckeditor_config'])) {
      // Suppress the "Saved" status message and redirect to the CKEditor Config page
      $session = CRM_Core_Session::singleton();
      $session->getStatus(TRUE);
      $url = CRM_Utils_System::url('civicrm/admin/ckeditor', 'reset=1');
      $session->pushUserContext($url);
    }
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
