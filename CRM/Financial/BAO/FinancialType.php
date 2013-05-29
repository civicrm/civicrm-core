<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.3                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

class CRM_Financial_BAO_FinancialType extends CRM_Financial_DAO_FinancialType {

  /**
   * static holder for the default LT
   */
  static $_defaultContributionType = null;

  /**
   * class constructor
   */
  function __construct( ) {
    parent::__construct( );
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Contribute_BAO_ContributionType object
   * @access public
   * @static
   */
  static function retrieve( &$params, &$defaults ) {
    $financialType = new CRM_Financial_DAO_FinancialType( );
    $financialType->copyValues( $params );
    if ($financialType->find(true)) {
      CRM_Core_DAO::storeValues( $financialType, $defaults );
      return $financialType;
    }
    return null;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive( $id, $is_active ) {
    return CRM_Core_DAO::setFieldValue( 'CRM_Financial_DAO_FinancialType', $id, 'is_active', $is_active );
  }

  /**
   * function to add the financial types
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   * @return object
   */
  static function add(&$params, &$ids) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, false);
    $params['is_deductible'] = CRM_Utils_Array::value('is_deductible', $params, false);
    $params['is_reserved'] = CRM_Utils_Array::value('is_reserved', $params, false);

    // action is taken depending upon the mode
    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->copyValues($params);
    if (CRM_Utils_Array::value('financialType', $ids)) {
      $financialType->id = CRM_Utils_Array::value('financialType', $ids);
    }
    $financialType->save();
    // CRM-12470
    if (!CRM_Utils_Array::value('financialType', $ids)) {
      $titles = CRM_Financial_BAO_FinancialTypeAccount::createDefaultFinancialAccounts($financialType);
      $financialType->titles = $titles;
    }
    return $financialType;
  }

  /**
   * Function to delete financial Types
   *
   * @param int $contributionTypeId
   * @static
   */
  static function del($financialTypeId) {
    //checking if financial type is present
    $check = false;

    // ensure that we have no objects that have an FK to this financial type id that cannot be null
    $tables =
      array(
        array(
          'table'  => 'civicrm_contribution',
          'column' => 'financial_type_id'
        ),
        array(
          'table'  => 'civicrm_contribution_page',
          'column' => 'financial_type_id'
        ),
        array(
          'table'  => 'civicrm_contribution_recur',
          'column' => 'financial_type_id'
        ),
        array(
          'table'  => 'civicrm_membership_type',
          'column' => 'financial_type_id'
        ),
        array(
          'table'  => 'civicrm_pledge',
          'column' => 'financial_type_id',
        ),
        array(
          'table'  => 'civicrm_grant',
          'column' => 'financial_type_id',
        ),
        array(
          'table'  => 'civicrm_product',
          'column' => 'financial_type_id',
        ),
        array(
          'table'  => 'civicrm_event',
          'column' => 'financial_type_id',
        ),
        array(
          'table'  => 'civicrm_premiums_product',
          'column' => 'financial_type_id',
        ),
        array(
          'table'  => 'civicrm_price_set',
          'column' => 'financial_type_id',
        ),
        array(
          'table'  => 'civicrm_price_field_value',
          'column' => 'financial_type_id',
        ),
        array(
          'table'  => 'civicrm_line_item',
          'column' => 'financial_type_id',
        ),
        array(
          'table'  => 'civicrm_contribution_product ',
          'column' => 'financial_type_id',
        ),
      );

    $errors = array();
    $params = array( 1 => array($financialTypeId, 'Integer'));
    if (CRM_Core_DAO::doesValueExistInTable( $tables, $params, $errors)) {
      $message  = ts('The following tables have an entry for this financial type') . ': ';
      $message .= implode( ', ', array_keys($errors));

      $errors = array();
      $errors['is_error'] = 1;
      $errors['error_message'] = $message;
      return $errors;
    }

    //delete from financial Type table
    $financialType = new CRM_Financial_DAO_FinancialType( );
    $financialType->id = $financialTypeId;
    $financialType->delete();

    $entityFinancialType = new CRM_Financial_DAO_EntityFinancialAccount( );
    $entityFinancialType->entity_id = $financialTypeId;
    $entityFinancialType->entity_table = 'civicrm_financial_type';
    $entityFinancialType ->delete();
    return FALSE;
  }
  
  /**
   * to fetch financial type having relationship as Income Account is
   *
   *
   * @return array  all financial type with income account is relationship
   * @static
   */
  static function getIncomeFinancialType() { 
    // Financial Type
    $financialType = CRM_Contribute_PseudoConstant::financialType();
    $revenueFinancialType = array();
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    CRM_Core_PseudoConstant::populate( 
      $revenueFinancialType,
      'CRM_Financial_DAO_EntityFinancialAccount',
      $all = True, 
      $retrieve = 'entity_id', 
      $filter = null, 
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

