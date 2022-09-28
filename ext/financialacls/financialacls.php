<?php

require_once 'financialacls.civix.php';
// phpcs:disable
use Civi\Api4\EntityFinancialAccount;
use Civi\Api4\MembershipType;
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
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function financialacls_civicrm_container($container) {
  $dispatcherDefn = $container->getDefinition('dispatcher');
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $dispatcherDefn->addMethodCall('addListener', ['civi.api4.authorizeRecord::Contribution', '_financialacls_civi_api4_authorizeContribution']);
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
 * Intervene to prevent deletion, where permissions block it.
 *
 * @param string $op
 * @param string $objectName
 * @param int|null $id
 * @param array $params
 *
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
    if (!array_key_exists($params['financial_type_id'], $types)) {
      throw new CRM_Core_Exception('You do not have permission to ' . $op . ' this line item');
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
    case 'ContributionRecur':
    case 'Contribution':
      $clauses['financial_type_id'] = _financialacls_civicrm_get_type_clause();
      break;

    case 'Membership':
      $clauses['membership_type_id'] = _financialacls_civicrm_get_membership_type_clause();
      break;

    case 'FinancialType':
      $clauses['id'] = _financialacls_civicrm_get_type_clause();
      break;

    case 'FinancialAccount':
      $clauses['id'] = _financialacls_civicrm_get_accounts_clause();
      break;

  }

}

/**
 * Get the clause to limit available types.
 *
 * @return string
 */
function _financialacls_civicrm_get_accounts_clause(): string {
  if (!isset(Civi::$statics['financial_acls'][__FUNCTION__][CRM_Core_Session::getLoggedInContactID()])) {
    try {
      $clause = '= 0';
      Civi::$statics['financial_acls'][__FUNCTION__][CRM_Core_Session::getLoggedInContactID()] = &$clause;
      $accounts = (array) EntityFinancialAccount::get()
        ->addWhere('account_relationship:name', '=', 'Income Account is')
        ->addWhere('entity_table', '=', 'civicrm_financial_type')
        ->addSelect('entity_id', 'financial_account_id')
        ->addJoin('FinancialType AS financial_type', 'LEFT', [
          'entity_id',
          '=',
          'financial_type.id',
        ])
        ->execute()->indexBy('financial_account_id');
      if (!empty($accounts)) {
        $clause = 'IN (' . implode(',', array_keys($accounts)) . ')';
      }
    }
    catch (\CRM_Core_Exception $e) {
      // We've already set it to 0 so we can quietly handle this.
    }
  }
  return Civi::$statics['financial_acls'][__FUNCTION__][CRM_Core_Session::getLoggedInContactID()];
}

/**
 * Get the clause to limit available types.
 *
 * @return string
 */
function _financialacls_civicrm_get_type_clause(): string {
  return 'IN (' . implode(',', _financialacls_civicrm_get_accessible_financial_types()) . ')';
}

/**
 * Get an array of the ids of accessible financial types.
 *
 * If none then it will be [0]
 *
 * @return int[]
 */
function _financialacls_civicrm_get_accessible_financial_types(): array {
  $types = [];
  CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types);
  if (empty($types)) {
    $types = [0];
  }
  return array_keys($types);
}

/**
 * Get the clause to limit available membership types.
 *
 * @return string
 *
 * @throws \CRM_Core_Exception
 */
function _financialacls_civicrm_get_membership_type_clause(): string {
  $financialTypes = _financialacls_civicrm_get_accessible_financial_types();
  if ($financialTypes === [0] || !CRM_Core_Component::isEnabled('CiviMember')) {
    return '= 0';
  }
  $membershipTypes = (array) MembershipType::get(FALSE)
    ->addWhere('financial_type_id', 'IN', $financialTypes)->execute()->indexBy('id');
  return empty($membershipTypes) ? '= 0' : ('IN (' . implode(',', array_keys($membershipTypes)) . ')');
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
 * Listener for 'civi.api4.authorizeRecord::Contribution'
 *
 * @param \Civi\Api4\Event\AuthorizeRecordEvent $e
 * @throws \CRM_Core_Exception
 */
function _financialacls_civi_api4_authorizeContribution(\Civi\Api4\Event\AuthorizeRecordEvent $e) {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }
  if ($e->getEntityName() === 'Contribution') {
    $contributionID = $e->getRecord()['id'] ?? NULL;
    $financialTypeID = $e->getRecord()['financial_type_id'] ?? CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionID, 'financial_type_id');
    if (!CRM_Core_Permission::check(_financialacls_getRequiredPermission($financialTypeID, $e->getActionName()), $e->getUserID())) {
      $e->setAuthorized(FALSE);
    }
    if ($e->getActionName() === 'delete') {
      // First check contribution financial type
      // Now check permissioned line items & permissioned contribution
      if (!CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($contributionID, 'delete', FALSE, $e->getUserID())
      ) {
        $e->setAuthorized(FALSE);
      }
    }
  }
}

/**
 * Get the permission required to perform this action on this financial type.
 *
 * @param int $financialTypeID
 * @param string $action
 *
 * @return string
 */
function _financialacls_getRequiredPermission(int $financialTypeID, string $action): string {
  $financialType = CRM_Core_PseudoConstant::getName('CRM_Contribute_DAO_Contribution', 'financial_type_id', $financialTypeID);
  $actionMap = [
    'create' => 'add',
    'update' => 'edit',
    'delete' => 'delete',
  ];
  return $actionMap[$action] . ' contributions of type ' . $financialType;
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
  if (in_array($entity, ['Contribution', 'ContributionRecur'], TRUE) && $field === 'financial_type_id' && $params['context'] === 'search') {
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

/**
 * Clear the statics cache when the setting is enabled or disabled.
 *
 * Note the setting will eventually disappear in favour of whether
 * the extension is enabled or disabled.
 */
function financialacls_toggle() {
  unset(\Civi::$statics['CRM_Financial_BAO_FinancialType']);
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
 * Require financial acl permissions for financial screens.
 *
 * @param array $menu
 */
function financialacls_civicrm_alterMenu(array &$menu): void {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }
  $menu['civicrm/admin/financial/financialType']['access_arguments'] = [['administer CiviCRM Financial Types']];
}

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
