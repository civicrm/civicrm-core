<?php

require_once 'civicrm_admin_ui.civix.php';
// phpcs:disable
use CRM_CivicrmAdminUi_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function civicrm_admin_ui_civicrm_config(&$config) {
  _civicrm_admin_ui_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_postProcess().
 */
function civicrm_admin_ui_civicrm_postProcess($className, $form) {
  // Alter core forms to redirect to the new AdminUI afform pages
  switch ($className) {
    case 'CRM_Custom_Form_Group':
      if ($form->getAction() & CRM_Core_Action::ADD) {
        $redirect = "civicrm/admin/custom/group/fields#/?gid=$form->_id";
      }
      else {
        $redirect = 'civicrm/admin/custom/group';
      }
      break;

    case 'CRM_Custom_Form_Field':
      $buttonName = $form->controller->getButtonName();
      // Redirect to field list unless "Save and New" was clicked
      if ($buttonName != $form->getButtonName('next', 'new')) {
        $redirect = "civicrm/admin/custom/group/fields#/?gid=$form->_gid";
      }
      break;

    case 'CRM_UF_Form_Group':
      if ($form->getAction() & CRM_Core_Action::ADD) {
        $redirect = "civicrm/admin/uf/group/field#/?uf_group_id=$form->_id";
      }
      else {
        $redirect = 'civicrm/admin/uf/group';
      }
      break;

    case 'CRM_UF_Form_Field':
      $buttonName = $form->controller->getButtonName();
      // Redirect to field list unless "Save and New" was clicked
      if ($buttonName != $form->getButtonName('next', 'new')) {
        $redirect = "civicrm/admin/uf/group/field#/?uf_group_id=$form->_gid";
      }
      break;
  }

  if (isset($redirect)) {
    $url = CRM_Utils_System::url($redirect, '', FALSE, NULL, FALSE);
    CRM_Core_Session::singleton()->replaceUserContext($url);
  }
}
