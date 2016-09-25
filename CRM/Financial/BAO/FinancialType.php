<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Financial_BAO_FinancialType extends CRM_Financial_DAO_FinancialType {

  /**
   * Static holder for the default LT.
   */
  static $_defaultContributionType = NULL;
  /**
   * Static cache holder of available financial types for this session
   */
  static $_availableFinancialTypes = array();
  /**
   * Static cache holder of status of ACL-FT enabled/disabled for this session
   */
  static $_statusACLFt = array();

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
   * @return CRM_Contribute_BAO_ContributionType
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
   * @return Object
   *   DAO object on success, null otherwise
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
  public static function add(&$params, &$ids = array()) {
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
    // tables to ingore checks for financial_type_id
    $ignoreTables = array('CRM_Financial_DAO_EntityFinancialAccount');

    // TODO: if (!$financialType->find(true)) {

    // ensure that we have no objects that have an FK to this financial type id TODO: that cannot be null
    $occurrences = $financialType->findReferences();
    if ($occurrences) {
      $tables = array();
      foreach ($occurrences as $occurrence) {
        $className = get_class($occurrence);
        if (!in_array($className, $tables) && !in_array($className, $ignoreTables)) {
          $tables[] = $className;
        }
      }
      if (!empty($tables)) {
        $message = ts('The following tables have an entry for this financial type: %1', array('%1' => implode(', ', $tables)));

        $errors = array();
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
    $revenueFinancialType = array();
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
    $actions = array('add', 'view', 'edit', 'delete');
    foreach ($financialTypes as $id => $type) {
      foreach ($actions as $action) {
        if ($descriptions) {
          $permissions[$action . ' contributions of type ' . $type] = array(
            $prefix . ts($action . ' contributions of type ') . $type,
            ts(ucfirst($action) . ' contributions of type ') . $type,
          );
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
      $permissions['administer CiviCRM Financial Types'] = array(
        $prefix . ts('administer CiviCRM Financial Types'),
        ts('Administer access to Financial Types'),
      );
    }
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
   *
   * @return array
   */
  public static function getAvailableFinancialTypes(&$financialTypes = NULL, $action = CRM_Core_Action::VIEW, $resetCache = FALSE) {
    if (empty($financialTypes)) {
      $financialTypes = CRM_Contribute_PseudoConstant::financialType();
    }
    if (!self::isACLFinancialTypeStatus()) {
      return $financialTypes;
    }
    $actions = array(
      CRM_Core_Action::VIEW => 'view',
      CRM_Core_Action::UPDATE => 'edit',
      CRM_Core_Action::ADD => 'add',
      CRM_Core_Action::DELETE => 'delete',
    );
    // check cached value
    if (CRM_Utils_Array::value($action, self::$_availableFinancialTypes) && !$resetCache) {
      $financialTypes = self::$_availableFinancialTypes[$action];
      return self::$_availableFinancialTypes[$action];
    }
    foreach ($financialTypes as $finTypeId => $type) {
      if (!CRM_Core_Permission::check($actions[$action] . ' contributions of type ' . $type)) {
        unset($financialTypes[$finTypeId]);
      }
    }
    self::$_availableFinancialTypes[$action] = $financialTypes;
    return $financialTypes;
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
    $actions = array(
      CRM_Core_Action::VIEW => 'view',
      CRM_Core_Action::UPDATE => 'edit',
      CRM_Core_Action::ADD => 'add',
      CRM_Core_Action::DELETE => 'delete',
    );
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
    if (!self::isACLFinancialTypeStatus()) {
      return FALSE;
    }
    if (is_array($whereClauses)) {
      self::getAvailableFinancialTypes($types);
      if (empty($types)) {
        $whereClauses[] = ' ' . $alias . '.financial_type_id IN (0)';
      }
      else {
        $whereClauses[] = ' ' . $alias . '.financial_type_id IN (' . implode(',', array_keys($types)) . ')';
      }
    }
    else {
      if ($component == 'contribution') {
        self::getAvailableFinancialTypes($types);
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
   * Check if FT-ACL is turned on or off
   *
   * @return bool
   */
  public static function isACLFinancialTypeStatus() {
    if (array_key_exists('acl_financial_type', self::$_statusACLFt)) {
      return self::$_statusACLFt['acl_financial_type'];
    }
    $contributeSettings = Civi::settings()->get('contribution_invoice_settings');
    self::$_statusACLFt['acl_financial_type'] = FALSE;
    if (CRM_Utils_Array::value('acl_financial_type', $contributeSettings)) {
      self::$_statusACLFt['acl_financial_type'] = TRUE;
    }
    return self::$_statusACLFt['acl_financial_type'];
  }

}
