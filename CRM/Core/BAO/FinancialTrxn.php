<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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
   * @return CRM_Financial_DAO_FinancialTrxn
   */
  public static function create($params) {
    $trxn = new CRM_Financial_DAO_FinancialTrxn();
    $trxn->copyValues($params);

    if (empty($params['id']) && !CRM_Utils_Rule::currencyCode($trxn->currency)) {
      $trxn->currency = CRM_Core_Config::singleton()->defaultCurrency;
    }

    $trxn->save();

    if (!empty($params['id'])) {
      // For an update entity financial transaction record will already exist. Return early.
      return $trxn;
    }

    // Save to entity_financial_trxn table.
    $entityFinancialTrxnParams = array(
      'entity_table' => CRM_Utils_Array::value('entity_table', $params, 'civicrm_contribution'),
      'entity_id' => CRM_Utils_Array::value('entity_id', $params, CRM_Utils_Array::value('contribution_id', $params)),
      'financial_trxn_id' => $trxn->id,
      'amount' => $params['total_amount'],
    );

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
    $toFinancialAccount = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($contributionFinancialTypeId, 'Accounts Receivable Account is');
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
   * @return \CRM_Financial_DAO_FinancialTrxn
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
  public static function getFinancialTrxnId($entity_id, $orderBy = 'ASC', $newTrxn = FALSE, $whereClause = '', $fromAccountID = NULL) {
    $ids = array('entityFinancialTrxnId' => NULL, 'financialTrxnId' => NULL);

    $params = array(1 => array($entity_id, 'Integer'));
    $condition = "";
    if (!$newTrxn) {
      $condition = " AND ((ceft1.entity_table IS NOT NULL) OR (cft.payment_instrument_id IS NOT NULL AND ceft1.entity_table IS NULL)) ";
    }

    if ($fromAccountID) {
      $condition .= " AND (cft.from_financial_account_id <> %2 OR cft.from_financial_account_id IS NULL)";
      $params[2] = array($fromAccountID, 'Integer');
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
   * @return array
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
      $toFinancialAccountType = !empty($params['isDeleted']) ? 'Premiums Inventory Account is' : 'Cost of Sales Account is';
      $fromFinancialAccountType = !empty($params['isDeleted']) ? 'Cost of Sales Account is' : 'Premiums Inventory Account is';
      $financialtrxn = array(
        'to_financial_account_id' => CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($params['financial_type_id'], $toFinancialAccountType),
        'from_financial_account_id' => CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($params['financial_type_id'], $fromFinancialAccountType),
        'trxn_date' => date('YmdHis'),
        'total_amount' => CRM_Utils_Array::value('cost', $params) ? $params['cost'] : 0,
        'currency' => CRM_Utils_Array::value('currency', $params),
        'status_id' => array_search('Completed', $contributionStatuses),
        'entity_table' => 'civicrm_contribution',
        'entity_id' => $params['contributionId'],
      );
      CRM_Core_BAO_FinancialTrxn::create($financialtrxn);
    }

    if (!empty($params['oldPremium'])) {
      $premiumParams = array(
        'id' => $params['oldPremium']['product_id'],
      );
      $productDetails = array();
      CRM_Contribute_BAO_Product::retrieve($premiumParams, $productDetails);
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
   * @return bool|void
   */
  public static function recordFees($params) {
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
    $financialAccount = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($financialTypeId, 'Expense Account is');

    $params['trxnParams']['from_financial_account_id'] = $params['to_financial_account_id'];
    $params['trxnParams']['to_financial_account_id'] = $financialAccount;
    $params['trxnParams']['total_amount'] = $amount;
    $params['trxnParams']['fee_amount'] = $params['trxnParams']['net_amount'] = 0;
    $params['trxnParams']['status_id'] = $params['contribution_status_id'];
    $params['trxnParams']['contribution_id'] = $contributionId;
    $params['trxnParams']['is_payment'] = FALSE;
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
        'status_id' => CRM_Core_Pseudoconstant::getKey('CRM_Financial_BAO_FinancialItem', 'status_id', 'Paid'),
        'entity_table' => 'civicrm_financial_trxn',
        'entity_id' => $params['entity_id'],
        'currency' => $params['trxnParams']['currency'],
      );
    $trxnIDS['id'] = $trxn->id;
    CRM_Financial_BAO_FinancialItem::create($fItemParams, NULL, $trxnIDS);
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
    }
    elseif ($entityName == 'membership') {
      $contributionId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipPayment', $entityId, 'contribution_id', 'membership_id');
    }
    else {
      $contributionId = $entityId;
    }
    $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'financial_type_id');

    if ($contributionId && $financialTypeId) {
      $statusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
      $refundStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Refunded');

      if (empty($lineItemTotal)) {
        $lineItemTotal = CRM_Price_BAO_LineItem::getLineTotal($contributionId);
      }
      $sqlFtTotalAmt = "
SELECT SUM(ft.total_amount)
FROM civicrm_financial_trxn ft
  INNER JOIN civicrm_entity_financial_trxn eft ON (ft.id = eft.financial_trxn_id AND eft.entity_table = 'civicrm_contribution' AND eft.entity_id = {$contributionId})
WHERE ft.is_payment = 1
  AND ft.status_id IN ({$statusId}, {$refundStatusId})
";

      $ftTotalAmt = CRM_Core_DAO::singleValueQuery($sqlFtTotalAmt);
      if (!$ftTotalAmt) {
        $ftTotalAmt = 0;
      }
      $currency = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'currency');
      $value = $paymentVal = CRM_Utils_Money::subtractCurrencies($lineItemTotal, $ftTotalAmt, $currency);
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
    return $value;
  }

  /**
   * @param int $contributionId
   *
   * @return string
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

  /**
   * Function records partial payment, complete's contribution if payment is fully paid
   * and returns latest payment ie financial trxn
   *
   * @param array $contribution
   * @param array $params
   *
   * @return \CRM_Financial_DAO_FinancialTrxn
   */
  public static function getPartialPaymentTrxn($contribution, $params) {
    $trxn = CRM_Contribute_BAO_Contribution::recordPartialPayment($contribution, $params);
    $paid = CRM_Core_BAO_FinancialTrxn::getTotalPayments($params['contribution_id']);
    $total = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['contribution_id'], 'total_amount');
    $cmp = bccomp($total, $paid, 5);
    if ($cmp == 0 || $cmp == -1) {// If paid amount is greater or equal to total amount
      civicrm_api3('Contribution', 'completetransaction', array('id' => $contribution['id']));
    }
    return $trxn;
  }

  /**
   * Get revenue amount for membership.
   *
   * @param array $lineItem
   *
   * @return array
   */
  public static function getMembershipRevenueAmount($lineItem) {
    $revenueAmount = array();
    $membershipDetail = civicrm_api3('Membership', 'getsingle', array(
      'id' => $lineItem['entity_id'],
    ));
    if (empty($membershipDetail['end_date'])) {
      return $revenueAmount;
    }

    $startDate = strtotime($membershipDetail['start_date']);
    $endDate = strtotime($membershipDetail['end_date']);
    $startYear = date('Y', $startDate);
    $endYear = date('Y', $endDate);
    $startMonth = date('m', $startDate);
    $endMonth = date('m', $endDate);

    $monthOfService = (($endYear - $startYear) * 12) + ($endMonth - $startMonth);
    $startDateOfRevenue = $membershipDetail['start_date'];
    $typicalPayment = round(($lineItem['line_total'] / $monthOfService), 2);
    for ($i = 0; $i <= $monthOfService - 1; $i++) {
      $revenueAmount[$i]['amount'] = $typicalPayment;
      if ($i == 0) {
        $revenueAmount[$i]['amount'] -= (($typicalPayment * $monthOfService) - $lineItem['line_total']);
      }
      $revenueAmount[$i]['revenue_date'] = $startDateOfRevenue;
      $startDateOfRevenue = date('Y-m', strtotime('+1 month', strtotime($startDateOfRevenue))) . '-01';
    }
    return $revenueAmount;
  }

  /**
   * Create transaction for deferred revenue.
   *
   * @param array $lineItems
   *
   * @param CRM_Contribute_BAO_Contribution $contributionDetails
   *
   * @param bool $update
   *
   * @param string $context
   *
   */
  public static function createDeferredTrxn($lineItems, $contributionDetails, $update = FALSE, $context = NULL) {
    if (empty($lineItems)) {
      return;
    }
    $revenueRecognitionDate = $contributionDetails->revenue_recognition_date;
    if (!CRM_Utils_System::isNull($revenueRecognitionDate)) {
      $statuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      if (!$update
        && (CRM_Utils_Array::value($contributionDetails->contribution_status_id, $statuses) != 'Completed'
          || (CRM_Utils_Array::value($contributionDetails->contribution_status_id, $statuses) != 'Pending'
            && $contributionDetails->is_pay_later)
          )
      ) {
        return;
      }
      $trxnParams = array(
        'contribution_id' => $contributionDetails->id,
        'fee_amount' => '0.00',
        'currency' => $contributionDetails->currency,
        'trxn_id' => $contributionDetails->trxn_id,
        'status_id' => $contributionDetails->contribution_status_id,
        'payment_instrument_id' => $contributionDetails->payment_instrument_id,
        'check_number' => $contributionDetails->check_number,
      );

      $deferredRevenues = array();
      foreach ($lineItems as $priceSetID => $lineItem) {
        if (!$priceSetID) {
          continue;
        }
        foreach ($lineItem as $key => $item) {
          $lineTotal = !empty($item['deferred_line_total']) ? $item['deferred_line_total'] : $item['line_total'];
          if ($lineTotal <= 0 && !$update) {
            continue;
          }
          $deferredRevenues[$key] = $item;
          if ($context == 'changeFinancialType') {
            $deferredRevenues[$key]['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_LineItem', $item['id'], 'financial_type_id');
          }
          if (in_array($item['entity_table'],
            array('civicrm_participant', 'civicrm_contribution'))
          ) {
            $deferredRevenues[$key]['revenue'][] = array(
              'amount' => $lineTotal,
              'revenue_date' => $revenueRecognitionDate,
            );
          }
          else {
            // for membership
            $item['line_total'] = $lineTotal;
            $deferredRevenues[$key]['revenue'] = self::getMembershipRevenueAmount($item);
          }
        }
      }
      $accountRel = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));

      CRM_Utils_Hook::alterDeferredRevenueItems($deferredRevenues, $contributionDetails, $update, $context);

      foreach ($deferredRevenues as $key => $deferredRevenue) {
        $results = civicrm_api3('EntityFinancialAccount', 'get', array(
          'entity_table' => 'civicrm_financial_type',
          'entity_id' => $deferredRevenue['financial_type_id'],
          'account_relationship' => array('IN' => array('Income Account is', 'Deferred Revenue Account is')),
        ));
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
          $entityParams = array(
            'entity_id' => $deferredRevenue['financial_item_id'],
            'entity_table' => 'civicrm_financial_item',
            'amount' => $revenue['amount'],
            'financial_trxn_id' => $financialTxn->id,
          );
          civicrm_api3('EntityFinancialTrxn', 'create', $entityParams);
        }
      }
    }
  }

  /**
   * Update Credit Card Details in civicrm_financial_trxn table.
   *
   * @param int $contributionID
   * @param int $panTruncation
   * @param int $cardType
   *
   */
  public static function updateCreditCardDetails($contributionID, $panTruncation, $cardType) {
    $financialTrxn = civicrm_api3('EntityFinancialTrxn', 'get', array(
      'return' => array('financial_trxn_id.payment_processor_id', 'financial_trxn_id'),
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contributionID,
      'financial_trxn_id.is_payment' => TRUE,
      'options' => array('sort' => 'financial_trxn_id DESC', 'limit' => 1),
    ));

    // In case of Contribution status is Pending From Incomplete Transaction or Failed there is no Financial Entries created for Contribution.
    // Above api will return 0 count, in such case we won't update card type and pan truncation field.
    if (!$financialTrxn['count']) {
      return NULL;
    }

    $financialTrxn = $financialTrxn['values'][$financialTrxn['id']];
    $paymentProcessorID = CRM_Utils_Array::value('financial_trxn_id.payment_processor_id', $financialTrxn);

    if ($paymentProcessorID) {
      return NULL;
    }

    $financialTrxnId = $financialTrxn['financial_trxn_id'];
    $trxnparams = array('id' => $financialTrxnId);
    if (isset($cardType)) {
      $trxnparams['card_type_id'] = $cardType;
    }
    if (isset($panTruncation)) {
      $trxnparams['pan_truncation'] = $panTruncation;
    }
    civicrm_api3('FinancialTrxn', 'create', $trxnparams);
  }

  /**
   * The function is responsible for handling financial entries if payment instrument is changed
   *
   * @param array $inputParams
   *
   */
  public static function updateFinancialAccountsOnPaymentInstrumentChange($inputParams) {
    $prevContribution = $inputParams['prevContribution'];
    $currentContribution = $inputParams['contribution'];
    // ensure that there are all the information in updated contribution object identified by $currentContribution
    $currentContribution->find(TRUE);

    $deferredFinancialAccount = CRM_Utils_Array::value('deferred_financial_account_id', $inputParams);
    if (empty($deferredFinancialAccount)) {
      $deferredFinancialAccount = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($prevContribution->financial_type_id, 'Deferred Revenue Account is');
    }

    $lastFinancialTrxnId = self::getFinancialTrxnId($prevContribution->id, 'DESC', FALSE, NULL, $deferredFinancialAccount);

    // there is no point to proceed as we can't find the last payment made
    // @todo we should throw an exception here rather than return false.
    if (empty($lastFinancialTrxnId['financialTrxnId'])) {
      return FALSE;
    }

    // If payment instrument is changed reverse the last payment
    //  in terms of reversing financial item and trxn
    $lastFinancialTrxn = civicrm_api3('FinancialTrxn', 'getsingle', array('id' => $lastFinancialTrxnId['financialTrxnId']));
    unset($lastFinancialTrxn['id']);
    $lastFinancialTrxn['trxn_date'] = $inputParams['trxnParams']['trxn_date'];
    $lastFinancialTrxn['total_amount'] = -$inputParams['trxnParams']['total_amount'];
    $lastFinancialTrxn['net_amount'] = -$inputParams['trxnParams']['net_amount'];
    $lastFinancialTrxn['fee_amount'] = -$inputParams['trxnParams']['fee_amount'];
    $lastFinancialTrxn['contribution_id'] = $prevContribution->id;
    foreach (array($lastFinancialTrxn, $inputParams['trxnParams']) as $financialTrxnParams) {
      $trxn = CRM_Core_BAO_FinancialTrxn::create($financialTrxnParams);
      $trxnParams = array(
        'total_amount' => $trxn->total_amount,
        'contribution_id' => $currentContribution->id,
      );
      CRM_Contribute_BAO_Contribution::assignProportionalLineItems($trxnParams, $trxn->id, $prevContribution->total_amount);
    }

    self::createDeferredTrxn(CRM_Utils_Array::value('line_item', $inputParams), $currentContribution, TRUE, 'changePaymentInstrument');

    return TRUE;
  }

}
