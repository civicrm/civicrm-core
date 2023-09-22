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
 * This api exposes CiviCRM LineItem records.
 *
 * Line items are sub-components of a complete financial transaction record.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a line_item.
 *
 * @param array $params
 *   Array of property name/value pairs to insert in new 'line_item'
 *
 * @return array
 *   api result array
 *
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_line_item_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'LineItem');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_line_item_create_spec(&$params) {
  $params['entity_id']['api.required'] = 1;
  $params['qty']['api.required'] = 1;
  $params['unit_price']['api.required'] = 1;
  $params['line_total']['api.required'] = 1;
  $params['label']['api.default'] = 'line item';
}

/**
 * Returns array of line_items  matching a set of one or more group properties.
 *
 * @param array $params
 *   Array of one or more valid property_name=>value pairs. If $params is set.
 *   as null, all line_items will be returned (default limit is 25)
 *
 * @return array
 *   Array of matching line_items
 */
function civicrm_api3_line_item_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing LineItem.
 *
 * This method is used to delete any existing LineItem given its id.
 *
 * @param array $params
 *   Array containing id of the group to be deleted.
 *
 * @return array API result array
 * @throws CRM_Core_Exception
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_line_item_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
