<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

class CRM_Financial_BAO_FinancialAccount extends CRM_Financial_DAO_FinancialAccount {

  /**
   * static holder for the default LT
   */
  static $_defaultContributionType = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
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
   * @return object CRM_Contribute_BAO_FinancialAccount object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $financialAccount = new CRM_Financial_DAO_FinancialAccount();
    $financialAccount->copyValues($params);
    if ($financialAccount->find(TRUE)) {
      CRM_Core_DAO::storeValues($financialAccount, $defaults);
      return $financialAccount;
    }
    return NULL;
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
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_FinancialAccount', $id, 'is_active', $is_active);
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
  static function add(&$params, &$ids = array()) {
    if(empty($params['id'])) {
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['is_deductible'] = CRM_Utils_Array::value('is_deductible', $params, FALSE);
      $params['is_tax'] = CRM_Utils_Array::value('is_tax', $params, FALSE);
      $params['is_header_account'] = CRM_Utils_Array::value('is_header_account', $params, FALSE);
      $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);
    }
    if (!empty($params['is_default'])) {
      $query = 'UPDATE civicrm_financial_account SET is_default = 0 WHERE financial_account_type_id = %1';
      $queryParams = array(1 => array($params['financial_account_type_id'], 'Integer'));
      CRM_Core_DAO::executeQuery($query, $queryParams);
    }

    // action is taken depending upon the mode
    $financialAccount = new CRM_Financial_DAO_FinancialAccount();
    $financialAccount->copyValues($params);
    if (!empty($ids['contributionType'])) {
      $financialAccount->id = CRM_Utils_Array::value('contributionType', $ids);
    }
    $financialAccount->save();
    return $financialAccount;
  }

  /**
   * Function to delete financial Types
   *
   * @param int $financialAccountId
   * @static
   */
  static function del($financialAccountId) {
    //checking if financial type is present
    $check = FALSE;

    //check dependencies
    $dependancy = array(
      array('Core', 'FinancialTrxn', 'to_financial_account_id'),
      array('Financial', 'FinancialTypeAccount', 'financial_account_id'),
      );
    foreach ($dependancy as $name) {
      require_once (str_replace('_', DIRECTORY_SEPARATOR, "CRM_" . $name[0] . "_BAO_" . $name[1]) . ".php");
      $className = "CRM_{$name[0]}_BAO_{$name[1]}";
      $bao = new $className();
      $bao->$name[2] = $financialAccountId;
      if ($bao->find(TRUE)) {
        $check = TRUE;
      }
    }

    if ($check) {
      CRM_Core_Session::setStatus(ts('This financial account cannot be deleted since it is being used as a header account. Please remove it from being a header account before trying to delete it again.'));
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/financial/financialAccount', "reset=1&action=browse"));
    }

    //delete from financial Type table
    $financialAccount = new CRM_Financial_DAO_FinancialAccount();
    $financialAccount->id = $financialAccountId;
    $financialAccount->delete();
  }

  /**
   * get accounting code for a financial type with account relation Income Account is
   *
   * @financialTypeId int      Financial Type Id
   *
   * @param $financialTypeId
   *
   * @return accounting code
   * @static
   */
  static function getAccountingCode($financialTypeId) {
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    $query = "SELECT cfa.accounting_code
FROM civicrm_financial_type cft
LEFT JOIN civicrm_entity_financial_account cefa ON cefa.entity_id = cft.id AND cefa.entity_table = 'civicrm_financial_type'
LEFT JOIN  civicrm_financial_account cfa ON cefa.financial_account_id = cfa.id
WHERE cft.id = %1
  AND account_relationship = %2";
    $params = array(
      1 => array($financialTypeId, 'Integer'),
      2 => array($relationTypeId, 'Integer'),
    );
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * get AR account
   *
   * @param $financialAccountId financial account id
   *
   * @param $financialAccountTypeId financial account type id
   *
   * @param \account|string $accountTypeCode account type code
   *
   * @return integer count
   * @static
   */
  static function getARAccounts($financialAccountId, $financialAccountTypeId = NULL, $accountTypeCode = 'ar') {
    if (!$financialAccountTypeId) {
      $financialAccountType = CRM_Core_PseudoConstant::accountOptionValues('financial_account_type');
      $financialAccountTypeId = array_search('Asset', $financialAccountType);
    }
    $query = "SELECT count(id) FROM civicrm_financial_account WHERE financial_account_type_id = %1 AND LCASE(account_type_code) = %2
      AND id != %3 AND is_active = 1;";
    $params = array(
      1 => array($financialAccountTypeId, 'Integer'),
      2 => array(strtolower($accountTypeCode), 'String'),
      3 => array($financialAccountId, 'Integer'),
    );
     return CRM_Core_DAO::singleValueQuery($query, $params);
  }
}

