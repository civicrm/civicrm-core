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

use Civi\Api4\Contribution;
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

  private array $existingFinancialItems;

  /**
   * Sum of existing financial item amounts, keyed by line item (entity) ID -
   * see checkFinancialItemTotalAmountByLineItemID().
   *
   * @var array
   */
  private array $financialItemTotalsByLineItemID = [];

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
    $this->setNonCancelledFinancialItems();
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

  /**
   * @param array $params
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function updateFinancialRecordsOnContributionUpdate(array $params): array {
    if ($this->isUnpaidAndNotPayLater()) {
      $this->saveLineItemsWithNoFinancialTransaction();
      return $params;
    }
    $trxnParams = $this->getTrxnParams();
    $params['trxnParams'] = $trxnParams;
    $params['trxnParams']['total_amount'] = $trxnParams['total_amount'] = $params['total_amount'] = $this->getOriginalContribution()->total_amount;
    $params['trxnParams']['fee_amount'] = $this->getOriginalContribution()->fee_amount;
    $params['trxnParams']['net_amount'] = $this->getOriginalContribution()->net_amount;
    $params['trxnParams']['status_id'] = $this->getOriginalContribution()->contribution_status_id;
    if (!($this->isOriginalStatusPending() && $this->isCompletedTransaction())
    ) {
      $params['trxnParams']['payment_instrument_id'] = $this->getOriginalContribution()->payment_instrument_id;
      $params['trxnParams']['check_number'] = $this->getOriginalContribution()->check_number;
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
        $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($this->getOriginalContribution()->id, 'DESC');
        if (!empty($lastFinancialTrxnId['financialTrxnId'])) {
          $params['trxnParams']['to_financial_account_id'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $lastFinancialTrxnId['financialTrxnId'], 'to_financial_account_id');
        }
      }
      $params['skipLineItem'] = FALSE;
      // Set amounts to create a reversal transaction.
      $reversalTrxnParams = $params['trxnParams'];
      $reversalTrxnParams['total_amount'] = $reversalTrxnParams['net_amount'] = -$this->getOriginalContribution()->total_amount;
      $reversalTrxnParams['fee_amount'] = 0 - $this->getOriginalContribution()->fee_amount;
      $this->reversePriorFinancialTrxn($reversalTrxnParams, 'changeFinancialType');
      // Use the current (updated) amounts for the replacement positive trxn - $reversalTrxnParams
      // was a separate copy, so $params['trxnParams'] still holds the original contribution's amounts.
      $params['trxnParams']['total_amount'] = $params['trxnParams']['net_amount'] = $this->getUpdatedContribution()->total_amount;
      $params['trxnParams']['fee_amount'] = $this->getUpdatedContribution()->fee_amount;
      $this->updateFinancialAccounts($params['trxnParams'], NULL, $this->getUpdatedFinancialAccount());
      $params['trxnParams']['to_financial_account_id'] = $trxnParams['to_financial_account_id'];
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
      $this->getOriginalContribution()->contribution_status_id != $this->getUpdatedContribution()->contribution_status_id
    ) {
      //Update Financial Records
      $this->updateFinancialAccountsOnContributionStatusChange($params);
    }
    $trxnParams = $params['trxnParams'];
    unset($params['trxnParams']);

    // change Payment Instrument for a Completed contribution
    // first handle special case when contribution is changed from Pending to Completed status when initial payment
    // instrument is null and now new payment instrument is added along with the payment
    $trxnParams['payment_instrument_id'] = $this->getUpdatedContribution()->payment_instrument_id;
    $trxnParams['check_number'] = $this->getInputValue('check_number');

    if ($this->isPaymentInstrumentChange()) {
      $this->updateFinancialAccountsOnPaymentInstrumentChange($trxnParams);
    }

    //if Change contribution amount
    $trxnParams['fee_amount'] = $this->getUpdatedContributionValue('fee_amount');
    $trxnParams['net_amount'] = $this->getUpdatedContributionValue('net_amount');
    $trxnParams['total_amount'] = $this->getUpdatedContribution()->total_amount ?? 0;
    $trxnParams['trxn_id'] = $this->getUpdatedContribution()->trxn_id;
    // If the total has changed then create adjustments, but it the financial
    // account has ALSO changed this will already have been dealt with using reverse & recreate above.
    if ($this->isContributionTotalChanged() && !$this->isFinancialAccountChanged()) {
      //Update Financial Records
      $trxnParams['from_financial_account_id'] = NULL;
      $trxnParams['total_amount'] = $trxnParams['net_amount'] = ($this->getUpdatedContributionValue('total_amount') - $this->getOriginalContribution()->total_amount);
      $this->updateFinancialAccounts($trxnParams, 'changedAmount');
    }

    // Process fee.
    if (!empty($this->getUpdatedContributionValue('fee_amount')) && (empty($this->getOriginalContribution()) || $this->getUpdatedContribution()->fee_amount != $this->getOriginalContribution()->fee_amount)) {
      $amount = $this->getUpdatedContributionValue('fee_amount') - ($this->getOriginalContributionValue('fee_amount') ?: 0);
      if ($amount) {
        $financialTxnID = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($this->getContributionID(), 'DESC')['financialTrxnId'];
        CRM_Core_BAO_FinancialTrxn::recordFees([
          'to_financial_account_id' => $this->getToFinancialAccount(),
          'contribution_status_id' => $this->getUpdatedContributionValue('contribution_status_id'),
          'trxnParams' => $trxnParams,
        ], $amount, $this->getContributionID(), $this->getUpdatedContributionValue('financial_type_id'), $financialTxnID);
      }
    }
    if (empty($params['skipLineItem'])) {
      foreach ($this->getUpdatedLineItems() as $line) {
        CRM_Price_BAO_LineItem::create($line);
      }
    }
    return $params;
  }

  /**
   * Save line items that do not have a payment or Accounts receivable transaction.
   *
   * In practice this is pending or in progress incomplete transactions or ones initially created
   * as failed or cancelled.
   *
   * @throws \CRM_Core_Exception
   */
  private function saveLineItemsWithNoFinancialTransaction(): void {
    if (empty($this->getInputValue('skipLineItem'))) {
      foreach ($this->getUpdatedLineItems() as $lineParams) {
        $createdLineItem = CRM_Price_BAO_LineItem::create($lineParams);
        // We create financial items with NO transaction ID on create if the transaction
        // has failed or if we do not yet have a payment, and it is not pay_later. Payment.create
        // finds these and links them.
        // This feels smelly and is an artifact from making always-record-accounts-receiveable
        // optional. More concerning - we have never updated these when the transactions are altered
        // while in this state - leaving the potential for unbalanced lines. This feels like
        // a latent bug.
        if (!$this->isUpdate()) {
          $lineItemValues = [
            'id' => $createdLineItem->id,
            'line_total' => $createdLineItem->line_total,
            'qty' => $createdLineItem->qty,
            'label' => $createdLineItem->label,
            'tax_amount' => $createdLineItem->tax_amount ?? NULL,
            'financial_type_id' => $createdLineItem->financial_type_id,
          ];
          $this->addFinancialItem($lineItemValues, FALSE, NULL);
          if (!empty($lineParams['tax_amount'])) {
            $this->addFinancialItem($lineItemValues, TRUE, NULL);
          }
        }
      }
    }
  }

  /**
   * @return int|null
   */
  private function getUpdatedAccountsReceivableAccount(): ?int {
    return $this->getAccountsReceivableAccount($this->getUpdatedContributionValue('financial_type_id'));
  }

  /**
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function updateFinancialRecordsOnContributionCreate(): void {
    $trxnParams = $this->getTrxnParams();
    if ($this->isRecordAccountsReceivable()) {
      $this->recordAlwaysAccountsReceivable($trxnParams);
      $trxnParams['from_financial_account_id'] = $this->getUpdatedAccountsReceivableAccount();
    }
    $financialTxnID = $this->createFinancialTrxn($trxnParams);
    // Process fee.
    if (!empty($this->getUpdatedContributionValue('fee_amount')) && (empty($this->getOriginalContribution()) || $this->getUpdatedContribution()->fee_amount != $this->getOriginalContribution()->fee_amount)) {
      $amount = $this->getUpdatedContributionValue('fee_amount') - ($this->getOriginalContributionValue('fee_amount') ?: 0);
      if ($amount) {
        CRM_Core_BAO_FinancialTrxn::recordFees([
          'to_financial_account_id' => $this->getToFinancialAccount(),
          'contribution_status_id' => $this->getUpdatedContributionValue('contribution_status_id'),
          'trxnParams' => $this->getTrxnParams(),
        ], $amount, $this->getContributionID(), $this->getUpdatedContributionValue('financial_type_id'), $financialTxnID);
      }
    }
    if (empty($this->getInputValue('skipLineItem'))) {
      foreach ($this->getAddedLineItems() as $lineParams) {
        $this->addLineItem($lineParams, $financialTxnID);
      }
      $this->createDeferredTrxn();
    }

    // create batch entry if batch_id is passed and
    if ($this->getInputValue('batch_id')) {
      $entityParams = [
        'batch_id' => $this->getInputValue('batch_id'),
        'entity_table' => 'civicrm_financial_trxn',
        'entity_id' => $financialTxnID,
      ];
      CRM_Batch_BAO_EntityBatch::create($entityParams);
    }
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
    $itemParams['financial_account_id'] = $this->getSalesTaxFinancialAccount($financial_type_id);
    $itemParams['amount'] = CRM_Contribute_BAO_FinancialProcessor::getMultiplier($this->getUpdatedContribution()->contribution_status_id) * $taxAmount;
    $this->createFinancialItem($itemParams, $trxnID);
  }

  /**
   * @param int $financialTypeID
   * @return int|null
   */
  private function getSalesTaxFinancialAccount(int $financialTypeID): ?int {
    return CRM_Financial_BAO_FinancialAccount::getSalesTaxFinancialAccount($financialTypeID);
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

  /**
   * @return float
   */
  private function getUpdatedTaxAmount(): float {
    $taxAmount = 0.0;
    foreach ($this->getUpdatedLineItems() as $submittedLineItem) {
      $taxAmount += $submittedLineItem['tax_amount'] ?? 0.0;
    }
    return $taxAmount;
  }

  /**
   * @return float
   */
  private function getUpdatedTotalAmount(): float {
    $totalAmount = 0.0;
    foreach ($this->getUpdatedLineItems() as $submittedLineItem) {
      $totalAmount += $submittedLineItem['line_total'] ?? 0.0;
    }
    return $totalAmount + $this->getUpdatedTaxAmount();
  }

  /**
   * Total of the line items as they stood before this change - see
   * changeFeeSelections(), where $this->originalContribution is NULL (the
   * contribution object is loaded but not treated as the pre-change state)
   * so this can't be read off $this->getOriginalContribution()->total_amount
   * the way the isUpdate() path does.
   *
   * @return float
   */
  private function getOriginalTotalAmount(): float {
    $totalAmount = 0.0;
    foreach ($this->originalLineItems as $originalLineItem) {
      $totalAmount += $originalLineItem['line_total'] ?? 0.0;
      $totalAmount += $originalLineItem['tax_amount'] ?? 0.0;
    }
    return $totalAmount;
  }

  /**
   * @return string|null
   */
  public function getUpdatedAmountLevel(): ?string {
    // update line item with changed line total and other information. A resurrected
    // line counts here too, same as a changed one.
    $totalParticipant = 0;
    $amountLevel = [];
    foreach ($this->getUpdatedLineItems() as $lineItem) {
      if (($lineItem['qty'] ?? 0) > 0) {
        $amountLevel[] = $lineItem['label'] . ' - ' . (float) $lineItem['qty'];
        if (($lineItem['entity_table'] ?? NULL) === 'civicrm_participant' && isset($lineItem['participant_count'])) {
          $totalParticipant += $lineItem['participant_count'];
        }
      }
    }
    $displayParticipantCount = '';
    if ($totalParticipant > 0) {
      $displayParticipantCount = ' Participant Count -' . $totalParticipant;
    }
    $updateAmountLevel = NULL;
    if (!empty($amountLevel)) {
      $updateAmountLevel = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $amountLevel) . $displayParticipantCount . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    return $updateAmountLevel;
  }

  /**
   * @return bool
   */
  private function isUnpaidAndNotPayLater(): bool {
    // Checking $this->getInputValue('is_pay_later') means we only pick this up if
    // is_pay_later has been passed in - this feels like a mistake but it
    // is an entrenched mistake (the previous code did the same although
    // less obviously as it checked a partially populated contribution object.
    $isPayLater = !empty($this->getInputValue('is_pay_later'));
    $isIncompletePending = $this->isPendingTransaction() && !$isPayLater;
    return $isIncompletePending || $this->isFailedTransaction();
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
   * @param int $default
   *
   * @return int
   */
  private function getFinancialAccountForStatusChangeTrxn($default): int {
    $relationshipType = [
      'Refunded' => 'Credit/Contra Revenue Account is',
      'Chargeback' => 'Chargeback Account is',
    ][$this->getUpdatedContributionStatus()] ?? NULL;

    if ($relationshipType) {
      return CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $this->getUpdatedContributionValue('financial_type_id'),
        $relationshipType
      );
    }
    return $default;
  }

  /**
   * The financial account to transfer the money to.
   *
   * This is the account determined by the payment processor or payment_instrument_id.
   * The payment_processor feels like an old brittle semi-supported value and configuring
   * appropriate payment types makes more sense.
   *
   * @return int
   * @throws CRM_Core_Exception
   */
  private function getToFinancialAccount(): int {
    if ($this->isAccountsReceivableTransaction()) {
      return CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $this->getUpdatedContributionValue('financial_type_id'),
        'Accounts Receivable Account is'
      );
    }
    $accountID = NULL;
    if (!empty($this->getInputValue('payment_processor'))) {
      $accountID = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($this->getInputValue('payment_processor'), NULL, 'civicrm_payment_processor');
    }

    if (!$accountID) {
      $accountID = CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount($this->getUpdatedContributionValue('payment_instrument_id'));
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
    if ($this->isUpdate()) {
      $params = $this->updateFinancialRecordsOnContributionUpdate($params);
    }
    else {
      if ($this->isUnpaidAndNotPayLater()) {
        $this->saveLineItemsWithNoFinancialTransaction();
      }
      else {
        $this->updateFinancialRecordsOnContributionCreate();
      }
    }
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
   * @return array
   * @throws CRM_Core_Exception
   */
  private function getTrxnParams(): array {
    $trxnParams = [
      'contribution_id' => $this->getContributionID(),
      'to_financial_account_id' => $this->getToFinancialAccount(),
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
   * @param array $trxnParams
   *   Params for the trxn to record.
   * @param string $context
   *   Update scenarios.
   * @param int|null $financialAccount
   *   Financial account to use for every line, overriding the per-line default - only
   *   passed for the second pass of a changeFinancialType update.
   */
  private function updateFinancialAccounts($trxnParams, $context = NULL, ?int $financialAccount = NULL): void {
    $trxnID = $this->createFinancialTrxn($trxnParams);
    $lineItems = $this->getUpdatedLineItems();
    $postUpdateContribution = $this->getUpdatedContribution();
    foreach ($lineItems as $lineItemDetails) {
      $previousLineItem = $this->originalLineItems[$lineItemDetails['id'] ?? NULL] ?? [];
      $prevFinancialItem = $this->getExistingFinancialItemForLine($lineItemDetails['id'], FALSE);
      $lineFinancialAccount = $financialAccount ?? $this->getFinancialAccountForStatusChangeTrxn($prevFinancialItem['financial_account_id']);

      $itemParams = [
        'transaction_date' => CRM_Utils_Date::isoToMysql($postUpdateContribution->receive_date),
        'contact_id' => $postUpdateContribution->contact_id,
        'currency' => $postUpdateContribution->currency,
        'amount' => $this->getFinancialItemAmountFromParams($context, $lineItemDetails),
        'description' => $prevFinancialItem['description'] ?? NULL,
        'status_id' => $prevFinancialItem['status_id'],
        'financial_account_id' => $lineFinancialAccount,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => $lineItemDetails['id'],
      ];
      $this->createFinancialItem($itemParams, $trxnID);

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

      if ($isTaxTransactionRequired) {
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
  }

  /**
   * Update all financial accounts entry.
   *
   * @param array $trxnParams
   *   Params for the trxn to record.
   * @param string $context
   *   Update scenarios.
   */
  private function reversePriorFinancialTrxn($trxnParams, $context = NULL): void {
    $trxnID = $this->createFinancialTrxn($trxnParams);
    $lineItems = $this->getUpdatedLineItems();
    $postUpdateContribution = $this->getUpdatedContribution();
    foreach ($lineItems as $lineItemDetails) {
      $prevFinancialItem = $this->getExistingFinancialItemForLine($lineItemDetails['id'], FALSE);
      $lineFinancialAccount = $this->getFinancialAccountForStatusChangeTrxn($prevFinancialItem['financial_account_id']);

      $itemParams = [
        'transaction_date' => CRM_Utils_Date::isoToMysql($postUpdateContribution->receive_date),
        'contact_id' => $postUpdateContribution->contact_id,
        'currency' => $postUpdateContribution->currency,
        'amount' => -$this->getOriginalLineItemAmount($lineItemDetails['id'] ?? NULL, 'line_total'),
        'description' => $prevFinancialItem['description'] ?? NULL,
        'status_id' => $prevFinancialItem['status_id'],
        'financial_account_id' => $lineFinancialAccount,
        'entity_table' => 'civicrm_line_item',
        'entity_id' => $lineItemDetails['id'],
      ];
      $this->createFinancialItem($itemParams, $trxnID);
      $this->reverseLineFinancialItem($lineItemDetails, TRUE, $trxnID);
    }
    $this->createDeferredTrxn(TRUE, $context);
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
      || ($previousContributionStatus === 'Pending' && $this->getOriginalContribution()->is_pay_later == TRUE
        && $currentContributionStatus === 'Partially paid'))
    ) {
      return;
    }

    if ($this->isContributionUpdateARefund()) {
      // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
      $params['trxnParams']['total_amount'] = -$params['total_amount'];
    }
    elseif (($previousContributionStatus === 'Pending'
        && $this->getOriginalContribution()->is_pay_later) || $previousContributionStatus === 'In Progress'
    ) {
      if ($currentContributionStatus === 'Cancelled') {
        // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
        $params['trxnParams']['to_financial_account_id'] = $this->getUpdatedAccountsReceivableAccount();
        $params['trxnParams']['total_amount'] = -$params['total_amount'];
      }
      else {
        // @todo we should stop passing $params by reference - splitting this out would be a step towards that.
        $params['trxnParams']['from_financial_account_id'] = $this->getUpdatedAccountsReceivableAccount();
      }
    }

    if ($this->isOriginalStatusPending() && $this->isCompletedTransaction()
    ) {
      if ($this->isRecordAccountsReceivable() && !$this->getOriginalContributionValue('is_pay_later')) {
        $financialTrxnIDs[] = $this->recordAlwaysAccountsReceivable($params['trxnParams']);
        $params['trxnParams']['from_financial_account_id'] = $this->getUpdatedAccountsReceivableAccount();
      }

      $financialTrxnIDs[] = $this->createFinancialTrxn($params['trxnParams']);

      foreach ($this->getUpdatedLineItems() as $lineItemDetails) {
        $this->updateFinancialItemForLineItemToPaid($lineItemDetails['id']);
        $financialItems = FinancialItem::get(FALSE)
          ->addSelect('id', 'amount')
          ->addWhere('entity_id', '=', $lineItemDetails['id'])
          ->addWhere('entity_table', '=', 'civicrm_line_item')
          ->execute();
        if ($financialItems->count() > 0) {
          foreach ($financialItems as $financialItem) {
            foreach ($financialTrxnIDs as $financialTrxnID) {
              EntityFinancialTrxn::save(FALSE)
                ->addRecord([
                  'financial_trxn_id' => $financialTrxnID,
                  'entity_id' => $financialItem['id'],
                  'entity_table' => 'civicrm_financial_item',
                  'amount' => $financialItem['amount'],
                ])
                ->execute();
            }
          }
        }
      }
      return;
    }
    $this->updateFinancialAccounts($params['trxnParams'], 'changedStatus');
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
   *
   * @return null|int
   */
  private function recordAlwaysAccountsReceivable($trxnParams): ?int {
    $params = $trxnParams;
    $params['to_financial_account_id'] = $this->getUpdatedAccountsReceivableAccount();
    $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
    $params['is_payment'] = FALSE;
    return $this->createFinancialTrxn($params);
  }

  /**
   * Does this transaction reflect a payment instrument change.
   *
   * Function is very complicated - is there a reason? It probably just dates
   * back to when the contribution objects were unreliably loaded.
   *
   * @return bool
   */
  private function isPaymentInstrumentChange(): bool {
    if (array_key_exists('payment_instrument_id', $this->inputValues)) {
      if (CRM_Utils_System::isNull($this->getOriginalContribution()->payment_instrument_id) &&
        !CRM_Utils_System::isNull($this->inputValues['payment_instrument_id'])
      ) {
        //check if status is changed from Pending to Completed
        // do not update payment instrument changes for Pending to Completed
        if (!($this->isCompletedTransaction() &&
          $this->isOriginalStatusPending())
        ) {
          return TRUE;
        }
      }
      elseif ((!CRM_Utils_System::isNull($this->inputValues['payment_instrument_id']) &&
          !CRM_Utils_System::isNull($this->getOriginalContribution()->payment_instrument_id)) &&
        $this->inputValues['payment_instrument_id'] != $this->getOriginalContribution()->payment_instrument_id
      ) {
        return TRUE;
      }
      elseif (!CRM_Utils_System::isNull($this->getUpdatedContribution()->check_number) &&
        $this->getUpdatedContribution()->check_number != $this->getOriginalContribution()->check_number
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
   * @param array $trxnParams
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private function updateFinancialAccountsOnPaymentInstrumentChange($trxnParams) {
    // This is refactored code but the assumed logic is that if the financialAccount changed then there has already been a reversal
    // and re-play so we now reverse the new one & re-do it so we want the account from the new one...
    $deferredFinancialAccount = $this->isFinancialAccountChanged() ? $this->getUpdatedFinancialAccount() : CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($this->getOriginalContributionValue('financial_type_id'), 'Deferred Revenue Account is');

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
    $lastFinancialTrxn['trxn_date'] = $trxnParams['trxn_date'];
    $lastFinancialTrxn['total_amount'] = -$trxnParams['total_amount'];
    $lastFinancialTrxn['net_amount'] = -$trxnParams['net_amount'];
    $lastFinancialTrxn['fee_amount'] = -$trxnParams['fee_amount'];
    $lastFinancialTrxn['contribution_id'] = $this->getContributionID();
    foreach ([$lastFinancialTrxn, $trxnParams] as $index => $financialTrxnParams) {
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
          $lineItemAmount = $this->getOriginalContribution()->total_amount == 0.0 ? 0 : $lineItem['line_total'];
          $eftParams['entity_id'] = $financialItemIds[$lineItem['price_field_value_id']];
          $eftParams['amount'] = $isReversal ? -$lineItemAmount : $lineItemAmount;
          EntityFinancialTrxn::create(FALSE)->setValues($eftParams)->execute();
          if (array_key_exists($lineItem['price_field_value_id'], $taxItems)) {
            $taxItemAmount = $this->getOriginalContribution()->total_amount == 0.0 ? 0 : $taxItems[$lineItem['price_field_value_id']]['amount'];
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
   * Add the financial items and financial trxn.
   *
   * @param array $lineItem
   *   Line item values - id, line_total, qty, label, tax_amount, financial_type_id.
   * @param bool $taxTrxnID
   * @param int|null $trxnId
   *   Transaction paying for this item. Pass NULL only when nothing has paid for it yet.
   */
  private function addFinancialItem(array $lineItem, $taxTrxnID = FALSE, ?int $trxnId = NULL): void {
    $contribution = $this->getUpdatedContribution();
    $financialItemStatus = array_column(\Civi::entity('FinancialItem')->getOptions('status_id'), 'id', 'name');
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution->contribution_status_id);
    $itemStatus = NULL;
    if ($contributionStatus === 'Completed' || $contributionStatus === 'Pending refund') {
      $itemStatus = $financialItemStatus['Paid'];
    }
    elseif ($contributionStatus === 'Pending'
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
      'amount' => $lineItem['line_total'],
      'currency' => $contribution->currency,
      'entity_table' => 'civicrm_line_item',
      'entity_id' => $lineItem['id'],
      'description' => ($lineItem['qty'] != 1 ? $lineItem['qty'] . ' of ' : '') . $lineItem['label'],
      'status_id' => $itemStatus,
    ];

    if ($taxTrxnID) {
      $params['amount'] = $lineItem['tax_amount'];
      $params['description'] = Civi::settings()->get('tax_term');
      $accountRelName = 'Sales Tax Account is';
    }
    else {
      $accountRelName = 'Income Account is';
      if (property_exists($contribution, 'revenue_recognition_date') && !CRM_Utils_System::isNull($contribution->revenue_recognition_date)) {
        $accountRelName = 'Deferred Revenue Account is';
      }
    }
    if (!empty($lineItem['financial_type_id'])) {
      $params['financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
        $lineItem['financial_type_id'],
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
   * Reverse a line item's existing financial item(s) - its own, and its tax one
   * if it had one - regardless of $line['tax_amount'], which is always 0 here.
   * The row itself must already be saved before this runs.
   *
   * @param array $line
   * @param int $trxnID
   *
   * @throws CRM_Core_Exception
   */
  private function reverseLineItem(array $line, int $trxnID): void {
    CRM_Price_BAO_LineItem::create($line);
    $this->reverseLineItemFinancialItem($line, FALSE, $trxnID);
    $this->reverseLineItemFinancialItem($line, TRUE, $trxnID);
  }

  /**
   * Reverse a line item's financial item for a given tax-or-not slot, if a
   * previous one exists with a non-zero amount - see reverseLineItem().
   *
   * @param array $line
   * @param bool $isTax
   * @param int $trxnID
   */
  private function reverseLineItemFinancialItem(array $line, bool $isTax, int $trxnID): void {
    $previousItem = $this->getExistingFinancialItemForLine($line['id'], $isTax);
    if (empty($previousItem) || !$previousItem['amount']) {
      return;
    }
    $itemParams = [
      'transaction_date' => CRM_Utils_Date::isoToMysql($this->getUpdatedContribution()->receive_date),
      'contact_id' => $this->getUpdatedContribution()->contact_id,
      'entity_table' => 'civicrm_line_item',
      'entity_id' => $line['id'],
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
    if ($isTax) {
      if ($previousItem['amount'] !== $newLineItem['tax_amount']) {
        return TRUE;
      }
      return $previousItem['financial_account_id'] !== (int) $this->getSalesTaxFinancialAccount($newLineItem['financial_type_id']);
    }
    return $previousItem['financial_account_id'] !== $this->getRevenueAccountForType($newLineItem['financial_type_id']);
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
   * @param array $lineItemIDsToCancel
   * @param array $lineItemsToUpdate
   *
   * @return array
   *   List of formatted reverse Financial Items to be recorded
   */
  private function getAdjustedFinancialItemsToRecord($lineItemIDsToCancel, $lineItemsToUpdate): array {
    $financialItemsArray = [];
    $financialItemResult = $this->getNonCancelledFinancialItems();
    foreach ($financialItemResult as $updateFinancialItemInfoValues) {
      $updateFinancialItemInfoValues['transaction_date'] = date('YmdHis');

      // the below params are not needed as we are creating new financial item
      $totalFinancialAmount = $this->checkFinancialItemTotalAmountByLineItemID($updateFinancialItemInfoValues['entity_id']);
      unset($updateFinancialItemInfoValues['id']);
      unset($updateFinancialItemInfoValues['created_date']);

      // Reverse line items omitted from the submitted lines.
      if (in_array($updateFinancialItemInfoValues['entity_id'], $lineItemIDsToCancel)
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
        && isset($lineItemsToUpdate[$updateFinancialItemInfoValues['entity_id']])
        && $lineItemsToUpdate[$updateFinancialItemInfoValues['entity_id']]['html_type'] == 'Text'
        && $updateFinancialItemInfoValues['amount'] > 0
      ) {
        $amountChangeOnTextLineItem = $lineItemsToUpdate[$updateFinancialItemInfoValues['entity_id']]['line_total'] - $totalFinancialAmount;
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
   */
  private function setNonCancelledFinancialItems(): void {
    if (empty($this->originalLineItems) || !$this->hasLineItemChanges()) {
      $this->existingFinancialItems = [];
      return;
    }
    $this->existingFinancialItems = (array) FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_line_item')
      ->addWhere('entity_id', 'IN', array_keys($this->originalLineItems))
      ->execute();

    // price_field_value_id isn't a financial_item field - pull it in from the line item.
    foreach ($this->existingFinancialItems as $index => $financialItem) {
      $this->existingFinancialItems[$index]['price_field_value_id'] = $this->originalLineItems[$financialItem['entity_id']]['price_field_value_id'];
    }

    foreach ($this->existingFinancialItems as $financialItem) {
      $this->financialItemTotalsByLineItemID[$financialItem['entity_id']]
        = ($this->financialItemTotalsByLineItemID[$financialItem['entity_id']] ?? 0) + $financialItem['amount'];
    }

    $items = [];
    foreach ($this->existingFinancialItems as $index => $financialItem) {
      $items[$financialItem['entity_id']][$index] = $financialItem['amount'];

      foreach ($items[$financialItem['entity_id']] as $existingItemID => $existingAmount) {
        if ($financialItem['amount'] + $existingAmount == 0) {
          // Filter both rows as they cancel each other out.
          unset($this->existingFinancialItems[$index]);
          unset($this->existingFinancialItems[$existingItemID]);
          unset($items[$financialItem['entity_id']][$existingItemID]);
          unset($items[$financialItem['entity_id']][$index]);
        }
      }
    }
  }

  private function getNonCancelledFinancialItems(): array {
    return $this->existingFinancialItems;
  }

  /**
   * Sum of financial item amounts already recorded for a line item - from
   * the financial items fetched in setNonCancelledFinancialItems().
   */
  private function checkFinancialItemTotalAmountByLineItemID(int $lineItemID): float {
    return $this->financialItemTotalsByLineItemID[$lineItemID] ?? 0;
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
    $trxnID = $this->recordAdjustmentTrxn();

    foreach ($this->getRemovedLineItems() as $lineItemToAlter) {
      $this->reverseLineItem($lineItemToAlter, $trxnID);
    }

    foreach ($this->getChangedLineItems() as $lineItemToAlter) {
      $this->alterLineItem($lineItemToAlter, $trxnID);
    }

    foreach ($this->getAddedLineItems() as $lineParams) {
      $this->addLineItem($lineParams, $trxnID);
    }
  }

  /**
   * Update a changed line item's row, and its financial item if it is a
   * 'Text' price field - see recordTextLineItemAdjustment().
   */
  private function alterLineItem(array $line, int $trxnID): void {
    CRM_Price_BAO_LineItem::create($line);
    if ($line['html_type'] === 'Text') {
      $this->recordTextLineItemAdjustment($line, $trxnID);
    }
  }

  /**
   * Create a new (or resurrected) line item's row, and its financial item(s) -
   * a resurrected line (see getResurrectedLineItems()) carries the cancelled
   * row's id forward, so it's an update rather than an insert.
   */
  private function addLineItem(array $line, int $trxnID): void {
    $createdLineItem = CRM_Price_BAO_LineItem::create($line);
    $lineItemValues = [
      'id' => $createdLineItem->id,
      'line_total' => $createdLineItem->line_total,
      'qty' => $createdLineItem->qty,
      'label' => $createdLineItem->label,
      'tax_amount' => $createdLineItem->tax_amount ?? NULL,
      'financial_type_id' => $createdLineItem->financial_type_id,
    ];
    $this->addFinancialItem($lineItemValues, FALSE, $trxnID);
    if ((float) ($lineItemValues['tax_amount'] ?? 0) !== 0.00) {
      $this->addFinancialItem($lineItemValues, TRUE, $trxnID);
    }
  }

  /**
   * Record the surplus/deficit financial item for a changed 'Text' price field -
   * its price_field_value_id (and so financial account) never changes, so there's
   * an amount adjustment rather than anything to reverse.
   */
  private function recordTextLineItemAdjustment(array $lineItemToAlter, int $trxnID): void {
    foreach ($this->getNonCancelledFinancialItems() as $existingFinancialItem) {
      if ((int) $existingFinancialItem['entity_id'] !== $lineItemToAlter['id'] || $existingFinancialItem['amount'] <= 0) {
        continue;
      }
      $amountChange = $lineItemToAlter['line_total'] - $this->checkFinancialItemTotalAmountByLineItemID($lineItemToAlter['id']);
      if ($amountChange !== (float) 0) {
        unset($existingFinancialItem['id'], $existingFinancialItem['created_date']);
        $existingFinancialItem['amount'] = $amountChange;
        $existingFinancialItem['transaction_date'] = date('YmdHis');
        $this->createFinancialItem($existingFinancialItem, $trxnID);
      }
      return;
    }
  }

  /**
   * Is any line item being added, changed or removed - see classifyLineItemChanges().
   */
  private function hasLineItemChanges(): bool {
    return !empty($this->lineItemChanges['added'])
      || !empty($this->lineItemChanges['changed'])
      || !empty($this->lineItemChanges['removed']);
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
    foreach ($this->getAddedLineItems() as $key => $lineItem) {
      if (!isset($lineItem['id'])) {
        $newLineItems[$key] = $lineItem;
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
    foreach ($this->getAddedLineItems() as $key => $lineItem) {
      if (isset($lineItem['id'])) {
        $resurrectedLineItems[$key] = $lineItem;
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
   * Matching (findActivePreviousLineItem()/findCancelledPreviousLineItem()) is by
   * entity_table + entity_id + price_field_value_id together - price_field_value_id
   * alone is not enough, since more than one entity on the contribution can hold the
   * same price option at once (eg. two participants in a group registration both on
   * "Standard"), including the case where the entity being edited switches onto an
   * option another entity already holds.
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

    foreach ($this->getUpdatedLineItems() as $key => $submittedLineItem) {
      $previousLineItem = $this->findActivePreviousLineItem($submittedLineItem);
      if ($previousLineItem === NULL) {
        $cancelledLineItem = $this->findCancelledPreviousLineItem($submittedLineItem);
        if ($cancelledLineItem !== NULL) {
          $submittedLineItem['id'] = $cancelledLineItem['id'];
        }
        $added[$key] = $submittedLineItem;
        continue;
      }
      $matchedIDs[] = $previousLineItem['id'];
      if ($this->lineItemValueChanged($previousLineItem, $submittedLineItem)) {
        $changed[$key] = array_merge($submittedLineItem, ['id' => $previousLineItem['id']]);
      }
    }

    foreach ($this->originalLineItems as $id => $previousLineItem) {
      if (!$this->isCancelled($previousLineItem) && !in_array($id, $matchedIDs)) {
        $removed[$id] = array_merge($previousLineItem, [
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
   * the same entity and price field value as the given submitted line, if any.
   */
  private function findActivePreviousLineItem(array $submittedLineItem): ?array {
    foreach ($this->originalLineItems as $previousLineItem) {
      if ($this->isSameEntityAndPriceFieldValue($previousLineItem, $submittedLineItem) && !$this->isCancelled($previousLineItem)) {
        return $previousLineItem;
      }
    }
    return NULL;
  }

  /**
   * Find a previously-cancelled line item for the same entity and price field value
   * as the given submitted line, if any - so a re-selected option reuses its row
   * rather than inserting a fresh one.
   */
  private function findCancelledPreviousLineItem(array $submittedLineItem): ?array {
    foreach ($this->originalLineItems as $previousLineItem) {
      if ($this->isSameEntityAndPriceFieldValue($previousLineItem, $submittedLineItem) && $this->isCancelled($previousLineItem)) {
        return $previousLineItem;
      }
    }
    return NULL;
  }

  private function isSameEntityAndPriceFieldValue(array $previousLineItem, array $submittedLineItem): bool {
    return $previousLineItem['price_field_value_id'] == $submittedLineItem['price_field_value_id']
      && $previousLineItem['entity_table'] === $submittedLineItem['entity_table']
      && (int) $previousLineItem['entity_id'] === (int) $submittedLineItem['entity_id'];
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
   * Find the submitted line item, if any, for the same entity and price field value.
   *
   * @param array $submittedLineItems
   * @param array $previousLineItem
   *
   * @return array|null
   */
  private function findSubmittedLineItem(array $submittedLineItems, array $previousLineItem): ?array {
    foreach ($submittedLineItems as $submittedLineItem) {
      if ((int) $submittedLineItem['price_field_value_id'] === (int) $previousLineItem['price_field_value_id']
        && $submittedLineItem['entity_table'] === $previousLineItem['entity_table']
        && (int) $submittedLineItem['entity_id'] === (int) $previousLineItem['entity_id']
      ) {
        return $submittedLineItem;
      }
    }
    return NULL;
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
   * Record adjusted amount.
   *
   * @return int
   *   The trxn recording the net change to the line items this fee-change
   *   actually reverses/adds/alters, created even when that net change is
   *   zero (eg. swapping two equal-priced options) so every financial item
   *   touched by the fee change has something to link to.
   */
  private function recordAdjustmentTrxn(): int {
    $contributionId = $this->getContributionID();
    $taxAmount = $this->getUpdatedTaxAmount();
    $updatedAmount = $this->getUpdatedTotalAmount();
    $paidAmount = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addSelect('paid_amount')
      ->execute()->first()['paid_amount'];

    // Used below to decide the contribution's resulting status - whether anything
    // is still owed/overpaid once this change is applied - not the amount of this
    // trxn itself, which instead needs to match what's about to be allocated to
    // the line items it will be linked to (see dev/core#6873).
    $balanceAmt = $updatedAmount - $paidAmount;
    $lineItemAdjustment = $updatedAmount - $this->getOriginalTotalAmount();

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
    if ($this->getUpdatedAmountLevel()) {
      $updatedContributionDAO->amount_level = $this->getUpdatedAmountLevel();
    }
    $updatedContributionDAO->save();

    $adjustedTrxnValues = [
      'from_financial_account_id' => NULL,
      'to_financial_account_id' => $this->getUpdatedAccountsReceivableAccount(),
      'total_amount' => $lineItemAdjustment,
      'net_amount' => $lineItemAdjustment,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'payment_instrument_id' => $this->getUpdatedContributionValue('payment_instrument_id'),
      'contribution_id' => $this->getContributionID(),
      'trxn_date' => date('YmdHis'),
      'currency' => $this->getUpdatedContributionValue('currency'),
    ];
    return $this->createFinancialTrxn($adjustedTrxnValues);
  }

}
