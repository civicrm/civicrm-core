<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * This api exposes CiviCRM Contribution Payment records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve a set of financial transactions which are payments.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   Array of financial transactions which are payments, if error an array with an error id and error message
 */
function civicrm_api3_payment_get($params) {
  $ft = array();
  if (CRM_Utils_Array::value('contribution_id', $params)) {
    $params['entity_id'] = $params['contribution_id'];
  }
  $params['entity_table'] = 'civicrm_contribution';
  $eft = civicrm_api3('EntityFinancialTrxn', 'get', $params);
  if (!empty($eft['values'])) {
    foreach ($eft['values'] as $efts) {
      $eftids[] = $efts['financial_trxn_id'];
      $map[$efts['financial_trxn_id']] = $efts['entity_id'];
    }
    $ftParams = array(
      'id' => array( 'IN' => $eftids ),
      'is_payment' => 1,
    );
    $ft = civicrm_api3('FinancialTrxn', 'get', $ftParams);
    foreach ($ft['values'] as &$values) {
      $values['contribution_id'] = $map[$values['id']];
    }
  }
  return civicrm_api3_create_success($ft['values'], $params, 'Payment', 'cancel');
}

/**
 * Delete a payment.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_payment_delete(&$params) {
  if (!isset($params['civicrm_financial_trxn_id']) && !isset($params['payment_id'])) {
    return civicrm_api3_create_error(ts('You need to supply a Payment ID to delete a payment'));
  }
  else {
    $params['id'] = isset($params['civicrm_financial_trxn_id']) ? $params['civicrm_financial_trxn_id'] : $params['payment_id'];
    if (!CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $params['id'], 'id')) {
      return civicrm_api3_create_error(ts('You need to supply a valid Payment ID to delete a payment'));
    }
    return civicrm_api3('FinancialTrxn', 'delete', $params);
  }
}

/**
 * Cancel/Refund a payment for a Contribution.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_payment_cancel(&$params) {
  if (!isset($params['civicrm_financial_trxn_id']) && !isset($params['payment_id'])) {
    return civicrm_api3_create_error(ts('You need to supply a Payment ID to refund a payment'));
  }
  $ftId = isset($params['civicrm_financial_trxn_id']) ? $params['civicrm_financial_trxn_id'] : $params['payment_id'];
  if (!CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $ftId, 'id')) {
    return civicrm_api3_create_error(ts('You need to supply a valid Payment ID to refund a payment'));
  }
  $eftParams = array(
    'entity_table' => 'civicrm_contribution',
    'financial_trxn_id' => $ftId,
  );
  $entity = civicrm_api3('EntityFinancialTrxn', 'get', $eftParams);
  $entity = reset($entity['values']);
  $contributionId = $entity['entity_id'];
  $params['total_amount'] = $entity['amount'];
  
  $trxn = CRM_Contribute_BAO_Contribution::recordAdditionalPayment($contributionId, $params, 'refund', NULL, FALSE);
  
  $values = array(); 
  if (is_a($trxn, 'CRM_Core_Error')) {
    throw new API_Exception($trxn->_errors[0]['message']);
  }
  else {
    _civicrm_api3_object_to_array_unique_fields($trxn, $values[$trxn->id]);
  }
  return civicrm_api3_create_success($values, $params, 'Payment', 'cancel', $trxn);
}

/**
 * Add a payment for a Contribution.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_payment_create(&$params) {
  if (empty($params['contribution_id']) || 
      (isset($params['contribution_id']) && !CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['contribution_id'], 'id'))) {
    return civicrm_api3_create_error(ts('You need to supply a valid contribution ID to create a payment'));
  }
  // Check if it is an update
  $ftId = isset($params['civicrm_financial_trxn_id']) ? $params['civicrm_financial_trxn_id'] : $params['payment_id'];
  if ($ftId && (!CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $ftId, 'id') || !CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $ftId, 'is_payment'))) {
    return civicrm_api3_create_error(ts('You need to supply a valid Payment ID to update a payment'));
  }
  elseif ($ftId) {
    $amount = $params['total_amount'];
    civicrm_api3('Payment', 'cancel', $params);
    $params['total_amount'] = $amount;
  }
  // Get contribution
  $contribution = civicrm_api3('Contribution', 'get', array('id' => $params['contribution_id']));
  $contribution = reset($contribution['values']);
  if ($contribution['contribution_status'] != 'Partially paid') {
    return civicrm_api3_create_error(ts('Please select a contribution which has a partial payment'));
  }
  else {
    $trxn = CRM_Contribute_BAO_Contribution::recordPartialPayment($contribution, $params);
    $paid = CRM_Core_BAO_FinancialTrxn::getTotalPayments($params['contribution_id']);
    $total = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['contribution_id'], 'total_amount');
    $cmp = bccomp($total, $paid, 5);
    if ($cmp == 0 || $cmp == -1) { // If paid amount is greater or equal to total amount
      $statusId = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
      CRM_Core_DAO::setFieldValue('CRM_Contribute_BAO_Contribution', $params['contribution_id'], 'contribution_status_id', $statusId);
    }
  }
  if (CRM_Utils_Array::value('line_item', $params) && !empty($trxn)) {
    foreach ($params['line_item'] as $values) {
      foreach ($values as $id => $amount) {
        $p = array('id' => $id);
        $check = CRM_Price_BAO_LineItem::retrieve($p, $defaults);
        if (empty($check)) {
          return civicrm_api3_create_error(ts('Please specify a valid Line Item.'));
        }
        // get financial item
        $sql = "SELECT fi.id
          FROM civicrm_financial_item fi
          INNER JOIN civicrm_line_item li ON li.id = fi.entity_id
          WHERE li.contribution_id = %1 AND li.id = %2";
        $sqlParams = array(
          1 => array($params['contribution_id'], 'Integer'),
          2 => array($id, 'Integer'),
        );
        $fid = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
        // Record Entity Financial Trxn
        $eftParams = array(
          'entity_table' => 'civicrm_financial_item',
          'financial_trxn_id' => $trxn->id,
          'amount' => $amount,
          'entity_id' => $fid,
        );
        civicrm_api3('EntityFinancialTrxn', 'create', $eftParams);
      }
    }
  }
  elseif (!empty($trxn)) {
    // Assign the lineitems proportionally
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($params['contribution_id']);
    if (!empty($lineItems)) {
      // get financial item
      $sql = "SELECT fi.id, li.price_field_value_id
      FROM civicrm_financial_item fi
      INNER JOIN civicrm_line_item li ON li.id = fi.entity_id
      WHERE li.contribution_id = %1";
      $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($params['contribution_id'], 'Integer')));
      while ($dao->fetch()) {
        $ftIds[$dao->price_field_value_id] = $dao->id;
      }
      foreach ($lineItems as $key => $value) {
        $paid = $value['line_total'] * ($params['total_amount']/$contribution['total_amount']);
        // Record Entity Financial Trxn
        $eftParams = array(
          'entity_table' => 'civicrm_financial_item',
          'financial_trxn_id' => $trxn->id,
          'amount' => $paid,
          'entity_id' => $ftIds[$value['price_field_value_id']],
        );
        $entityTrxn = new CRM_Financial_DAO_EntityFinancialTrxn();
        $entityTrxn->copyValues($eftParams);
        $entityTrxn->save();
      }
    }
  }
  $values = array(); 
  if (is_a($trxn, 'CRM_Core_Error')) {
    throw new API_Exception($trxn->_errors[0]['message']);
  }
  else {
    _civicrm_api3_object_to_array_unique_fields($trxn, $values[$trxn->id]);
  }
  return civicrm_api3_create_success($values, $params, 'Payment', 'create', $trxn);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters.
 */
function _civicrm_api3_payment_create_spec(&$params) {
  $params = array( 
    'contribution_id' => array(
      'api.required' => 1 ,
      'title' => 'Contribution ID',
      'type' => CRM_Utils_Type::T_INT,
    ),
    'total_amount' => array(
      'api.required' => 1 ,
      'title' => 'Total Payment Amount',
      'type' => CRM_Utils_Type::T_FLOAT,
    ),
    'payment_processor_id' => array(
      'title' => 'Payment Processor ID',
      'type' => CRM_Utils_Type::T_INT,
      'description' => ts('Payment processor ID - required for payment processor payments'),
    ),
    'civicrm_financial_trxn_id' => array(
      'title' => 'Payment ID',
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => array('payment_id'),
    ),
  );
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_payment_get_spec(&$params) {
  $params = array( 
    'contribution_id' => array(
      'title' => 'Contribution ID',
      'type' => CRM_Utils_Type::T_INT,
    )
  );
}

/**
 * Adjust Metadata for Delete action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters.
 */
function _civicrm_api3_payment_delete_spec(&$params) {
  $params = array( 
    'civicrm_financial_trxn_id' => array(
      'api.required' => 1 ,
      'title' => 'Payment ID',
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => array('payment_id'),
    )
  ); 
}

/**
 * Adjust Metadata for Cancel action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters.
 */
function _civicrm_api3_payment_cancel_spec(&$params) {
  $params = array( 
    'civicrm_financial_trxn_id' => array(
      'api.required' => 1 ,
      'title' => 'Payment ID',
      'type' => CRM_Utils_Type::T_INT,
      'api.aliases' => array('payment_id'),
    )
  ); 
}