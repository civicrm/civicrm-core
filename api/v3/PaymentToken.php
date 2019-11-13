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
 * This api exposes CiviCRM Payment Token records.
 *
 * @note Contribute component must be enabled.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create/update Payment Token.
 *
 * This API is used to create new campaign or update any of the existing
 * In case of updating existing campaign, id of that particular campaign must
 * be in $params array.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_payment_token_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'PaymentToken');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_payment_token_create_spec(&$params) {
  $params['token']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['payment_processor_id']['api.required'] = 1;
  $params['created_id']['api.default'] = 'user_contact_id';
  $params['created_date']['api.default'] = 'now';
}

/**
 * Returns array of campaigns matching a set of one or more properties.
 *
 * @param array $params
 *   Array per getfields
 *
 * @return array
 *   Array of matching campaigns
 */
function civicrm_api3_payment_token_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'PaymentToken');
}

/**
 * Delete an existing payment token.
 *
 * This method is used to delete any existing payment token.
 * Id of the payment token to be deleted is required field in $params array
 *
 * @param array $params
 *   array containing id of the group to be deleted
 *
 * @return array
 */
function civicrm_api3_payment_token_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
