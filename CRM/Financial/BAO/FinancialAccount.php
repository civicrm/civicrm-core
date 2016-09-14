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
class CRM_Financial_BAO_FinancialAccount extends CRM_Financial_DAO_FinancialAccount {

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
   * @return CRM_Financial_BAO_FinancialAccount
   */
  public static function retrieve(&$params, &$defaults) {
    $financialAccount = new CRM_Financial_DAO_FinancialAccount();
    $financialAccount->copyValues($params);
    if ($financialAccount->find(TRUE)) {
      CRM_Core_DAO::storeValues($financialAccount, $defaults);
      return $financialAccount;
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
   * @return CRM_Core_DAO|null
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_FinancialAccount', $id, 'is_active', $is_active);
  }

  /**
   * Add the financial types.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   * @return CRM_Financial_DAO_FinancialAccount
   */
  public static function add(&$params) {
    if (empty($params['id'])) {
      $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
      $params['is_deductible'] = CRM_Utils_Array::value('is_deductible', $params, FALSE);
      $params['is_tax'] = CRM_Utils_Array::value('is_tax', $params, FALSE);
      $params['is_header_account'] = CRM_Utils_Array::value('is_header_account', $params, FALSE);
      $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);
    }
    if (!empty($params['id'])
      && !empty($params['financial_account_type_id'])
      && CRM_Financial_BAO_FinancialAccount::validateFinancialAccount(
        $params['id'],
        $params['financial_account_type_id']
      )
    ) {
      throw new CRM_Core_Exception(ts('You cannot change the account type since this financial account refers to a financial item having an account type of Revenue/Liability.'));
    }
    if (!empty($params['is_default'])) {
      $query = 'UPDATE civicrm_financial_account SET is_default = 0 WHERE financial_account_type_id = %1';
      $queryParams = array(1 => array($params['financial_account_type_id'], 'Integer'));
      CRM_Core_DAO::executeQuery($query, $queryParams);
    }

    // action is taken depending upon the mode
    $financialAccount = new CRM_Financial_DAO_FinancialAccount();
    if (!empty($params['id'])) {
      $financialAccount->id = $params['id'];
      $financialAccount->find(TRUE);
    }

    $financialAccount->copyValues($params);
    //CRM-16189
    $accountType = CRM_Core_PseudoConstant::accountOptionValues(
      'financial_account_type',
      NULL,
      " AND v.name IN ('Liability', 'Asset') "
    );
    if (empty($params['id']) && !CRM_Utils_Array::value($financialAccount->financial_account_type_id, $accountType)) {
      $financialAccount->opening_balance = $financialAccount->current_period_opening_balance = '0.00';
    }
    $financialAccount->save();
    return $financialAccount;
  }

  /**
   * Delete financial Types.
   *
   * @param int $financialAccountId
   */
  public static function del($financialAccountId) {
    // checking if financial type is present
    $check = FALSE;

    //check dependencies
    $dependency = array(
      array('Core', 'FinancialTrxn', 'to_financial_account_id'),
      array('Financial', 'FinancialTypeAccount', 'financial_account_id'),
    );
    foreach ($dependency as $name) {
      require_once str_replace('_', DIRECTORY_SEPARATOR, "CRM_" . $name[0] . "_BAO_" . $name[1]) . ".php";
      $className = "CRM_{$name[0]}_BAO_{$name[1]}";
      $bao = new $className();
      $bao->{$name[2]} = $financialAccountId;
      if ($bao->find(TRUE)) {
        $check = TRUE;
      }
    }

    if ($check) {
      CRM_Core_Session::setStatus(ts('This financial account cannot be deleted since it is being used as a header account. Please remove it from being a header account before trying to delete it again.'));
      return CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/financial/financialAccount', "reset=1&action=browse"));
    }

    // delete from financial Type table
    $financialAccount = new CRM_Financial_DAO_FinancialAccount();
    $financialAccount->id = $financialAccountId;
    $financialAccount->delete();
  }

  /**
   * Get accounting code for a financial type with account relation Income Account is.
   *
   * @param int $financialTypeId
   *
   * @return int
   *   accounting code
   */
  public static function getAccountingCode($financialTypeId) {
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
   * Get AR account.
   *
   * @param $financialAccountId
   *   Financial account id.
   *
   * @param $financialAccountTypeId
   *   Financial account type id.
   *
   * @param string $accountTypeCode
   *   account type code
   *
   * @return int
   *   count
   */
  public static function getARAccounts($financialAccountId, $financialAccountTypeId = NULL, $accountTypeCode = 'ar') {
    if (!$financialAccountTypeId) {
      $financialAccountTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Asset' "));
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

  /**
   * Get the Financial Account for a Financial Type Relationship Combo.
   *
   * Note that some relationships are optionally configured - so far
   * Chargeback and Credit / Contra. Since these are the only 2 currently Income
   * is an appropriate fallback. In future it might make sense to extend the logic.
   *
   * Note that we avoid the CRM_Core_PseudoConstant function as it stores one
   * account per financial type and is unreliable.
   *
   * @param int $financialTypeID
   *
   * @param string $relationshipType
   *
   * @return int
   */
  public static function getFinancialAccountForFinancialTypeByRelationship($financialTypeID, $relationshipType) {
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE '{$relationshipType}' "));

    if (!isset(Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$relationTypeId])) {
      $accounts = civicrm_api3('EntityFinancialAccount', 'get', array(
        'entity_id' => $financialTypeID,
        'entity_table' => 'civicrm_financial_type',
      ));

      foreach ($accounts['values'] as $account) {
        Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$account['account_relationship']] = $account['financial_account_id'];
      }

      $accountRelationships = CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL);

      $incomeAccountRelationshipID = array_search('Income Account is', $accountRelationships);
      $incomeAccountFinancialAccountID = Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$incomeAccountRelationshipID];

      foreach (array('Chargeback Account is', 'Credit/Contra Revenue Account is') as $optionalAccountRelationship) {

        $accountRelationshipID = array_search($optionalAccountRelationship, $accountRelationships);
        if (empty(Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$accountRelationshipID])) {
          Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$accountRelationshipID] = $incomeAccountFinancialAccountID;
        }
      }

    }
    return Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$relationTypeId];
  }

  /**
   * Get Financial Account type relations.
   *
   * @param $flip bool
   *
   * @return array
   *
   */
  public static function getfinancialAccountRelations($flip = FALSE) {
    $params = array('labelColumn' => 'name');
    $financialAccountType = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id', $params);
    $accountRelationships = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship', $params);
    $Links = array(
      'Expense Account is' => 'Expenses',
      'Accounts Receivable Account is' => 'Asset',
      'Income Account is' => 'Revenue',
      'Asset Account is' => 'Asset',
      'Cost of Sales Account is' => 'Cost of Sales',
      'Premiums Inventory Account is' => 'Asset',
      'Discounts Account is' => 'Revenue',
      'Sales Tax Account is' => 'Liability',
      'Deferred Revenue Account is' => 'Liability',
    );
    if (!$flip) {
      foreach ($Links as $accountRelation => $accountType) {
        $financialAccountLinks[array_search($accountRelation, $accountRelationships)] = array_search($accountType, $financialAccountType);
      }
    }
    else {
      foreach ($Links as $accountRelation => $accountType) {
        $financialAccountLinks[array_search($accountType, $financialAccountType)][] = array_search($accountRelation, $accountRelationships);
      }
    }
    return $financialAccountLinks;
  }

  /**
   * Get Deferred Financial type.
   *
   * @return array
   *
   */
  public static function getDeferredFinancialType() {
    $deferredFinancialType = array();
    $query = "SELECT ce.entity_id, cft.name FROM civicrm_entity_financial_account ce
INNER JOIN civicrm_financial_type cft ON ce.entity_id = cft.id
WHERE ce.entity_table = 'civicrm_financial_type' AND ce.account_relationship = %1 AND cft.is_active = 1";
    $deferredAccountRel = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Deferred Revenue Account is' "));
    $queryParams = array(1 => array($deferredAccountRel, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $deferredFinancialType[$dao->entity_id] = $dao->name;
    }
    return $deferredFinancialType;
  }

  /**
   * Check if financial account is referenced by financial item.
   *
   * @param int $financialAccountId
   *
   * @param int $financialAccountTypeID
   *
   * @return bool
   *
   */
  public static function validateFinancialAccount($financialAccountId, $financialAccountTypeID = NULL) {
    $sql = "SELECT f.financial_account_type_id FROM civicrm_financial_account f
INNER JOIN civicrm_financial_item fi ON fi.financial_account_id = f.id
WHERE f.id = %1 AND f.financial_account_type_id IN (%2)
LIMIT 1";
    $params = array('labelColumn' => 'name');
    $financialAccountType = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id', $params);
    $params = array(
      1 => array($financialAccountId, 'Integer'),
      2 => array(
        implode(',',
          array(
            array_search('Revenue', $financialAccountType),
            array_search('Liability', $financialAccountType),
          )
        ),
        'Text',
      ),
    );
    $result = CRM_Core_DAO::singleValueQuery($sql, $params);
    if ($result && $result != $financialAccountTypeID) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate Financial Type has Deferred Revenue account relationship
   * with Financial Account.
   *
   * @param array $params
   *   Holds submitted formvalues and params from api for updating/adding contribution.
   *
   * @param int $contributionID
   *   Contribution ID
   *
   * @param array $priceSetFields
   *   Array of price fields of a price set.
   *
   * @return bool
   *
   */
  public static function checkFinancialTypeHasDeferred($params, $contributionID = NULL, $priceSetFields = NULL) {
    if (!CRM_Contribute_BAO_Contribution::checkContributeSettings('deferred_revenue_enabled')) {
      return FALSE;
    }
    $recognitionDate = CRM_Utils_Array::value('revenue_recognition_date', $params);
    if (!(!CRM_Utils_System::isNull($recognitionDate)
      || ($contributionID && isset($params['prevContribution'])
      && !CRM_Utils_System::isNull($params['prevContribution']->revenue_recognition_date)))
    ) {
      return FALSE;
    }

    $lineItems = CRM_Utils_Array::value('line_item', $params);
    $financialTypeID = CRM_Utils_Array::value('financial_type_id', $params);
    if (!$financialTypeID) {
      $financialTypeID = $params['prevContribution']->financial_type_id;
    }
    if (($contributionID || !empty($params['price_set_id'])) && empty($lineItems)) {
      if (!$contributionID) {
        CRM_Price_BAO_PriceSet::processAmount($priceSetFields,
        $params, $items);
      }
      else {
        $items = CRM_Price_BAO_LineItem::getLineItems($contributionID, 'contribution', TRUE, TRUE, TRUE);
      }
      if (!empty($items)) {
        $lineItems[] = $items;
      }
    }
    $deferredFinancialType = self::getDeferredFinancialType();
    $isError = FALSE;
    if (!empty($lineItems)) {
      foreach ($lineItems as $lineItem) {
        foreach ($lineItem as $items) {
          if (!array_key_exists($items['financial_type_id'], $deferredFinancialType)) {
            $isError = TRUE;
          }
        }
      }
    }
    elseif (!array_key_exists($financialTypeID, $deferredFinancialType)) {
      $isError = TRUE;
    }

    if ($isError) {
      $error = ts('Revenue recognition date can only be specified if the financial type selected has a deferred revenue account configured. Please have an administrator set up the deferred revenue account at Administer > CiviContribute > Financial Accounts, then configure it for financial types at Administer > CiviContribution > Financial Types, Accounts');
      throw new CRM_Core_Exception($error);
    }
    return $isError;
  }

  /**
   * Check if financial type has Deferred Revenue Account is relationship
   * with Financial Account.
   *
   * @param int $financialTypeId
   *   Financial Type Id.
   *
   * @param int $entityID
   *   Holds id for PriceSet/PriceField/PriceFieldValue.
   *
   * @param string $entity
   *   Entity like PriceSet/PriceField/PriceFieldValue.
   *
   * @return bool
   *
   */
  public static function validateFinancialType($financialTypeId, $entityID = NULL, $entity = NULL) {
    if (!CRM_Contribute_BAO_Contribution::checkContributeSettings('deferred_revenue_enabled')) {
      return FALSE;
    }
    if ($entityID) {
      $query = ' SELECT ps.extends FROM civicrm_price_set ps';
      $params = array(
        1 => array('ps', 'Text'),
        2 => array($entityID, 'Integer'),
      );
      if ($entity == 'PriceField') {
        $params[1] = array('pf', 'Text');
        $query .= ' INNER JOIN civicrm_price_field pf ON pf.price_set_id = ps.id ';
      }
      $query .= ' WHERE %1.id = %2';
      $extends = CRM_Core_DAO::singleValueQuery($query, $params);
      $extends = explode('', $extends);
      if (!(in_array(CRM_Core_Component::getComponentID('CiviEvent'), $extends)
        || in_array(CRM_Core_Component::getComponentID('CiviMember'), $extends))
      ) {
        return FALSE;
      }
    }
    $deferredFinancialType = self::getDeferredFinancialType();
    if (!array_key_exists($financialTypeId, $deferredFinancialType)) {
      throw new CRM_Core_Exception(ts('Deferred revenue account is not configured for selected financial type. Please have an administrator set up the deferred revenue account at Administer > CiviContribute > Financial Accounts, then configure it for financial types at Administer > CiviContribution > Financial Types, Accounts'));
    }
    return FALSE;
  }

  /**
   * Validate if Deferred Account is set for Financial Type
   * when Deferred Revenue is enabled
   *
   * @return string
   *
   */
  public static function validateTogglingDeferredRevenue() {
    $deferredFinancialType = self::getDeferredFinancialType();
    $message = ts('Before Deferred Revenue can be enabled, a Deferred Revenue Account relationship must be defined for all financial types currently used for Memberships and Events, including
<ul>
<li>those specified for each membership type at Admin > CiviMember > Membership Types, edit</li>
<li>on the Fees tab when managing events</li>
<li>the default financial type associated with a membership or event price set</li>
<li>the financial type for a membership price set field option when the membership type is non-blank</li>
<li>as the financial type for a price set field with participant count > 0</li>
<li>as the financial type for a price set field option with participant count > 0</li>
</ul>
In other words, please create deferred revenue accounts at Administer > CiviContribute > Financial Accounts, then configure them for the following financial types at Administer > CiviContribute > Financial Types, accounts:');
    $tables = array(
      'civicrm_membership_type',
      'civicrm_event',
      'civicrm_price_set',
      'civicrm_price_field_value',
    );
    $params[2] = array('', 'Text');
    if (!empty($deferredFinancialType)) {
      $params[2] = array(' AND financial_type_id NOT IN (' . implode(',', array_keys($deferredFinancialType)) . ') ', 'Text');
    }
    $query_1 = 'SELECT %5.id FROM %4 WHERE %5.is_active = 1';
    $query_2 = $query_1 . ' %2';
    foreach ($tables as $table) {
      $params[4] = array($table, 'Text');
      $params[5] = array($table, 'Text');
      $dao = CRM_Core_DAO::executeQuery($query_1, $params);
      if ($dao->N) {
        if (in_array($table, array('civicrm_price_set', 'civicrm_price_field_value'))) {
          $query_2 .= " AND civicrm_price_set.name NOT IN ('default_contribution_amount', 'default_membership_type_amount') AND (civicrm_price_set.extends LIKE '%1%' OR civicrm_price_set.extends like '3')";
          if ($table == 'civicrm_price_field_value') {
            $string = $table . ' INNER JOIN civicrm_price_field ON civicrm_price_field.id = civicrm_price_field_value.price_field_id INNER JOIN civicrm_price_set ON civicrm_price_set.id = civicrm_price_field.price_set_id ';
            $params[4] = array($string, 'Text');
            $params[2][0] = str_replace('financial_type_id', "{$table}.financial_type_id", $params[2][0]);
          }
        }
        $dao = CRM_Core_DAO::executeQuery($query_2, $params);
        if ($dao->N) {
          $message .= '<ul>';
          $financialTypes = CRM_Contribute_PseudoConstant::financialType();
          $financialTypes = array_diff_key($financialTypes, $deferredFinancialType);
          foreach ($financialTypes as $financialType) {
            $message .= "<li>{$financialType}</li>";
          }
          $message .= '</ul>';
          return $message;
        }
      }
    }
    return NULL;
  }

  /**
   * Retrieve all Deferred Financial Accounts.
   *
   *
   * @return array of Deferred Financial Account
   *
   */
  public static function getAllDeferredFinancialAccount() {
    $query = "SELECT cfa.id, cfa.name FROM civicrm_entity_financial_account ce
INNER JOIN civicrm_financial_account cfa ON ce.financial_account_id = cfa.id
WHERE `entity_table` = 'civicrm_financial_type' AND cfa.is_active = 1 AND ce.account_relationship = %1 GROUP BY cfa.id";
    $deferredAccountRel = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Deferred Revenue Account is' "));
    $queryParams = array(1 => array($deferredAccountRel, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $financialAccount = array();
    while ($dao->fetch()) {
      $financialAccount[$dao->id] = $dao->name;
    }
    return $financialAccount;
  }

}
