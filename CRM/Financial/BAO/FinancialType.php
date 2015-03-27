<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.6                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Financial_BAO_FinancialType extends CRM_Financial_DAO_FinancialType {

  /**
   * Static holder for the default LT.
   */
  static $_defaultContributionType = NULL;

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
   *   DAO object on sucess, null otherwise
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

    //TODO: if (!$financialType->find(true)) {

    // ensure that we have no objects that have an FK to this financial type id TODO: that cannot be null
    $occurrences = $financialType->findReferences();
    if ($occurrences) {
      $tables = array();
      foreach ($occurrences as $occurence) {
        $className = get_class($occurence);
        if (!in_array($className, $ignoreTables)) {
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

    //delete from financial Type table
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
      if (!in_array($key, $revenueFinancialType)) {
        unset($financialType[$key]);
      }
    }
    return $financialType;
  }

}
