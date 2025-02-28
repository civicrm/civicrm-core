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
  private static function createFinancialItemsForLine($params, $context, $fields, array $previousLineItems, array $inputParams, bool $isARefund, $trxnIds, $fieldId): array {
    foreach ($fields as $fieldValueId => $lineItemDetails) {
      $prevFinancialItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($lineItemDetails['id']);
      $receiveDate = CRM_Utils_Date::isoToMysql($params['prevContribution']->receive_date);
      if ($params['contribution']->receive_date) {
        $receiveDate = CRM_Utils_Date::isoToMysql($params['contribution']->receive_date);
      }

      $financialAccount = CRM_Contribute_BAO_FinancialProcessor::getFinancialAccountForStatusChangeTrxn($params, $prevFinancialItem['financial_account_id']);

      $currency = $params['prevContribution']->currency;
      if ($params['contribution']->currency) {
        $currency = $params['contribution']->currency;
      }
      $previousLineItemTotal = $previousLineItems[$fieldValueId]['line_total'] ?? 0;
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
          $taxAmount = $previousLineItems[$fieldValueId]['tax_amount'] ?? 0;
        }
        elseif ($previousLineItemTotal != $lineItemDetails['line_total']) {
          $taxAmount -= $previousLineItems[$fieldValueId]['tax_amount'] ?? 0;
        }
        if ($taxAmount != 0) {
          $itemParams['amount'] = CRM_Contribute_BAO_FinancialProcessor::getMultiplier($params['contribution']->contribution_status_id, $context) * $taxAmount;
          $itemParams['description'] = \Civi::settings()->get('tax_term');
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
  private static function getMultiplier($contribution_status_id, $context) {
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
        $cancelledTaxAmount = $lineItemDetails['tax_amount'] ?? '0.00';
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

  /**
   * Do any accounting updates required as a result of a contribution status change.
   *
   * Currently we have a bit of a roundabout where adding a payment results in this being called &
   * this may attempt to add a payment. We need to resolve that....
   *
   * The 'right' way to add payments or refunds is through the Payment.create api. That api
   * then updates the contribution but this process should not also record another financial trxn.
   * Currently we have weak detection fot that scenario & where it is detected the first returned
   * value is FALSE - meaning 'do not continue'.
   *
   * We should also look at the fact that the calling function - updateFinancialAccounts
   * bunches together some disparate processes rather than having separate appropriate
   * functions.
   *
   * @param array $params
   *
   * @return bool
   *   Return indicates whether the updateFinancialAccounts function should continue.
   */
  public static function updateFinancialAccountsOnContributionStatusChange(&$params) {
    $previousContributionStatus = CRM_Contribute_PseudoConstant::contributionStatus($params['prevContribution']->contribution_status_id, 'name');
    $currentContributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $params['contribution']->contribution_status_id);

    if ((($previousContributionStatus === 'Partially paid' && $currentContributionStatus === 'Completed')
      || ($previousContributionStatus === 'Pending refund' && $currentContributionStatus === 'Completed')
      // This concept of pay_later as different to any other sort of pending is deprecated & it's unclear
      // why it is here or where it is handled instead.
      || ($previousContributionStatus === 'Pending' && $params['prevContribution']->is_pay_later == TRUE
        && $currentContributionStatus === 'Partially paid'))
    ) {
      return FALSE;
    }

    if (CRM_Contribute_BAO_FinancialProcessor::isContributionUpdateARefund($params['prevContribution']->contribution_status_id, $params['contribution']->contribution_status_id)) {
      // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
      $params['trxnParams']['total_amount'] = -$params['total_amount'];
    }
    elseif (($previousContributionStatus === 'Pending'
        && $params['prevContribution']->is_pay_later) || $previousContributionStatus === 'In Progress'
    ) {
      $financialTypeID = !empty($params['financial_type_id']) ? $params['financial_type_id'] : $params['prevContribution']->financial_type_id;
      $arAccountId = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($financialTypeID, 'Accounts Receivable Account is');

      if ($currentContributionStatus === 'Cancelled') {
        // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
        $params['trxnParams']['to_financial_account_id'] = $arAccountId;
        $params['trxnParams']['total_amount'] = -$params['total_amount'];
      }
      else {
        // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
        $params['trxnParams']['from_financial_account_id'] = $arAccountId;
      }
    }

    if (($previousContributionStatus === 'Pending'
        || $previousContributionStatus === 'In Progress')
      && ($currentContributionStatus === 'Completed')
    ) {
      if (empty($params['line_item'])) {
        //CRM-15296
        //@todo - check with Joe regarding this situation - payment processors create pending transactions with no line items
        // when creating recurring membership payment - there are 2 lines to comment out in contributionPageTest if fixed
        // & this can be removed
        return FALSE;
      }
      // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
      // This is an update so original currency if none passed in.
      $params['trxnParams']['currency'] = $params['currency'] ?? $params['prevContribution']->currency;

      $transactionIDs[] = CRM_Contribute_BAO_FinancialProcessor::recordAlwaysAccountsReceivable($params['trxnParams'], $params);
      $trxn = CRM_Core_BAO_FinancialTrxn::create($params['trxnParams']);
      // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
      $params['entity_id'] = $transactionIDs[] = $trxn->id;

      $sql = "SELECT id, amount FROM civicrm_financial_item WHERE entity_id = %1 and entity_table = 'civicrm_line_item'";

      $entityParams = [
        'entity_table' => 'civicrm_financial_item',
      ];
      foreach ($params['line_item'] as $fieldId => $fields) {
        foreach ($fields as $fieldValueId => $lineItemDetails) {
          self::updateFinancialItemForLineItemToPaid($lineItemDetails['id']);
          $fparams = [
            1 => [$lineItemDetails['id'], 'Integer'],
          ];
          $financialItem = CRM_Core_DAO::executeQuery($sql, $fparams);
          while ($financialItem->fetch()) {
            $entityParams['entity_id'] = $financialItem->id;
            $entityParams['amount'] = $financialItem->amount;
            foreach ($transactionIDs as $tID) {
              $entityParams['financial_trxn_id'] = $tID;
              CRM_Financial_BAO_FinancialItem::createEntityTrxn($entityParams);
            }
          }
        }
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Update all financial items related to the line item tto have a status of paid.
   *
   * @param int $lineItemID
   */
  private static function updateFinancialItemForLineItemToPaid($lineItemID) {
    $fparams = [
      1 => [
        CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_FinancialItem', 'status_id', 'Paid'),
        'Integer',
      ],
      2 => [$lineItemID, 'Integer'],
    ];
    $query = "UPDATE civicrm_financial_item SET status_id = %1 WHERE entity_id = %2 and entity_table = 'civicrm_line_item'";
    CRM_Core_DAO::executeQuery($query, $fparams);
  }

  /**
   * Create Accounts Receivable financial trxn entry for Completed Contribution.
   *
   * @param array $trxnParams
   *   Financial trxn params
   * @param array $contributionParams
   *   Contribution Params
   *
   * @return null|int
   */
  public static function recordAlwaysAccountsReceivable(&$trxnParams, $contributionParams) {
    if (!Civi::settings()->get('always_post_to_accounts_receivable')) {
      return NULL;
    }
    $statusId = $contributionParams['contribution']->contribution_status_id;
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $contributionStatus = empty($statusId) ? NULL : $contributionStatuses[$statusId];
    $previousContributionStatus = empty($contributionParams['prevContribution']) ? NULL : $contributionStatuses[$contributionParams['prevContribution']->contribution_status_id];
    // Return if contribution status is not completed.
    if (!($contributionStatus == 'Completed' && (empty($previousContributionStatus)
        || (!empty($previousContributionStatus) && $previousContributionStatus == 'Pending'
          && $contributionParams['prevContribution']->is_pay_later == 0
        )))
    ) {
      return NULL;
    }

    $params = $trxnParams;
    $financialTypeID = !empty($contributionParams['financial_type_id']) ? $contributionParams['financial_type_id'] : $contributionParams['prevContribution']->financial_type_id;
    $arAccountId = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($financialTypeID, 'Accounts Receivable Account is');
    $params['to_financial_account_id'] = $arAccountId;
    $params['status_id'] = array_search('Pending', $contributionStatuses);
    $params['is_payment'] = FALSE;
    $trxn = CRM_Core_BAO_FinancialTrxn::create($params);
    $trxnParams['from_financial_account_id'] = $params['to_financial_account_id'];
    return $trxn->id;
  }

  /**
   * Does this transaction reflect a payment instrument change.
   *
   * @param array $params
   * @param array $pendingStatuses
   *
   * @return bool
   */
  public static function isPaymentInstrumentChange(&$params, $pendingStatuses) {
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $params['contribution']->contribution_status_id);

    if (array_key_exists('payment_instrument_id', $params)) {
      if (CRM_Utils_System::isNull($params['prevContribution']->payment_instrument_id) &&
        !CRM_Utils_System::isNull($params['payment_instrument_id'])
      ) {
        //check if status is changed from Pending to Completed
        // do not update payment instrument changes for Pending to Completed
        if (!($contributionStatus == 'Completed' &&
          in_array($params['prevContribution']->contribution_status_id, $pendingStatuses))
        ) {
          return TRUE;
        }
      }
      elseif ((!CRM_Utils_System::isNull($params['payment_instrument_id']) &&
          !CRM_Utils_System::isNull($params['prevContribution']->payment_instrument_id)) &&
        $params['payment_instrument_id'] != $params['prevContribution']->payment_instrument_id
      ) {
        return TRUE;
      }
      elseif (!CRM_Utils_System::isNull($params['contribution']->check_number) &&
        $params['contribution']->check_number != $params['prevContribution']->check_number
      ) {
        // another special case when check number is changed, create new financial records
        // create financial trxn with negative amount
        return TRUE;
      }
    }
    return FALSE;
  }

}
