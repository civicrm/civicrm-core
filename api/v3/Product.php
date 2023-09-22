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
 * This api exposes CiviCRM premium products.
 *
 * Premiums are used as incentive gifts on contribution pages.
 * Use chaining to create a premium and related products in one api call.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a product.
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 * @return array
 */
function civicrm_api3_product_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Product');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_product_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  $params['name']['api.required'] = 1;
}

/**
 * Get a product.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved product property values.
 */
function civicrm_api3_product_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a product.
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_product_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
