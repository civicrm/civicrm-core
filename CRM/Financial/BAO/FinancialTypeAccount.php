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
    $relationValues = CRM_Core_PseudoConstant::accountOptionValues('account_relationship');
    
    $financialTypeId = CRM_Core_DAO::getFieldValue( 'CRM_Financial_DAO_EntityFinancialAccount', $financialTypeAccountId, 'entity_id' );
    //check dependencies
    // FIXME more table containing financial_type_id to come
    $dependancy = array(
      array('Contribute', 'Contribution'),
      array('Contribute', 'ContributionPage'),
      array('Member', 'MembershipType'),
      array('Price', 'FieldValue'),
      array('Grant', 'Grant'),
      array('Contribute', 'PremiumsProduct'),
      array('Contribute', 'Product'),
      array('Price', 'LineItem'),
    );

    foreach ($dependancy as $name) {
      eval('$dao = new CRM_' . $name[0] . '_DAO_' . $name[1] . '();');
      $dao->financial_type_id = $financialTypeId;
      if ($dao->find(true)) {
        $check = true;
        break;
      }
    }

    if ($check) {
      if ($name[1] == 'PremiumsProduct' || $name[1] == 'Product') {
        CRM_Core_Session::setStatus( ts('You cannot remove an account with a '.$relationValues[$financialTypeAccountId].'relationship while the Financial Type is used for a Premium.'));
      }
      else {
        CRM_Core_Session::setStatus( ts('You cannot remove an account with a '.$relationValues[$financialTypeAccountId].'relationship because it is being referenced by one or more of the following types of records: Contributions, Contribution Pages, or Membership Types. Consider disabling this type instead if you no longer want it used.') );
      }
      return CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/admin/financial/financialType/accounts', "reset=1&action=browse&aid={$accountId}" ));
    }
    
    //delete from financial Type table
    $financialType = new CRM_Financial_DAO_EntityFinancialAccount( );
    $financialType->id = $financialTypeAccountId;
    $financialType->delete();
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
}

