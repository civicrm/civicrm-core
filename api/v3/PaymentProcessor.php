<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
  if (empty($params['id']) && empty($params['payment_instrument_id'])) {
    $params['payment_instrument_id'] = civicrm_api3('PaymentProcessorType', 'getvalue', array(
      'id' => $params['payment_processor_type_id'],
      'return' => 'payment_instrument_id',
    ));
  }
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
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
  return array(
    'description_field' => array(
      'payment_processor_type_id',
      'description',
    ),
    'params' => array(
      'is_test' => 0,
      'is_active' => 1,
    ),
  );
}
