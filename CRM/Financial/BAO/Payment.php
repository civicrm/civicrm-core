<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class contains payment related functions.
 */
class CRM_Financial_BAO_Payment {

  /**
   * Function to process additional payment for partial and refund contributions.
   *
   * This function is called via API payment.create function. All forms that add payments
   * should use this.
   *
   * @param array $params
   *   - contribution_id
   *   - total_amount
   *   - line_item
   *
   * @return \CRM_Financial_DAO_FinancialTrxn
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public static function create($params) {
    $contribution = civicrm_api3('Contribution', 'getsingle', ['id' => $params['contribution_id']]);
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus($contribution['contribution_status_id'], 'name');
    if ($contributionStatus != 'Partially paid'
      && !($contributionStatus == 'Pending' && $contribution['is_pay_later'] == TRUE)
    ) {
      throw new API_Exception('Please select a contribution which has a partial or pending payment');
    }
    else {
      // Check if pending contribution
      $fullyPaidPayLater = FALSE;
      if ($contributionStatus == 'Pending') {
        $cmp = bccomp($contribution['total_amount'], $params['total_amount'], 5);
        // Total payment amount is the whole amount paid against pending contribution
        if ($cmp == 0 || $cmp == -1) {
          civicrm_api3('Contribution', 'completetransaction', ['id' => $contribution['id']]);
          // Get the trxn
          $trxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
          $ftParams = ['id' => $trxnId['financialTrxnId']];
          $trxn = CRM_Core_BAO_FinancialTrxn::retrieve($ftParams, CRM_Core_DAO::$_nullArray);
          $fullyPaidPayLater = TRUE;
        }
        else {
          civicrm_api3('Contribution', 'create',
            [
              'id' => $contribution['id'],
              'contribution_status_id' => 'Partially paid',
            ]
          );
        }
      }
      if (!$fullyPaidPayLater) {
        $trxn = CRM_Core_BAO_FinancialTrxn::getPartialPaymentTrxn($contribution, $params);
        if (CRM_Utils_Array::value('line_item', $params) && !empty($trxn)) {
          foreach ($params['line_item'] as $values) {
            foreach ($values as $id => $amount) {
              $p = ['id' => $id];
              $check = CRM_Price_BAO_LineItem::retrieve($p, $defaults);
              if (empty($check)) {
                throw new API_Exception('Please specify a valid Line Item.');
              }
              // get financial item
              $sql = "SELECT fi.id
              FROM civicrm_financial_item fi
              INNER JOIN civicrm_line_item li ON li.id = fi.entity_id and fi.entity_table = 'civicrm_line_item'
              WHERE li.contribution_id = %1 AND li.id = %2";
              $sqlParams = [
                1 => [$params['contribution_id'], 'Integer'],
                2 => [$id, 'Integer'],
              ];
              $fid = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
              // Record Entity Financial Trxn
              $eftParams = [
                'entity_table' => 'civicrm_financial_item',
                'financial_trxn_id' => $trxn->id,
                'amount' => $amount,
                'entity_id' => $fid,
              ];
              civicrm_api3('EntityFinancialTrxn', 'create', $eftParams);
            }
          }
        }
        elseif (!empty($trxn)) {
          CRM_Contribute_BAO_Contribution::assignProportionalLineItems($params, $trxn->id, $contribution['total_amount']);
        }
      }
    }
    return $trxn;
  }

}
