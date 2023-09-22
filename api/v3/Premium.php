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
 * This api exposes CiviCRM premiums.
 *
 * Premiums are used as incentive gifts on contribution pages.
 * Premiums contain "Products" which has a separate api.
 * Use chaining to create a premium and related products in one api call.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a premium.
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 * @return array
 */
function civicrm_api3_premium_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Premium');
}

/**
 * Get a premium.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved premium property values.
 */
function civicrm_api3_premium_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a premium.
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_premium_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Return field specification specific to get requests.
 *
 * @param array $params
 */
function _civicrm_api3_premium_get_spec(&$params) {
  $params['premiums_active']['api.aliases'] = ['is_active'];
}

/**
 * Return field specification specific to create requests.
 *
 * @param array $params
 */
function _civicrm_api3_premium_create_spec(&$params) {
  $params['premiums_active']['api.aliases'] = ['is_active'];
}
