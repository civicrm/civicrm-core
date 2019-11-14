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
 * This api exposes CiviCRM payment processor types.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create payment_processor type.
 *
 * @param array $params
 *   Associative array of property name/value pairs to insert in new payment_processor type.
 *
 * @return array
 */
function civicrm_api3_payment_processor_type_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'PaymentProcessorType');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_payment_processor_type_create_spec(&$params) {
  $params['billing_mode']['api.required'] = 1;
  $params['class_name']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
  $params['is_recur']['api.default'] = FALSE;
  $params['name']['api.required'] = 1;
  $params['title']['api.required'] = 1;
  $params['payment_instrument_id']['api.default'] = 'Credit Card';
}

/**
 * Get all payment_processor types.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_payment_processor_type_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a payment_processor type delete.
 *
 * @param array $params
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_payment_processor_type_delete($params) {
  if ($params['id'] != NULL && !CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for payment processor type ID');
  }

  $payProcTypeBAO = new CRM_Financial_BAO_PaymentProcessorType();
  $result = $payProcTypeBAO->del($params['id']);
  if (!$result) {
    return civicrm_api3_create_error('Could not delete payment processor type');
  }
  return civicrm_api3_create_success($result, $params, 'PaymentProcessorType', 'delete', $payProcTypeBAO);
}
