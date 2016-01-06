<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_BAO_FinancialTrxn extends CRM_Financial_DAO_FinancialTrxn {
  /**
   * Class constructor.
   *
   * @return \CRM_Financial_DAO_FinancialTrxn
   */
  /**
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Takes an associative array and creates a financial transaction object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @param string $trxnEntityTable
   *   Entity_table.
   *
   * @return CRM_Core_BAO_FinancialTrxn
   */
  public static function create(&$params, $trxnEntityTable = NULL) {
    $trxn = new CRM_Financial_DAO_FinancialTrxn();
    $trxn->copyValues($params);

    if (!CRM_Utils_Rule::currencyCode($trxn->currency)) {
      $trxn->currency = CRM_Core_Config::singleton()->defaultCurrency;
    }

    $trxn->save();

    // save to entity_financial_trxn table
    $entityFinancialTrxnParams
      = array(
        'entity_table' => "civicrm_contribution",
        'financial_trxn_id' => $trxn->id,
        'amount' => $params['total_amount'],
        'currency' => $trxn->currency,
      );

    if (!empty($trxnEntityTable)) {
      $entityFinancialTrxnParams['entity_table'] = $trxnEntityTable['entity_table'];
      $entityFinancialTrxnParams['entity_id'] = $trxnEntityTable['entity_id'];
    }
    else {
      $entityFinancialTrxnParams['entity_id'] = $params['contribution_id'];
    }

    $entityTrxn = new CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->copyValues($entityFinancialTrxnParams);
    $entityTrxn->save();
    return $trxn;
  }

  /**
   * @param int $contributionId
   * @param int $contributionFinancialTypeId
   *
   * @return array
   */
  public static function getBalanceTrxnAmt($contributionId, $contributionFinancialTypeId = NULL) {
    if (!$contributionFinancialTypeId) {
      $contributionFinancialTypeId = CRM_Core_DAO::getFieldValue('CRM_Contribute_BAO_Contribution', $contributionId, 'financial_type_id');
    }
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
    $toFinancialAccount = CRM_Contribute_PseudoConstant::financialAccountType($contributionFinancialTypeId, $relationTypeId);
    $q = "SELECT ft.id, ft.total_amount FROM civicrm_financial_trxn ft INNER JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution') WHERE eft.entity_id = %1 AND ft.to_financial_account_id = %2";

    $p[1] = array($contributionId, 'Integer');
    $p[2] = array($toFinancialAccount, 'Integer');

    $balanceAmtDAO = CRM_Core_DAO::executeQuery($q, $p);
    $ret = array();
    if ($balanceAmtDAO->N) {
      $ret['total_amount'] = 0;
    }
    while ($balanceAmtDAO->fetch()) {
      $ret['trxn_id'] = $balanceAmtDAO->id;
      $ret['total_amount'] += $balanceAmtDAO->total_amount;
    }

    return $ret;
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Contribute_BAO_ContributionType
   */
  public static function retrieve(&$params, &$defaults) {
    $financialItem = new CRM_Financial_DAO_FinancialTrxn();
    $financialItem->copyValues($params);
    if ($financialItem->find(TRUE)) {
      CRM_Core_DAO::storeValues($financialItem, $defaults);
      return $financialItem;
    }
    return NULL;
  }

  /**
   * Given an entity_id and entity_table, check for corresponding entity_financial_trxn and financial_trxn record.
   * NOTE: This should be moved to separate BAO for EntityFinancialTrxn when we start adding more code for that object.
   *
   * @param $entity_id
   *   Id of the entity usually the contributionID.
   * @param string $orderBy
   *   To get single trxn id for a entity table i.e last or first.
   * @param bool $newTrxn
   * @param string $whereClause
   *   Additional where parameters
   *
   * @return array
   *   array of category id's the contact belongs to.
   *
   */
  public static function getFinancialTrxnId($entity_id, $orderBy = 'ASC', $newTrxn = FALSE, $whereClause = '') {
    $ids = array('entityFinancialTrxnId' => NULL, 'financialTrxnId' => NULL);

    $condition = "";
    if (!$newTrxn) {
      $condition = " AND ((ceft1.entity_table IS NOT NULL) OR (cft.payment_instrument_id IS NOT NULL AND ceft1.entity_table IS NULL)) ";
    }

    if ($orderBy) {
      $orderBy = CRM_Utils_Type::escape($orderBy, 'String');
    }

    $query = "SELECT ceft.id, ceft.financial_trxn_id, cft.trxn_id FROM `civicrm_financial_trxn` cft
LEFT JOIN civicrm_entity_financial_trxn ceft
ON ceft.financial_trxn_id = cft.id AND ceft.entity_table = 'civicrm_contribution'
LEFT JOIN civicrm_entity_financial_trxn ceft1
ON ceft1.financial_trxn_id = cft.id AND ceft1.entity_table = 'civicrm_financial_item'
LEFT JOIN civicrm_financial_item cfi ON ceft1.entity_table = 'civicrm_financial_item' and cfi.id = ceft1.entity_id
WHERE ceft.entity_id = %1 AND (cfi.entity_table <> 'civicrm_financial_trxn' or cfi.entity_table is NULL)
{$condition}
{$whereClause}
ORDER BY cft.id {$orderBy}
LIMIT 1;";

    $params = array(1 => array($entity_id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      $ids['entityFinancialTrxnId'] = $dao->id;
      $ids['financialTrxnId'] = $dao->financial_trxn_id;
      $ids['trxn_id'] = $dao->trxn_id;
    }
    return $ids;
  }

  /**
   * Get the transaction id for the (latest) refund associated with a contribution.
   *
   * @param int $contributionID
   * @return string
   */
  public static function getRefundTransactionTrxnID($contributionID) {
    $ids = self::getRefundTransactionIDs($contributionID);
    return isset($ids['trxn_id']) ? $ids['trxn_id'] : NULL;
  }

  /**
   * Get the transaction id for the (latest) refund associated with a contribution.
   *
   * @param int $contributionID
   * @return string
   */
  public static function getRefundTransactionIDs($contributionID) {
    $refundStatusID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Refunded');
    return self::getFinancialTrxnId($contributionID, 'DESC', FALSE, " AND cft.status_id = $refundStatusID");
  }

  /**
   * Given an entity_id and entity_table, check for corresponding entity_financial_trxn and financial_trxn record.
   * @todo This should be moved to separate BAO for EntityFinancialTrxn when we start adding more code for that object.
   *
   * @param int $entity_id
   *   Id of the entity usually the contactID.
   *
   * @return array
   *   array of category id's the contact belongs to.
   *
   */
  public static function getFinancialTrxnTotal($entity_id) {
    $query = "
      SELECT (ft.amount+SUM(ceft.amount)) AS total FROM civicrm_entity_financial_trxn AS ft
LEFT JOIN civicrm_entity_financial_trxn AS ceft ON ft.financial_trxn_id = ceft.entity_id
WHERE ft.entity_table = 'civicrm_contribution' AND ft.entity_id = %1
        ";

    $sqlParams = array(1 => array($entity_id, 'Integer'));
    return CRM_Core_DAO::singleValueQuery($query, $sqlParams);

  }

  /**
   * Given an financial_trxn_id  check for previous entity_financial_trxn.
   *
   * @param $financial_trxn_id
   *   Id of the latest payment.
   *
   *
   * @return array
   *   array of previous payments
   *
   */
  public static function getPayments($financial_trxn_id) {
    $query = "
SELECT ef1.financial_trxn_id, sum(ef1.amount) amount
FROM civicrm_entity_financial_trxn ef1
LEFT JOIN civicrm_entity_financial_trxn ef2 ON ef1.financial_trxn_id = ef2.entity_id
WHERE ef2.financial_trxn_id =%1
  AND ef2.entity_table = 'civicrm_financial_trxn'
  AND ef1.entity_table = 'civicrm_financial_item'
GROUP BY ef1.financial_trxn_id
UNION
SELECT ef1.financial_trxn_id, ef1.amount
FROM civicrm_entity_financial_trxn ef1
LEFT JOIN civicrm_entity_financial_trxn ef2 ON ef1.entity_id = ef2.entity_id
WHERE  ef2.financial_trxn_id =%1
  AND ef2.entity_table = 'civicrm_financial_trxn'
  AND ef1.entity_table = 'civicrm_financial_trxn'";

    $sqlParams = array(1 => array($financial_trxn_id, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);
    $i = 0;
    $result = array();
    while ($dao->fetch()) {
      $result[$i]['financial_trxn_id'] = $dao->financial_trxn_id;
      $result[$i]['amount'] = $dao->amount;
      $i++;
    }

    if (empty($result)) {
      $query = "SELECT sum( amount ) amount FROM civicrm_entity_financial_trxn WHERE financial_trxn_id =%1 AND entity_table = 'civicrm_financial_item'";
      $sqlParams = array(1 => array($financial_trxn_id, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);

      if ($dao->fetch()) {
        $result[0]['financial_trxn_id'] = $financial_trxn_id;
        $result[0]['amount'] = $dao->amount;
      }
    }
    return $result;
  }

  /**
   * Given an entity_id and entity_table, check for corresponding entity_financial_trxn and financial_trxn record.
   * NOTE: This should be moved to separate BAO for EntityFinancialTrxn when we start adding more code for that object.
   *
   * @param $entity_id
   *   Id of the entity usually the contactID.
   * @param string $entity_table
   *   Name of the entity table usually 'civicrm_contact'.
   *
   * @return array
   *   array of category id's the contact belongs to.
   *
   */
  public static function getFinancialTrxnLineTotal($entity_id, $entity_table = 'civicrm_contribution') {
    $query = "SELECT lt.price_field_value_id AS id, ft.financial_trxn_id,ft.amount AS amount FROM civicrm_entity_financial_trxn AS ft
LEFT JOIN civicrm_financial_item AS fi ON fi.id = ft.entity_id AND fi.entity_table = 'civicrm_line_item' AND ft.entity_table = 'civicrm_financial_item'
LEFT JOIN civicrm_line_item AS lt ON lt.id = fi.entity_id AND lt.entity_table = %2
WHERE lt.entity_id = %1 ";

    $sqlParams = array(1 => array($entity_id, 'Integer'), 2 => array($entity_table, 'String'));
    $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);
    while ($dao->fetch()) {
      $result[$dao->financial_trxn_id][$dao->id] = $dao->amount;
    }
    if (!empty($result)) {
      return $result;
    }
    else {
      return NULL;
    }
  }

  /**
   * Delete financial transaction.
   *
   * @param int $entity_id
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public static function deleteFinancialTrxn($entity_id) {
    $query = "DELETE ceft1, cfi, ceft, cft FROM `civicrm_financial_trxn` cft
LEFT JOIN civicrm_entity_financial_trxn ceft
  ON ceft.financial_trxn_id = cft.id AND ceft.entity_table = 'civicrm_contribution'
LEFT JOIN civicrm_entity_financial_trxn ceft1
  ON ceft1.financial_trxn_id = cft.id AND ceft1.entity_table = 'civicrm_financial_item'
LEFT JOIN civicrm_financial_item cfi
  ON ceft1.entity_table = 'civicrm_financial_item' and cfi.id = ceft1.entity_id
WHERE ceft.entity_id = %1";
    CRM_Core_DAO::executeQuery($query, array(1 => array($entity_id, 'Integer')));
    return TRUE;
  }

  /**
   * Create financial transaction for premium.
   *
   * @param array $params
   *   - oldPremium
   *   - financial_type_id
   *   - contributionId
   *   - isDeleted
   *   - cost
   *   - currency
   */
  public static function createPremiumTrxn($params) {
    if ((empty($params['financial_type_id']) || empty($params['contributionId'])) && empty($params['oldPremium'])) {
      return;
    }

    if (!empty($params['cost'])) {
      $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      $financialAccountType = CRM_Contribute_PseudoConstant::financialAccountType($params['financial_type_id']);
      $accountRelationship = CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name IN ('Premiums Inventory Account is', 'Cost of Sales Account is')");
      $toFinancialAccount = !empty($params['isDeleted']) ? 'Premiums Inventory Account is' : 'Cost of Sales Account is';
      $fromFinancialAccount = !empty($params['isDeleted']) ? 'Cost of Sales Account is' : 'Premiums Inventory Account is';
      $accountRelationship = array_flip($accountRelationship);
      $financialtrxn = array(
        'to_financial_account_id' => $financialAccountType[$accountRelationship[$toFinancialAccount]],
        'from_financial_account_id' => $financialAccountType[$accountRelationship[$fromFinancialAccount]],
        'trxn_date' => date('YmdHis'),
        'total_amount' => CRM_Utils_Array::value('cost', $params) ? $params['cost'] : 0,
        'currency' => CRM_Utils_Array::value('currency', $params),
        'status_id' => array_search('Completed', $contributionStatuses),
      );
      $trxnEntityTable['entity_table'] = 'civicrm_contribution';
      $trxnEntityTable['entity_id'] = $params['contributionId'];
      CRM_Core_BAO_FinancialTrxn::create($financialtrxn, $trxnEntityTable);
    }

    if (!empty($params['oldPremium'])) {
      $premiumParams = array(
        'id' => $params['oldPremium']['product_id'],
      );
      $productDetails = array();
      CRM_Contribute_BAO_ManagePremiums::retrieve($premiumParams, $productDetails);
      $params = array(
        'cost' => CRM_Utils_Array::value('cost', $productDetails),
        'currency' => CRM_Utils_Array::value('currency', $productDetails),
        'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $productDetails),
        'contributionId' => $params['oldPremium']['contribution_id'],
        'isDeleted' => TRUE,
      );
      CRM_Core_BAO_FinancialTrxn::createPremiumTrxn($params);
    }
  }

  /**
   * Create financial trxn and items when fee is charged.
   *
   * @param array $params
   *   To create trxn entries.
   *
   * @return bool
   */
  public static function recordFees($params) {
    $expenseTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Expense Account is' "));
    $domainId = CRM_Core_Config::domainID();
    $amount = 0;
    if (!empty($params['prevContribution'])) {
      $amount = $params['prevContribution']->fee_amount;
    }
    $amount = $params['fee_amount'] - $amount;
    if (!$amount) {
      return FALSE;
    }
    $contributionId = isset($params['contribution']->id) ? $params['contribution']->id : $params['contribution_id'];
    if (empty($params['financial_type_id'])) {
      $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'financial_type_id', 'id');
    }
    else {
      $financialTypeId = $params['financial_type_id'];
    }
    $financialAccount = CRM_Contribute_PseudoConstant::financialAccountType($financialTypeId, $expenseTypeId);

    $params['trxnParams']['from_financial_account_id'] = $params['to_financial_account_id'];
    $params['trxnParams']['to_financial_account_id'] = $financialAccount;
    $params['trxnParams']['total_amount'] = $amount;
    $params['trxnParams']['fee_amount'] = $params['trxnParams']['net_amount'] = 0;
    $params['trxnParams']['status_id'] = $params['contribution_status_id'];
    $params['trxnParams']['contribution_id'] = $contributionId;
    $trxn = self::create($params['trxnParams']);
    if (empty($params['entity_id'])) {
      $financialTrxnID = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($params['trxnParams']['contribution_id'], 'DESC');
      $params['entity_id'] = $financialTrxnID['financialTrxnId'];
    }
    $fItemParams
      = array(
        'financial_account_id' => $financialAccount,
        'contact_id' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', $domainId, 'contact_id'),
        'created_date' => date('YmdHis'),
        'transaction_date' => date('YmdHis'),
        'amount' => $amount,
        'description' => 'Fee',
        'status_id' => CRM_Core_OptionGroup::getValue('financial_item_status', 'Paid', 'name'),
        'entity_table' => 'civicrm_financial_trxn',
        'entity_id' => $params['entity_id'],
        'currency' => $params['trxnParams']['currency'],
      );
    $trxnIDS['id'] = $trxn->id;
    $financialItem = CRM_Financial_BAO_FinancialItem::create($fItemParams, NULL, $trxnIDS);
  }

  /**
   * get partial payment amount and type of it.
   *
   * @param int $entityId
   * @param string $entityName
   * @param bool $returnType
   * @param int $lineItemTotal
   *
   * @return array|int|NULL|string
   *   [payment type => amount]
   *   payment type: 'amount_owed' or 'refund_due'
   */
  public static function getPartialPaymentWithType($entityId, $entityName = 'participant', $returnType = TRUE, $lineItemTotal = NULL) {
    $value = NULL;
    if (empty($entityName)) {
      return $value;
    }

    if ($entityName == 'participant') {
      $contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $entityId, 'contribution_id', 'participant_id');
      $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'financial_type_id');

      if ($contributionId && $financialTypeId) {
        $statusId = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
        $refundStatusId = CRM_Core_OptionGroup::getValue('contribution_status', 'Refunded', 'name');

        $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
        $toFinancialAccount = CRM_Contribute_PseudoConstant::financialAccountType($financialTypeId, $relationTypeId);
        $feeRelationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Expense Account is' "));
        $feeFinancialAccount = CRM_Contribute_PseudoConstant::financialAccountType($financialTypeId, $feeRelationTypeId);

        if (empty($lineItemTotal)) {
          $ids = CRM_Event_BAO_Participant::getParticipantIds($contributionId);
          if (count($ids) > 1) {
            $total = 0;
            foreach ($ids as $val) {
              $total += CRM_Price_BAO_LineItem::getLineTotal($val, 'civicrm_participant');
            }
            $lineItemTotal = $total;
          }
          else {
            $lineItemTotal = CRM_Price_BAO_LineItem::getLineTotal($entityId, 'civicrm_participant');
          }
        }
        $sqlFtTotalAmt = "
SELECT SUM(ft.total_amount)
FROM civicrm_financial_trxn ft
  LEFT JOIN civicrm_entity_financial_trxn eft ON (ft.id = eft.financial_trxn_id AND eft.entity_table = 'civicrm_contribution')
  LEFT JOIN civicrm_contribution c ON (eft.entity_id = c.id)
  LEFT JOIN civicrm_participant_payment pp ON (pp.contribution_id = c.id)
WHERE pp.participant_id = {$entityId} AND ft.to_financial_account_id != {$toFinancialAccount} AND ft.to_financial_account_id != {$feeFinancialAccount}
  AND ft.status_id IN ({$statusId}, {$refundStatusId})
";
        $ftTotalAmt = CRM_Core_DAO::singleValueQuery($sqlFtTotalAmt);
        $value = 0;
        if ($ftTotalAmt) {
          $value = $paymentVal = $lineItemTotal - $ftTotalAmt;
        }
        if ($returnType) {
          $value = array();
          if ($paymentVal < 0) {
            $value['refund_due'] = $paymentVal;
          }
          elseif ($paymentVal > 0) {
            $value['amount_owed'] = $paymentVal;
          }
          elseif ($lineItemTotal == $ftTotalAmt) {
            $value['full_paid'] = $ftTotalAmt;
          }
        }
      }
    }
    return $value;
  }

  /**
   * @param int $contributionId
   *
   * @return array
   */
  public static function getTotalPayments($contributionId) {
    $statusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

    $sql = "SELECT SUM(ft.total_amount) FROM civicrm_financial_trxn ft
      INNER JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
      WHERE eft.entity_id = %1 AND ft.is_payment = 1 AND ft.status_id = %2";

    $params = array(
      1 => array($contributionId, 'Integer'),
      2 => array($statusId, 'Integer'),
    );

    return CRM_Core_DAO::singleValueQuery($sql, $params);
  }

}
