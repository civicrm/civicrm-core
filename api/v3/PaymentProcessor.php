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
 * This api exposes CiviCRM PaymentProcessor.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add/Update a PaymentProcessor.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_payment_processor_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'PaymentProcessor');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_payment_processor_create_spec(&$params) {
  $params['payment_processor_type_id']['api.required'] = 1;
  $params['is_default']['api.default'] = 0;
  $params['is_test']['api.default'] = 0;
  $params['is_active']['api.default'] = TRUE;
  $params['domain_id']['api.default'] = CRM_Core_Config::domainID();
  $params['financial_account_id']['api.default'] = CRM_Financial_BAO_PaymentProcessor::getDefaultFinancialAccountID();
  $params['financial_account_id']['api.required'] = TRUE;
  $params['financial_account_id']['type'] = CRM_Utils_Type::T_INT;
  $params['financial_account_id']['title'] = ts('Financial Account for Processor');
  $params['financial_account_id']['pseudoconstant'] = [
    'table' => 'civicrm_financial_account',
    'keyColumn' => 'id',
    'labelColumn' => 'name',
  ];
}

/**
 * Deletes an existing PaymentProcessor.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_payment_processor_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more PaymentProcessor.
 *
 * @param array $params
 *   Array of name/value pairs.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_payment_processor_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Set default getlist parameters.
 *
 * @see _civicrm_api3_generic_getlist_defaults
 *
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_payment_processor_getlist_defaults(&$request) {
  return [
    'description_field' => [
      'payment_processor_type_id',
      'description',
    ],
    'params' => [
      'is_test' => 0,
      'is_active' => 1,
    ],
  ];
}

/**
 * Action payment.
 *
 * @param array $params
 *
 * @return array
 *   API result array.
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_payment_processor_pay($params) {
  $processor = Civi\Payment\System::singleton()->getById($params['payment_processor_id']);
  $processor->setPaymentProcessor(civicrm_api3('PaymentProcessor', 'getsingle', ['id' => $params['payment_processor_id']]));
  try {
    $result = $processor->doPayment($params);
  }
  catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
    $code = $e->getErrorCode();
    $errorData = $e->getErrorData();
    if (empty($code)) {
      $code = 'EXTERNAL_FAILURE';
    }
    $message = $e->getMessage() ?? 'Payment Failed';
    throw new CRM_Core_Exception($message, $code, $errorData, $e);
  }
  return civicrm_api3_create_success(array($result), $params);
}

/**
 * Action payment.
 *
 * @param array $params
 */
function _civicrm_api3_payment_processor_pay_spec(&$params) {
  $params['payment_processor_id'] = [
    'api.required' => TRUE,
    'title' => ts('Payment processor'),
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['amount'] = [
    'api.required' => TRUE,
    'title' => ts('Amount to pay'),
    'type' => CRM_Utils_Type::T_MONEY,
  ];
  $params['contribution_id'] = [
    'api.required' => TRUE,
    'title' => ts('Contribution ID'),
    'type' => CRM_Utils_Type::T_INT,
    'api.aliases' => ['order_id'],
  ];
  $params['contact_id'] = [
    'title' => ts('Contact ID'),
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['contribution_recur_id'] = [
    'title' => ts('Contribution Recur ID'),
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['invoice_id'] = [
    'title' => ts('Invoice ID'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Action refund.
 *
 * @param array $params
 *
 * @return array
 *   API result array.
 *
 * @throws \CRM_Core_Exception
 * @throws \Civi\Payment\Exception\PaymentProcessorException
 */
function civicrm_api3_payment_processor_refund($params) {
  /** @var \CRM_Core_Payment $processor */
  $processor = Civi\Payment\System::singleton()->getById($params['payment_processor_id']);
  $processor->setPaymentProcessor(civicrm_api3('PaymentProcessor', 'getsingle', ['id' => $params['payment_processor_id']]));
  if (!$processor->supportsRefund()) {
    throw new CRM_Core_Exception('Payment Processor does not support refund');
  }
  $result = $processor->doRefund($params);
  return civicrm_api3_create_success([$result], $params);
}

/**
 * Action Refund.
 *
 * @param array $params
 *
 */
function _civicrm_api3_payment_processor_refund_spec(&$params) {
  $params['payment_processor_id'] = [
    'api.required' => TRUE,
    'title' => ts('Payment processor'),
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['amount'] = [
    'api.required' => TRUE,
    'title' => ts('Amount to refund'),
    'type' => CRM_Utils_Type::T_MONEY,
  ];
}
