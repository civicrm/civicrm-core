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
class CRM_Financial_BAO_FinancialTrxn extends CRM_Financial_DAO_FinancialTrxn {

  /**
   * Takes an associative array and creates a financial transaction object.
   *
   * @param array $params
   *
   * @return CRM_Financial_DAO_FinancialTrxn
   */
  public static function create(array $params): CRM_Financial_DAO_FinancialTrxn {
    $trxn = new CRM_Financial_DAO_FinancialTrxn();
    $trxn->copyValues($params);

    if (isset($params['fee_amount']) && is_numeric($params['fee_amount'])) {
      if (!isset($params['total_amount'])) {
        $trxn->fetch();
        $params['total_amount'] = $trxn->total_amount;
      }
      $trxn->net_amount = $params['total_amount'] - $params['fee_amount'];
    }

    if (empty($params['id']) && !CRM_Utils_Rule::currencyCode($trxn->currency)) {
      $trxn->currency = CRM_Core_Config::singleton()->defaultCurrency;
    }

    $trxn->save();

    if (!empty($params['id'])) {
      // For an update entity financial transaction record will already exist. Return early.
      return $trxn;
    }

    // Save to entity_financial_trxn table.
    $entityFinancialTrxnParams = [
      'entity_table' => CRM_Utils_Array::value('entity_table', $params, 'civicrm_contribution'),
      'entity_id' => CRM_Utils_Array::value('entity_id', $params, CRM_Utils_Array::value('contribution_id', $params)),
      'financial_trxn_id' => $trxn->id,
      'amount' => $params['total_amount'],
    ];

    $entityTrxn = new CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->copyValues($entityFinancialTrxnParams);
    $entityTrxn->save();
    return $trxn;
  }

  /**
   * Generate and assign an arbitrary value to a field of a test object.
   *
   * Always set is_payment to 1 as this is used for Payment api as  well as
   * FinancialTrxn.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   *   The globally-unique ID of the test object.
   *
   * @throws \CRM_Core_Exception
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter): void {
    if ($fieldName === 'is_payment') {
      $this->is_payment = 1;
    }
    else {
      parent::assignTestValue($fieldName, $fieldDef, $counter);
    }
  }

}
