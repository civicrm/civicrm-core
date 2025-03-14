<?php

require_once 'financialacls.civix.php';

use Civi\Api4\EntityFinancialAccount;
use Civi\Api4\FinancialType;
use Civi\Api4\MembershipType;
use CRM_Financialacls_ExtensionUtil as E;

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
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function financialacls_civicrm_enable() {
  _financialacls_civix_civicrm_enable();
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
  if (in_array($objectName, ['LineItem', 'Product'], TRUE) && !empty($params['check_permissions'])) {
    if (empty($params['financial_type_id']) && !empty($params['id'])) {
      $dao = CRM_Core_DAO_AllCoreTables::getDAONameForEntity($objectName);
      $params['financial_type_id'] = CRM_Core_DAO::getFieldValue($dao, $params['id'], 'financial_type_id');
    }
    $operationMap = ['delete' => CRM_Core_Action::DELETE, 'edit' => CRM_Core_Action::UPDATE, 'create' => CRM_Core_Action::ADD];
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types, $operationMap[$op]);
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

  switch ($entity) {
    case 'LineItem':
    case 'MembershipType':
    case 'ContributionRecur':
    case 'Contribution':
    case 'Product':
      $clauses['financial_type_id'][] = _financialacls_civicrm_get_type_clause();
      if ($entity === 'Contribution') {
        $unavailableTypes = _financialacls_civicrm_get_inaccessible_financial_types();
        if (!empty($unavailableTypes)) {
          $clauses['id'][] = 'AND NOT EXISTS (SELECT 1 FROM civicrm_line_item WHERE contribution_id = {id} AND financial_type_id IN (' . implode(',', $unavailableTypes) . '))';
        }
      }
      break;

    case 'Membership':
      $clauses['membership_type_id'][] = _financialacls_civicrm_get_membership_type_clause();
      break;

    case 'FinancialType':
      $clauses['id'][] = _financialacls_civicrm_get_type_clause();
      break;

    case 'FinancialAccount':
      $clauses['id'][] = _financialacls_civicrm_get_accounts_clause();
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
  CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($types, CRM_Core_Action::VIEW, FALSE, TRUE);
  if (empty($types)) {
    $types = [0];
  }
  return array_keys($types);
}

/**
 * Get an array of the ids of accessible financial types.
 *
 * If none then it will be [0]
 *
 * @return int[]
 */
function _financialacls_civicrm_get_inaccessible_financial_types(): array {
  $types = (array) FinancialType::get(FALSE)->addSelect('id')->execute()->indexBy('id');
  foreach (_financialacls_civicrm_get_accessible_financial_types() as $accessibleFinancialType) {
    unset($types[$accessibleFinancialType]);
  }
  return array_keys($types);
}

/**
 * Get the clause to limit available membership types.
 *
 * @return string
 *
 * @noinspection PhpUnhandledExceptionInspection
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
      if (!CRM_Core_Permission::check('add contributions of type ' . CRM_Core_PseudoConstant::getName('CRM_Contribute_DAO_Contribution', 'financial_type_id', $options['financial_type_id']))) {
        unset($feeBlock[$key]['options'][$k]);
      }
    }
    if (empty($feeBlock[$key]['options'])) {
      unset($feeBlock[$key]);
    }
  }
  if (is_a($form, 'CRM_Event_Form_Participant')
    && empty($feeBlock)
    && ($_REQUEST['snippet'] ?? NULL) == CRM_Core_Smarty::PRINT_NOFORM
  ) {
    CRM_Core_Session::setStatus(ts('You do not have all the permissions needed for this page.'), 'Permission Denied', 'error');
    return FALSE;
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
    'add' => E::ts('add'),
    'view' => E::ts('view'),
    'edit' => E::ts('edit'),
    'delete' => E::ts('delete'),
  ];
  foreach ($actions as $action => $action_ts) {
    $permissions[$action . ' contributions of all types'] = [
      'label' => E::ts("CiviCRM: %1 contributions of all types", [1 => $action_ts]),
      'description' => E::ts('%1 contributions of all types', [1 => $action_ts]),
    ];
  }
  try {
    $financialTypes = CRM_Core_DAO::executeQuery('SELECT id, `name`, label FROM civicrm_financial_type')->fetchAll();
  }
  catch (\Civi\Core\Exception\DBQueryException $e) {
    // dev/core#5794: While upgrade is pending, the 'label' column may not yet exist. We just need a 'label' that's good enough to get to upgrader.
    $financialTypes = CRM_Core_DAO::executeQuery('SELECT id, `name`, name AS label FROM civicrm_financial_type')->fetchAll();
    // N.B. That's the most likely problem+fix. If there's some other SQL problem, then the fallback query will also throw an exception.
  }
  foreach ($financialTypes as $type) {
    foreach ($actions as $action => $action_ts) {
      $permissions[$action . ' contributions of type ' . $type['name']] = [
        'label' => E::ts("CiviCRM: %1 contributions of type %2", [1 => $action_ts, 2 => $type['label']]),
        'description' => E::ts('%1 contributions of type %2', [1 => $action_ts, 2 => $type['label']]),
        'implied_by' => [$action . ' contributions of all types'],
      ];
    }
  }
  $permissions['administer CiviCRM Financial Types'] = [
    'label' => E::ts('CiviCRM: administer CiviCRM Financial Types'),
    'description' => E::ts('Administer access to Financial Types'),
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
      if (!_civicrm_financial_acls_check_permissioned_line_items($contributionID, 'delete', FALSE, $e->getUserID())) {
        $e->setAuthorized(FALSE);
      }
    }
  }
}

/**
 * Function to check if lineitems present in a contribution have permissioned FTs.
 *
 * @param int $id
 *   contribution id
 * @param string $op
 *   the mode of operation, can be add, view, edit, delete
 * @param bool $force
 * @param int $contactID
 *
 * @return bool
 */
function _civicrm_financial_acls_check_permissioned_line_items($id, $op, $force = TRUE, $contactID = NULL) {
  if (!financialacls_is_acl_limiting_enabled()) {
    return TRUE;
  }
  $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($id);
  $flag = FALSE;
  foreach ($lineItems as $items) {
    if (!CRM_Core_Permission::check($op . ' contributions of type ' . CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'financial_type_id', $items['financial_type_id']), $contactID)) {
      if ($force) {
        throw new CRM_Core_Exception(ts('You do not have permission to access this page.'));
      }
      $flag = FALSE;
      break;
    }
    else {
      $flag = TRUE;
    }
  }
  return $flag;
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
  $context = $params['context'];
  $checkPermissions = (bool) ($params['check_permissions'] ?? TRUE);
  if (in_array($entity, ['Contribution', 'ContributionRecur'], TRUE) && $field === 'financial_type_id' && $checkPermissions) {
    if ($context === 'search' || $context === 'create' || $context === 'full') {
      // At this stage we are only considering the view & create actions. Code from
      // CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes().
      $actions = [
        CRM_Core_Action::VIEW => 'view',
        CRM_Core_Action::UPDATE => 'edit',
        CRM_Core_Action::ADD => 'add',
        CRM_Core_Action::DELETE => 'delete',
      ];
      $action = $context === 'search' ? CRM_Core_Action::VIEW : CRM_Core_Action::ADD;
      $cacheKey = 'available_types_' . $context;
      if (!isset(\Civi::$statics['CRM_Financial_BAO_FinancialType'][$cacheKey])) {
        foreach ($options as $finTypeId => $option) {
          $type = is_string($option) ? $option : $option['name'];
          if (!CRM_Core_Permission::check($actions[$action] . ' contributions of type ' . $type)) {
            unset($options[$finTypeId]);
          }
        }
        \Civi::$statics['CRM_Financial_BAO_FinancialType'][$cacheKey] = $options;
      }
      $options = \Civi::$statics['CRM_Financial_BAO_FinancialType'][$cacheKey];
    }
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
  // @todo - remove this...
  return TRUE;
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
 * @param string $formName
 * @param \CRM_Event_Form_Registration|\CRM_Contribute_Form_Contribution $form
 */
function financialacls_civicrm_preProcess(string $formName, \CRM_Core_Form $form): void {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }
  if (str_starts_with($formName, 'CRM_Contribute_Form_Contribution_')) {
    /* @var \CRM_Contribute_Form_Contribution_Main $form */
    if (!CRM_Core_Permission::check('add contributions of type ' . $form->getContributionPageValue('financial_type_id:name'))) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
  }

  // check for ability to add contributions of type
  if (str_starts_with($formName, 'CRM_Event_Form_Registration_') && $form->getEventValue('is_monetary')
    && !CRM_Core_Permission::check(
      'add contributions of type ' . CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'financial_type_id', $form->getEventValue('financial_type_id')))
  ) {
    CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
  }

}

/**
 * Hide edit/enable/disable links for memberships of a given Financial Type
 * Note: The $objectID param can be an int, string or null, hence not typed
 *
 * Implements hook_civicrm_links()
 */
function financialacls_civicrm_links(string $op, ?string $objectName, $objectID, array &$links, ?int &$mask, array &$values) {
  if (!financialacls_is_acl_limiting_enabled()) {
    return;
  }
  if ($objectName === 'MembershipType') {
    $financialType = CRM_Core_PseudoConstant::getName('CRM_Member_BAO_MembershipType', 'financial_type_id', CRM_Member_BAO_MembershipType::getMembershipType($objectID)['financial_type_id']);
  }
  if ($objectName === 'Contribution') {
    // Now check for lineItems
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID((int) $objectID);
    foreach ($lineItems as $item) {
      $financialType = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'financial_type_id', $item['financial_type_id']);
      if (!CRM_Core_Permission::check('view contributions of type ' . $financialType)) {
        // Remove all links & early return for this contribution if there is an un-viewable financial type.
        $links = [];
        return;
      }
      if (!CRM_Core_Permission::check('edit contributions of type ' . $financialType)) {
        unset($links[CRM_Core_Action::UPDATE]);
      }
      if (!CRM_Core_Permission::check('delete contributions of type ' . $financialType)) {
        unset($links[CRM_Core_Action::DELETE]);
      }
    }
    $financialTypeID = $values['financial_type_id'] ?? CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $objectID, 'financial_type_id');
    $financialType = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'financial_type_id', $financialTypeID);
  }

  if (!empty($financialType)) {
    $hasEditPermission = CRM_Core_Permission::check('edit contributions of type ' . $financialType);
    $hasDeletePermission = CRM_Core_Permission::check('delete contributions of type ' . $financialType);
    if (!$hasDeletePermission || !$hasEditPermission) {
      foreach ($links as $index => $link) {
        if (!$hasEditPermission && in_array($link['name'], ['Edit', 'Enable', 'Disable'], TRUE)) {
          unset($links[$index]);
        }
        if (!$hasDeletePermission && $link['name'] === 'Delete') {
          unset($links[$index]);
        }
      }
    }
  }

}
