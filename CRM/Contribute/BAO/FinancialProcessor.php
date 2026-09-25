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

use Civi\Api4\EntityFinancialTrxn;
use Civi\Api4\FinancialItem;
use Civi\Api4\PaymentProcessor;

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

  private CRM_Contribute_DAO_Contribution $updatedContribution;

  private ?CRM_Contribute_BAO_Contribution $originalContribution;

  private array $originalLineItems;
  private array $inputValues;
  private array $previousFinancialItems = [];

  public function __construct(?CRM_Contribute_BAO_Contribution $originalContribution, CRM_Contribute_DAO_Contribution $updatedContribution, array $originalLineItems, array $inputValues) {
    // Deal with slopping typing first.
    if ($originalContribution) {
      $originalContribution->contribution_status_id = (int) $originalContribution->contribution_status_id;
    }
    $updatedContribution->contribution_status_id = (int) $updatedContribution->contribution_status_id;
    $this->originalContribution = $originalContribution;
    $this->updatedContribution = $updatedContribution;
    $this->originalLineItems = $originalLineItems;
    $this->inputValues = $inputValues;
  }

  private function getUpdatedContribution(): CRM_Contribute_DAO_Contribution {
    return $this->updatedContribution;
  }

  private function getOriginalContribution(): ?CRM_Contribute_BAO_Contribution {
    return $this->originalContribution;
  }

  private function getUpdatedContributionStatus(): string {
    return CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $this->updatedContribution->contribution_status_id);
  }

  private function getOriginalContributionStatus(): ?string {
    if (!$this->originalContribution) {
      return NULL;
    }
    return CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $this->originalContribution->contribution_status_id);
  }

  private function getOriginalPaymentInstrumentID(): ?int {
    if (!$this->originalContribution) {
      return NULL;
    }
    return $this->originalContribution->payment_instrument_id;
  }

  private function getOriginalContributionValue(string $key): mixed {
    if (!$this->originalContribution) {
      return NULL;
    }
    return $this->originalContribution->$key;
  }

  private function isNegativeTransaction(): bool {
    return in_array($this->getUpdatedContributionStatus(), ['Refunded', 'Chargeback', 'Cancelled'], TRUE);
  }

  private function isFailedTransaction(): bool {
    return $this->getUpdatedContributionStatus() === 'Failed';
  }

  private function isPendingTransaction(): bool {
    return in_array($this->getUpdatedContributionStatus(), ['Pending', 'In Progress']);
  }

  private function isCompletedTransaction(): bool {
    return $this->getUpdatedContributionStatus() === 'Completed';
  }

  private function isAccountsReceivableTransaction(): bool {
    return $this->getUpdatedContributionStatus() === 'Pending' || $this->getUpdatedContributionStatus() === 'In Progress';
  }

  private function isOriginalStatusPending(): bool {
    return in_array($this->getOriginalContributionStatus(), ['Pending', 'In Progress'], TRUE);
  }

  /**
   * @param array $itemParams
   * @param $financial_type_id
   * @param mixed $taxAmount
   * @param int $trxnID
   *
   * @return void
   */
  private function createTaxFinancialItem(array $itemParams, $financial_type_id, $taxAmount, int $trxnID): void {
    $itemParams['description'] = \Civi::settings()->get('tax_term');
    $itemParams['financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getSalesTaxFinancialAccount($financial_type_id);
    $itemParams['amount'] = CRM_Contribute_BAO_FinancialProcessor::getMultiplier($this->getUpdatedContribution()->contribution_status_id) * $taxAmount;
    $this->createFinancialItem($itemParams, $trxnID);
  }

  /**
   * @param array $itemParams
   * @param int|null $trxnID
   */
  private function createFinancialItem(array $itemParams, ?int $trxnID): void {
    $item = CRM_Financial_BAO_FinancialItem::writeRecord($itemParams);
    if ($trxnID && $item->amount != 0) {
      EntityFinancialTrxn::save(FALSE)->addRecord([
        'entity_table' => "civicrm_financial_item",
        'entity_id' => $item->id,
        'financial_trxn_id' => $trxnID,
        'amount' => $itemParams['amount'],
      ])->execute();
    }
  }

  private function isStatusChange(): bool {
    return $this->originalContribution->contribution_status_id !== $this->updatedContribution->contribution_status_id;
  }

  private function getOriginalFinancialAccount(): ?int {
    if (!$this->originalContribution) {
      return NULL;
    }
    $accountRelationship = $this->updatedContribution->revenue_recognition_date ? 'Deferred Revenue Account is' : 'Income Account is';
    return CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($this->originalContribution->financial_type_id, $accountRelationship);
  }

  /**
   * @throws CRM_Core_Exception
   */
  private function getUpdatedFinancialAccount(): int {
    $financialTypeID = $this->updatedContribution->financial_type_id;
    return $this->getRevenueAccountForType($financialTypeID);
  }

  /**
   * @param int $financialTypeID
   * @return int
   * @throws CRM_Core_Exception
   */
  private function getRevenueAccountForType(int $financialTypeID): int {
    $accountRelationship = $this->updatedContribution->revenue_recognition_date ? 'Deferred Revenue Account is' : 'Income Account is';
    $account = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($financialTypeID, $accountRelationship);
    if (!$account) {
      throw new CRM_Core_Exception(ts("Account not configured '%1' for financial type %2", [
        '1' => $accountRelationship,
        '2' => CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'financial_type_id', $financialTypeID),
      ]));
    }
    return $account;
  }

  /**
   * Get the financial account for the item associated with the new transaction.
   *
   * @param array $params
   * @param int $default
   *
   * @return int
   */
  private function getFinancialAccountForStatusChangeTrxn($params, $default): int {
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
   * @param array $params
   *
   * @return int
   * @throws CRM_Core_Exception
   */
  private function getToFinancialAccount(array $params): int {
    if ($this->isAccountsReceivableTransaction()) {
      return CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $params['financial_type_id'],
        'Accounts Receivable Account is'
      );
    }
    $accountID = NULL;
    if (!empty($params['payment_processor'])) {
      $accountID = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($params['payment_processor'], NULL, 'civicrm_payment_processor');
    }
    // Probably here we should check $this->updatedContribution instead of params
    // and then we would not need the next if.
    if (!$accountID && !empty($params['payment_instrument_id'])) {
      $accountID = CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount($params['payment_instrument_id']);
    }
    // Probably updatedContribution makes more sense - per previous comment.
    // dev/financial#160 - If this is a contribution update, also check for an existing payment_instrument_id.
    elseif (!$accountID && $this->getOriginalPaymentInstrumentID()) {
      $accountID = CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount((int) $params['prevContribution']->payment_instrument_id);
    }
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Asset' "));
    $queryParams = [1 => [$relationTypeId, 'Integer']];
    return $accountID ?: CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = %1", $queryParams);
  }

  /**
   * Create all financial accounts entry.
   *
   * @param array $params
   *   Contribution object, line item array and params for trxn.
   * @throws CRM_Core_Exception
   */
  public function recordFinancialAccounts(array &$params): void {
    $skipRecords = FALSE;

    // Checking $params['is_pay_later'] means we only pick this up if
    // is_pay_later has been passed in - this feels like a mistake but it
    // is an entrenched mistake (the previous code did the same although
    // less obviously as it checked a partially populated contribution object.
    $isPayLater = !empty($params['is_pay_later']);
    $isIncompletePending = $this->isPendingTransaction() && !$isPayLater;
    if (!$isIncompletePending && !$this->isFailedTransaction()) {
      $skipRecords = TRUE;

      //build financial transaction params

      if ($this->isUpdate()) {
        $trxnParams = $this->getTrxnParams($params);
        $params['trxnParams'] = $trxnParams;
        $updated = FALSE;
        $params['trxnParams']['total_amount'] = $trxnParams['total_amount'] = $params['total_amount'] = $params['prevContribution']->total_amount;
        $params['trxnParams']['fee_amount'] = $params['prevContribution']->fee_amount;
        $params['trxnParams']['net_amount'] = $params['prevContribution']->net_amount;
        $params['trxnParams']['status_id'] = $params['prevContribution']->contribution_status_id;
        if (!($this->isOriginalStatusPending() && $this->isCompletedTransaction())
        ) {
          $params['trxnParams']['payment_instrument_id'] = $params['prevContribution']->payment_instrument_id;
          $params['trxnParams']['check_number'] = $params['prevContribution']->check_number;
        }

        //if financial account is changed
        if ($this->isFinancialAccountChanged()) {
          $params['trxnParams']['trxn_date'] = date('YmdHis');
          if ($this->isAccountsReceivableTransaction()) {
            $accountRelationship = $this->getUpdatedContribution()->revenue_recognition_date ? 'Deferred Revenue Account is' : 'Income Account is';
            $params['trxnParams']['to_financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
              $this->getOriginalContribution()->financial_type_id, $accountRelationship);
          }
          else {
            $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($params['prevContribution']->id, 'DESC');
            if (!empty($lastFinancialTrxnId['financialTrxnId'])) {
              $params['trxnParams']['to_financial_account_id'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $lastFinancialTrxnId['financialTrxnId'], 'to_financial_account_id');
            }
          }
          $params['skipLineItem'] = FALSE;
          // Set amounts to create a reversal transaction.
          $params['trxnParams']['total_amount'] = $params['trxnParams']['net_amount'] = -$this->getOriginalContribution()->total_amount;
          $params['trxnParams']['fee_amount'] = 0 - $this->getOriginalContribution()->fee_amount;
          $lineItems = $this->updateFinancialAccounts($params, 'changeFinancialType');
          $this->createDeferredTrxn($lineItems, TRUE, 'changeFinancialType');
          /* $params['trxnParams']['to_financial_account_id'] = $trxnParams['to_financial_account_id']; */
          $params['financial_account_id'] = $this->getUpdatedFinancialAccount();
          // Set the amounts back to the original value for creating the new positive financial trxn.
          $params['total_amount'] = $params['trxnParams']['net_amount'] = $params['trxnParams']['total_amount'] = $this->getUpdatedContribution()->total_amount;
          $params['trxnParams']['fee_amount'] = $this->getUpdatedContribution()->fee_amount;
          $lineItems = $this->updateFinancialAccounts($params);
          $this->createDeferredTrxn($lineItems, TRUE);
          $params['trxnParams']['to_financial_account_id'] = $trxnParams['to_financial_account_id'];
          $updated = TRUE;
          $params['deferred_financial_account_id'] = $this->getUpdatedFinancialAccount();
        }

        //Update contribution status
        $params['trxnParams']['status_id'] = $params['contribution']->contribution_status_id;
        if (!isset($params['refund_trxn_id'])) {
          // CRM-17751 This has previously been deliberately set. No explanation as to why one variant
          // gets preference over another so I am only 'protecting' a very specific tested flow
          // and letting natural justice take care of the rest.
          $params['trxnParams']['trxn_id'] = $params['contribution']->trxn_id;
        }
        if (!empty($params['contribution_status_id']) &&
          $params['prevContribution']->contribution_status_id != $params['contribution']->contribution_status_id
        ) {
          //Update Financial Records
          $this->updateFinancialAccountsOnContributionStatusChange($params);
          $updated = TRUE;
        }

        // change Payment Instrument for a Completed contribution
        // first handle special case when contribution is changed from Pending to Completed status when initial payment
        // instrument is null and now new payment instrument is added along with the payment
        $params['trxnParams']['payment_instrument_id'] = $params['contribution']->payment_instrument_id;
        $params['trxnParams']['check_number'] = $params['check_number'] ?? NULL;

        if ($this->isPaymentInstrumentChange($params)) {
          $updated = $this->updateFinancialAccountsOnPaymentInstrumentChange($params);
        }

        //if Change contribution amount
        $params['trxnParams']['fee_amount'] = $params['fee_amount'] ?? NULL;
        $params['trxnParams']['net_amount'] = $params['net_amount'] ?? NULL;
        $totalAmount = $this->getUpdatedContribution()->total_amount ?? 0;
        $params['trxnParams']['total_amount'] = $trxnParams['total_amount'] = $params['total_amount'] = $totalAmount;
        $params['trxnParams']['trxn_id'] = $params['contribution']->trxn_id;
        // If the total has changed then create adjustments, but it the financial
        // account has ALSO changed this will already have been dealt with using reverse & recreate above.
        if ($this->isContributionTotalChanged() && !$this->isFinancialAccountChanged()) {
          //Update Financial Records
          $params['trxnParams']['from_financial_account_id'] = NULL;
          $params['trxnParams']['total_amount'] = $params['trxnParams']['net_amount'] = ($params['total_amount'] - $params['prevContribution']->total_amount);
          $lineItems = $this->updateFinancialAccounts($params, 'changedAmount');
          $this->createDeferredTrxn($lineItems, TRUE, 'changedAmount');
          $updated = TRUE;
        }

        if (!$updated) {
          // Looks like we might have a data correction update.
          // This would be a case where a transaction id has been entered but it is incorrect &
          // the person goes back in & fixes it, as opposed to a new transaction.
          // Currently the UI doesn't support multiple refunds against a single transaction & we are only supporting
          // the data fix scenario.
          // CRM-17751.
          if (isset($params['refund_trxn_id'])) {
            $refundIDs = CRM_Core_BAO_FinancialTrxn::getRefundTransactionIDs($params['id']);
            if (!empty($refundIDs['financialTrxnId']) && $refundIDs['trxn_id'] != $params['refund_trxn_id']) {
              civicrm_api3('FinancialTrxn', 'create', [
                'id' => $refundIDs['financialTrxnId'],
                'trxn_id' => $params['refund_trxn_id'],
              ]);
            }
          }
          $cardType = $params['card_type_id'] ?? NULL;
          $panTruncation = $params['pan_truncation'] ?? NULL;
          CRM_Core_BAO_FinancialTrxn::updateCreditCardDetails($params['contribution']->id, $panTruncation, $cardType);
        }
      }

      else {
        $trxnParams = $params['trxnParams'] = $this->getTrxnParams($params);
        // records finanical trxn and entity financial trxn
        // also make it available as return value
        $this->recordAlwaysAccountsReceivable($trxnParams, $params);
        $financialTxn = CRM_Core_BAO_FinancialTrxn::create($trxnParams);
        $params['entity_id'] = $financialTxn->id;
      }
    }
    // record line items and financial items
    if (empty($params['skipLineItem'])) {
      $this->createLineItems($params['line_item']);
    }

    // create batch entry if batch_id is passed and
    // ensure no batch entry is been made on 'Pending' or 'Failed' contribution, CRM-16611
    if (!empty($params['batch_id']) && !empty($financialTxn)) {
      $entityParams = [
        'batch_id' => $params['batch_id'],
        'entity_table' => 'civicrm_financial_trxn',
        'entity_id' => $financialTxn->id,
      ];
      CRM_Batch_BAO_EntityBatch::create($entityParams);
    }

    // when a fee is charged
    if (!empty($params['fee_amount']) && (empty($params['prevContribution']) || $params['contribution']->fee_amount != $params['prevContribution']->fee_amount) && $skipRecords) {
      CRM_Core_BAO_FinancialTrxn::recordFees($params + ['to_financial_account_id' => $this->getToFinancialAccount($params)]);
    }

    unset($params['line_item']);
  }

  /**
   * Create the financial items for the line.
   *
   * @param array $params
   * @param string $context
   * @param array $fields
   * @param array $trxnIds
   * @param int $fieldId
   *
   * @internal
   *
   * @return array
   */
  private function createFinancialItemsForLine($params, $context, $fields, $trxnIds, $fieldId): array {
    $postUpdateContribution = $params['contribution'];
    foreach ($fields as $fieldValueId => $lineItemDetails) {
      $previousLineItem = $this->originalLineItems[$lineItemDetails['id'] ?? NULL] ?? [];
      $prevFinancialItem = $this->getExistingFinancialItemForLine($lineItemDetails['id'], FALSE);
      $financialAccount = $this->getFinancialAccountForStatusChangeTrxn($params, $prevFinancialItem['financial_account_id']);

      $itemParams = [
        'transaction_date' => CRM_Utils_Date::isoToMysql($postUpdateContribution->receive_date),
        'contact_id' => $postUpdateContribution->contact_id,
        'currency' => $postUpdateContribution->currency,
        'amount' => $this->getFinancialItemAmountFromParams($context, $lineItemDetails),
        'description' => $prevFinancialItem['description'] ?? NULL,
        'status_id' => $prevFinancialItem['status_id'],
        'financial_account_id' => $financialAccount,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => $lineItemDetails['id'],
      ];
      $financialItem = CRM_Financial_BAO_FinancialItem::create($itemParams, NULL, $trxnIds);
      $params['line_item'][$fieldId][$fieldValueId]['deferred_line_total'] = $itemParams['amount'];
      $params['line_item'][$fieldId][$fieldValueId]['financial_item_id'] = $financialItem->id;

      // If changing the financial type we reverse & recreate but really we should do this
      // a) if the line item financial type changes (not contribution) and
      // b) if the currency changes....
      $isReversePrior = $context === 'changeFinancialType';

      // We have to create a new tax transaction if the last one was reversed OR the new one has tax
      // and there was no last one to reverse.
      $isTaxTransactionRequired = $lineItemDetails['tax_amount'] &&
        // excluding changedAmount is a bit odd as the line could have changed amount and type
        // without the contribution total having changed type - but we can only change the code
        // so quickly.
        (($this->isReversalRequired($lineItemDetails, TRUE) && $context !== 'changedAmount')
        ||
          (
            !$this->getExistingFinancialItemForLine($lineItemDetails['id'], TRUE)
            || !$this->getExistingFinancialItemForLine($lineItemDetails['id'], TRUE)['amount']
          )
        );

      // Adjusting tax is a bit yuck - would be better to reverse & redo IMHO.
      $isTaxAdjustmentRequired = ($lineItemDetails['tax_amount'] ?? NULL)
        && $this->getOriginalLineItemAmount($lineItemDetails['id'], 'tax_amount') !== $lineItemDetails['tax_amount'];

      if ($isReversePrior) {
        // In this case we are on the first pass - reverse. Second pass will create new
        // although would be better to restructure to a single pass.
        $this->reverseLineFinancialItem($lineItemDetails, TRUE, $trxnIds['id']);
      }
      elseif ($isTaxTransactionRequired) {
        // In this scenario we are on the second pass. We have done a reversal
        // on the first pass and now we are doing a second pass to create the
        // new correct new transaction (ideally we would not do as 2 passes
        // but one after the other)
        $taxAmount = $lineItemDetails['tax_amount'];
        $this->createTaxFinancialItem($itemParams, $lineItemDetails['financial_type_id'], $taxAmount, $trxnIds['id']);
      }
      elseif ($isTaxAdjustmentRequired) {
        $previousTaxAmount = $previousLineItem['tax_amount'] ?? 0;
        $taxAmount = (float) $lineItemDetails['tax_amount'] - $previousTaxAmount;
        $this->createTaxFinancialItem($itemParams, $lineItemDetails['financial_type_id'], $taxAmount, $trxnIds['id']);
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
   *
   * @return int
   */
  private function getMultiplier($contribution_status_id) {
    if (CRM_Contribute_BAO_Contribution::isContributionStatusNegative($contribution_status_id)) {
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
   * @param string|null $context
   *   changeFinancialType| changedAmount | changedStatus
   * @param array $lineItemDetails
   *
   * @return float
   *
   */
  private function getFinancialItemAmountFromParams(?string $context, array $lineItemDetails) {
    if ($context === 'changedAmount') {
      $lineTotal = $lineItemDetails['line_total'];
      $previousLineItemTotal = $this->getOriginalLineItemAmount($lineItemDetails['id'] ?? NULL, 'line_total');
      if ($lineTotal != $previousLineItemTotal) {
        $lineTotal -= $previousLineItemTotal;
      }
      return $lineTotal;
    }
    if ($context === 'changeFinancialType') {
      // This is a reversal that will be followed by a replacement line. We should also
      // do this when currency changes or, perhaps we should always reverse & redo to mitigate
      // complexity / error risk.
      return -$this->getOriginalLineItemAmount($lineItemDetails['id'] ?? NULL, 'line_total');
    }
    if ($context === 'changedStatus') {
      $cancelledTaxAmount = 0;
      if ($this->isContributionUpdateARefund()) {
        $cancelledTaxAmount = $lineItemDetails['tax_amount'] ?? '0.00';
      }
      $isContributionStatusNegative = CRM_Contribute_BAO_Contribution::isContributionStatusNegative($this->updatedContribution->contribution_status_id);
      return ($isContributionStatusNegative ? -1 : 1) * ((float) $lineItemDetails['line_total'] + (float) $cancelledTaxAmount);
    }
    if ($context === NULL) {
      // erm, yes because? but, hey, it's tested.
      return $lineItemDetails['line_total'];
    }
    throw new CRM_Core_Exception('unreachable');
  }

  /**
   * @param array $params
   * @return array
   * @throws CRM_Core_Exception
   */
  private function getTrxnParams(array $params): array {
    $trxnParams = [
      'contribution_id' => $this->getContributionID(),
      'to_financial_account_id' => $this->getToFinancialAccount($params),
      // If receive_date is not deliberately passed in we assume 'now'.
      // test testCompleteTransactionWithReceiptDateSet ensures we don't
      // default to loading the stored contribution receive_date.
      // Note that as we deprecate completetransaction in favour
      // of Payment.create handling of trxn_date will tighten up.
      'trxn_date' => $this->getInputValue('receive_date') ?: date('YmdHis'),
      'currency' => $this->getUpdatedContribution()->currency,
      // CRM-17751, Fallback to original contribution is historical and probably not needed now as it was probably because updatedContribution
      // was not historically always reliably reloaded.
      'trxn_id' => $this->getInputValue('trxn_id') ?: $this->getUpdatedContribution()->trxn_id ?: $this->getOriginalContributionValue('trxn_id'),
      'payment_instrument_id' => $this->getInputValue('payment_instrument_id') ?: $this->getUpdatedContribution()->payment_instrument_id,
      'check_number' => $this->getInputValue('check_number'),
      'pan_truncation' => $this->getInputValue('pan_truncation'),
      'card_type_id' => $this->getInputValue('card_type_id'),
    ];
    //CRM-16259, set is_payment flag for non pending status
    if (!$this->isAccountsReceivableTransaction()) {
      $trxnParams['is_payment'] = 1;
    }
    if ($this->getInputValue('payment_processor')) {
      $trxnParams['payment_processor_id'] = $this->getInputValue('payment_processor');
      if (!$this->isAccountsReceivableTransaction()) {
        $trxnParams['payment_instrument_id'] = PaymentProcessor::get(FALSE)
          ->addWhere('id', '=', $this->getInputValue('payment_processor'))
          ->addSelect('payment_instrument_id')
          ->execute()->single()['payment_instrument_id'];
      }
    }

    if (empty($trxnParams['payment_processor_id'])) {
      unset($trxnParams['payment_processor_id']);
    }
    if ($this->isNegativeTransaction()) {
      $trxnParams['trxn_date'] = !empty($this->getUpdatedContribution()->cancel_date) ? $this->getUpdatedContribution()->cancel_date : date('YmdHis');
      // See testCreateUpdateContributionRefundRefundNullTrxnIDPassedIn - if refund_trxn_id isset, even if empty
      // it takes precedence. Unclear whether there is a reason or the test was just written
      // to protect behaviour during refactoring.
      if (isset($this->inputValues['refund_trxn_id'])) {
        // CRM-17751 allow a separate trxn_id for the refund to be passed in via api & form.
        $trxnParams['trxn_id'] = $this->getInputValue('refund_trxn_id');
      }
    }
    if (empty($this->originalContribution)) {
      // New contribution - populate amounts too
      $trxnParams['total_amount'] = $this->updatedContribution->total_amount;
      $trxnParams['fee_amount'] = $this->updatedContribution->fee_amount;
      $trxnParams['net_amount'] = $this->updatedContribution->net_amount;
      // @todo - this is getting the status id from the contribution - that is BAD - ie the contribution could be partially
      // paid but each payment is completed. The work around is to pass in the status_id in the trxn_params but
      // this should really default to completed (after discussion). But after moving this
      // to the only place it is actually used - maybe it makes more sense?
      $trxnParams['status_id'] = $this->updatedContribution->contribution_status_id;
    }
    return $trxnParams;
  }

  /**
   * Get a value input to the Contribution.
   *
   * This function should be used when rather than looking for the final value
   * (ie stored on the original contribution) we care whether the value was input
   * into the function. (In practice many historical uses could probably be
   * replaces with $this->updatedContribution->)
   * @param string $string
   * @return mixed
   */
  private function getInputValue(string $string): mixed {
    return $this->inputValues[$string] ?? NULL;
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
   * @return array
   *   The updated line_item array, keyed as $params['line_item'] was, with
   *   'deferred_line_total' & 'financial_item_id' added.
   */
  private function updateFinancialAccounts($params, $context = NULL): array {
    $trxn = CRM_Core_BAO_FinancialTrxn::create($params['trxnParams']);
    $trxnIds['id'] = $trxn->id;
    foreach ($params['line_item'] as $fieldId => $fields) {
      $params = $this->createFinancialItemsForLine($params, $context, $fields, $trxnIds, $fieldId);
    }
    return $params['line_item'];
  }

  /**
   * Does this contribution status update represent a refund.
   *
   * @return bool
   */
  private function isContributionUpdateARefund(): bool {
    if ('Completed' !== $this->getOriginalContributionStatus()) {
      return FALSE;
    }
    return CRM_Contribute_BAO_Contribution::isContributionStatusNegative($this->getUpdatedContribution()->contribution_status_id);
  }

  /**
   * Is the financial account changed on the contribution.
   *
   * @todo - this should only matter at the line item level.
   *
   * @throws CRM_Core_Exception
   */
  private function isFinancialAccountChanged(): bool {
    $oldFinancialAccount = $this->getOriginalFinancialAccount();
    $newFinancialAccount = $this->getUpdatedFinancialAccount();
    return $oldFinancialAccount !== $newFinancialAccount;
  }

  private function isContributionTotalChanged(): bool {
    $newAmount = $this->updatedContribution->total_amount;
    $previousAmount = $this->originalContribution->total_amount;
    return bccomp($newAmount, $previousAmount, 5) !== 0;
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
   */
  private function updateFinancialAccountsOnContributionStatusChange(&$params): void {
    $previousContributionStatus = $this->getOriginalContributionStatus();
    $currentContributionStatus = $this->getUpdatedContributionStatus();

    if ((($previousContributionStatus === 'Partially paid' && $this->isCompletedTransaction())
      || ($previousContributionStatus === 'Pending refund' && $this->isCompletedTransaction())
      // This concept of pay_later as different to any other sort of pending is deprecated & it's unclear
      // why it is here or where it is handled instead.
      || ($previousContributionStatus === 'Pending' && $params['prevContribution']->is_pay_later == TRUE
        && $currentContributionStatus === 'Partially paid'))
    ) {
      return;
    }

    if ($this->isContributionUpdateARefund()) {
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

    if ($this->isOriginalStatusPending() && $this->isCompletedTransaction()
    ) {
      if (empty($params['line_item'])) {
        //CRM-15296
        //@todo - check with Joe regarding this situation - payment processors create pending transactions with no line items
        // when creating recurring membership payment - there are 2 lines to comment out in contributionPageTest if fixed
        // & this can be removed
        return;
      }
      // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
      // This is an update so original currency if none passed in.
      $params['trxnParams']['currency'] = $params['currency'] ?? $params['prevContribution']->currency;

      $financialTrxnIDs[] = $this->recordAlwaysAccountsReceivable($params['trxnParams'], $params);
      $trxn = CRM_Core_BAO_FinancialTrxn::create($params['trxnParams']);
      // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
      $params['entity_id'] = $financialTrxnIDs[] = $trxn->id;

      $entityFinancialTrxnRecord = [
        'entity_table' => 'civicrm_financial_item',
      ];
      foreach ($params['line_item'] as $fieldId => $fields) {
        foreach ($fields as $fieldValueId => $lineItemDetails) {
          $this->updateFinancialItemForLineItemToPaid($lineItemDetails['id']);
          $financialItems = FinancialItem::get(FALSE)
            ->addSelect('id', 'amount')
            ->addWhere('entity_id', '=', $lineItemDetails['id'])
            ->addWhere('entity_table', '=', 'civicrm_line_item')
            ->execute();
          if ($financialItems->count() > 0) {
            $entityFinancialTrxnRecordsToCreate = [];
            foreach ($financialItems as $financialItem) {
              $entityFinancialTrxnRecord['entity_id'] = $financialItem['id'];
              $entityFinancialTrxnRecord['amount'] = $financialItem['amount'];
              foreach ($financialTrxnIDs as $financialTrxnID) {
                $entityFinancialTrxnRecord['financial_trxn_id'] = $financialTrxnID;
                $entityFinancialTrxnRecordsToCreate[] = $entityFinancialTrxnRecord;
              }
            }
            EntityFinancialTrxn::save(FALSE)
              ->setRecords($entityFinancialTrxnRecordsToCreate)
              ->execute();
          }
        }
      }
      return;
    }
    $lineItems = $this->updateFinancialAccounts($params, 'changedStatus');
    $this->createDeferredTrxn($lineItems, TRUE, 'changedStatus');
  }

  /**
   * Update all financial items related to the line item tto have a status of paid.
   *
   * @param int $lineItemID
   */
  private function updateFinancialItemForLineItemToPaid($lineItemID) {
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
  private function recordAlwaysAccountsReceivable(&$trxnParams, $contributionParams) {
    if (!Civi::settings()->get('always_post_to_accounts_receivable')) {
      return NULL;
    }
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $previousContributionStatus = empty($contributionParams['prevContribution']) ? NULL : $contributionStatuses[$contributionParams['prevContribution']->contribution_status_id];
    // Return if contribution status is not completed.
    if (!($this->isCompletedTransaction() && (empty($previousContributionStatus)
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
   *
   * @return bool
   */
  private function isPaymentInstrumentChange(array $params): bool {
    if (array_key_exists('payment_instrument_id', $params)) {
      if (CRM_Utils_System::isNull($params['prevContribution']->payment_instrument_id) &&
        !CRM_Utils_System::isNull($params['payment_instrument_id'])
      ) {
        //check if status is changed from Pending to Completed
        // do not update payment instrument changes for Pending to Completed
        if (!($this->isCompletedTransaction() &&
          $this->isOriginalStatusPending())
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

  /**
   * The function is responsible for handling financial entries if payment instrument is changed
   *
   * @param array $inputParams
   *
   */
  private function updateFinancialAccountsOnPaymentInstrumentChange($inputParams) {
    $prevContribution = $inputParams['prevContribution'];
    $deferredFinancialAccount = $inputParams['deferred_financial_account_id'] ?? NULL;
    if (empty($deferredFinancialAccount)) {
      $deferredFinancialAccount = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($this->getOriginalContributionValue('financial_type_id'), 'Deferred Revenue Account is');
    }

    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($this->getContributionID(), 'DESC', FALSE, NULL, $deferredFinancialAccount);

    // there is no point to proceed as we can't find the last payment made
    // @todo we should throw an exception here rather than return false.
    if (empty($lastFinancialTrxnId['financialTrxnId'])) {
      return FALSE;
    }

    // If payment instrument is changed reverse the last payment
    //  in terms of reversing financial item and trxn
    $lastFinancialTrxn = civicrm_api3('FinancialTrxn', 'getsingle', ['id' => $lastFinancialTrxnId['financialTrxnId']]);
    unset($lastFinancialTrxn['id']);
    $lastFinancialTrxn['trxn_date'] = $inputParams['trxnParams']['trxn_date'];
    $lastFinancialTrxn['total_amount'] = -$inputParams['trxnParams']['total_amount'];
    $lastFinancialTrxn['net_amount'] = -$inputParams['trxnParams']['net_amount'];
    $lastFinancialTrxn['fee_amount'] = -$inputParams['trxnParams']['fee_amount'];
    $lastFinancialTrxn['contribution_id'] = $this->getContributionID();
    foreach ([$lastFinancialTrxn, $inputParams['trxnParams']] as $financialTrxnParams) {
      $trxn = CRM_Core_BAO_FinancialTrxn::create($financialTrxnParams);
      $trxnParams = [
        'total_amount' => $trxn->total_amount,
        'contribution_id' => $this->getContributionID(),
      ];
      $this->assignProportionalLineItems($trxnParams, $trxn->id, $prevContribution->total_amount);
    }

    $this->createDeferredTrxn($inputParams['line_item'] ?? NULL, TRUE, 'changePaymentInstrument');

    return TRUE;
  }

  /**
   * Create transaction for deferred revenue. Previously shared function being refactored
   *
   * @param array $lineItems
   * @param bool $update
   * @param string $context
   *
   */
  private function createDeferredTrxn($lineItems, $update = FALSE, $context = NULL) {
    $contributionDetails = $this->getUpdatedContribution();
    if (empty($lineItems)) {
      return;
    }
    $revenueRecognitionDate = $contributionDetails->revenue_recognition_date;
    if (!CRM_Utils_System::isNull($revenueRecognitionDate)) {
      if (!$update
        && (!$this->isCompletedTransaction()
          || ($contributionDetails->contribution_status_id != CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')
            && $contributionDetails->is_pay_later)
        )
      ) {
        return;
      }
      $trxnParams = [
        'contribution_id' => $this->getContributionID(),
        'fee_amount' => '0.00',
        'currency' => $contributionDetails->currency,
        'trxn_id' => $contributionDetails->trxn_id,
        'status_id' => $contributionDetails->contribution_status_id,
        'payment_instrument_id' => $contributionDetails->payment_instrument_id,
        'check_number' => $contributionDetails->check_number,
      ];

      $deferredRevenues = [];
      $lineItems = reset($lineItems);
      foreach ($lineItems as $key => $lineItem) {
        $lineTotal = !empty($lineItem['deferred_line_total']) ? $lineItem['deferred_line_total'] : $lineItem['line_total'];
        if ($lineTotal <= 0 && !$update) {
          continue;
        }
        $deferredRevenues[$key] = $lineItem;
        if (in_array($lineItem['entity_table'],
          ['civicrm_participant', 'civicrm_contribution'])
        ) {
          $deferredRevenues[$key]['revenue'][] = [
            'amount' => $lineTotal,
            'revenue_date' => $revenueRecognitionDate,
          ];
        }
        else {
          // for membership
          $lineItem['line_total'] = $lineTotal;
          $deferredRevenues[$key]['revenue'] = CRM_Core_BAO_FinancialTrxn::getMembershipRevenueAmount($lineItem);
        }
      }
      $accountRel = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));

      CRM_Utils_Hook::alterDeferredRevenueItems($deferredRevenues, $contributionDetails, $update, $context);

      foreach ($deferredRevenues as $key => $deferredRevenue) {
        $results = civicrm_api3('EntityFinancialAccount', 'get', [
          'entity_table' => 'civicrm_financial_type',
          'entity_id' => $deferredRevenue['financial_type_id'],
          'account_relationship' => ['IN' => ['Income Account is', 'Deferred Revenue Account is']],
        ]);
        if ($results['count'] != 2) {
          continue;
        }
        foreach ($results['values'] as $result) {
          if ($result['account_relationship'] == $accountRel) {
            $trxnParams['from_financial_account_id'] = $result['financial_account_id'];
          }
          else {
            $trxnParams['to_financial_account_id'] = $result['financial_account_id'];
          }
        }
        foreach ($deferredRevenue['revenue'] as $revenue) {
          $trxnParams['total_amount'] = $trxnParams['net_amount'] = $revenue['amount'];
          $trxnParams['trxn_date'] = CRM_Utils_Date::isoToMysql($revenue['revenue_date']);
          $financialTxn = CRM_Core_BAO_FinancialTrxn::create($trxnParams);
          $entityParams = [
            'entity_id' => $deferredRevenue['financial_item_id'],
            'entity_table' => 'civicrm_financial_item',
            'amount' => $revenue['amount'],
            'financial_trxn_id' => $financialTxn->id,
          ];
          civicrm_api3('EntityFinancialTrxn', 'create', $entityParams);
        }
      }
    }
  }

  /**
   * Create proportional entries in civicrm_entity_financial_trxn.
   *
   * @param array $entityParams
   * @param array $lineItems
   * @param array $financialItemIds
   * @param array $taxItems
   *
   * @throws \CRM_Core_Exception
   */
  private function createProportionalFinancialEntries(array $entityParams, array $lineItems, array $financialItemIds, array $taxItems) {
    $eftParams = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $entityParams['trxn_id'],
    ];
    foreach ($lineItems as $lineItem) {
      if ($lineItem['qty'] == 0) {
        continue;
      }
      $eftParams['entity_id'] = $financialItemIds[$lineItem['price_field_value_id']];
      $entityParams['line_item_amount'] = $lineItem['line_total'];
      $this->createProportionalEntry($entityParams, $eftParams);
      if (array_key_exists($lineItem['price_field_value_id'], $taxItems)) {
        $entityParams['line_item_amount'] = $taxItems[$lineItem['price_field_value_id']]['amount'];
        $eftParams['entity_id'] = $taxItems[$lineItem['price_field_value_id']]['financial_item_id'];
        $this->createProportionalEntry($entityParams, $eftParams);
      }
    }
  }

  /**
   * Create tax entry in civicrm_entity_financial_trxn table.
   *
   * @param array $entityParams
   *
   * @param array $eftParams
   *
   * @throws \CRM_Core_Exception
   */
  private function createProportionalEntry(array $entityParams, array $eftParams): void {
    $eftParams['amount'] = 0;
    if ($entityParams['contribution_total_amount'] != 0) {
      $eftParams['amount'] = $entityParams['line_item_amount'] * ($entityParams['trxn_total_amount'] / $entityParams['contribution_total_amount']);
    }
    // Record Entity Financial Trxn; CRM-20145
    EntityFinancialTrxn::create(FALSE)->setValues($eftParams)->execute();
  }

  /**
   * Function use to store line item proportionally in in entity financial trxn table
   *
   * @param array $trxnParams
   *
   * @param int $trxnId
   *
   * @param float $contributionTotalAmount
   *
   * @throws \CRM_Core_Exception
   */
  private function assignProportionalLineItems($trxnParams, $trxnId, $contributionTotalAmount) {
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($this->getContributionID());
    if (!empty($lineItems)) {
      // get financial item
      [$financialItemIds, $taxItems] = CRM_Contribute_BAO_Contribution::getLastFinancialItemIds($this->getContributionID());
      $entityParams = [
        'contribution_total_amount' => $contributionTotalAmount,
        'trxn_total_amount' => $trxnParams['total_amount'],
        'trxn_id' => $trxnId,
      ];
      $this->createProportionalFinancialEntries($entityParams, $lineItems, $financialItemIds, $taxItems);
    }
  }

  /**
   * Process price set and line items.
   *
   * @internal
   *
   * @param array $lineItems
   *   Line item array.
   *
   * @throws \CRM_Core_Exception
   */
  private function createLineItems($lineItems) {
    foreach ($lineItems as &$values) {
      foreach ($values as &$line) {
        $createdLineItem = CRM_Price_BAO_LineItem::create($line);

        if (!$this->isUpdate()) {
          $financialItem = CRM_Financial_BAO_FinancialItem::add($createdLineItem, $this->getUpdatedContribution());
          $line['financial_item_id'] = $financialItem->id;
          if (!empty($line['tax_amount'])) {
            CRM_Financial_BAO_FinancialItem::add($createdLineItem, $this->getUpdatedContribution(), TRUE);
          }
        }
      }
    }
    if (!$this->isUpdate()) {
      $this->createDeferredTrxn($lineItems);
    }
  }

  /**
   * @return bool
   */
  private function isUpdate(): bool {
    return !empty($this->originalContribution);
  }

  /**
   * @param int|null $lineItemID
   * @param $isTax
   * @return array
   *
   * @throws CRM_Core_Exception
   */
  private function getExistingFinancialItemForLine(?int $lineItemID, $isTax): array {
    if (!$lineItemID) {
      // This is being called to get the existing financial item, but it could be for a
      // new line which would not have one.
      return [];
    }
    if (!isset($this->previousFinancialItems[$lineItemID][$isTax])) {
      $this->previousFinancialItems[$lineItemID][$isTax] = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($lineItemID, $isTax);
    }
    return $this->previousFinancialItems[$lineItemID][$isTax] ?? [];
  }

  /**
   * Get the amount from the original line item.
   *
   * @param int|null $lineItemID
   * @param string $name
   * @return float
   */
  private function getOriginalLineItemAmount(?int $lineItemID, string $name): float {
    if (!$lineItemID) {
      return 0.0;
    }
    $originalLineItem = $this->originalLineItems[$lineItemID] ?? [];
    return $originalLineItem[$name] ?? 0;
  }

  /**
   * @param array $newLineItem
   * @param bool $isTax
   * @param int $trxnID
   *
   * @throws CRM_Core_Exception
   */
  private function reverseLineFinancialItem(array $newLineItem, bool $isTax, int $trxnID): void {
    $previousItem = $this->getExistingFinancialItemForLine($newLineItem['id'], $isTax);
    $isReversalRequired = $this->isReversalRequired($newLineItem, $isTax);
    if (!$isReversalRequired) {
      return;
    }
    $itemParams = [
      'transaction_date' => CRM_Utils_Date::isoToMysql($this->getUpdatedContribution()->receive_date),
      'contact_id' => $this->getUpdatedContribution()->contact_id,
      'entity_table' => 'civicrm_line_item',
      'entity_id' => $newLineItem['id'],
      'amount' => -$previousItem['amount'],
      'financial_account_id' => $previousItem['financial_account_id'],
      'currency' => $previousItem['currency'],
      'description' => $previousItem['description'],
      'status_id' => $previousItem['status_id'],
    ];
    $this->createFinancialItem($itemParams, $trxnID);
  }

  /**
   * @param array $newLineItem
   * @param bool $isTax
   * @return bool
   * @throws CRM_Core_Exception
   */
  public function isReversalRequired(array $newLineItem, bool $isTax): bool {
    $previousItem = $this->getExistingFinancialItemForLine($newLineItem['id'], $isTax);
    if (!$previousItem || !$previousItem['amount']) {
      return FALSE;
    }
    if ($previousItem['currency'] !== $this->updatedContribution->currency) {
      return TRUE;
    }
    if ($previousItem['contact_id'] !== (int) $this->updatedContribution->contact_id) {
      return TRUE;
    }
    if ($isTax && $previousItem['amount'] !== $newLineItem['tax_amount']) {
      return TRUE;
    }
    if ($isTax && $previousItem['financial_account_id'] !== (int) CRM_Financial_BAO_FinancialAccount::getSalesTaxFinancialAccount($newLineItem['financial_type_id'])) {
      return TRUE;
    }
    // @todo - compare line item financial account for normal ones- this has been a missing piece for a long time!
    return FALSE;
  }

  /**
   * @return int
   */
  public function getContributionID(): int {
    return $this->getUpdatedContribution()->id;
  }

  /**
   * Function to retrieve financial items that need to be recorded as result of changed fee.
   *
   * Relocated as-is from CRM_Price_BAO_LineItem::changeFeeSelections()'s support code.
   * This does the same job as isReversalRequired()/reverseLineFinancialItem() above -
   * deciding whether a line item's previous financial item needs reversing - but as a
   * bulk operation over a set of line items rather than per-line-item. Consolidating
   * the two is a follow-up, not part of this move.
   *
   * @param array $priceFieldValueIDsToCancel
   * @param array $lineItemsToUpdate
   *
   * @return array
   *   List of formatted reverse Financial Items to be recorded
   */
  private function getAdjustedFinancialItemsToRecord($priceFieldValueIDsToCancel, $lineItemsToUpdate): array {
    $financialItemsArray = [];
    $financialItemResult = $this->getNonCancelledFinancialItems();
    foreach ($financialItemResult as $updateFinancialItemInfoValues) {
      $updateFinancialItemInfoValues['transaction_date'] = date('YmdHis');

      // the below params are not needed as we are creating new financial item
      $totalFinancialAmount = $this->checkFinancialItemTotalAmountByLineItemID($updateFinancialItemInfoValues['entity_id']);
      unset($updateFinancialItemInfoValues['id']);
      unset($updateFinancialItemInfoValues['created_date']);

      // Reverse line items omitted from the submitted lines.
      if (in_array($updateFinancialItemInfoValues['price_field_value_id'], $priceFieldValueIDsToCancel)
        && $updateFinancialItemInfoValues['amount'] != 0
      ) {

        // INSERT negative financial_items
        $updateFinancialItemInfoValues['amount'] = -$updateFinancialItemInfoValues['amount'];
        // Append rather than key on entity_id: one line item can carry several
        // financial items (revenue plus sales tax) and each needs its own reversal
        // on its own financial account. The loop that consumes this array reads
        // only the values, so the key carries no meaning.
        $financialItemsArray[] = $updateFinancialItemInfoValues;
      }
      // INSERT a financial item to record surplus/lesser amount when a text price fee is changed
      elseif (
        !empty($lineItemsToUpdate)
        && isset($lineItemsToUpdate[$updateFinancialItemInfoValues['price_field_value_id']])
        && $lineItemsToUpdate[$updateFinancialItemInfoValues['price_field_value_id']]['html_type'] == 'Text'
        && $updateFinancialItemInfoValues['amount'] > 0
      ) {
        $amountChangeOnTextLineItem = $lineItemsToUpdate[$updateFinancialItemInfoValues['price_field_value_id']]['line_total'] - $totalFinancialAmount;
        if ($amountChangeOnTextLineItem !== (float) 0) {
          // calculate the amount difference, considered as financial item amount
          $updateFinancialItemInfoValues['amount'] = $amountChangeOnTextLineItem;
          $financialItemsArray[$updateFinancialItemInfoValues['entity_id']] = $updateFinancialItemInfoValues;
        }
      }
    }

    return $financialItemsArray;
  }

  /**
   * Get Financial items, culling out any that have already been reversed.
   *
   * Only financial items belonging to one of $this->originalLineItems are eligible -
   * matching purely on price_field_value_id would otherwise also catch a
   * settled financial item on a completely different contribution that
   * happens to reuse the same price option (eg. a membership renewal reusing
   * the same price field value each time).
   *
   * @return array
   *   Array of financial items that have not been reversed.
   */
  private function getNonCancelledFinancialItems(): array {
    if (empty($this->originalLineItems)) {
      return [];
    }
    $financialItemResult = (array) FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_line_item')
      ->addWhere('entity_id', 'IN', array_keys($this->originalLineItems))
      ->execute();

    // price_field_value_id isn't a financial_item field - pull it in from the line item.
    foreach ($financialItemResult as $index => $financialItem) {
      $financialItemResult[$index]['price_field_value_id'] = $this->originalLineItems[$financialItem['entity_id']]['price_field_value_id'];
    }

    $items = [];
    foreach ($financialItemResult as $index => $financialItem) {
      $items[$financialItem['price_field_value_id']][$index] = $financialItem['amount'];

      foreach ($items[$financialItem['price_field_value_id']] as $existingItemID => $existingAmount) {
        if ($financialItem['amount'] + $existingAmount == 0) {
          // Filter both rows as they cancel each other out.
          unset($financialItemResult[$index]);
          unset($financialItemResult[$existingItemID]);
          unset($items[$financialItem['price_field_value_id']][$existingItemID]);
          unset($items[$financialItem['price_field_value_id']][$index]);
        }
      }
    }
    return $financialItemResult;
  }

  /**
   * Helper function to return sum of financial item's amount related to a line-item
   * @param int $lineItemID
   *
   * @return float $financialItem
   */
  private function checkFinancialItemTotalAmountByLineItemID($lineItemID) {
    return CRM_Core_DAO::singleValueQuery("
      SELECT SUM(amount)
      FROM civicrm_financial_item
      WHERE entity_table = 'civicrm_line_item' AND entity_id = {$lineItemID}
    ");
  }

  /**
   * Update related contribution of an entity and add/update/cancel financial
   * records on a change of fee selection.
   *
   * @param array $submittedLineItems
   * @param int $contributionId
   * @param float $taxAmount
   *
   * @internal function is expected to change. Tests are in CRM_Event_BAO_ChangeFeeSelectionTest
   * and CRM_Member_Form_MembershipTest and should not directly call this.
   *
   * @throws \CRM_Core_Exception
   */
  public function changeFeeSelections(array $submittedLineItems, int $contributionId, float $taxAmount): void {
    $lineItemsToAdd = $this->getLineItemsToAdd($submittedLineItems);
    $requiredChanges = $this->getLineItemsToAlter($submittedLineItems);

    // get financial information that need to be recorded on basis on submitted price field value IDs
    if (!empty($requiredChanges['line_items_to_cancel']) || !empty($requiredChanges['line_items_to_update'])) {
      // @todo - this IF is to get this through PR merge but I suspect that it should not
      // be necessary & is masking something else.
      $financialItemsArray = $this->getAdjustedFinancialItemsToRecord(
        array_keys($requiredChanges['line_items_to_cancel']),
        $requiredChanges['line_items_to_update']
      );
    }

    // update line item with changed line total and other information
    $totalParticipant = 0;
    $amountLevel = [];
    if (!empty($requiredChanges['line_items_to_update'])) {
      foreach ($requiredChanges['line_items_to_update'] as $priceFieldValueID => $priceFieldValue) {
        $amountLevel[] = $priceFieldValue['label'] . ' - ' . (float) $priceFieldValue['qty'];
        if (($priceFieldValue['entity_table'] ?? NULL) === 'civicrm_participant' && isset($priceFieldValue['participant_count'])) {
          $totalParticipant += $priceFieldValue['participant_count'];
        }
      }
    }

    foreach (array_merge($requiredChanges['line_items_to_resurrect'], $requiredChanges['line_items_to_cancel'], $requiredChanges['line_items_to_update']) as $lineItemToAlter) {
      // Must use BAO rather than api because a bad line it in the api which we want to avoid.
      CRM_Price_BAO_LineItem::create($lineItemToAlter);
    }

    // $contributionId may be NULL here and will get written to LineItem, maybe we don't need to pass it in if empty?
    $this->addLineItemOnChangeFeeSelection($lineItemsToAdd);

    // If $contributionId is NULL this will crash
    $updatedAmount = CRM_Price_BAO_LineItem::getLineTotal($contributionId);
    $displayParticipantCount = '';
    if ($totalParticipant > 0) {
      $displayParticipantCount = ' Participant Count -' . $totalParticipant;
    }
    $updateAmountLevel = NULL;
    if (!empty($amountLevel)) {
      $updateAmountLevel = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $amountLevel) . $displayParticipantCount . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    // $contributionId must not be NULL
    $trxn = $this->recordAdjustedAmount($updatedAmount, $contributionId, $taxAmount, $updateAmountLevel);

    if (!empty($financialItemsArray)) {
      foreach ($financialItemsArray as $updateFinancialItemInfoValues) {
        $this->createFinancialItem($updateFinancialItemInfoValues, $trxn ? $trxn->id : NULL);
      }
    }

    // This won't work if there is no contribution
    $this->addFinancialItemsOnLineItemsChange(array_merge($lineItemsToAdd, $requiredChanges['line_items_to_resurrect']), $contributionId, $trxn->id ?? NULL);
  }

  /**
   * Get the submitted line items that do not correspond to any of the contribution's
   * existing line items.
   *
   * Unlike updates, cancellations or resurrections these need no comparison against
   * previous state - a submitted price field value with no matching previous line item
   * is, by definition, a new one.
   *
   * @param array $submittedLineItems
   *   Line items, keyed by price_field_value_id, as currently submitted for the contribution.
   *
   * @return array
   */
  private function getLineItemsToAdd(array $submittedLineItems): array {
    $previousPriceFieldValueIDs = array_column($this->originalLineItems, 'price_field_value_id');
    return array_diff_key($submittedLineItems, array_flip($previousPriceFieldValueIDs));
  }

  /**
   * Helper function to retrieve line items that need to be altered.
   *
   * We iterate through the previous line items for the given entity to determine
   * what alterations to line items need to be made to reflect the new line items.
   *
   * There are 3 possible changes required - per the keys in the return array. (A fourth
   * possible change, adding a line item with no previous counterpart at all, is handled
   * separately by getLineItemsToAdd() as it needs no comparison against previous state.)
   *
   * Relocated as-is from CRM_Price_BAO_LineItem::changeFeeSelections()'s support code.
   *
   * @internal - will change.
   *
   * @param array $submittedLineItems
   *
   * @return array
   *   Array of line items to alter with the following keys
   *   - line_items_to_update. If we have already been an active option and a change has
   *     happened then it should be in this array.
   *   - line_items_to_cancel. Line items currently selected but not selected in the new selection.
   *     These need to be zero'd out.
   *   - line_items_to_resurrect. Line items previously selected and then deselected. These need to be
   *     re-enabled rather than a new one added.
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function getLineItemsToAlter(array $submittedLineItems): array {
    $lineItemsToUpdate = [];
    $submittedPriceFieldValueIDs = array_keys($submittedLineItems);
    $lineItemsToCancel = $lineItemsToResurrect = [];

    foreach ($this->originalLineItems as $id => $previousLineItem) {
      if (in_array($previousLineItem['price_field_value_id'], $submittedPriceFieldValueIDs)) {
        $submittedLineItem = $submittedLineItems[$previousLineItem['price_field_value_id']];
        if (($submittedLineItem['html_type'] ?? NULL) == 'Text') {
          // If a 'Text' price field was updated by changing qty value, then we are not adding new line-item but updating the existing one,
          //  because unlike other kind of price-field, it's related price-field-value-id isn't changed and thats why we need to make an
          //  exception here by adding financial item for updated line-item and will reverse any previous financial item entries.
          $lineItemsToUpdate[$previousLineItem['price_field_value_id']] = array_merge($submittedLineItem, ['id' => $id]);
        }
        else {
          // for updating the line items i.e. use-case - once deselect-option selecting again
          if (($previousLineItem['line_total'] != $submittedLineItem['line_total'])
            || (
              // This would be a $0 line item - but why it should be catered to
              // other than when the above condition is unclear.
              $submittedLineItem['line_total'] == 0 && $submittedLineItem['qty'] == 1
            )
            || (
              $previousLineItem['qty'] != $submittedLineItem['qty']
            )
          ) {
            $lineItemsToUpdate[$previousLineItem['price_field_value_id']] = $submittedLineItem;
            $lineItemsToUpdate[$previousLineItem['price_field_value_id']]['id'] = $id;
            // Format is actually '0.00'
            if ($previousLineItem['line_total'] == 0) {
              $resurrectedLineItem = $submittedLineItem;
              $resurrectedLineItem['id'] = $id;
              $lineItemsToResurrect[] = $resurrectedLineItem;
            }
          }
          // If there was previously a submitted line item for the same option value then there is
          // either no change or a qty adjustment. In either case we are not doing an add + reversal.
          unset($lineItemsToCancel[$previousLineItem['price_field_value_id']]);
        }
      }
      else {
        if (!$this->isCancelled($previousLineItem)) {
          $cancelParams = ['qty' => 0, 'line_total' => 0, 'tax_amount' => 0, 'participant_count' => 0, 'non_deductible_amount' => 0, 'id' => $id];
          $lineItemsToCancel[$previousLineItem['price_field_value_id']] = array_merge($previousLineItem, $cancelParams);

        }
      }
    }

    return [
      'line_items_to_update' => $lineItemsToUpdate,
      'line_items_to_cancel' => $lineItemsToCancel,
      'line_items_to_resurrect' => $lineItemsToResurrect,
    ];
  }

  /**
   * Check if a line item has already been cancelled.
   *
   * Relocated as-is from CRM_Price_BAO_LineItem::changeFeeSelections()'s support code.
   *
   * @internal - will change.
   *
   * @param array $lineItem
   *
   * @return bool
   */
  private function isCancelled($lineItem) {
    if ($lineItem['qty'] == 0 && $lineItem['line_total'] == 0) {
      return TRUE;
    }
  }

  /**
   * Add line Items as result of fee change.
   *
   * Each line item is expected to already carry its own
   * entity_id/entity_table/contribution_id.
   *
   * Relocated as-is from CRM_Price_BAO_LineItem::changeFeeSelections()'s support code.
   *
   * @internal - will change.
   *
   * @param array $lineItemsToAdd
   */
  private function addLineItemOnChangeFeeSelection($lineItemsToAdd) {
    // if there is no line item to add, do not proceed
    if (empty($lineItemsToAdd)) {
      return;
    }

    // insert financial items
    foreach ($lineItemsToAdd as $priceFieldValueID => $lineParams) {
      if (!array_key_exists('skip', $lineParams)) {
        CRM_Price_BAO_LineItem::create($lineParams);
      }
    }
  }

  /**
   * Add financial transactions when an array of line items is changed.
   *
   * Each line item is expected to already carry its own entity_id/entity_table.
   *
   * Relocated as-is from CRM_Price_BAO_LineItem::changeFeeSelections()'s support code.
   *
   * @internal - will change.
   *
   * @param array $lineItemsToAdd
   * @param int $contributionID
   * @param bool $trxnID
   *   Is there a change to the total balance requiring additional transactions to be created.
   */
  private function addFinancialItemsOnLineItemsChange($lineItemsToAdd, $contributionID, $trxnID) {
    $updatedContribution = new CRM_Contribute_BAO_Contribution();
    $updatedContribution->id = $contributionID;
    $updatedContribution->find(TRUE);
    $trxnArray = $trxnID ? ['id' => $trxnID] : NULL;

    foreach ($lineItemsToAdd as $priceFieldValueID => $lineParams) {
      $lineParams['contribution_id'] = $contributionID;
      $lineObj = CRM_Price_BAO_LineItem::retrieve($lineParams);
      // insert financial items
      // ensure entity_financial_trxn table has a linking of it.
      CRM_Financial_BAO_FinancialItem::add($lineObj, $updatedContribution, NULL, $trxnArray);
      if (isset($lineObj->tax_amount) && (float) $lineObj->tax_amount !== 0.00) {
        CRM_Financial_BAO_FinancialItem::add($lineObj, $updatedContribution, TRUE, $trxnArray);
      }
    }
  }

  /**
   * Record adjusted amount.
   *
   * Relocated as-is from CRM_Price_BAO_LineItem::changeFeeSelections()'s support code.
   *
   * @internal - will change.
   *
   * @param int $updatedAmount
   * @param int $contributionId
   * @param int $taxAmount
   * @param bool $updateAmountLevel
   *
   * @return bool|\CRM_Core_BAO_FinancialTrxn
   */
  private function recordAdjustedAmount($updatedAmount, $contributionId, $taxAmount = NULL, $updateAmountLevel = NULL) {
    $paidAmount = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addSelect('paid_amount')
      ->execute()->first()['paid_amount'];

    $balanceAmt = $updatedAmount - $paidAmount;

    $contributionStatuses = array_column(\Civi::entity('Contribution')->getOptions('contribution_status_id'), 'id', 'name');

    $updatedContributionDAO = new CRM_Contribute_BAO_Contribution();
    $adjustedTrxn = FALSE;
    if ($balanceAmt) {
      if ($paidAmount === 0.0) {
        //skip updating the contribution status if no payment is made
        $updatedContributionDAO->cancel_date = 'null';
        $updatedContributionDAO->cancel_reason = NULL;
      }
      else {
        $updatedContributionDAO->contribution_status_id = $balanceAmt > 0 ? $contributionStatuses['Partially paid'] : $contributionStatuses['Pending refund'];
      }

      // update contribution status and total amount without trigger financial code
      // as this is handled in current BAO function used for change selection
      $updatedContributionDAO->id = $contributionId;

      $updatedContributionDAO->total_amount = $updatedContributionDAO->net_amount = $updatedAmount;
      $updatedContributionDAO->fee_amount = 0;
      $updatedContributionDAO->tax_amount = $taxAmount;
      if (!empty($updateAmountLevel)) {
        $updatedContributionDAO->amount_level = $updateAmountLevel;
      }
      $updatedContributionDAO->save();
      // adjusted amount financial_trxn creation
      $updatedContribution = CRM_Contribute_BAO_Contribution::getValues(
        ['id' => $contributionId]
      );
      $toFinancialAccount = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($updatedContribution->financial_type_id, 'Accounts Receivable Account is');
      $adjustedTrxnValues = [
        'from_financial_account_id' => NULL,
        'to_financial_account_id' => $toFinancialAccount,
        'total_amount' => $balanceAmt,
        'net_amount' => $balanceAmt,
        'status_id' => $contributionStatuses['Completed'],
        'payment_instrument_id' => $updatedContribution->payment_instrument_id,
        'contribution_id' => $updatedContribution->id,
        'trxn_date' => date('YmdHis'),
        'currency' => $updatedContribution->currency,
      ];
      $adjustedTrxn = CRM_Core_BAO_FinancialTrxn::create($adjustedTrxnValues);
    }
    // CRM-17151: Update the contribution status to completed if balance is zero,
    //  because due to sucessive fee change will leave the related contribution status incorrect
    else {
      CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'contribution_status_id', $contributionStatuses['Completed']);
    }

    return $adjustedTrxn;
  }

}
