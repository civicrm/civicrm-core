<?php

require_once 'financialacls.civix.php';
// phpcs:disable
use CRM_Financialacls_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function financialacls_civicrm_config(&$config) {
  _financialacls_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function financialacls_civicrm_xmlMenu(&$files) {
  _financialacls_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function financialacls_civicrm_install() {
  _financialacls_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function financialacls_civicrm_postInstall() {
  _financialacls_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function financialacls_civicrm_uninstall() {
  _financialacls_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function financialacls_civicrm_enable() {
  _financialacls_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function financialacls_civicrm_disable() {
  _financialacls_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function financialacls_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _financialacls_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function financialacls_civicrm_managed(&$entities) {
  _financialacls_civix_civicrm_managed($entities);
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
function financialacls_civicrm_caseTypes(&$caseTypes) {
  _financialacls_civix_civicrm_caseTypes($caseTypes);
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
function financialacls_civicrm_angularModules(&$angularModules) {
  _financialacls_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function financialacls_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _financialacls_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function financialacls_civicrm_entityTypes(&$entityTypes) {
  _financialacls_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function financialacls_civicrm_themes(&$themes) {
  _financialacls_civix_civicrm_themes($themes);
}

/**
 * Intervene to prevent deletion, where permissions block it.
 *
 * @param \CRM_Core_DAO $op
 * @param string $objectName
 * @param int|null $id
 * @param array $params
 *
 * @throws \API_Exception
 * @throws \CRM_Core_Exception
 */
function financialacls_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName === 'LineItem' && $op === 'delete' && !empty($params['check_permissions'])) {
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types, CRM_Core_Action::DELETE);
      if (empty($params['financial_type_id'])) {
        $params['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_LineItem', $params['id'], 'financial_type_id');
      }
      if (!in_array($params['financial_type_id'], array_keys($types))) {
        throw new API_Exception('You do not have permission to delete this line item');
      }
    }
  }
}

/**
 * Implements hook_civicrm_selectWhereClause().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_selectWhereClause
 */
function financialacls_civicrm_selectWhereClause($entity, &$clauses) {
  if ($entity === 'LineItem') {
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      $types = [];
      CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types);
      if ($types) {
        $clauses['financial_type_id'] = 'IN (' . implode(',', array_keys($types)) . ')';
      }
      else {
        $clauses['financial_type_id'] = '= 0';
      }
    }
  }

}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function financialacls_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function financialacls_civicrm_navigationMenu(&$menu) {
//  _financialacls_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _financialacls_civix_navigationMenu($menu);
//}
