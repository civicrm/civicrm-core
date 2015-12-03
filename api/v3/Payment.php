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
  $params['is_payment'] = 1;
  return civicrm_api3('Contribution', 'get', $params);
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
    $trxn = CRM_Contribute_BAO_Contribution::recordPartialPayment($contribution, $params);
    $participantPayment = civicrm_api3('ParticipantPayment', 'get', array('contribution_id' => $params['contribution_id']));
    if (!empty($participantPayment['values'])) {
      $values = reset($participantPayment['values']);
      $participantId = $values['participant_id'];
    }
    // Get payment balance
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($participantId, 'event');
    if ($paymentInfo['paid'] >= $paymentInfo['total']) {
      $params['contribution_status_id'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
    }
  }
  if ($contribution['contribution_status'] == 'Pending') {
    $trxn = CRM_Contribute_BAO_Contribution::recordPartialPayment($contribution, $params);
    $balance = CRM_Core_BAO_FinancialTrxn::getBalanceTrxnAmt($params['contribution_id']);
    $total = CRM_Price_BAO_LineItem::getLineTotal($params['contribution_id'], 'civicrm_contribution');
    $cmp = bccomp($total, $paid, 5); // Compare the two floating point amounts till the 5th decimal place.
    if ($cmp == 0 || $cmp == -1) { // If paid amount is greater or equal to total amount
      $params['contribution_status_id'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
    }
    else if ($cmp == 1) { // If paid amount is lesser than total amount
      $params['contribution_status_id'] = CRM_Core_OptionGroup::getValue('contribution_status', 'Partially paid', 'name');
    }
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