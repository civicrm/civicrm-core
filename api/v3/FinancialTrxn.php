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
 * This api exposes CiviCRM FinancialItem.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a Financial Item.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_financial_trxn_create($params) {
  if (empty($params['id']) && empty($params['contribution_id']) && empty($params['entity_id'])) {
    throw new CRM_Core_Exception("Mandatory key(s) missing from params array: both contribution_id and entity_id are missing");
  }

  return _civicrm_api3_basic_create('CRM_Core_BAO_FinancialTrxn', $params, 'FinancialTrxn');
}

/**
 * Get a Financialtrxn.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved Financial trxn property values.
 */
function civicrm_api3_financial_trxn_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a Financial trxn.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_financial_trxn_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 * Array of parameters determined by getfields.
 */
function _civicrm_api3_financial_trxn_create_spec(&$params) {
  $params['to_financial_account_id']['api.required'] = 1;
  $params['status_id']['api.required'] = 1;
  $params['payment_instrument_id']['api.required'] = 1;
  $params['total_amount']['api.required'] = 1;
}
