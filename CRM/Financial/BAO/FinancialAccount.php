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
class CRM_Financial_BAO_FinancialAccount extends CRM_Financial_DAO_FinancialAccount implements \Civi\Core\HookInterface {

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults = []) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_FinancialAccount', $id, 'is_active', $is_active);
  }

  /**
   * Add the financial types.
   *
   * @deprecated
   * @param array $params
   *
   * @return CRM_Financial_DAO_FinancialAccount
   */
  public static function add($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Delete financial Types.
   *
   * @deprecated
   * @param int $financialAccountId
   *
   * @return bool
   */
  public static function del($financialAccountId) {
    try {
      static::deleteRecord(['id' => $financialAccountId]);
      return TRUE;
    }
    catch (CRM_Core_Exception $e) {
      // FIXME: Setting status messages within a BAO CRUD function is bad bad bad. But this fn is deprecated so who cares.
      CRM_Core_Session::setStatus($e->getMessage(), ts('Delete Error'), 'error');
      return FALSE;
    }
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete') {
      // Check dependencies before deleting
      $dependency = [
        ['Core', 'FinancialTrxn', 'to_financial_account_id'],
        ['Financial', 'FinancialTypeAccount', 'financial_account_id'],
        ['Financial', 'FinancialItem', 'financial_account_id'],
      ];
      foreach ($dependency as $name) {
        $className = "CRM_{$name[0]}_BAO_{$name[1]}";
        $bao = new $className();
        $bao->{$name[2]} = $event->id;
        if ($bao->find(TRUE)) {
          throw new CRM_Core_Exception(ts('This financial account cannot be deleted since it is being used as a header account. Please remove it from being a header account before trying to delete it again.'));
        }
      }
    }
    if ($event->action === 'create' || $event->action === 'edit') {
      $params = $event->params;
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
        if (empty($params['financial_account_type_id'])) {
          $params['financial_account_type_id'] = CRM_Core_DAO::getFieldValue(__CLASS__, $params['id'], 'financial_account_type_id');
        }
        $query = 'UPDATE civicrm_financial_account SET is_default = 0 WHERE financial_account_type_id = %1';
        $queryParams = [1 => [$params['financial_account_type_id'], 'Integer']];
        CRM_Core_DAO::executeQuery($query, $queryParams);
      }
    }
  }

  /**
   * Callback for hook_civicrm_post().
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    CRM_Core_PseudoConstant::flush();
    Civi::cache('metadata')->clear();
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
    $params = [
      1 => [$financialTypeId, 'Integer'],
      2 => [$relationTypeId, 'Integer'],
    ];
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
    $params = [
      1 => [$financialAccountTypeId, 'Integer'],
      2 => [strtolower($accountTypeCode), 'String'],
      3 => [$financialAccountId, 'Integer'],
    ];
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
   * @todo Not sure what the above comment means, and the function uses the
   * PseudoConstant twice. Three times if you count the for loop.
   *
   * @param int $financialTypeID
   *
   * @param string $relationshipType
   *
   * @return int
   */
  public static function getFinancialAccountForFinancialTypeByRelationship(int $financialTypeID, string $relationshipType) {
    // This is keyed on the `value` column from civicrm_option_value
    $accountRelationshipsByValue = CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, NULL, 'name');
    // We look up by the name a couple times below, so flip it.
    $accountRelationships = array_flip($accountRelationshipsByValue);

    $relationTypeId = $accountRelationships[$relationshipType] ?? NULL;

    if (!isset(Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$relationTypeId])) {
      $accounts = Civi\Api4\EntityFinancialAccount::get(FALSE)
        ->addSelect('account_relationship', 'financial_account_id')
        ->addWhere('entity_id', '=', $financialTypeID)
        ->addWhere('entity_table', '=', 'civicrm_financial_type')
        ->execute()->column('financial_account_id', 'account_relationship');

      Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID] = $accounts;

      $incomeAccountRelationshipID = $accountRelationships['Income Account is'] ?? FALSE;
      $incomeAccountFinancialAccountID = Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$incomeAccountRelationshipID];

      foreach (['Chargeback Account is', 'Credit/Contra Revenue Account is'] as $optionalAccountRelationship) {

        $accountRelationshipID = $accountRelationships[$optionalAccountRelationship] ?? FALSE;
        if (empty(Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$accountRelationshipID])) {
          Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$accountRelationshipID] = $incomeAccountFinancialAccountID;
        }
      }
      if (!isset(Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$relationTypeId])) {
        Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$relationTypeId] = NULL;
      }
    }
    return Civi::$statics[__CLASS__]['entity_financial_account'][$financialTypeID][$relationTypeId];
  }

  /**
   * Get the sales tax financial account id for the financial type id.
   *
   * This is a helper wrapper to make the function name more readable.
   *
   * @param int $financialAccountID
   *
   * @return int
   */
  public static function getSalesTaxFinancialAccount($financialAccountID) {
    return self::getFinancialAccountForFinancialTypeByRelationship($financialAccountID, 'Sales Tax Account is');
  }

  /**
   * Get Financial Account type relations.
   *
   * @param bool $flip
   *
   * @return array
   *
   */
  public static function getfinancialAccountRelations($flip = FALSE) {
    $params = ['labelColumn' => 'name'];
    $financialAccountType = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id', $params);
    $accountRelationships = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship', $params);
    $Links = [
      'Expense Account is' => 'Expenses',
      'Accounts Receivable Account is' => 'Asset',
      'Income Account is' => 'Revenue',
      'Asset Account is' => 'Asset',
      'Cost of Sales Account is' => 'Cost of Sales',
      'Premiums Inventory Account is' => 'Asset',
      'Discounts Account is' => 'Revenue',
      'Sales Tax Account is' => 'Liability',
      'Deferred Revenue Account is' => 'Liability',
    ];
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
    $deferredFinancialType = [];
    $query = "SELECT ce.entity_id, cft.name FROM civicrm_entity_financial_account ce
INNER JOIN civicrm_financial_type cft ON ce.entity_id = cft.id
WHERE ce.entity_table = 'civicrm_financial_type' AND ce.account_relationship = %1 AND cft.is_active = 1";
    $deferredAccountRel = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Deferred Revenue Account is' "));
    $queryParams = [1 => [$deferredAccountRel, 'Integer']];
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
    $params = ['labelColumn' => 'name'];
    $financialAccountType = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id', $params);
    $params = [
      1 => [$financialAccountId, 'Integer'],
      2 => [
        implode(',',
          [
            array_search('Revenue', $financialAccountType),
            array_search('Liability', $financialAccountType),
          ]
        ),
        'Text',
      ],
    ];
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
   * @param array $orderLineItems
   *   The line items from the Order.
   *
   * @return bool
   *
   */
  public static function checkFinancialTypeHasDeferred($params, $contributionID = NULL, $orderLineItems = []) {
    if (!Civi::settings()->get('deferred_revenue_enabled')) {
      return FALSE;
    }
    $recognitionDate = $params['revenue_recognition_date'] ?? NULL;
    if (!(!CRM_Utils_System::isNull($recognitionDate)
      || ($contributionID && isset($params['prevContribution'])
        && !CRM_Utils_System::isNull($params['prevContribution']->revenue_recognition_date)))
    ) {
      return FALSE;
    }

    $lineItems = $params['line_item'] ?? NULL;
    $financialTypeID = $params['financial_type_id'] ?? NULL;
    if (!$financialTypeID) {
      $financialTypeID = $params['prevContribution']->financial_type_id;
    }
    if (($contributionID || !empty($params['price_set_id'])) && empty($lineItems)) {
      $lineItems[] = $orderLineItems;
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
      $error = ts('Revenue Recognition Date cannot be processed unless there is a Deferred Revenue account setup for the Financial Type. Please remove Revenue Recognition Date, select a different Financial Type with a Deferred Revenue account setup for it, or setup a Deferred Revenue account for this Financial Type.');
      throw new CRM_Core_Exception($error);
    }
    return $isError;
  }

  /**
   * Retrieve all Deferred Financial Accounts.
   *
   *
   * @return array of Deferred Financial Account
   *
   */
  public static function getAllDeferredFinancialAccount() {
    $financialAccount = [];
    $result = civicrm_api3('EntityFinancialAccount', 'get', [
      'sequential' => 1,
      'return' => ["financial_account_id.id", "financial_account_id.name", "financial_account_id.accounting_code"],
      'entity_table' => "civicrm_financial_type",
      'account_relationship' => "Deferred Revenue Account is",
    ]);
    if ($result['count'] > 0) {
      foreach ($result['values'] as $key => $value) {
        $financialAccount[$value['financial_account_id.id']] = $value['financial_account_id.name'] . ' (' . $value['financial_account_id.accounting_code'] . ')';
      }
    }
    return $financialAccount;
  }

  /**
   * Get Organization Name associated with Financial Account.
   *
   * @param bool $checkPermissions
   *
   * @return array
   *
   */
  public static function getOrganizationNames($checkPermissions = TRUE) {
    $result = civicrm_api3('FinancialAccount', 'get', [
      'return' => ["contact_id.organization_name", "contact_id"],
      'contact_id.is_deleted' => 0,
      'options' => ['limit' => 0],
      'check_permissions' => $checkPermissions,
    ]);
    $organizationNames = [];
    foreach ($result['values'] as $values) {
      $organizationNames[$values['contact_id']] = $values['contact_id.organization_name'];
    }
    return $organizationNames;
  }

}
