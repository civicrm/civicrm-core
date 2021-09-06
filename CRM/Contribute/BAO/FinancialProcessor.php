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
 * Class for handling processing of financial records.
 *
 * This is a place to extract the financial record processing code to
 * in order to clean it up.
 *
 * @internal core use only.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contribute_BAO_FinancialProcessor {

  /**
   * Get the financial account for the item associated with the new transaction.
   *
   * @param array $params
   * @param int $default
   *
   * @return int
   */
  private static function getFinancialAccountForStatusChangeTrxn($params, $default): int {
    if (!empty($params['financial_account_id'])) {
      return $params['financial_account_id'];
    }

    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus($params['contribution_status_id'], 'name');
    $preferredAccountsRelationships = [
      'Refunded' => 'Credit/Contra Revenue Account is',
      'Chargeback' => 'Chargeback Account is',
    ];

    if (array_key_exists($contributionStatus, $preferredAccountsRelationships)) {
      $financialTypeID = !empty($params['financial_type_id']) ? $params['financial_type_id'] : $params['prevContribution']->financial_type_id;
      return CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $financialTypeID,
        $preferredAccountsRelationships[$contributionStatus]
      );
    }
    return $default;
  }

  /**
   * Create the financial items for the line.
   *
   * @param array $params
   * @param string $context
   * @param array $fields
   * @param array $previousLineItems
   * @param array $inputParams
   * @param bool $isARefund
   * @param array $trxnIds
   * @param int $fieldId
   *
   * @internal
   *
   * @return array
   */
  public static function createFinancialItemsForLine($params, $context, $fields, array $previousLineItems, array $inputParams, bool $isARefund, $trxnIds, $fieldId): array {
    foreach ($fields as $fieldValueId => $lineItemDetails) {
      $prevFinancialItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($lineItemDetails['id']);
      $receiveDate = CRM_Utils_Date::isoToMysql($params['prevContribution']->receive_date);
      if ($params['contribution']->receive_date) {
        $receiveDate = CRM_Utils_Date::isoToMysql($params['contribution']->receive_date);
      }

      $financialAccount = CRM_Contribute_BAO_FinancialProcessor::getFinancialAccountForStatusChangeTrxn($params, CRM_Utils_Array::value('financial_account_id', $prevFinancialItem));

      $currency = $params['prevContribution']->currency;
      if ($params['contribution']->currency) {
        $currency = $params['contribution']->currency;
      }
      $previousLineItemTotal = CRM_Utils_Array::value('line_total', CRM_Utils_Array::value($fieldValueId, $previousLineItems), 0);
      $itemParams = [
        'transaction_date' => $receiveDate,
        'contact_id' => $params['prevContribution']->contact_id,
        'currency' => $currency,
        'amount' => self::getFinancialItemAmountFromParams($inputParams, $context, $lineItemDetails, $isARefund, $previousLineItemTotal),
        'description' => $prevFinancialItem['description'] ?? NULL,
        'status_id' => $prevFinancialItem['status_id'],
        'financial_account_id' => $financialAccount,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => $lineItemDetails['id'],
      ];
      $financialItem = CRM_Financial_BAO_FinancialItem::create($itemParams, NULL, $trxnIds);
      $params['line_item'][$fieldId][$fieldValueId]['deferred_line_total'] = $itemParams['amount'];
      $params['line_item'][$fieldId][$fieldValueId]['financial_item_id'] = $financialItem->id;

      if (($lineItemDetails['tax_amount'] && $lineItemDetails['tax_amount'] !== 'null') || ($context === 'changeFinancialType')) {
        $taxAmount = (float) $lineItemDetails['tax_amount'];
        if ($context === 'changeFinancialType' && $lineItemDetails['tax_amount'] === 'null') {
          // reverse the Sale Tax amount if there is no tax rate associated with new Financial Type
          $taxAmount = CRM_Utils_Array::value('tax_amount', CRM_Utils_Array::value($fieldValueId, $previousLineItems), 0);
        }
        elseif ($previousLineItemTotal != $lineItemDetails['line_total']) {
          $taxAmount -= CRM_Utils_Array::value('tax_amount', CRM_Utils_Array::value($fieldValueId, $previousLineItems), 0);
        }
        if ($taxAmount != 0) {
          $itemParams['amount'] = CRM_Contribute_BAO_FinancialProcessor::getMultiplier($params['contribution']->contribution_status_id, $context) * $taxAmount;
          $itemParams['description'] = CRM_Invoicing_Utils::getTaxTerm();
          if ($lineItemDetails['financial_type_id']) {
            $itemParams['financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getSalesTaxFinancialAccount($lineItemDetails['financial_type_id']);
          }
          CRM_Financial_BAO_FinancialItem::create($itemParams, NULL, $trxnIds);
        }
      }
    }
    return $params;
  }

  /**
   * Get the multiplier for adjusting rows.
   *
   * If we are dealing with a refund or cancellation then it will be a negative
   * amount to reflect the negative transaction.
   *
   * If we are changing Financial Type it will be a negative amount to
   * adjust down the old type.
   *
   * @param int $contribution_status_id
   * @param string $context
   *
   * @return int
   */
  public static function getMultiplier($contribution_status_id, $context) {
    if ($context === 'changeFinancialType' || CRM_Contribute_BAO_Contribution::isContributionStatusNegative($contribution_status_id)) {
      return -1;
    }
    return 1;
  }

  /**
   * Get the amount for the financial item row.
   *
   * Helper function to start to break down recordFinancialTransactions for readability.
   *
   * The logic is more historical than .. logical. Paths other than the deprecated one are tested.
   *
   * Codewise, several somewhat disimmilar things have been squished into recordFinancialAccounts
   * for historical reasons. Going forwards we can hope to add tests & improve readibility
   * of that function
   *
   * @param array $params
   *   Params as passed to contribution.create
   *
   * @param string $context
   *   changeFinancialType| changedAmount
   * @param array $lineItemDetails
   *   Line items.
   * @param bool $isARefund
   *   Is this a refund / negative transaction.
   * @param int $previousLineItemTotal
   *
   * @return float
   * @todo move recordFinancialAccounts & helper functions to their own class?
   *
   */
  protected static function getFinancialItemAmountFromParams($params, $context, $lineItemDetails, $isARefund, $previousLineItemTotal) {
    if ($context == 'changedAmount') {
      $lineTotal = $lineItemDetails['line_total'];
      if ($lineTotal != $previousLineItemTotal) {
        $lineTotal -= $previousLineItemTotal;
      }
      return $lineTotal;
    }
    elseif ($context == 'changeFinancialType') {
      return -$lineItemDetails['line_total'];
    }
    elseif ($context == 'changedStatus') {
      $cancelledTaxAmount = 0;
      if ($isARefund) {
        $cancelledTaxAmount = CRM_Utils_Array::value('tax_amount', $lineItemDetails, '0.00');
      }
      return CRM_Contribute_BAO_FinancialProcessor::getMultiplier($params['contribution']->contribution_status_id, $context) * ((float) $lineItemDetails['line_total'] + (float) $cancelledTaxAmount);
    }
    elseif ($context === NULL) {
      // erm, yes because? but, hey, it's tested.
      return $lineItemDetails['line_total'];
    }
    else {
      return CRM_Contribute_BAO_FinancialProcessor::getMultiplier($params['contribution']->contribution_status_id, $context) * ((float) $lineItemDetails['line_total']);
    }
  }

  /**
   * Update all financial accounts entry.
   *
   * @param array $params
   *   Contribution object, line item array and params for trxn.
   *
   * @param string $context
   *   Update scenarios.
   *
   * @todo stop passing $params by reference. It is unclear the purpose of doing this &
   * adds unpredictability.
   *
   */
  public static function updateFinancialAccounts(&$params, $context = NULL) {
    $inputParams = $params;
    $isARefund = self::isContributionUpdateARefund($params['prevContribution']->contribution_status_id, $params['contribution']->contribution_status_id);

    if ($context === 'changedAmount' || $context === 'changeFinancialType') {
      // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
      $params['trxnParams']['total_amount'] = $params['trxnParams']['net_amount'] = ($params['total_amount'] - $params['prevContribution']->total_amount);
    }

    $trxn = CRM_Core_BAO_FinancialTrxn::create($params['trxnParams']);
    // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
    $params['entity_id'] = $trxn->id;

    $trxnIds['id'] = $params['entity_id'];
    $previousLineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($params['contribution']->id);
    foreach ($params['line_item'] as $fieldId => $fields) {
      $params = CRM_Contribute_BAO_FinancialProcessor::createFinancialItemsForLine($params, $context, $fields, $previousLineItems, $inputParams, $isARefund, $trxnIds, $fieldId);
    }
  }

  /**
   * Does this contribution status update represent a refund.
   *
   * @param int $previousContributionStatusID
   * @param int $currentContributionStatusID
   *
   * @return bool
   */
  public static function isContributionUpdateARefund($previousContributionStatusID, $currentContributionStatusID): bool {
    if ('Completed' !== CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $previousContributionStatusID)) {
      return FALSE;
    }
    return CRM_Contribute_BAO_Contribution::isContributionStatusNegative($currentContributionStatusID);
  }

}
