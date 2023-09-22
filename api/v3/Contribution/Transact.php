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
 * @package CiviCRM_APIv3
 */

/**
 * Adjust Metadata for Transact action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contribution_transact_spec(&$params) {
  $fields = civicrm_api3('Contribution', 'getfields', ['action' => 'create']);
  $params = array_merge($params, $fields['values']);
  $params['receive_date']['api.default'] = 'now';
}

/**
 * Process a transaction and record it against the contact.
 *
 * @deprecated
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   contribution of created or updated record (or a civicrm error)
 */
function civicrm_api3_contribution_transact($params) {
  CRM_Core_Error::deprecatedFunctionWarning('The contibution.transact api is unsupported & known to have issues. Please see the section at the bottom of https://docs.civicrm.org/dev/en/latest/financial/orderAPI/ for getting off it');
  // Set some params specific to payment processing
  // @todo - fix this function - none of the results checked by civicrm_error would ever be an array with
  // 'is_error' set
  // also trxn_id is not saved.
  // but since there is no test it's not desirable to jump in & make the obvious changes.
  $params['payment_processor_mode'] = empty($params['is_test']) ? 'live' : 'test';
  $params['amount'] = $params['total_amount'];
  if (!isset($params['net_amount'])) {
    $params['net_amount'] = $params['amount'];
  }
  if (!isset($params['invoiceID']) && isset($params['invoice_id'])) {
    $params['invoiceID'] = $params['invoice_id'];
  }

  // Some payment processors expect a unique invoice_id - generate one if not supplied
  $params['invoice_id'] = CRM_Utils_Array::value('invoice_id', $params, md5(uniqid(rand(), TRUE)));

  $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($params['payment_processor'], $params['payment_processor_mode']);
  $params = $paymentProcessor['object']->doPayment($params);

  $params['payment_instrument_id'] = $paymentProcessor['object']->getPaymentInstrumentID();

  return civicrm_api('Contribution', 'create', $params);
}
