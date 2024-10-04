<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\EntityFinancialAccount;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Financial_BAO_FinancialType extends CRM_Financial_DAO_FinancialType implements \Civi\Core\HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_FinancialType', $id, 'is_active', $is_active);
  }

  /**
   * Create a financial type.
   *
   * @param array $params
   *
   * @return \CRM_Financial_DAO_FinancialType
   * @deprecated
   */
  public static function create(array $params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Add the financial types.
   *
   * Note that $ids isn't passed anywhere except tests, which pass an empty array.
   *
   * @param array $params
   *   Values from the database object.
   * @param array $ids
   *   Array that we wish to deprecate and remove.
   *
   * @return object
   * @deprecated
   */
  public static function add(array $params, $ids = []) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return self::writeRecord($params);
  }

  /**
   * Delete financial Types.
   *
   * @param int $financialTypeId
   * @deprecated
   * @return array|bool
   */
  public static function del($financialTypeId) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    try {
      static::deleteRecord(['id' => $financialTypeId]);
      return TRUE;
    }
    catch (CRM_Core_Exception $e) {
      return [
        'is_error' => 1,
        'error_message' => $e->getMessage(),
      ];
    }
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete') {
      $financialType = new CRM_Financial_DAO_FinancialType();
      $financialType->id = $event->id;

      // tables to ignore checks for financial_type_id
      $ignoreTables = ['CRM_Financial_DAO_EntityFinancialAccount'];

      // ensure that we have no objects that have an FK to this financial type id TODO: that cannot be null
      $tables = [];
      foreach ($financialType->findReferences() as $occurrence) {
        $className = get_class($occurrence);
        if (!in_array($className, $tables) && !in_array($className, $ignoreTables)) {
          $tables[] = $className;
        }
      }
      if (!empty($tables)) {
        throw new CRM_Core_Exception(ts('The following tables have an entry for this financial type: %1', [1 => implode(', ', $tables)]));
      }
    }
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    if ($event->action === 'create') {
      $titles = CRM_Financial_BAO_EntityFinancialAccount::createDefaultFinancialAccounts($event->object);
      $event->object->titles = $titles;
    }
    if ($event->action === 'delete') {
      \Civi\Api4\EntityFinancialAccount::delete(FALSE)
        ->addWhere('entity_id', '=', $event->id)
        ->addWhere('entity_table', '=', 'civicrm_financial_type')
        ->execute();
    }
  }

  /**
   * Pseudoconstant condition_provider for financial_type_id field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterIncomeFinancialTypes(string $fieldName, CRM_Utils_SQL_Select $conditions, $params): void {
    $allowedTypes = self::getIncomeFinancialType($params['check_permissions']);
    $allowedTypes = array_keys($allowedTypes) ?: 0;
    $conditions->where('id IN (#financialTypeId)', ['financialTypeId' => $allowedTypes]);
  }

  /**
   * Fetch financial types having relationship as Income Account is.
   *
   * @return array
   *   all financial type with income account is relationship
   *
   * @throws \CRM_Core_Exception
   */
  public static function getIncomeFinancialType($checkPermissions = TRUE): array {
    if (!CRM_Core_Component::isEnabled('CiviContribute')) {
      return [];
    }
    // Realistically tests are the only place where logged in contact can
    // change during the session at this stage.
    $key = 'income_type' . (int) $checkPermissions;
    if ($checkPermissions) {
      $key .= '_' . CRM_Core_Session::getLoggedInContactID();
    }
    if (isset(Civi::$statics[__CLASS__][$key])) {
      return Civi::$statics[__CLASS__][$key];
    }
    try {
      $types = EntityFinancialAccount::get($checkPermissions)
        ->addWhere('account_relationship:name', '=', 'Income Account is')
        ->addWhere('entity_table', '=', 'civicrm_financial_type')
        ->addSelect('entity_id', 'financial_type.name')
        ->addJoin('FinancialType AS financial_type', 'LEFT', [
          'entity_id',
          '=',
          'financial_type.id',
        ])
        ->addWhere('financial_type.is_active', '=', '1')
        ->execute()->indexBy('entity_id');
      Civi::$statics[__CLASS__][$key] = [];
      foreach ($types as $type) {
        Civi::$statics[__CLASS__][$key][$type['entity_id']] = $type['financial_type.name'];
      }
    }
    catch (UnauthorizedException $e) {
      Civi::$statics[__CLASS__][$key] = [];
    }
    return Civi::$statics[__CLASS__][$key];
  }

  /**
   * Wrapper aroung getAvailableFinancialTypes to get all including disabled FinancialTypes
   * @param int|string $action
   *   the type of action, can be add, view, edit, delete
   * @param bool $resetCache
   *   load values from static cache
   *
   * @return array
   */
  public static function getAllAvailableFinancialTypes($action = CRM_Core_Action::VIEW, $resetCache = FALSE) {
    // Flush pseudoconstant cache
    CRM_Contribute_PseudoConstant::flush('financialType');
    $thisIsAUselessVariableButSolvesPHPError = NULL;
    $financialTypes = self::getAvailableFinancialTypes($thisIsAUselessVariableButSolvesPHPError, $action, $resetCache, TRUE);
    return $financialTypes;
  }

  /**
   * Wrapper aroung getAvailableFinancialTypes to get all FinancialTypes Excluding Disabled ones.
   * @param int|string $action
   *   the type of action, can be add, view, edit, delete
   * @param bool $resetCache
   *   load values from static cache
   *
   * @return array
   */
  public static function getAllEnabledAvailableFinancialTypes($action = CRM_Core_Action::VIEW, $resetCache = FALSE) {
    $thisIsAUselessVariableButSolvesPHPError = NULL;
    $financialTypes = self::getAvailableFinancialTypes($thisIsAUselessVariableButSolvesPHPError, $action, $resetCache);
    return $financialTypes;
  }

  /**
   * Get available Financial Types.
   *
   * This logic is being moved into the financialacls extension.
   *
   * Rather than call this function consider using
   *
   * $types = \CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search');
   *
   * @param array $financialTypes
   *   (reference ) an array of financial types
   * @param int|string $action
   *   the type of action, can be add, view, edit, delete
   * @param bool $resetCache
   *   load values from static cache
   * @param bool $includeDisabled
   *   Whether we should load in disabled FinancialTypes or Not
   *
   * @return array
   */
  public static function getAvailableFinancialTypes(&$financialTypes = NULL, $action = CRM_Core_Action::VIEW, $resetCache = FALSE, $includeDisabled = FALSE) {
    if (empty($financialTypes)) {
      $financialTypes = CRM_Contribute_PseudoConstant::financialType(NULL, $includeDisabled);
    }
    if (!self::isACLFinancialTypeStatus()) {
      return $financialTypes;
    }
    $actions = [
      CRM_Core_Action::VIEW => 'view',
      CRM_Core_Action::UPDATE => 'edit',
      CRM_Core_Action::ADD => 'add',
      CRM_Core_Action::DELETE => 'delete',
    ];

    if (!isset(\Civi::$statics[__CLASS__]['available_types_' . $action])) {
      foreach ($financialTypes as $finTypeId => $type) {
        if (!CRM_Core_Permission::check($actions[$action] . ' contributions of type ' . $type)) {
          unset($financialTypes[$finTypeId]);
        }
      }
      \Civi::$statics[__CLASS__]['available_types_' . $action] = $financialTypes;
    }
    $financialTypes = \Civi::$statics[__CLASS__]['available_types_' . $action];
    return \Civi::$statics[__CLASS__]['available_types_' . $action];
  }

  /**
   * Get available Membership Types.
   *
   * @param array $membershipTypes
   *   (reference ) an array of membership types
   * @param int|string $action
   *   the type of action, can be add, view, edit, delete
   *
   * @return array
   */
  public static function getAvailableMembershipTypes(&$membershipTypes = NULL, $action = CRM_Core_Action::VIEW) {
    if (empty($membershipTypes)) {
      $membershipTypes = CRM_Member_BAO_Membership::buildOptions('membership_type_id');
    }
    if (!self::isACLFinancialTypeStatus()) {
      return $membershipTypes;
    }
    $actions = [
      CRM_Core_Action::VIEW => 'view',
      CRM_Core_Action::UPDATE => 'edit',
      CRM_Core_Action::ADD => 'add',
      CRM_Core_Action::DELETE => 'delete',
    ];
    foreach ($membershipTypes as $memTypeId => $type) {
      $finTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $memTypeId, 'financial_type_id');
      $finType = CRM_Contribute_PseudoConstant::financialType($finTypeId);
      if (!CRM_Core_Permission::check($actions[$action] . ' contributions of type ' . $finType)) {
        unset($membershipTypes[$memTypeId]);
      }
    }
    return $membershipTypes;
  }

  /**
   * This function adds the Financial ACL clauses to the where clause.
   *
   * This is currently somewhat mocking the native hook implementation
   * for the acls that are in core. If the financialaclreport extension is installed
   * core acls are not applied as that would result in them being applied twice.
   *
   * Long term we should either consolidate the financial acls in core or use only the extension.
   * Both require substantial clean up before implementing and by the time the code is clean enough to
   * take the final step we should
   * be able to implement by removing one half of the other of this function.
   *
   * @param array $whereClauses
   */
  public static function addACLClausesToWhereClauses(&$whereClauses) {
    $contributionBAO = new CRM_Contribute_BAO_Contribution();
    $whereClauses = array_merge($whereClauses, $contributionBAO->addSelectWhereClause());

  }

  /**
   * Function to build a permissioned sql where clause based on available financial types.
   *
   * @param string $component
   *   the type of component
   *
   * @deprecated
   *
   * @return string $clauses
   */
  public static function buildPermissionedClause(string $component): string {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    // There are no non-test usages of this function (including in a universe
    // search).
    if ($component === 'contribution') {
      $clauses = CRM_Contribute_BAO_Contribution::getSelectWhereClause();
    }
    if ($component === 'membership') {
      $clauses = CRM_Member_BAO_Membership::getSelectWhereClause();
    }
    return 'AND ' . implode(' AND ', $clauses);
  }

  /**
   * Function to check if lineitems present in a contribution have permissioned FTs.
   *
   * @deprecated since 5.68 not part of core - to be removed 5.74
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
  public static function checkPermissionedLineItems($id, $op, $force = TRUE, $contactID = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('use financial acls extension');
    if (!self::isACLFinancialTypeStatus()) {
      return TRUE;
    }
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($id);
    $flag = FALSE;
    foreach ($lineItems as $items) {
      if (!CRM_Core_Permission::check($op . ' contributions of type ' . CRM_Contribute_PseudoConstant::financialType($items['financial_type_id']), $contactID)) {
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
   * Check if the logged in user has permission to edit the given financial type.
   *
   * This is called when determining if they can edit things like option values
   * in price sets. At the moment it is not possible to change an option value from
   * a type you do not have permission to to a type that you do.
   *
   * @todo it is currently not possible to edit disabled types if you have ACLs on.
   * Do ACLs still apply once disabled? That question should be resolved if tackling
   * that gap.
   *
   * @param int $financialTypeID
   *
   * @return bool
   */
  public static function checkPermissionToEditFinancialType($financialTypeID) {
    if (!self::isACLFinancialTypeStatus()) {
      return TRUE;
    }
    $financialTypes = CRM_Financial_BAO_FinancialType::getAllAvailableFinancialTypes(CRM_Core_Action::UPDATE);
    return isset($financialTypes[$financialTypeID]);
  }

  /**
   * Check if FT-ACL is turned on or off.
   *
   * @deprecated since 5.75 will be removed around 5.90
   * Generally you should call hooks & allow the extension to engage but if you need to
   * then check the extension status directly - do not use a helper.
   *
   * @return bool
   */
  public static function isACLFinancialTypeStatus() {
    return self::isFinancialTypeAclExtensionInstalled();
  }

  /**
   * @return bool
   * @throws \CRM_Core_Exception
   * @internal transitional function.
   */
  public static function isFinancialTypeAclExtensionInstalled(): bool {
    $financialAclExtension = civicrm_api3('extension', 'get', ['key' => 'financialacls', 'sequential' => 1])['values'];
    return !empty($financialAclExtension) && $financialAclExtension[0]['status'] === 'installed';
  }

}
