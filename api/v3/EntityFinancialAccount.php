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
 * This api exposes CiviCRM EntityFinancialAccount.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save an Entity Financial Account record.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_financial_account_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'EntityFinancialAccount');
}

/**
 * Save an Entity Financial Account record.
 *
 * @param array $params
 */
function _civicrm_api3_entity_financial_account_create_spec(&$params) {
  $params['entity_table']['api.default'] = 'civicrm_financial_type';
}

/**
 * Get an Entity Financial Account record.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved EntityFinancialAccount property values.
 */
function civicrm_api3_entity_financial_account_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an Entity Financial Account record.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_entity_financial_account_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
