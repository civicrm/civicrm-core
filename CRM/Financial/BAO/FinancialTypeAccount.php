<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Financial_BAO_FinancialTypeAccount extends CRM_Financial_DAO_EntityFinancialAccount {

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
   * @param array $allValues
   *
   * @return CRM_Contribute_BAO_ContributionType
   */
  public static function retrieve(&$params, &$defaults, &$allValues = array()) {
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
   * Add the financial types.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Reference array contains the id.
   *
   * @return object
   */
  public static function add(&$params, &$ids = NULL) {
    // action is taken depending upon the mode
    $financialTypeAccount = new CRM_Financial_DAO_EntityFinancialAccount();
    if ($params['entity_table'] != 'civicrm_financial_type') {
      $financialTypeAccount->entity_id = $params['entity_id'];
      $financialTypeAccount->entity_table = $params['entity_table'];
      $financialTypeAccount->find(TRUE);
    }
    if (!empty($ids['entityFinancialAccount'])) {
      $financialTypeAccount->id = $ids['entityFinancialAccount'];
      $financialTypeAccount->find(TRUE);
    }
    $financialTypeAccount->copyValues($params);
    self::validateRelationship($financialTypeAccount);
    $financialTypeAccount->save();
    return $financialTypeAccount;
  }

  /**
   * Delete financial Types.
   *
   * @param int $financialTypeAccountId
   * @param int $accountId
   *
   */
  public static function del($financialTypeAccountId, $accountId = NULL) {
    // check if financial type is present
    $check = FALSE;
    $relationValues = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship');

    $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_EntityFinancialAccount', $financialTypeAccountId, 'entity_id');
    // check dependencies
    // FIXME more table containing financial_type_id to come
    $dependency = array(
      array('Contribute', 'Contribution'),
      array('Contribute', 'ContributionPage'),
      array('Member', 'MembershipType'),
      array('Price', 'PriceFieldValue'),
      array('Grant', 'Grant'),
      array('Contribute', 'PremiumsProduct'),
      array('Contribute', 'Product'),
      array('Price', 'LineItem'),
    );

    foreach ($dependency as $name) {
      $daoString = 'CRM_' . $name[0] . '_DAO_' . $name[1];
      $dao = new $daoString();
      $dao->financial_type_id = $financialTypeId;
      if ($dao->find(TRUE)) {
        $check = TRUE;
        break;
      }
    }

    if ($check) {
      if ($name[1] == 'PremiumsProduct' || $name[1] == 'Product') {
        CRM_Core_Session::setStatus(ts('You cannot remove an account with a %1 relationship while the Financial Type is used for a Premium.', array(1 => $relationValues[$financialTypeAccountId])));
      }
      else {
        $accountRelationShipId = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_EntityFinancialAccount', $financialTypeAccountId, 'account_relationship');
        CRM_Core_Session::setStatus(ts('You cannot remove an account with a %1 relationship because it is being referenced by one or more of the following types of records: Contributions, Contribution Pages, or Membership Types. Consider disabling this type instead if you no longer want it used.', array(1 => $relationValues[$accountRelationShipId])), NULL, 'error');
      }
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/financial/financialType/accounts', "reset=1&action=browse&aid={$accountId}"));
    }

    // delete from financial Type table
    $financialType = new CRM_Financial_DAO_EntityFinancialAccount();
    $financialType->id = $financialTypeAccountId;
    $financialType->find(TRUE);
    $financialType->delete();
    CRM_Core_Session::setStatus(ts('Unbalanced transactions may be created if you delete the account of type: %1.', array(1 => $relationValues[$financialType->account_relationship])));
  }

  /**
   * Financial Account for payment instrument.
   *
   * @param int $paymentInstrumentValue
   *   Payment instrument value.
   *
   * @return null|int
   */
  public static function getInstrumentFinancialAccount($paymentInstrumentValue) {
    $paymentInstrument = civicrm_api3('OptionValue', 'getsingle', array(
      'return' => array("id"),
      'value' => $paymentInstrumentValue,
      'option_group_id' => "payment_instrument",
    ));
    $financialAccountId = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount(
      $paymentInstrument['id'],
      NULL,
      'civicrm_option_value'
    );
    return $financialAccountId;
  }

  /**
   * Create default entity financial accounts
   * for financial type
   * CRM-12470
   *
   * @param $financialType
   *
   * @return array
   */
  public static function createDefaultFinancialAccounts($financialType) {
    $titles = array();
    $financialAccountTypeID = CRM_Core_OptionGroup::values('financial_account_type', FALSE, FALSE, FALSE, NULL, 'name');
    $accountRelationship    = CRM_Core_OptionGroup::values('account_relationship', FALSE, FALSE, FALSE, NULL, 'name');

    $relationships = array(
      array_search('Accounts Receivable Account is', $accountRelationship) => array_search('Asset', $financialAccountTypeID),
      array_search('Expense Account is', $accountRelationship) => array_search('Expenses', $financialAccountTypeID),
      array_search('Cost of Sales Account is', $accountRelationship) => array_search('Cost of Sales', $financialAccountTypeID),
      array_search('Income Account is', $accountRelationship) => array_search('Revenue', $financialAccountTypeID),
    );

    $dao = CRM_Core_DAO::executeQuery('SELECT id, financial_account_type_id FROM civicrm_financial_account WHERE name LIKE %1',
      array(1 => array($financialType->name, 'String'))
    );
    $dao->fetch();
    $existingFinancialAccount = array();
    if (!$dao->N) {
      $params = array(
        'name' => $financialType->name,
        'contact_id' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', CRM_Core_Config::domainID(), 'contact_id'),
        'financial_account_type_id' => array_search('Revenue', $financialAccountTypeID),
        'description' => $financialType->description,
        'account_type_code' => 'INC',
        'is_active' => 1,
      );
      $financialAccount = CRM_Financial_BAO_FinancialAccount::add($params);
    }
    else {
      $existingFinancialAccount[$dao->financial_account_type_id] = $dao->id;
    }
    $params = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType->id,
    );
    foreach ($relationships as $key => $value) {
      if (!array_key_exists($value, $existingFinancialAccount)) {
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
        elseif ($accountRelationship[$key] == 'Income Account is' && empty($existingFinancialAccount)) {
          $params['financial_account_id'] = $financialAccount->id;
        }
        else {
          $query = "SELECT id, name FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = {$value}";
          $dao = CRM_Core_DAO::executeQuery($query);
          $dao->fetch();
          $params['financial_account_id'] = $dao->id;
          $titles[] = $dao->name;
        }
      }
      else {
        $params['financial_account_id'] = $existingFinancialAccount[$value];
        $titles[] = $financialType->name;
      }
      $params['account_relationship'] = $key;
      self::add($params);
    }
    if (!empty($existingFinancialAccount)) {
      $titles = array();
    }
    return $titles;
  }

  /**
   * Validate account relationship with financial account type
   *
   * @param obj $financialTypeAccount of CRM_Financial_DAO_EntityFinancialAccount
   *
   * @throws CRM_Core_Exception
   */
  public static function validateRelationship($financialTypeAccount) {
    $financialAccountLinks = CRM_Financial_BAO_FinancialAccount::getfinancialAccountRelations();
    $financialAccountType = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $financialTypeAccount->financial_account_id, 'financial_account_type_id');
    if (CRM_Utils_Array::value($financialTypeAccount->account_relationship, $financialAccountLinks) != $financialAccountType) {
      $accountRelationships = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship');
      $params = array(
        1 => $accountRelationships[$financialTypeAccount->account_relationship],
      );
      throw new CRM_Core_Exception(ts("This financial account cannot have '%1' relationship.", $params));
    }
  }

}
