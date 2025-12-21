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

  public function getContributionID(): int {
    return $this->getUpdatedContribution()['id'];
  }

  public function getUpdatedContribution(): CRM_Contribute_DAO_Contribution {
    return $this->updatedContribution;
  }

  public function getOriginalContribution(): ?CRM_Contribute_BAO_Contribution {
    return $this->originalContribution;
  }

  public function getUpdatedContributionStatus(): string {
    return CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $this->updatedContribution->contribution_status_id);
  }

  public function getOriginalContributionStatus(): ?string {
    if (!$this->originalContribution) {
      return NULL;
    }
    return CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $this->originalContribution->contribution_status_id);
  }

  public function getOriginalPaymentInstrumentID(): ?int {
    if (!$this->originalContribution) {
      return NULL;
    }
    return $this->originalContribution->payment_instrument_id;
  }

  public function getOriginalContributionValue(string $key): mixed {
    if (!$this->originalContribution) {
      return NULL;
    }
    return $this->originalContribution->$key;
  }

  public function isNegativeTransaction(): bool {
    return in_array($this->getUpdatedContributionStatus(), ['Refunded', 'Chargeback', 'Cancelled'], TRUE);
  }

  public function isFailedTransaction(): bool {
    return $this->getUpdatedContributionStatus() === 'Failed';
  }

  public function isPendingTransaction(): bool {
    return $this->getUpdatedContributionStatus() === 'Pending';
  }

  public function isCompletedTransaction(): bool {
    return $this->getUpdatedContributionStatus() === 'Completed';
  }

  public function isAccountsReceivableTransaction(): bool {
    return $this->getUpdatedContributionStatus() === 'Pending' || $this->getUpdatedContributionStatus() === 'In Progress';
  }

  public function isOriginalStatusPending(): bool {
    return in_array($this->getOriginalContributionStatus(), ['Pending', 'In Progress'], TRUE);
  }

  public function isStatusChange(): bool {
    return $this->originalContribution->contribution_status_id !== $this->updatedContribution->contribution_status_id;
  }

  public function getOriginalFinancialAccount(): ?int {
    if (!$this->originalContribution) {
      return NULL;
    }
    $accountRelationship = $this->updatedContribution->revenue_recognition_date ? 'Deferred Revenue Account is' : 'Income Account is';
    return CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($this->originalContribution->financial_type_id, $accountRelationship);
  }

  /**
   * @throws CRM_Core_Exception
   */
  public function getUpdatedFinancialAccount(): int {
    $accountRelationship = $this->updatedContribution->revenue_recognition_date ? 'Deferred Revenue Account is' : 'Income Account is';
    $account = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($this->updatedContribution->financial_type_id, $accountRelationship);
    if (!$account) {
      throw new CRM_Core_Exception(ts("Account not configured '%1' for financial type %2", [
        '1' => $accountRelationship,
        '2' => CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'financial_type_id', $this->updatedContribution->financial_type_id),
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
   * @param array $params
   *
   * @return int
   * @throws CRM_Core_Exception
   */
  public function getToFinancialAccount(array $params): int {
    if ($this->isAccountsReceivableTransaction()) {
      return CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $params['financial_type_id'],
        'Accounts Receivable Account is'
      );
    }
    if (!empty($params['payment_processor'])) {
      return CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($params['payment_processor'], NULL, 'civicrm_payment_processor');
    }
    // Probably here we should check $this->updatedContribution instead of params
    // and then we would not need the next if.
    if (!empty($params['payment_instrument_id'])) {
      return CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount($params['payment_instrument_id']);
    }
    // Probably updatedContribution makes more sense - per previous comment.
    // dev/financial#160 - If this is a contribution update, also check for an existing payment_instrument_id.
    if ($this->getOriginalPaymentInstrumentID()) {
      return CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount((int) $params['prevContribution']->payment_instrument_id);
    }
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Asset' "));
    $queryParams = [1 => [$relationTypeId, 'Integer']];
    return CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = %1", $queryParams);
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
    $isUpdate = $this->isUpdate();

    $contributionStatus = $this->getUpdatedContributionStatus();

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
        $previousContributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $params['prevContribution']->contribution_status_id);
        if (!(($previousContributionStatus === 'Pending' || $previousContributionStatus === 'In Progress')
          && $contributionStatus === 'Completed')
        ) {
          $params['trxnParams']['payment_instrument_id'] = $params['prevContribution']->payment_instrument_id;
          $params['trxnParams']['check_number'] = $params['prevContribution']->check_number;
        }

        //if financial account is changed
        if ($this->isFinancialAccountChanged()) {
          $params['trxnParams']['trxn_date'] = date('YmdHis');
          $params['total_amount'] = 0;
          // If we have a fee amount set reverse this as well.
          if (isset($params['fee_amount'])) {
            $params['trxnParams']['fee_amount'] = 0 - $params['fee_amount'];
          }
          if ($this->isAccountsReceivableTransaction()) {
            $accountRelationship = $this->getUpdatedContribution()->revenue_recognition_date ? 'Deferred Revenue Account is' : 'Income Account is';
            $params['trxnParams']['to_financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
              $params['prevContribution']->financial_type_id, $accountRelationship);
          }
          else {
            $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($params['prevContribution']->id, 'DESC');
            if (!empty($lastFinancialTrxnId['financialTrxnId'])) {
              $params['trxnParams']['to_financial_account_id'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $lastFinancialTrxnId['financialTrxnId'], 'to_financial_account_id');
            }
          }
          $params['trxnParams']['total_amount'] = $params['trxnParams']['net_amount'] = ($params['total_amount'] - $params['prevContribution']->total_amount);
          $this->updateFinancialAccounts($params, 'changeFinancialType');
          $params['skipLineItem'] = FALSE;
          foreach ($params['line_item'] as &$lineItems) {
            foreach ($lineItems as &$line) {
              $line['financial_type_id'] = $params['financial_type_id'];
            }
          }
          $this->createDeferredTrxn($params['line_item'] ?? NULL, TRUE, 'changeFinancialType');
          /* $params['trxnParams']['to_financial_account_id'] = $trxnParams['to_financial_account_id']; */
          $params['financial_account_id'] = $this->getUpdatedFinancialAccount();
          $params['total_amount'] = $params['trxnParams']['total_amount'] = $params['trxnParams']['net_amount'] = $trxnParams['total_amount'];
          // Set the transaction fee amount back to the original value for creating the new positive financial trxn.
          if (isset($params['fee_amount'])) {
            $params['trxnParams']['fee_amount'] = $params['fee_amount'];
          }
          $this->updateFinancialAccounts($params);
          $this->createDeferredTrxn($params['line_item'] ?? NULL, TRUE);
          $params['trxnParams']['to_financial_account_id'] = $trxnParams['to_financial_account_id'];
          $updated = TRUE;
          $params['deferred_financial_account_id'] = $this->getUpdatedFinancialAccount();
        }

        //Update contribution status
        $params['trxnParams']['status_id'] = $this->getUpdatedContribution()->contribution_status_id;
        if (!isset($params['refund_trxn_id'])) {
          // CRM-17751 This has previously been deliberately set. No explanation as to why one variant
          // gets preference over another so I am only 'protecting' a very specific tested flow
          // and letting natural justice take care of the rest.
          $params['trxnParams']['trxn_id'] = $this->getUpdatedContribution()->trxn_id;
        }
        if (!empty($params['contribution_status_id']) &&
          $this->getOriginalContributionStatus() !== $this->getUpdatedContributionStatus()
        ) {
          //Update Financial Records
          $callUpdateFinancialAccounts = $this->updateFinancialAccountsOnContributionStatusChange($params);
          if ($callUpdateFinancialAccounts) {
            $this->updateFinancialAccounts($params, 'changedStatus');
            $this->createDeferredTrxn($params['line_item'] ?? NULL, TRUE, 'changedStatus');
          }
          $updated = TRUE;
        }

        // change Payment Instrument for a Completed contribution
        // first handle special case when contribution is changed from Pending to Completed status when initial payment
        // instrument is null and now new payment instrument is added along with the payment
        $params['trxnParams']['payment_instrument_id'] = $this->getUpdatedContribution()->payment_instrument_id;
        $params['trxnParams']['check_number'] = $params['check_number'] ?? NULL;

        if ($this->isPaymentInstrumentChange($params)) {
          $updated = $this->updateFinancialAccountsOnPaymentInstrumentChange($params);
        }

        //if Change contribution amount
        $params['trxnParams']['fee_amount'] = $params['fee_amount'] ?? NULL;
        $params['trxnParams']['net_amount'] = $params['net_amount'] ?? NULL;
        $totalAmount = $this->getUpdatedContribution()->total_amount ?? 0;
        $params['trxnParams']['total_amount'] = $trxnParams['total_amount'] = $params['total_amount'] = $totalAmount;
        $params['trxnParams']['trxn_id'] = $this->getUpdatedContribution()->trxn_id;
        if ($this->isContributionTotalChanged()) {
          //Update Financial Records
          $params['trxnParams']['from_financial_account_id'] = NULL;
          $params['trxnParams']['total_amount'] = $params['trxnParams']['net_amount'] = ($params['total_amount'] - $params['prevContribution']->total_amount);
          $this->updateFinancialAccounts($params, 'changedAmount');
          $this->createDeferredTrxn($params['line_item'] ?? NULL, TRUE, 'changedAmount');
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
      if (!empty($params['membership_id'])) {
        //so far $params['membership_id'] should only be set coming in from membershipBAO::create so the situation where multiple memberships
        // are created off one contribution should be handled elsewhere
        $entityId = $params['membership_id'];
        $entityTable = 'civicrm_membership';
      }
      else {
        $entityId = $this->getContributionID();
        $entityTable = 'civicrm_contribution';
      }
      $this->createLineItems($entityId, $params['line_item'], $entityTable);
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
      $prevFinancialItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($lineItemDetails['id']);
      $financialAccount = CRM_Contribute_BAO_FinancialProcessor::getFinancialAccountForStatusChangeTrxn($params, $prevFinancialItem['financial_account_id']);

      $previousLineItemTotal = $previousLineItem['line_total'] ?? 0;
      $itemParams = [
        'transaction_date' => CRM_Utils_Date::isoToMysql($postUpdateContribution->receive_date),
        'contact_id' => $postUpdateContribution->contact_id,
        'currency' => $postUpdateContribution->currency,
        'amount' => $this->getFinancialItemAmountFromParams($context, $lineItemDetails, $previousLineItemTotal),
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
          $taxAmount = $previousLineItem['tax_amount'] ?? 0;
        }
        elseif ($previousLineItemTotal != $lineItemDetails['line_total']) {
          $taxAmount -= $previousLineItem['tax_amount'] ?? 0;
        }
        if ($taxAmount != 0) {
          $itemParams['amount'] = CRM_Contribute_BAO_FinancialProcessor::getMultiplier($postUpdateContribution->contribution_status_id, $context) * $taxAmount;
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
   * @param string $context
   *   changeFinancialType| changedAmount
   * @param array $lineItemDetails
   *   Line items.
   * @param int $previousLineItemTotal
   *
   * @return float
   * @todo move recordFinancialAccounts & helper functions to their own class?
   *
   */
  protected function getFinancialItemAmountFromParams($context, $lineItemDetails, $previousLineItemTotal) {
    if ($context === 'changedAmount') {
      $lineTotal = $lineItemDetails['line_total'];
      if ($lineTotal != $previousLineItemTotal) {
        $lineTotal -= $previousLineItemTotal;
      }
      return $lineTotal;
    }
    elseif ($context === 'changeFinancialType') {
      return -$lineItemDetails['line_total'];
    }
    elseif ($context === 'changedStatus') {
      $cancelledTaxAmount = 0;
      if ($this->isContributionUpdateARefund()) {
        $cancelledTaxAmount = $lineItemDetails['tax_amount'] ?? '0.00';
      }
      $isContributionStatusNegative = CRM_Contribute_BAO_Contribution::isContributionStatusNegative($this->updatedContribution->contribution_status_id);
      return ($isContributionStatusNegative ? -1 : 1) * ((float) $lineItemDetails['line_total'] + (float) $cancelledTaxAmount);
    }
    elseif ($context === NULL) {
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
   * @todo stop passing $params by reference. It is unclear the purpose of doing this &
   * adds unpredictability.
   *
   */
  private function updateFinancialAccounts(&$params, $context = NULL) {
    $trxn = CRM_Core_BAO_FinancialTrxn::create($params['trxnParams']);
    // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
    $params['entity_id'] = $trxn->id;

    $trxnIds['id'] = $params['entity_id'];
    foreach ($params['line_item'] as $fieldId => $fields) {
      $params = $this->createFinancialItemsForLine($params, $context, $fields, $trxnIds, $fieldId);
    }
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
  public function isFinancialAccountChanged(): bool {
    $oldFinancialAccount = $this->getOriginalFinancialAccount();
    $newFinancialAccount = $this->getUpdatedFinancialAccount();
    return $oldFinancialAccount !== $newFinancialAccount;
  }

  public function isContributionTotalChanged(): bool {
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
   *
   * @return bool
   *   Return indicates whether the updateFinancialAccounts function should continue.
   */
  private function updateFinancialAccountsOnContributionStatusChange(&$params) {
    $previousContributionStatus = $this->getOriginalContributionStatus();
    $currentContributionStatus = $this->getUpdatedContributionStatus();

    if ((($previousContributionStatus === 'Partially paid' && $currentContributionStatus === 'Completed')
      || ($previousContributionStatus === 'Pending refund' && $currentContributionStatus === 'Completed')
      // This concept of pay_later as different to any other sort of pending is deprecated & it's unclear
      // why it is here or where it is handled instead.
      || ($previousContributionStatus === 'Pending' && $params['prevContribution']->is_pay_later == TRUE
        && $currentContributionStatus === 'Partially paid'))
    ) {
      return FALSE;
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

      $transactionIDs[] = $this->recordAlwaysAccountsReceivable($params['trxnParams'], $params);
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
  private function recordAlwaysAccountsReceivable(&$trxnParams, $contributionParams) {
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
   *
   * @return bool
   */
  public function isPaymentInstrumentChange(array $params): bool {
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
      $deferredFinancialAccount = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($prevContribution->financial_type_id, 'Deferred Revenue Account is');
    }

    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($prevContribution->id, 'DESC', FALSE, NULL, $deferredFinancialAccount);

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
    $lastFinancialTrxn['contribution_id'] = $prevContribution->id;
    foreach ([$lastFinancialTrxn, $inputParams['trxnParams']] as $financialTrxnParams) {
      $trxn = CRM_Core_BAO_FinancialTrxn::create($financialTrxnParams);
      $trxnParams = [
        'total_amount' => $trxn->total_amount,
        'contribution_id' => $this->getUpdatedContribution()->id,
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
        && ($contributionDetails->contribution_status_id != CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed')
          || ($contributionDetails->contribution_status_id != CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')
            && $contributionDetails->is_pay_later)
        )
      ) {
        return;
      }
      $trxnParams = [
        'contribution_id' => $contributionDetails->id,
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
        if ($context === 'changeFinancialType') {
          $deferredRevenues[$key]['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_LineItem', $lineItem['id'], 'financial_type_id');
        }
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
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($trxnParams['contribution_id']);
    if (!empty($lineItems)) {
      // get financial item
      [$financialItemIds, $taxItems] = CRM_Contribute_BAO_Contribution::getLastFinancialItemIds($trxnParams['contribution_id']);
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
   * @param int $entityId
   * @param array $lineItems
   *   Line item array.
   * @param string $entityTable
   *   Entity table.
   *
   * @throws \CRM_Core_Exception
   */
  private function createLineItems(int $entityId, array $lineItems, $entityTable = 'civicrm_contribution') {
    $contributionID = $this->getContributionID();
    foreach ($lineItems as &$values) {

      foreach ($values as &$line) {
        if (empty($line['entity_table'])) {
          $line['entity_table'] = $entityTable;
        }
        if (empty($line['entity_id'])) {
          $line['entity_id'] = $entityId;
        }
        $line['contribution_id'] = $contributionID;
        if ($line['entity_table'] === 'civicrm_contribution') {
          $line['entity_id'] = $contributionID;
        }

        // if financial type is not set and if price field value is NOT NULL
        // get financial type id of price field value
        if (!empty($line['price_field_value_id']) && empty($line['financial_type_id'])) {
          $line['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $line['price_field_value_id'], 'financial_type_id');
        }
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

}
