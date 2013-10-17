<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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

class CRM_Financial_BAO_FinancialTypeAccount extends CRM_Financial_DAO_EntityFinancialAccount {

  /**
   * class constructor
   */
  function __construct( ) {
    parent::__construct( );
  }

  /**
   * financial account
   * @var array
   * @static
   */
  private static $financialAccount;

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
  static function retrieve(&$params, &$defaults, &$allValues = array()) {
    $financialTypeAccount = new CRM_Financial_DAO_EntityFinancialAccount();
    $financialTypeAccount->copyValues($params);
    $financialTypeAccount->find();
    while ($financialTypeAccount->fetch()) {
      CRM_Core_DAO::storeValues($financialTypeAccount, $defaults);
      $allValues[] = $defaults;
    }
    return $defaults;
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
  static function add(&$params, &$ids = NULL) {
    // action is taken depending upon the mode
    $financialTypeAccount = new CRM_Financial_DAO_EntityFinancialAccount();
    if ($params['entity_table'] != 'civicrm_financial_type') {
      $financialTypeAccount->entity_id = $params['entity_id'];
      $financialTypeAccount->entity_table = $params['entity_table'];
      $financialTypeAccount->find(TRUE);
    }
    else {
      $financialTypeAccount->id = CRM_Utils_Array::value('entityFinancialAccount', $ids);
    }
    if (CRM_Utils_Array::value('entityFinancialAccount', $ids)) {
      $financialTypeAccount->id = $ids['entityFinancialAccount'];
    }
    $financialTypeAccount->copyValues($params);
    $financialTypeAccount->save();
    return $financialTypeAccount;
  }

  /**
   * Function to delete financial Types
   *
   * @param int $contributionTypeId
   * @static
   */
  static function del($financialTypeAccountId, $accountId = null) {
    //checking if financial type is present
    $check = false;
    $relationValues = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship');

    $financialTypeId = CRM_Core_DAO::getFieldValue( 'CRM_Financial_DAO_EntityFinancialAccount', $financialTypeAccountId, 'entity_id' );
    //check dependencies
    // FIXME more table containing financial_type_id to come
    $dependancy = array(
      array('Contribute', 'Contribution'),
      array('Contribute', 'ContributionPage'),
      array('Member', 'MembershipType'),
      array('Price', 'PriceFieldValue'),
      array('Grant', 'Grant'),
      array('Contribute', 'PremiumsProduct'),
      array('Contribute', 'Product'),
      array('Price', 'LineItem'),
    );

    foreach ($dependancy as $name) {
      $daoString = 'CRM_' . $name[0] . '_DAO_' . $name[1];
      $dao = new $daoString();
      $dao->financial_type_id = $financialTypeId;
      if ($dao->find(true)) {
        $check = true;
        break;
      }
    }

    if ($check) {
      if ($name[1] == 'PremiumsProduct' || $name[1] == 'Product') {
        CRM_Core_Session::setStatus(ts('You cannot remove an account with a %1 relationship while the Financial Type is used for a Premium.', array(1 => $relationValues[$financialTypeAccountId])));
      }
      else {
        $accountRelationShipId = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_EntityFinancialAccount', $financialTypeAccountId, 'account_relationship');
        CRM_Core_Session::setStatus(ts('You cannot remove an account with a %1 relationship because it is being referenced by one or more of the following types of records: Contributions, Contribution Pages, or Membership Types. Consider disabling this type instead if you no longer want it used.', array(1 => $relationValues[$accountRelationShipId])), NUll, 'error');
      }
      return CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/admin/financial/financialType/accounts', "reset=1&action=browse&aid={$accountId}" ));
    }

    //delete from financial Type table
    $financialType = new CRM_Financial_DAO_EntityFinancialAccount( );
    $financialType->id = $financialTypeAccountId;
    $financialType->find(TRUE);
    $financialType->delete();
    CRM_Core_Session::setStatus(ts('Unbalanced transactions may be created if you delete the account of type: %1.', array(1 => $relationValues[$financialType->account_relationship])));
  }

  /**
   * Function to get Financial Account Name
   *
   * @param int $entityId
   *
   * @param string $entityTable
   *
   * @param string $columnName Column to fetch
   * @static
   */
  static function getFinancialAccount($entityId, $entityTable, $columnName = 'name') {
    $join = $columnName == 'name' ? 'LEFT JOIN civicrm_financial_account ON civicrm_entity_financial_account.financial_account_id = civicrm_financial_account.id' : NULL;
    $query = "
SELECT {$columnName}
FROM civicrm_entity_financial_account
{$join}
WHERE entity_table = %1
AND entity_id = %2";

    $params = array(
      1 => array($entityTable, 'String'),
      2 => array($entityId, 'Integer'),
    );
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Function to financial Account for payment instrument
   *
   * @param int $paymentInstrumentValue payment instrument value
   *
   * @static
   */
  static function getInstrumentFinancialAccount($paymentInstrumentValue = NULL) {
    if (!self::$financialAccount) {
      $query = "SELECT ceft.financial_account_id, cov.value
FROM civicrm_entity_financial_account ceft
INNER JOIN civicrm_option_value cov ON cov.id = ceft.entity_id AND ceft.entity_table = 'civicrm_option_value'
INNER JOIN civicrm_option_group cog ON cog.id = cov.option_group_id
WHERE cog.name = 'payment_instrument' ";

      if ($paymentInstrumentValue) {
        $query .= " AND cov.value = '{$paymentInstrumentValue}' ";
        return CRM_Core_DAO::singleValueQuery($query);
      }
      else {
        $result = CRM_Core_DAO::executeQuery($query);
        while ($result->fetch()) {
          self::$financialAccount[$result->value] = $result->financial_account_id;
        }
        return self::$financialAccount;
      }
    }

    return $paymentInstrumentValue ? self::$financialAccount[$paymentInstrumentValue] : self::$financialAccount;
  }

  /**
   * Function to create default entity financial accounts
   * for financial type
   * CRM-12470
   *
   * @param int $financialTypeId financial type id
   *
   * @static
   */
  static function createDefaultFinancialAccounts($financialType) {
    $titles = array();
    $financialAccountTypeID = CRM_Core_PseudoConstant::accountOptionValues('financial_account_type');
    $accountRelationship = CRM_Core_PseudoConstant::accountOptionValues('account_relationship');
    $relationships = array (
      array_search('Accounts Receivable Account is', $accountRelationship) => array_search('Asset', $financialAccountTypeID),
      array_search('Expense Account is', $accountRelationship) => array_search('Expenses', $financialAccountTypeID),
      array_search('Cost of Sales Account is', $accountRelationship) => array_search('Cost of Sales', $financialAccountTypeID),
      array_search('Income Account is', $accountRelationship) => array_search('Revenue', $financialAccountTypeID),
    );
    $params = array(
      'name' => $financialType->name,
      'contact_id' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', CRM_Core_Config::domainID(), 'contact_id'),
      'financial_account_type_id' => array_search('Revenue', $financialAccountTypeID),
      'description' => $financialType->description,
      'account_type_code' => 'INC',
      'is_active' => 1,
    );
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params, CRM_Core_DAO::$_nullArray);
    $params = array (
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType->id,
    );
    foreach ($relationships as $key => $value) {
      if ($accountRelationship[$key] == 'Accounts Receivable Account is') {
        $params['financial_account_id'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', 'Accounts Receivable', 'id', 'name');
        if (!empty($params['financial_account_id'])) {
          $titles[] = 'Accounts Receivable';
        }
        else {
          $query = "SELECT financial_account_id, name FROM civicrm_entity_financial_account
            LEFT JOIN civicrm_financial_account ON civicrm_financial_account.id = civicrm_entity_financial_account.financial_account_id
            WHERE account_relationship = {$key} AND entity_table = 'civicrm_financial_type' LIMIT 1";
          $dao = CRM_Core_DAO::executeQuery($query);
          $dao->fetch();
          $params['financial_account_id'] = $dao->financial_account_id;
          $titles[] = $dao->name;
        }
      }
      elseif ($accountRelationship[$key] == 'Income Account is') {
        $params['financial_account_id'] = $financialAccount->id;
      }
      else {
        $query = "SELECT id, name FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = {$value}";
        $dao = CRM_Core_DAO::executeQuery($query);
        $dao->fetch();
        $params['financial_account_id'] = $dao->id;
        $titles[] = $dao->name;
      }
      $params['account_relationship'] = $key;
      self::add($params);
    }
    return $titles;
  }
}

