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

namespace Civi\Api4\Action\Payment;

use Civi\Api4\EntityFinancialTrxn;
use Civi\Api4\FinancialTrxn;

/**
 * This API Action gets a payment
 *
 * @method $this setContributionID(int $contributionID) Set the contribution Id for payments we are looking for
 * @method bool getContributionID() Get ContributionID Param
 *
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {

  /**
   * Contribution ID for filtering
   *
   * @var int
   */
  protected $contributionID = NULL;

  public static function getGetFields(): array {
    $financialTrxnFields = FinancialTrxn::getFields(FALSE)->execute()->getArrayCopy();
    foreach ($financialTrxnFields as $key => $field) {
      if ($field['name'] === 'is_payment') {
        $financialTrxnFields[$key]['default_value'] = TRUE;
      }
    }
    $financialTrxnFields[] = [
      'column_name' => 'financial_trxn_id',
      'description' => ts('Contribution ID linked to the financial trxn'),
      'label' => ts('Contribution ID'),
      'name' => 'contribution_id',
      'data_type' => 'Integer',
      'sql_renderer' => [__CLASS__, 'getContributionIDSQL'],
    ];
    return $financialTrxnFields;
  }

  public function fields(): array {
    return self::getGetFields();
  }

  public function getBaoName() {
    return 'CRM_Financial_BAO_FinancialTrxn';
  }

  public function getEntityName() {
    return 'FinancialTrxn';
  }

  /**
   *
   * Note that the result class is that of the annotation below, not the h
   * in the method (which must match the parent class)
   *
   * @var \Civi\Api4\Generic\Result $result
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    if (!empty($this->contributionID)) {
      $financialTrxns = FinancialTrxn::get(FALSE)
        ->addJoin('EntityFinancialTrxn AS entity_financial_trxn', 'INNER', 'id = entity_financial_trxn.financial_trxn_id')
        ->addWhere('is_payment', '=', TRUE)
        ->addWhere('entity_financial_trxn.entity_id', '=', $this->contributionID)
        ->addWhere('entity_financial_trxn.entity_table', '=', 'civicrm_contribution')
        ->execute()
        ->column('id');
      $this->addWhere('id', 'IN', $financialTrxns);
    }
    $this->addWhere('is_payment', '=', TRUE);
    parent::_run($result);
    foreach ($result as $key => $r) {
      $result[$key]['contribution_id'] = EntityFinancialTrxn::get(FALSE)->addWhere('financial_trxn_id', '=', $r['id'])->addWhere('entity_table', '=', 'civicrm_contribution')->execute()->first()['entity_id'] ?? NULL;
    }
  }

  /**
   * Generate SQL for getting the contribution id for a financial trxn
   * in static and smart groups
   *
   * @return string
   */
  public static function getContributionIDSQL(array $field): string {
    return "(SELECT entity_id
      FROM civicrm_entity_financial_trxn
      WHERE entity_table='civicrm_contribution'
      AND financial_trxn_id = {$field['sql_name']})";
  }

}
