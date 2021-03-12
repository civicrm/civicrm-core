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
 * @param string $op
 * @param string $objectName
 * @param int|null $id
 * @param array $params
 *
 * @throws \API_Exception
 * @throws \CRM_Core_Exception
 */
function financialacls_civicrm_pre($op, $objectName, $id, &$params) {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }
  if ($objectName === 'LineItem' && !empty($params['check_permissions'])) {
    $operationMap = ['delete' => CRM_Core_Action::DELETE, 'edit' => CRM_Core_Action::UPDATE, 'create' => CRM_Core_Action::ADD];
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types, $operationMap[$op]);
    if (empty($params['financial_type_id'])) {
      $params['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_LineItem', $params['id'], 'financial_type_id');
    }
    if (!in_array($params['financial_type_id'], array_keys($types))) {
      throw new API_Exception('You do not have permission to ' . $op . ' this line item');
    }
  }
  if ($objectName === 'FinancialType' && !empty($params['id']) && !empty($params['name'])) {
    $prevName = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $params['id']);
    if ($prevName !== $params['name']) {
      CRM_Core_Session::setStatus(ts("Changing the name of a Financial Type will result in losing the current permissions associated with that Financial Type.
            Before making this change you should likely note the existing permissions at Administer > Users and Permissions > Permissions (Access Control),
            then clicking the Access Control link for your Content Management System, then noting down the permissions for 'CiviCRM: {financial type name} view', etc.
            Then after making the change of name, reset the permissions to the way they were."), ts('Warning'), 'warning');
    }
  }
}

/**
 * Implements hook_civicrm_selectWhereClause().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_selectWhereClause
 */
function financialacls_civicrm_selectWhereClause($entity, &$clauses) {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }

  switch ($entity) {
    case 'LineItem':
    case 'MembershipType':
      $types = [];
      CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types);
      if ($types) {
        $clauses['financial_type_id'] = 'IN (' . implode(',', array_keys($types)) . ')';
      }
      else {
        $clauses['financial_type_id'] = '= 0';
      }
      break;

  }

}

/**
 * Remove unpermitted options.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildAmount
 *
 * @param string $component
 * @param \CRM_Core_Form $form
 * @param array $feeBlock
 */
function financialacls_civicrm_buildAmount($component, $form, &$feeBlock) {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }

  foreach ($feeBlock as $key => $value) {
    foreach ($value['options'] as $k => $options) {
      if (!CRM_Core_Permission::check('add contributions of type ' . CRM_Contribute_PseudoConstant::financialType($options['financial_type_id']))) {
        unset($feeBlock[$key]['options'][$k]);
      }
    }
    if (empty($feeBlock[$key]['options'])) {
      unset($feeBlock[$key]);
    }
  }
}

/**
 * Remove unpermitted membership types from selection availability..
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_membershipTypeValues
 *
 * @param \CRM_Core_Form $form
 * @param array $membershipTypeValues
 */
function financialacls_civicrm_membershipTypeValues($form, &$membershipTypeValues) {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }
  $financialTypes = NULL;
  $financialTypes = CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, CRM_Core_Action::ADD);
  foreach ($membershipTypeValues as $id => $type) {
    if (!isset($financialTypes[$type['financial_type_id']])) {
      unset($membershipTypeValues[$id]);
    }
  }
}

/**
 * Add permissions.
 *
 * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_permission/
 *
 * @param array $permissions
 */
function financialacls_civicrm_permission(&$permissions) {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }
  $actions = [
    'add' => ts('add'),
    'view' => ts('view'),
    'edit' => ts('edit'),
    'delete' => ts('delete'),
  ];
  $financialTypes = \CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'validate');
  foreach ($financialTypes as $id => $type) {
    foreach ($actions as $action => $action_ts) {
      $permissions[$action . ' contributions of type ' . $type] = [
        ts("CiviCRM: %1 contributions of type %2", [1 => $action_ts, 2 => $type]),
        ts('%1 contributions of type %2', [1 => $action_ts, 2 => $type]),
      ];
    }
  }
  $permissions['administer CiviCRM Financial Types'] = [
    ts('CiviCRM: administer CiviCRM Financial Types'),
    ts('Administer access to Financial Types'),
  ];
}

/**
 * Remove unpermitted financial types from field Options in search context.
 *
 * Search context is described as
 * 'search' => "search: searchable options are returned; labels are translated.",
 * So this is appropriate to removing the options from search screens.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_fieldOptions
 *
 * @param string $entity
 * @param string $field
 * @param array $options
 * @param array $params
 */
function financialacls_civicrm_fieldOptions($entity, $field, &$options, $params) {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }
  if ($entity === 'Contribution' && $field === 'financial_type_id' && $params['context'] === 'search') {
    $action = CRM_Core_Action::VIEW;
    // At this stage we are only considering the view action. Code from
    // CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes().
    $actions = [
      CRM_Core_Action::VIEW => 'view',
      CRM_Core_Action::UPDATE => 'edit',
      CRM_Core_Action::ADD => 'add',
      CRM_Core_Action::DELETE => 'delete',
    ];
    $cacheKey = 'available_types_' . $action;
    if (!isset(\Civi::$statics['CRM_Financial_BAO_FinancialType'][$cacheKey])) {
      foreach ($options as $finTypeId => $type) {
        if (!CRM_Core_Permission::check($actions[$action] . ' contributions of type ' . $type)) {
          unset($options[$finTypeId]);
        }
      }
      \Civi::$statics['CRM_Financial_BAO_FinancialType'][$cacheKey] = $options;
    }
    $options = \Civi::$statics['CRM_Financial_BAO_FinancialType'][$cacheKey];
  }
}

/**
 * Is financial acl limiting enabled.
 *
 * Once this extension is detangled enough to be optional this will go
 * and the status of the extension rather than the setting will dictate.
 *
 * @return bool
 */
function financialacls_is_acl_limiting_enabled(): bool {
  return (bool) Civi::settings()->get('acl_financial_type');
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
