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
 * Retrieve a set of contributions which are payments.
 *
 * @param array $params
 *  Input parameters.
 *
 * @return array
 *   Array of contributions which are payments, if error an array with an error id and error message
 */
function civicrm_api3_payment_get($params) {
  
  require_once 'api/v3/Contribution.php';

  $mode = CRM_Contact_BAO_Query::MODE_CONTRIBUTE;
  $params['is_payment'] = 1;
  list($dao, $query) = _civicrm_api3_get_query_object($params, $mode, 'Contribution');

  $contribution = array();
  while ($dao->fetch()) {
    //CRM-8662
    $contribution_details = $query->store($dao);
    $softContribution = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($dao->contribution_id, TRUE);
    $contribution[$dao->contribution_id] = array_merge($contribution_details, $softContribution);
    // format soft credit for backward compatibility
    _civicrm_api3_format_soft_credit($contribution[$dao->contribution_id]);
  }
  return civicrm_api3_create_success($contribution, $params, 'Contribution', 'get', $dao);
}

/**
 * Add or update a Contribution which is a payment.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   Api result array
 */
function civicrm_api3_payment_create(&$params) {
  $values = array();
  _civicrm_api3_custom_format_params($params, $values, 'Contribution');
  $params = array_merge($params, $values);
  if (empty($params['contribution_id']) || 
      (isset($params['contribution_id']) && !CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['contribution_id'], 'id'))) {
    return civicrm_api3_create_error(ts('You need to supply a valid contribution ID to create a payment'));
  }
  // Get contribution
  $contribution = civicrm_api3('Contribution', 'get', array('id' => $params['contribution_id']));
  $contribution = reset($contribution['values']);
  if ($contribution['contribution_status'] == 'Partially paid') {
    $statusId = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $pendingStatus = array(
      array_search('Pending', $contributionStatuses),
      array_search('In Progress', $contributionStatuses),
    );
    if (in_array(CRM_Utils_Array::value('contribution_status_id', $contribution), $pendingStatus)) {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
      $balanceTrxnParams['to_financial_account_id'] = CRM_Contribute_PseudoConstant::financialAccountType($params['financial_type_id'], $relationTypeId);
    }
    elseif (!empty($params['payment_processor'])) {
      $balanceTrxnParams['to_financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getFinancialAccount($contribution['payment_processor'], 'civicrm_payment_processor', 'financial_account_id');
    }
    elseif (!empty($params['payment_instrument_id'])) {
      $balanceTrxnParams['to_financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($contribution['payment_instrument_id']);
    }
    else {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Asset' "));
      $queryParams = array(1 => array($relationTypeId, 'Integer'));
      $balanceTrxnParams['to_financial_account_id'] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = %1", $queryParams);
    }
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
    $fromFinancialAccountId = CRM_Contribute_PseudoConstant::financialAccountType($contribution['financial_type_id'], $relationTypeId);
    $balanceTrxnParams['from_financial_account_id'] = $fromFinancialAccountId;
    $balanceTrxnParams['total_amount'] = $params['total_amount'];
    $balanceTrxnParams['contribution_id'] = $params['contribution_id'];
    $balanceTrxnParams['trxn_date'] = !empty($params['contribution_receive_date']) ? $params['contribution_receive_date'] : date('YmdHis');
    $balanceTrxnParams['fee_amount'] = CRM_Utils_Array::value('fee_amount', $params);
    $balanceTrxnParams['net_amount'] = CRM_Utils_Array::value('total_amount', $params);
    $balanceTrxnParams['currency'] = $contribution['currency'];
    $balanceTrxnParams['trxn_id'] = $params['contribution_trxn_id'];
    $balanceTrxnParams['status_id'] = $statusId;
    $balanceTrxnParams['payment_instrument_id'] = $params['payment_instrument_id'];
    $balanceTrxnParams['check_number'] = CRM_Utils_Array::value('check_number', $params);
    if ($fromFinancialAccountId != NULL && 
        (!$params['contribution']->is_pay_later && ($statusId == array_search('Completed', $contributionStatuses) || $statusId == array_search('Partially Paid', $contributionStatuses)))) {
      $balanceTrxnParams['is_payment'] = 1;
    }
    if (!empty($params['payment_processor'])) {
      $balanceTrxnParams['payment_processor_id'] = $params['payment_processor'];
    }
    $trxn = CRM_Core_BAO_FinancialTrxn::create($balanceTrxnParams);
  }
  $participantPayment = civicrm_api3('ParticipantPayment', 'get', array('contribution_id' => $params['contribution_id']));
  if (!empty($participantPayment['values'])) {
    $values = reset($participantPayment['values']);
    $participantId = $values['participant_id'];
  }
  // Get payment balance
  $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($participantId, 'event');
  if ($paymentInfo['paid'] >= $paymentInfo['total']) {
    $params['contribution_status_id'] = $statusId;
  }
  unset($params['total_amount']);
  $params['id'] = $params['contribution_id'];

  return _civicrm_api3_basic_create('CRM_Contribute_BAO_Contribution', $params, 'Contribution');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_payment_create_spec(&$params) {
  $params['contribution_id']['api.required'] = 1;   
  $params['total_amount']['api.required'] = 1;  
  $params['payment_processor_id']['description'] = 'Payment processor ID - required for payment processor payments'; 
}