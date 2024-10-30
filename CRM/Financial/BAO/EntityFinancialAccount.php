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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Financial_BAO_EntityFinancialAccount extends CRM_Financial_DAO_EntityFinancialAccount {

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @param array $allValues
   * @deprecated
   * @return array
   */
  public static function retrieve(&$params, &$defaults = [], &$allValues = []) {
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
   *   Reference array contains one possible value
   *   - entityFinancialAccount.
   *
   * @return CRM_Financial_DAO_EntityFinancialAccount
   * @deprecated
   * @throws \CRM_Core_Exception
   */
  public static function add(&$params, $ids = NULL) {
    // action is taken depending upon the mode
    $financialTypeAccount = new CRM_Financial_DAO_EntityFinancialAccount();
    if ($params['entity_table'] !== 'civicrm_financial_type') {
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
    unset(Civi::$statics['CRM_Core_PseudoConstant']['taxRates']);
    return $financialTypeAccount;
  }

  /**
   * Delete financial Types.
   *
   * @param int $financialTypeAccountId
   * @param int $accountId  (not used)
   *
   * @throws \CRM_Core_Exception
   * @deprecated
   */
  public static function del($financialTypeAccountId, $accountId = NULL) {
    CRM_Core_Error::deprecatedFunctionWarning('api');
    static::deleteRecord(['id' => $financialTypeAccountId]);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete' && $event->id) {
      $financialTypeAccountId = $event->id;

      // check if financial type is present
      $check = FALSE;
      $relationValues = CRM_Financial_DAO_EntityFinancialAccount::buildOptions('account_relationship');

      $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_EntityFinancialAccount', $financialTypeAccountId, 'entity_id');
      // check dependencies
      // FIXME hardcoded list = bad
      $dependency = [
        ['Contribute', 'Contribution'],
        ['Contribute', 'ContributionPage'],
        ['Member', 'MembershipType'],
        ['Price', 'PriceFieldValue'],
        ['Grant', 'Grant'],
        ['Contribute', 'PremiumsProduct'],
        ['Contribute', 'Product'],
        ['Price', 'LineItem'],
      ];

      foreach ($dependency as $name) {
        $daoString = 'CRM_' . $name[0] . '_DAO_' . $name[1];
        if (class_exists($daoString)) {
          /** @var \CRM_Core_DAO $dao */
          $dao = new $daoString();
          $dao->financial_type_id = $financialTypeId;
          if ($dao->find(TRUE)) {
            $check = TRUE;
            break;
          }
        }
      }

      if ($check) {
        if ($name[1] === 'PremiumsProduct' || $name[1] === 'Product') {
          throw new \CRM_Core_Exception(ts('You cannot remove an account with a %1 relationship while the Financial Type is used for a Premium.', [1 => $relationValues[$financialTypeAccountId]]));
        }
        else {
          $accountRelationShipId = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_EntityFinancialAccount', $financialTypeAccountId, 'account_relationship');
          throw new \CRM_Core_Exception(ts('You cannot remove an account with a %1 relationship because it is being referenced by one or more of the following types of records: Contributions, Contribution Pages, or Membership Types. Consider disabling this type instead if you no longer want it used.', [1 => $relationValues[$accountRelationShipId]]), NULL, 'error');
        }
      }
    }
  }

  /**
   * Financial Account for payment instrument.
   *
   * @param int $paymentInstrumentValue
   *   Payment instrument value.
   *
   * @return null|int
   * @throws \CRM_Core_Exception
   */
  public static function getInstrumentFinancialAccount($paymentInstrumentValue) {
    if (!isset(\Civi::$statics[__CLASS__]['instrument_financial_accounts'][$paymentInstrumentValue])) {
      $paymentInstrumentID = civicrm_api3('OptionValue', 'getvalue', [
        'return' => 'id',
        'value' => $paymentInstrumentValue,
        'option_group_id' => "payment_instrument",
      ]);
      $accounts = civicrm_api3('EntityFinancialAccount', 'get', [
        'return' => 'financial_account_id',
        'entity_table' => 'civicrm_option_value',
        'entity_id' => $paymentInstrumentID,
        'options' => ['limit' => 1],
        'sequential' => 1,
      ])['values'];
      if (empty($accounts)) {
        \Civi::$statics[__CLASS__]['instrument_financial_accounts'][$paymentInstrumentValue] = NULL;
      }
      else {
        \Civi::$statics[__CLASS__]['instrument_financial_accounts'][$paymentInstrumentValue] = $accounts[0]['financial_account_id'];
      }
    }
    return \Civi::$statics[__CLASS__]['instrument_financial_accounts'][$paymentInstrumentValue];
  }

  /**
   * Create default entity financial accounts
   * for financial type
   * @see https://issues.civicrm.org/jira/browse/CRM-12470
   *
   * @param $financialType
   *
   * @return array
   */
  public static function createDefaultFinancialAccounts($financialType) {
    $titles = [];
    $financialAccountTypeID = CRM_Core_OptionGroup::values('financial_account_type', FALSE, FALSE, FALSE, NULL, 'name');
    $accountRelationship    = CRM_Core_OptionGroup::values('account_relationship', FALSE, FALSE, FALSE, NULL, 'name');

    $relationships = [
      array_search('Accounts Receivable Account is', $accountRelationship) => array_search('Asset', $financialAccountTypeID),
      array_search('Expense Account is', $accountRelationship) => array_search('Expenses', $financialAccountTypeID),
      array_search('Cost of Sales Account is', $accountRelationship) => array_search('Cost of Sales', $financialAccountTypeID),
      array_search('Income Account is', $accountRelationship) => array_search('Revenue', $financialAccountTypeID),
    ];

    $dao = CRM_Core_DAO::executeQuery('SELECT id, financial_account_type_id FROM civicrm_financial_account WHERE name LIKE %1',
      [1 => [$financialType->name, 'String']]
    );
    $dao->fetch();
    $existingFinancialAccount = [];
    if (!$dao->N) {
      $params = [
        'label' => $financialType->label,
        'contact_id' => CRM_Core_BAO_Domain::getDomain()->contact_id,
        'financial_account_type_id' => array_search('Revenue', $financialAccountTypeID),
        'description' => $financialType->description,
        'account_type_code' => 'INC',
        'is_active' => 1,
      ];
      $financialAccount = CRM_Financial_BAO_FinancialAccount::writeRecord($params);
    }
    else {
      $existingFinancialAccount[$dao->financial_account_type_id] = $dao->id;
    }
    $params = [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType->id,
    ];
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
      $titles = [];
    }
    return $titles;
  }

  /**
   * Validate account relationship with financial account type
   *
   * @param CRM_Financial_DAO_EntityFinancialAccount $financialTypeAccount of CRM_Financial_DAO_EntityFinancialAccount
   *
   * @throws CRM_Core_Exception
   */
  public static function validateRelationship($financialTypeAccount) {
    $financialAccountLinks = CRM_Financial_BAO_FinancialAccount::getfinancialAccountRelations();
    $financialAccountType = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $financialTypeAccount->financial_account_id, 'financial_account_type_id');
    if (($financialAccountLinks[$financialTypeAccount->account_relationship] ?? NULL) != $financialAccountType) {
      $accountRelationships = CRM_Financial_DAO_EntityFinancialAccount::buildOptions('account_relationship');
      throw new CRM_Core_Exception(ts("This financial account cannot have '%1' relationship.", [1 => $accountRelationships[$financialTypeAccount->account_relationship]]));
    }
  }

  /**
   * Whitelist of possible values for the entity_table field
   *
   * @return array
   */
  public static function entityTables(): array {
    return [
      'civicrm_option_value' => ts('Payment Instrument'),
      'civicrm_financial_type' => ts('Financial Type'),
      'civicrm_payment_processor' => ts('Payment Processor'),
    ];
  }

}
