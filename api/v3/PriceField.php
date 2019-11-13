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
 * This api exposes CiviCRM price set fields.
 *
 * PriceSets contain PriceFields, which may contain PriceFieldValues.
 * Each has its own api.
 * Use chaining to create a PriceSet and associated PriceFields and values in one api call.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a price_field.
 *
 * @param array $params
 *   name/value pairs to insert in new 'price_field'
 *
 * @return array
 *   api result array
 */
function civicrm_api3_price_field_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'PriceField');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_price_field_create_spec(&$params) {
  $params['label']['api.required'] = TRUE;
}

/**
 * Returns array of price_fields matching a set of one or more group properties.
 *
 * @param array $params
 *   Array of one or more valid property_name=>value pairs. If $params is set.
 *   as null, all price_fields will be returned (default limit is 25)
 *
 * @return array
 *   Array of matching price_fields
 */
function civicrm_api3_price_field_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing price_field.
 *
 * This method is used to delete any existing price_field given its id.
 *
 * @param array $params
 *   Array containing id of the group to be deleted.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_price_field_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
