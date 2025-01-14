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

function civicrm_admin_ui_civicrm_managed(&$entities, $modules) {
  if ($modules && !in_array(E::LONG_NAME, $modules, TRUE)) {
    return;
  }

  $records = \Civi\Api4\Action\CustomGroup\GetSearchKit::getAllManaged();

  foreach ($records as $record) {
    $record['module'] = E::LONG_NAME;
    $entities[] = $record;
  }
}

/**
 * Implements hook_civicrm_tabset().
 *
 * Adds afforms as contact summary tabs.
 */
function civicrm_admin_ui_civicrm_tabset($tabsetName, &$tabs, $context) {
  $entities = [];
  $tabsetTemplate = NULL;

  switch ($tabsetName) {

    case 'civicrm/event/manage':
      $entities = ['Event'];
      \CRM_Core_Smarty::singleton()->assign('afformOptions', [
        'entity_id' => $context['event_id'] ?? NULL,
      ]);
      break;

    // NOTE: contact custom group tabs are added by
    // admin_civicrm_tabset using the contact_summary_tab
    // afform meta key
    // case 'civicrm/contact/view':
    //   $entities = \Civi\Api4\Generic\CoreUtil::contactEntityNames();
    //   break;
  }

  if (!$entities) {
    return;
  }

  $groups = \Civi\Api4\CustomGroup::get(FALSE)
    ->addWhere('extends', 'IN', $entities)
    ->addWhere('is_multiple', '=', TRUE)
    ->addWhere('style', 'IN', ['Tab', 'Tab with table'])
    ->addOrderBy('title')
    ->addSelect('name')
    ->execute();

  $weight = 100;

  foreach ($groups as $group) {
    $tabName = 'custom_' . $group['name'];
    $tabFormName = 'afsearchTabCustom_' . $group['name'];
    $afform = Civi\Api4\Afform::get(FALSE)
      ->addSelect('name', 'title', 'icon', 'module_name', 'directive_name')
      ->addWhere('name', '=', $tabFormName)
      ->execute()
      ->first();

    if (!$afform) {
      // form may be disabled?
      continue;
    }

    $tabs[$tabName] = [
      'title' => $afform['title'],
      // after core tabs
      'weight' => $weight++,
      'icon' => 'crm-i ' . ($afform['icon'] ?: 'fa-list-alt'),
      'active' => TRUE,
      'valid' => TRUE,
      'template' => 'afform/InlineAfform.tpl',
      'module' => $afform['module_name'],
      'directive' => $afform['directive_name'],
    ];

    // If this is the real contact summary page (and not a callback from ContactLayoutEditor), load module.
    if (empty($context['caller'])) {
      Civi::service('angularjs.loader')->addModules($afform['module_name']);
    }
  }

}
