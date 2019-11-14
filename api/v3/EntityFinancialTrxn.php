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
 * This api exposes CiviCRM FinancialType.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a Entity Financial Trxn.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_financial_trxn_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'EntityFinancialTrxn');
}

/**
 * Get a Entity Financial Trxn.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved Entity Financial Trxn property values.
 */
function civicrm_api3_entity_financial_trxn_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a Entity Financial Trxn.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_entity_financial_trxn_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_entity_financial_trxn_create_spec(&$params) {
  $params['entity_table']['api.required'] = 1;
  $params['entity_id']['api.required'] = 1;
  $params['financial_trxn_id']['api.required'] = 1;
  $params['amount']['api.required'] = 1;
}
