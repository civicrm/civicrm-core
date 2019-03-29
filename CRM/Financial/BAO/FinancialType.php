<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
  +--------------------------------------------------------------------+
  | This file is a part of CiviCRM.                                    |
  |                                                                    |
  | CiviCRM is free software; you can copy, modify, and distribute it  |
  | under the terms of the GNU Affero General Public License           |
  | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
  |                                                                    |
  | CiviCRM is distributed in the hope that it will be useful, but     |
  | WITHOUT ANY WARRANTY; without even the implied warranty of         |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
  | See the GNU Affero General Public License for more details.        |
  |                                                                    |
  | You should have received a copy of the GNU Affero General Public   |
  | License and the CiviCRM Licensing Exception along                  |
  | with this program; if not, contact CiviCRM LLC                     |
  | at info[AT]civicrm[DOT]org. If you have questions about the        |
  | GNU Affero General Public License or the licensing of CiviCRM,     |
  | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
  +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Financial_BAO_FinancialType extends CRM_Financial_DAO_FinancialType {

  /**
   * Static cache holder of available financial types for this session
   */
  static $_availableFinancialTypes = [];
  /**
   * Static cache holder of status of ACL-FT enabled/disabled for this session
   */
  static $_statusACLFt = [];

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Financial_DAO_FinancialType
   */
  public static function retrieve(&$params, &$defaults) {
    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->copyValues($params);
    if ($financialType->find(TRUE)) {
      CRM_Core_DAO::storeValues($financialType, $defaults);
      return $financialType;
    }
    return NULL;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_FinancialType', $id, 'is_active', $is_active);
  }

  /**
   * Add the financial types.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Reference array contains the id.
   *
   * @return object
   */
  public static function add(&$params, &$ids = []) {
    if (empty($params['id'])) {
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['is_deductible'] = CRM_Utils_Array::value('is_deductible', $params, FALSE);
      $params['is_reserved'] = CRM_Utils_Array::value('is_reserved', $params, FALSE);
    }

    // action is taken depending upon the mode
    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->copyValues($params);
    if (!empty($ids['financialType'])) {
      $financialType->id = CRM_Utils_Array::value('financialType', $ids);
      if (self::isACLFinancialTypeStatus()) {
        $prevName = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $financialType->id, 'name');
        if ($prevName != $params['name']) {
          CRM_Core_Session::setStatus(ts("Changing the name of a Financial Type will result in losing the current permissions associated with that Financial Type.
            Before making this change you should likely note the existing permissions at Administer > Users and Permissions > Permissions (Access Control),
            then clicking the Access Control link for your Content Management System, then noting down the permissions for 'CiviCRM: {financial type name} view', etc.
            Then after making the change of name, reset the permissions to the way they were."), ts('Warning'), 'warning');
        }
      }
    }
    $financialType->save();
    // CRM-12470
    if (empty($ids['financialType']) && empty($params['id'])) {
      $titles = CRM_Financial_BAO_FinancialTypeAccount::createDefaultFinancialAccounts($financialType);
      $financialType->titles = $titles;
    }
    return $financialType;
  }

  /**
   * Delete financial Types.
   *
   * @param int $financialTypeId
   *
   * @return array|bool
   */
  public static function del($financialTypeId) {
    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->id = $financialTypeId;
    $financialType->find(TRUE);
    // tables to ignore checks for financial_type_id
    $ignoreTables = ['CRM_Financial_DAO_EntityFinancialAccount'];

    // TODO: if (!$financialType->find(true)) {

    // ensure that we have no objects that have an FK to this financial type id TODO: that cannot be null
    $occurrences = $financialType->findReferences();
    if ($occurrences) {
      $tables = [];
      foreach ($occurrences as $occurrence) {
        $className = get_class($occurrence);
        if (!in_array($className, $tables) && !in_array($className, $ignoreTables)) {
          $tables[] = $className;
        }
      }
      if (!empty($tables)) {
        $message = ts('The following tables have an entry for this financial type: %1', ['%1' => implode(', ', $tables)]);

        $errors = [];
        $errors['is_error'] = 1;
        $errors['error_message'] = $message;
        return $errors;
      }
    }

    // delete from financial Type table
    $financialType->delete();

    $entityFinancialType = new CRM_Financial_DAO_EntityFinancialAccount();
    $entityFinancialType->entity_id = $financialTypeId;
    $entityFinancialType->entity_table = 'civicrm_financial_type';
    $entityFinancialType->delete();
    return FALSE;
  }

  /**
   * fetch financial type having relationship as Income Account is.
   *
   *
   * @return array
   *   all financial type with income account is relationship
   */
  public static function getIncomeFinancialType() {
    // Financial Type
    $financialType = CRM_Contribute_PseudoConstant::financialType();
    $revenueFinancialType = [];
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    CRM_Core_PseudoConstant::populate(
      $revenueFinancialType,
      'CRM_Financial_DAO_EntityFinancialAccount',
      $all = TRUE,
      $retrieve = 'entity_id',
      $filter = NULL,
      "account_relationship = $relationTypeId AND entity_table = 'civicrm_financial_type' "
    );

    foreach ($financialType as $key => $financialTypeName) {
      if (!in_array($key, $revenueFinancialType)
        || (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
          && !CRM_Core_Permission::check('add contributions of type ' . $financialTypeName))
      ) {
        unset($financialType[$key]);
      }
    }
    return $financialType;
  }

  /**
   * Add permissions for financial types.
   *
   * @param array $permissions
   * @param array $descriptions
   *
   * @return bool
   */
  public static function permissionedFinancialTypes(&$permissions, $descriptions) {
    if (!self::isACLFinancialTypeStatus()) {
      return FALSE;
    }
    $financialTypes = CRM_Contribute_PseudoConstant::financialType();
    $prefix = ts('CiviCRM') . ': ';
    $actions = ['add', 'view', 'edit', 'delete'];
    foreach ($financialTypes as $id => $type) {
      foreach ($actions as $action) {
        if ($descriptions) {
          $permissions[$action . ' contributions of type ' . $type] = [
            $prefix . ts($action . ' contributions of type ') . $type,
            ts(ucfirst($action) . ' contributions of type ') . $type,
          ];
        }
        else {
          $permissions[$action . ' contributions of type ' . $type] = $prefix . ts($action . ' contributions of type ') . $type;
        }
      }
    }
    if (!$descriptions) {
      $permissions['administer CiviCRM Financial Types'] = $prefix . ts('administer CiviCRM Financial Types');
    }
    else {
      $permissions['administer CiviCRM Financial Types'] = [
        $prefix . ts('administer CiviCRM Financial Types'),
        ts('Administer access to Financial Types'),
      ];
    }
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
      $membershipTypes = CRM_Member_PseudoConstant::membershipType();
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
   * @param array $whereClauses
   *   (reference ) an array of clauses
   * @param string $component
   *   the type of component
   * @param string $alias
   *   the alias to use
   *
   */
  public static function buildPermissionedClause(&$whereClauses, $component = NULL, $alias = NULL) {
    // @todo the relevant addSelectWhere clause should be called.
    if (!self::isACLFinancialTypeStatus()) {
      return FALSE;
    }
    if (is_array($whereClauses)) {
      $types = self::getAllEnabledAvailableFinancialTypes();
      if (empty($types)) {
        $whereClauses[] = ' ' . $alias . '.financial_type_id IN (0)';
      }
      else {
        $whereClauses[] = ' ' . $alias . '.financial_type_id IN (' . implode(',', array_keys($types)) . ')';
      }
    }
    else {
      if ($component == 'contribution') {
        $types = self::getAllEnabledAvailableFinancialTypes();
        $column = "financial_type_id";
      }
      if ($component == 'membership') {
        self::getAvailableMembershipTypes($types, CRM_Core_Action::VIEW);
        $column = "membership_type_id";
      }
      if (!empty($whereClauses)) {
        $whereClauses .= ' AND ';
      }
      if (empty($types)) {
        $whereClauses .= " civicrm_{$component}.{$column} IN (0)";
        return;
      }
      $whereClauses .= " civicrm_{$component}.{$column} IN (" . implode(',', array_keys($types)) . ")";
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
   *
   * @return bool
   */
  public static function checkPermissionedLineItems($id, $op, $force = TRUE) {
    if (!self::isACLFinancialTypeStatus()) {
      return TRUE;
    }
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($id);
    $flag = FALSE;
    foreach ($lineItems as $items) {
      if (!CRM_Core_Permission::check($op . ' contributions of type ' . CRM_Contribute_PseudoConstant::financialType($items['financial_type_id']))) {
        if ($force) {
          CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
          break;
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
   * @todo rename this function e.g isFinancialTypeACLsEnabled.
   *
   * @return bool
   */
  public static function isACLFinancialTypeStatus() {
    if (!isset(\Civi::$statics[__CLASS__]['is_acl_enabled'])) {
      \Civi::$statics[__CLASS__]['is_acl_enabled'] = FALSE;
      $realSetting = \Civi::$statics[__CLASS__]['is_acl_enabled'] = Civi::settings()->get('acl_financial_type');
      if (!$realSetting) {
        $contributeSettings = Civi::settings()->get('contribution_invoice_settings');
        if (CRM_Utils_Array::value('acl_financial_type', $contributeSettings)) {
          \Civi::$statics[__CLASS__]['is_acl_enabled'] = TRUE;
        }
      }
    }
    return \Civi::$statics[__CLASS__]['is_acl_enabled'];
  }

}
