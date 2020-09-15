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
function civicrm_api3_financial_item_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'FinancialItem');
}

/**
 * Get a FinancialItem.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved Financial Item property values.
 */
function civicrm_api3_financial_item_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a Financial Item.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_financial_item_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
