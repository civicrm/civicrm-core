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
  if (isset($params['id']) && !CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for payment_processor type ID');
  }

  $paymentProcessorType = CRM_Financial_BAO_PaymentProcessorType::create($params);

  $relType = array();

  _civicrm_api3_object_to_array($paymentProcessorType, $relType[$paymentProcessorType->id]);

  return civicrm_api3_create_success($relType, $params, 'PaymentProcessorType', 'create', $paymentProcessorType);
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
  // FIXME bool support // $params['is_recur']['api.required'] = 1;
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
