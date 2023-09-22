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
class CRM_Batch_BAO_EntityBatch extends CRM_Batch_DAO_EntityBatch {

  /**
   * Create entity batch entry.
   *
   * @param array $params
   * @return CRM_Batch_DAO_EntityBatch
   */
  public static function create($params) {
    // Only write the EntityBatch record if the financial trxn and batch match on currency and payment instrument.
    $batchId = $params['batch_id'] ?? NULL;
    $entityId = $params['entity_id'] ?? NULL;
    $entityTable = $params['entity_table'] ?? 'civicrm_financial_trxn';
    // Not having a batch ID and entity ID is only acceptable on an update.
    if (!$batchId) {
      $existingEntityBatch = \Civi\Api4\EntityBatch::get(FALSE)
        ->addSelect('id', '=', $params['id'])
        ->execute()
        ->first();
      $batchId = $existingEntityBatch['batch_id'] ?? NULL;
      $entityId = $existingEntityBatch['entity_id'] ?? NULL;
    }
    // There should never be a legitimate case where a record has an ID but no batch ID but SyntaxConformanceTest says otherwise.
    if ($batchId && $entityTable === 'civicrm_financial_trxn') {
      $batchCurrency = self::getBatchCurrency($batchId);
      $batchPID = (int) CRM_Core_DAO::getFieldValue('CRM_Batch_DAO_Batch', $batchId, 'payment_instrument_id');
      $trxn = \Civi\Api4\FinancialTrxn::get(FALSE)
        ->addSelect('currency', 'payment_instrument_id')
        ->addWhere('id', '=', $entityId)
        ->execute()
        ->first();
      if ($batchCurrency && $batchCurrency !== $trxn['currency']) {
        throw new \CRM_Core_Exception(ts('You cannot add items of two different currencies to a single contribution batch. Batch id %1 currency: %2. Entity id %3 currency: %4.', [1 => $batchId, 2 => $batchCurrency, 3 => $entityId, 4 => $trxn['currency']]));
      }
      if ($batchPID && $trxn && $batchPID !== $trxn['payment_instrument_id']) {
        $paymentInstrument = CRM_Core_PseudoConstant::getLabel('CRM_Batch_BAO_Batch', 'payment_instrument_id', $batchPID);
        throw new \CRM_Core_Exception(ts('This batch is configured to include only transactions using %1 payment method. If you want to include other transactions, please edit the batch first and modify the Payment Method.', [1 => $paymentInstrument]));
      }
    }
    return self::writeRecord($params);
  }

  /**
   * Remove entries from entity batch.
   * @param array|int $params
   * @deprecated
   * @return CRM_Batch_DAO_EntityBatch
   */
  public static function del($params) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    if (!is_array($params)) {
      $params = ['id' => $params];
    }
    return self::deleteRecord($params);
  }

  /**
   * Get the currency associated with a batch (if any).
   *
   * @param int $batchId
   *
   */
  public static function getBatchCurrency($batchId) : ?string {
    $sql = "SELECT DISTINCT ft.currency
      FROM  civicrm_batch batch
      JOIN civicrm_entity_batch eb
        ON batch.id = eb.batch_id
      JOIN civicrm_financial_trxn ft
        ON eb.entity_id = ft.id
      WHERE batch.id = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$batchId, 'Positive']]);
    if ($dao->N === 0) {
      return NULL;
    }
    else {
      $dao->fetch();
      return $dao->currency;
    }
  }

}
