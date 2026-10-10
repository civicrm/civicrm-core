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
use Civi\Api4\LineItem;
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
  private array $updatedLineItems;
  private array $inputValues;
  private array $previousFinancialItems = [];

  /**
   * Added/changed/removed classification of the submitted line items against the
   * contribution's current line items, for the changeFeeSelections() path - see
   * classifyLineItemChanges(). Calculated once, in the constructor.
   *
   * @var array
   */
  private array $lineItemChanges;

  public function __construct(?CRM_Contribute_BAO_Contribution $originalContribution, CRM_Contribute_DAO_Contribution $updatedContribution, array $originalLineItems, array $updatedLineItems, array $inputValues = []) {
    // Deal with slopping typing first.
    if ($originalContribution) {
      $originalContribution->contribution_status_id = (int) $originalContribution->contribution_status_id;
    }
    $updatedContribution->contribution_status_id = (int) $updatedContribution->contribution_status_id;
    $this->originalContribution = $originalContribution;
    $this->updatedContribution = $updatedContribution;
    $this->originalLineItems = $originalLineItems;
    $this->updatedLineItems = $updatedLineItems;
    $this->inputValues = $inputValues;
    $this->lineItemChanges = $this->classifyLineItemChanges();
  }

  private function getUpdatedContribution(): CRM_Contribute_DAO_Contribution {
    return $this->updatedContribution;
  }

  private function getUpdatedLineItems(): array {
    return $this->updatedLineItems;
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

  private function getUpdatedContributionValue(string $key): mixed {
    return $this->updatedContribution->$key;
  }

  private function isNegativeTransaction(): bool {
    return in_array($this->getUpdatedContributionStatus(), ['Refunded', 'Chargeback', 'Cancelled'], TRUE);
  }

  private function isFailedTransaction(): bool {
    return $this->getUpdatedContributionStatus() === 'Failed';
  }

  private function isPendingTransaction(): bool {
    return in_array($this->getUpdatedContributionStatus(), ['Pending', 'Pending (Processing)', 'In Progress'], TRUE);
  }

  private function isCompletedTransaction(): bool {
    return $this->getUpdatedContributionStatus() === 'Completed';
  }

  private function isAccountsReceivableTransaction(): bool {
    return in_array($this->getUpdatedContributionStatus(), ['Pending', 'Pending (Processing)', 'In Progress'], TRUE);
  }

  private function isOriginalStatusPending(): bool {
    return in_array($this->getOriginalContributionStatus(), ['Pending', 'Pending (Processing)', 'In Progress'], TRUE);
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

  /**
   * @param array $params
   *
   * @return int
   */
  private function createFinancialTrxn(array $params): int {
    $trxn = CRM_Core_BAO_FinancialTrxn::writeRecord($params);
    CRM_Financial_DAO_EntityFinancialTrxn::writeRecord([
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $this->getContributionID(),
      'financial_trxn_id' => $trxn->id,
      'amount' => $params['total_amount'],
    ]);
    return $trxn->id;
  }

  /**
   * @return bool
   */
  private function isRecordAccountsReceivable(): bool {
    return Civi::settings()
      ->get('always_post_to_accounts_receivable') && $this->isCompletedTransaction();
  }

  /**
   * @param int $financialTypeID
   *
   * @return int|null
   */
  private function getAccountsReceivableAccount(int $financialTypeID): ?int {
    return CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($financialTypeID, 'Accounts Receivable Account is');
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
          $this->updateFinancialAccounts($params, 'changeFinancialType');
          /* $params['trxnParams']['to_financial_account_id'] = $trxnParams['to_financial_account_id']; */
          $params['financial_account_id'] = $this->getUpdatedFinancialAccount();
          // Set the amounts back to the original value for creating the new positive financial trxn.
          $params['total_amount'] = $params['trxnParams']['net_amount'] = $params['trxnParams']['total_amount'] = $this->getUpdatedContribution()->total_amount;
          $params['trxnParams']['fee_amount'] = $this->getUpdatedContribution()->fee_amount;
          $this->updateFinancialAccounts($params);
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
          $this->updateFinancialAccounts($params, 'changedAmount');
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
        if ($this->isRecordAccountsReceivable()) {
          $this->recordAlwaysAccountsReceivable($trxnParams, $params);
        }
        $financialTxnID = $this->createFinancialTrxn($trxnParams);
        $params['entity_id'] = $financialTxnID;
      }
    }
    // record line items and financial items
    if (empty($params['skipLineItem'])) {
      $this->createLineItems($financialTxnID ?? NULL);
    }

    // create batch entry if batch_id is passed and
    // ensure no batch entry is been made on 'Pending' or 'Failed' contribution, CRM-16611
    if (!empty($params['batch_id']) && !empty($financialTxnID)) {
      $entityParams = [
        'batch_id' => $params['batch_id'],
        'entity_table' => 'civicrm_financial_trxn',
        'entity_id' => $financialTxnID,
      ];
      CRM_Batch_BAO_EntityBatch::create($entityParams);
    }

    // when a fee is charged
    if (!empty($params['fee_amount']) && (empty($params['prevContribution']) || $params['contribution']->fee_amount != $params['prevContribution']->fee_amount) && $skipRecords) {
      $amount = $params['fee_amount'] - ($this->getOriginalContributionValue('fee_amount') ?: 0);
      if ($amount) {
        if (empty($params['financial_type_id'])) {
          $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->getContributionID(), 'financial_type_id', 'id');
        }
        else {
          $financialTypeId = $params['financial_type_id'];
        }
        CRM_Core_BAO_FinancialTrxn::recordFees($params + ['to_financial_account_id' => $this->getToFinancialAccount($params)], $amount, $this->getContributionID(), $financialTypeId);
      }
    }

    unset($params['line_item']);
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
    if ($context === NULL || $context === 'changePaymentInstrument') {
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
    $trxnID = $this->createFinancialTrxn($params['trxnParams']);
    $lineItems = $this->getUpdatedLineItems();
    $postUpdateContribution = $this->getUpdatedContribution();
    foreach ($lineItems as $fieldValueId => $lineItemDetails) {
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
      $this->createFinancialItem($itemParams, $trxnID);

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
        $this->reverseLineFinancialItem($lineItemDetails, TRUE, $trxnID);
      }
      elseif ($isTaxTransactionRequired) {
        // In this scenario we are on the second pass. We have done a reversal
        // on the first pass and now we are doing a second pass to create the
        // new correct new transaction (ideally we would not do as 2 passes
        // but one after the other)
        $taxAmount = $lineItemDetails['tax_amount'];
        $this->createTaxFinancialItem($itemParams, $lineItemDetails['financial_type_id'], $taxAmount, $trxnID);
      }
      elseif ($isTaxAdjustmentRequired) {
        $previousTaxAmount = $previousLineItem['tax_amount'] ?? 0;
        $taxAmount = (float) $lineItemDetails['tax_amount'] - $previousTaxAmount;
        $this->createTaxFinancialItem($itemParams, $lineItemDetails['financial_type_id'], $taxAmount, $trxnID);
      }
    }
    $this->createDeferredTrxn(TRUE, $context);
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

    // Moving between processor-controlled pending states does not represent
    // any movement of money and must not create accounting transactions.
    if ($currentContributionStatus === 'Pending (Processing)'
      && in_array($previousContributionStatus, ['Pending', 'In Progress'], TRUE)
    ) {
      return;
    }

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
    elseif ((in_array($previousContributionStatus, ['Pending', 'Pending (Processing)'], TRUE)
        && $params['prevContribution']->is_pay_later)
      || $previousContributionStatus === 'In Progress'
    ) {
      $arAccountId = $this->getAccountsReceivableAccount($this->getUpdatedContributionValue('financial_type_id'));

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

      if ($this->isRecordAccountsReceivable() && !$this->getOriginalContributionValue('is_pay_later')) {
        $financialTrxnIDs[] = $this->recordAlwaysAccountsReceivable($params['trxnParams'], $params);
      }

      // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
      $params['entity_id'] = $financialTrxnIDs[] = $this->createFinancialTrxn($params['trxnParams']);

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
    $this->updateFinancialAccounts($params, 'changedStatus');
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
    $params = $trxnParams;
    $arAccountId = $this->getAccountsReceivableAccount($this->getUpdatedContributionValue('financial_type_id'));
    $params['to_financial_account_id'] = $arAccountId;
    $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
    $params['is_payment'] = FALSE;
    $trxnID = $this->createFinancialTrxn($params);
    $trxnParams['from_financial_account_id'] = $params['to_financial_account_id'];
    return $trxnID;
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
    foreach ([$lastFinancialTrxn, $inputParams['trxnParams']] as $index => $financialTrxnParams) {
      $trxnID = $this->createFinancialTrxn($financialTrxnParams);
      $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($this->getContributionID());
      if (!empty($lineItems)) {
        // get financial item
        [$financialItemIds, $taxItems] = CRM_Contribute_BAO_Contribution::getLastFinancialItemIds($this->getContributionID());
        // This trxn always carries the contribution's full total - reversed on this
        // first pass, then re-asserted on the second - so each line's share of it is
        // just its own total, signed to match.
        $isReversal = $index === 0;
        $eftParams = [
          'entity_table' => 'civicrm_financial_item',
          'financial_trxn_id' => $trxnID,
        ];
        foreach ($lineItems as $lineItem) {
          if ($lineItem['qty'] == 0) {
            continue;
          }
          $lineItemAmount = $prevContribution->total_amount == 0.0 ? 0 : $lineItem['line_total'];
          $eftParams['entity_id'] = $financialItemIds[$lineItem['price_field_value_id']];
          $eftParams['amount'] = $isReversal ? -$lineItemAmount : $lineItemAmount;
          EntityFinancialTrxn::create(FALSE)->setValues($eftParams)->execute();
          if (array_key_exists($lineItem['price_field_value_id'], $taxItems)) {
            $taxItemAmount = $prevContribution->total_amount == 0.0 ? 0 : $taxItems[$lineItem['price_field_value_id']]['amount'];
            $eftParams['entity_id'] = $taxItems[$lineItem['price_field_value_id']]['financial_item_id'];
            $eftParams['amount'] = $isReversal ? -$taxItemAmount : $taxItemAmount;
            EntityFinancialTrxn::create(FALSE)->setValues($eftParams)->execute();
          }
        }
      }
    }

    $this->createDeferredTrxn(TRUE, 'changePaymentInstrument');

    return TRUE;
  }

  /**
   * Create transaction for deferred revenue. Previously shared function being refactored
   *
   * @param bool $update
   * @param string $context
   *
   */
  private function createDeferredTrxn($update = FALSE, $context = NULL) {
    $contributionDetails = $this->getUpdatedContribution();
    // Load line items from the database (rather than relying on the construction-time
    // updatedLineItems snapshot) so this works for lines that only got their id on this
    // request, such as newly created contributions.
    $lineItems = (array) LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $this->getContributionID())
      ->execute()->indexBy('id');
    if (empty($lineItems) || CRM_Utils_System::isNull($this->getUpdatedContribution()->revenue_recognition_date)) {
      return;
    }
    // On initial creation (!$update) only completed, non-pay-later contributions get
    // their deferred revenue recorded here. Pay-later contributions get it recorded
    // later, when they transition to Completed via the $update = TRUE path.
    $isCompletedNonPayLater = $this->isCompletedTransaction() && !$this->getUpdatedContribution()->is_pay_later;
    if (!$update && !$isCompletedNonPayLater) {
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
    foreach ($lineItems as $key => $lineItem) {
      $lineTotal = $this->getFinancialItemAmountFromParams($context, $lineItem);
      if ($lineTotal <= 0 && !$update) {
        continue;
      }
      $deferredRevenues[$key] = $lineItem;
      $deferredRevenues[$key]['financial_item_id'] = $this->getExistingFinancialItemForLine($lineItem['id'] ?? NULL, FALSE)['id'] ?? NULL;
      if (in_array($lineItem['entity_table'],
        ['civicrm_participant', 'civicrm_contribution'])
      ) {
        $deferredRevenues[$key]['revenue'][] = [
          'amount' => $lineTotal,
          'revenue_date' => $this->getUpdatedContribution()->revenue_recognition_date,
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
        // @todo - this appears to result in 2 EntityFinancialTrxns rather than 1 - but if so
        // it is long-time broken & evidence of long-disuse.
        civicrm_api3('EntityFinancialTrxn', 'create', $entityParams);
      }
    }
  }

  /**
   * Process price set and line items.
   *
   * Reads the line items from the constructor's $updatedLineItems.
   *
   * @internal
   *
   * @param int|null $financialTrxnID
   *
   * @throws \CRM_Core_Exception
   */
  private function createLineItems(?int $financialTrxnID = NULL) {
    foreach ($this->getUpdatedLineItems() as $line) {
      $createdLineItem = CRM_Price_BAO_LineItem::create($line);

      if (!$this->isUpdate()) {
        $this->addFinancialItem($createdLineItem, FALSE, $financialTrxnID);
        if (!empty($line['tax_amount'])) {
          $this->addFinancialItem($createdLineItem, TRUE, $financialTrxnID);
        }
      }
    }
    if (!$this->isUpdate()) {
      $this->createDeferredTrxn();
    }
  }

  /**
   * Add the financial items and financial trxn.
   *
   * @param object $lineItem
   *   Line item object.
   * @param bool $taxTrxnID
   * @param int|null $trxnId
   *   Transaction paying for this item. Pass NULL only when nothing has paid for it yet.
   */
  private function addFinancialItem($lineItem, $taxTrxnID = FALSE, ?int $trxnId = NULL): void {
    $contribution = $this->getUpdatedContribution();
    $financialItemStatus = array_column(\Civi::entity('FinancialItem')->getOptions('status_id'), 'id', 'name');
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution->contribution_status_id);
    $itemStatus = NULL;
    if ($contributionStatus === 'Completed' || $contributionStatus === 'Pending refund') {
      $itemStatus = $financialItemStatus['Paid'];
    }
    elseif (in_array($contributionStatus, ['Pending', 'Pending (Processing)'], TRUE)
      // In progress is no longer present on new installs unless extensions add it.
      || $contributionStatus === 'In Progress'
    ) {
      $itemStatus = $financialItemStatus['Unpaid'];
    }
    elseif ($contributionStatus === 'Partially paid') {
      $itemStatus = $financialItemStatus['Partially paid'];
    }
    $params = [
      'transaction_date' => $contribution->receive_date,
      'contact_id' => $contribution->contact_id,
      'amount' => $lineItem->line_total,
      'currency' => $contribution->currency,
      'entity_table' => 'civicrm_line_item',
      'entity_id' => $lineItem->id,
      'description' => ($lineItem->qty != 1 ? $lineItem->qty . ' of ' : '') . $lineItem->label,
      'status_id' => $itemStatus,
    ];

    if ($taxTrxnID) {
      $params['amount'] = $lineItem->tax_amount;
      $params['description'] = Civi::settings()->get('tax_term');
      $accountRelName = 'Sales Tax Account is';
    }
    else {
      $accountRelName = 'Income Account is';
      if (property_exists($contribution, 'revenue_recognition_date') && !CRM_Utils_System::isNull($contribution->revenue_recognition_date)) {
        $accountRelName = 'Deferred Revenue Account is';
      }
    }
    if ($lineItem->financial_type_id) {
      $params['financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $lineItem->financial_type_id,
        $accountRelName
      );
    }
    $this->createFinancialItem($params, $trxnId);
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
   * Reads the removed/changed line items from getRemovedLineItems()/getChangedLineItems().
   *
   * @return array
   *   List of formatted reverse Financial Items to be recorded
   */
  private function getAdjustedFinancialItemsToRecord(): array {
    $priceFieldValueIDsToCancel = array_keys($this->getRemovedLineItems());
    $lineItemsToUpdate = $this->getChangedLineItems();
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
   * Reads the contribution id and submitted line items from the constructor's
   * $updatedContribution/$updatedLineItems.
   *
   * @internal function is expected to change. Tests are in CRM_Event_BAO_ChangeFeeSelectionTest
   * and CRM_Member_Form_MembershipTest and should not directly call this.
   *
   * @throws \CRM_Core_Exception
   */
  public function changeFeeSelections(): void {
    $contributionId = $this->getContributionID();
    $taxAmount = 0.0;
    foreach ($this->getUpdatedLineItems() as $submittedLineItem) {
      $taxAmount += $submittedLineItem['tax_amount'] ?? 0.0;
    }

    // get financial information that need to be recorded on basis on submitted price field value IDs
    $financialItemsArray = [];
    if (!empty($this->getRemovedLineItems()) || !empty($this->getChangedLineItems())) {
      // @todo - this IF is to get this through PR merge but I suspect that it should not
      // be necessary & is masking something else.
      $financialItemsArray = $this->getAdjustedFinancialItemsToRecord();
    }

    // update line item with changed line total and other information. A resurrected
    // line counts here too, same as a changed one.
    $totalParticipant = 0;
    $amountLevel = [];
    foreach (array_merge($this->getChangedLineItems(), $this->getResurrectedLineItems()) as $priceFieldValueID => $priceFieldValue) {
      $amountLevel[] = $priceFieldValue['label'] . ' - ' . (float) $priceFieldValue['qty'];
      if (($priceFieldValue['entity_table'] ?? NULL) === 'civicrm_participant' && isset($priceFieldValue['participant_count'])) {
        $totalParticipant += $priceFieldValue['participant_count'];
      }
    }

    foreach (array_merge($this->getResurrectedLineItems(), $this->getRemovedLineItems(), $this->getChangedLineItems()) as $lineItemToAlter) {
      // Must use BAO rather than api because a bad line it in the api which we want to avoid.
      CRM_Price_BAO_LineItem::create($lineItemToAlter);
    }

    $this->addLineItemOnChangeFeeSelection();

    $updatedAmount = CRM_Price_BAO_LineItem::getLineTotal($contributionId);
    $displayParticipantCount = '';
    if ($totalParticipant > 0) {
      $displayParticipantCount = ' Participant Count -' . $totalParticipant;
    }
    $updateAmountLevel = NULL;
    if (!empty($amountLevel)) {
      $updateAmountLevel = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $amountLevel) . $displayParticipantCount . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    $trxnID = $this->recordAdjustedAmount($updatedAmount, $taxAmount, $updateAmountLevel);

    if (!empty($financialItemsArray)) {
      foreach ($financialItemsArray as $updateFinancialItemInfoValues) {
        $this->createFinancialItem($updateFinancialItemInfoValues, $trxnID);
      }
    }

    // This won't work if there is no contribution
    $this->addFinancialItemsOnLineItemsChange($trxnID);
  }

  /**
   * Get the submitted line items with no active previous counterpart - see
   * classifyLineItemChanges(). Includes both a genuinely new selection and one
   * that was previously selected then cancelled (carrying the cancelled row's id
   * forward) - see getNewLineItems()/getResurrectedLineItems() to split those two
   * cases apart.
   */
  private function getAddedLineItems(): array {
    return $this->lineItemChanges['added'];
  }

  /**
   * Get the subset of getAddedLineItems() that is a genuinely new selection, with
   * no previous line item of any kind to reuse - these need inserting.
   */
  private function getNewLineItems(): array {
    $newLineItems = [];
    foreach ($this->getAddedLineItems() as $priceFieldValueID => $lineItem) {
      if (!isset($lineItem['id'])) {
        $newLineItems[$priceFieldValueID] = $lineItem;
      }
    }
    return $newLineItems;
  }

  /**
   * Get the subset of getAddedLineItems() that reuses a previously-cancelled line
   * item's row - these need updating, not inserting.
   */
  private function getResurrectedLineItems(): array {
    $resurrectedLineItems = [];
    foreach ($this->getAddedLineItems() as $priceFieldValueID => $lineItem) {
      if (isset($lineItem['id'])) {
        $resurrectedLineItems[$priceFieldValueID] = $lineItem;
      }
    }
    return $resurrectedLineItems;
  }

  /**
   * Get the submitted line items matching an active previous line whose value
   * differs - see classifyLineItemChanges().
   */
  private function getChangedLineItems(): array {
    return $this->lineItemChanges['changed'];
  }

  /**
   * Get the active previous line items with no submitted counterpart - see
   * classifyLineItemChanges().
   */
  private function getRemovedLineItems(): array {
    return $this->lineItemChanges['removed'];
  }

  /**
   * Classify the submitted line items (from the constructor's $updatedLineItems)
   * against the contribution's currently-active line items into added/changed/
   * removed buckets. Unchanged lines are simply omitted - nothing to do for them.
   * Calculated once, in the constructor - see $lineItemChanges.
   *
   * Identity is price_field_value_id - safe to use as a 1:1 match today. If a price
   * field value ever legitimately carries more than one line (eg. several free-text
   * amount entries against the same text field), findActivePreviousLineItem() is the
   * one place that would need to change - the rest of this classification doesn't
   * care how a match was found.
   *
   * @return array
   *   - added. Submitted lines with no active previous counterpart - this includes
   *     both a genuinely new selection and one that was previously selected then
   *     cancelled; the latter carries the cancelled row's id forward so it is
   *     reused rather than inserted fresh.
   *   - changed. Submitted lines matching an active previous line whose qty, unit
   *     price, line total, tax or non-deductible amount differs.
   *   - removed. Active previous lines with no submitted counterpart - zero'd out.
   */
  private function classifyLineItemChanges(): array {
    $added = $changed = $removed = [];
    $matchedIDs = [];

    foreach ($this->getUpdatedLineItems() as $priceFieldValueID => $submittedLineItem) {
      $previousLineItem = $this->findActivePreviousLineItem($priceFieldValueID);
      if ($previousLineItem === NULL) {
        $cancelledLineItem = $this->findCancelledPreviousLineItem($priceFieldValueID);
        if ($cancelledLineItem !== NULL) {
          $submittedLineItem['id'] = $cancelledLineItem['id'];
        }
        $added[$priceFieldValueID] = $submittedLineItem;
        continue;
      }
      $matchedIDs[] = $previousLineItem['id'];
      if ($this->lineItemValueChanged($previousLineItem, $submittedLineItem)) {
        $changed[$priceFieldValueID] = array_merge($submittedLineItem, ['id' => $previousLineItem['id']]);
      }
    }

    foreach ($this->originalLineItems as $id => $previousLineItem) {
      if (!$this->isCancelled($previousLineItem) && !in_array($id, $matchedIDs)) {
        $removed[$previousLineItem['price_field_value_id']] = array_merge($previousLineItem, [
          'qty' => 0,
          'line_total' => 0,
          'tax_amount' => 0,
          'participant_count' => 0,
          'non_deductible_amount' => 0,
          'id' => $id,
        ]);
      }
    }

    return ['added' => $added, 'changed' => $changed, 'removed' => $removed];
  }

  /**
   * Find the contribution's currently-active (non-cancelled) previous line item for
   * a price field value, if any.
   */
  private function findActivePreviousLineItem($priceFieldValueID): ?array {
    foreach ($this->originalLineItems as $previousLineItem) {
      if ($previousLineItem['price_field_value_id'] == $priceFieldValueID && !$this->isCancelled($previousLineItem)) {
        return $previousLineItem;
      }
    }
    return NULL;
  }

  /**
   * Find a previously-cancelled line item for a price field value, if any - so a
   * re-selected option reuses its row rather than inserting a fresh one.
   */
  private function findCancelledPreviousLineItem($priceFieldValueID): ?array {
    foreach ($this->originalLineItems as $previousLineItem) {
      if ($previousLineItem['price_field_value_id'] == $priceFieldValueID && $this->isCancelled($previousLineItem)) {
        return $previousLineItem;
      }
    }
    return NULL;
  }

  /**
   * Has a line item's value changed, for fee-selection-change purposes.
   */
  private function lineItemValueChanged(array $previousLineItem, array $submittedLineItem): bool {
    foreach (['qty', 'unit_price', 'line_total', 'tax_amount', 'non_deductible_amount'] as $field) {
      if ((float) ($previousLineItem[$field] ?? 0) !== (float) ($submittedLineItem[$field] ?? 0)) {
        return TRUE;
      }
    }
    return FALSE;
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
   * Reads the genuinely-new line items from getNewLineItems() - a resurrected one
   * (also in getAddedLineItems(), but carrying an id) already got its row updated
   * via changeFeeSelections()'s own row-update loop.
   *
   * @internal - will change.
   */
  private function addLineItemOnChangeFeeSelection() {
    // insert financial items
    foreach ($this->getNewLineItems() as $priceFieldValueID => $lineParams) {
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
   * Reads getAddedLineItems() - both a genuinely new line and a resurrected one
   * need 'add' (not 'adjust') financial-item treatment.
   *
   * @internal - will change.
   *
   * @param int $trxnID
   *   Transaction paying for the added items.
   */
  private function addFinancialItemsOnLineItemsChange(int $trxnID) {
    foreach ($this->getAddedLineItems() as $priceFieldValueID => $lineParams) {
      $lineParams['contribution_id'] = $this->getContributionID();
      $lineObj = CRM_Price_BAO_LineItem::retrieve($lineParams);
      // insert financial items
      // ensure entity_financial_trxn table has a linking of it.
      $this->addFinancialItem($lineObj, FALSE, $trxnID);
      if (isset($lineObj->tax_amount) && (float) $lineObj->tax_amount !== 0.00) {
        $this->addFinancialItem($lineObj, TRUE, $trxnID);
      }
    }
  }

  /**
   * Record adjusted amount.
   *
   * Relocated as-is from CRM_Price_BAO_LineItem::changeFeeSelections()'s support code.
   *
   * @param int $updatedAmount
   * @param int $taxAmount
   * @param bool $updateAmountLevel
   *
   * @return int
   *   The trxn recording the balance change, created even when the balance
   *   is unchanged (total_amount 0) so every financial item touched by the
   *   fee change has something to link to.
   */
  private function recordAdjustedAmount($updatedAmount, $taxAmount = NULL, $updateAmountLevel = NULL): int {
    $contributionId = $this->getContributionID();
    $paidAmount = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addSelect('paid_amount')
      ->execute()->first()['paid_amount'];

    $balanceAmt = $updatedAmount - $paidAmount;

    $contributionStatuses = array_column(\Civi::entity('Contribution')->getOptions('contribution_status_id'), 'id', 'name');

    // update contribution status and total amount without trigger financial code
    // as this is handled in current BAO function used for change selection
    $updatedContributionDAO = new CRM_Contribute_BAO_Contribution();
    $updatedContributionDAO->id = $contributionId;

    if ($balanceAmt) {
      if ($paidAmount === 0.0) {
        //skip updating the contribution status if no payment is made
        $updatedContributionDAO->cancel_date = 'null';
        $updatedContributionDAO->cancel_reason = NULL;
      }
      else {
        $updatedContributionDAO->contribution_status_id = $balanceAmt > 0 ? $contributionStatuses['Partially paid'] : $contributionStatuses['Pending refund'];
      }
    }
    else {
      // CRM-17151: Update the contribution status to completed if balance is zero,
      // because due to successive fee change will leave the related contribution status incorrect
      $updatedContributionDAO->contribution_status_id = $contributionStatuses['Completed'];
    }

    $updatedContributionDAO->total_amount = $updatedContributionDAO->net_amount = $updatedAmount;
    $updatedContributionDAO->fee_amount = 0;
    $updatedContributionDAO->tax_amount = $taxAmount;
    if (!empty($updateAmountLevel)) {
      $updatedContributionDAO->amount_level = $updateAmountLevel;
    }
    $updatedContributionDAO->save();

    $adjustedTrxnValues = [
      'from_financial_account_id' => NULL,
      'to_financial_account_id' => $this->getAccountsReceivableAccount($this->getUpdatedContributionValue('financial_type_id')),
      'total_amount' => $balanceAmt,
      'net_amount' => $balanceAmt,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'payment_instrument_id' => $this->getUpdatedContributionValue('payment_instrument_id'),
      'contribution_id' => $this->getContributionID(),
      'trxn_date' => date('YmdHis'),
      'currency' => $this->getUpdatedContributionValue('currency'),
    ];
    return $this->createFinancialTrxn($adjustedTrxnValues);
  }

}
