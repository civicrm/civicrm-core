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
 * This api exposes CiviCRM LocationType records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add a LocationType.
 *
 * @param array $params
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_location_type_create($params) {
  //set display_name equal to name if it's not defined
  if (!array_key_exists('display_name', $params) && array_key_exists('name', $params)) {
    $params['display_name'] = $params['name'];
  }

  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'LocationType');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_location_type_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  $params['name']['api.required'] = 1;
}

/**
 * Deletes an existing LocationType.
 *
 * @param array $params
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_location_type_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more LocationTypes.
 *
 * @param array $params
 *   An associative array of name/value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_location_type_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
