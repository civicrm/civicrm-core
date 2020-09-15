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
 * This api exposes CiviCRM price field values.
 *
 * PriceFields may contain zero or more PriceFieldValues.
 * Use chaining to create PriceFields and values in one api call.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a PriceFieldValue.
 *
 * @param array $params
 *   name/value pairs to insert in new 'PriceFieldValue'
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_price_field_value_create($params) {
  $ids = [];
  if (!empty($params['id'])) {
    $ids['id'] = $params['id'];
  }

  $bao = CRM_Price_BAO_PriceFieldValue::create($params, $ids);

  $values = [];
  _civicrm_api3_object_to_array($bao, $values[$bao->id]);
  return civicrm_api3_create_success($values, $params, 'PriceFieldValue', 'create', $bao);

}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_price_field_value_create_spec(&$params) {
  $params['price_field_id']['api.required'] = TRUE;
  $params['label']['api.required'] = TRUE;
  $params['amount']['api.required'] = TRUE;
  $params['is_active']['api.default'] = TRUE;
  $params['financial_type_id']['api.default'] = TRUE;
}

/**
 * Returns array of PriceFieldValues  matching a set of one or more group properties.
 *
 * @param array $params
 *   Array of one or more valid property_name=>value pairs. If $params is set.
 *   as null, all price_field_values will be returned (default limit is 25)
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_price_field_value_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing PriceFieldValue.
 *
 * This method is used to delete any existing PriceFieldValue given its id.
 *
 * @param array $params
 *   Array containing id of the group to be deleted.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_price_field_value_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
