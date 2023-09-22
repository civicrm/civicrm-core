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
 * This api exposes CiviCRM states/provinces
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add a state/province.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_state_province_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_DAO(__FUNCTION__), $params, 'StateProvince');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_state_province_create_spec(&$params) {
  $params['name']['api.required'] = 1;
  $params['country_id']['api.required'] = 1;
  $params['abbreviation']['api.required'] = 1;
}

/**
 * Deletes an existing state/province.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_state_province_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_DAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more states/provinces.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   api result array
 */
function civicrm_api3_state_province_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_DAO(__FUNCTION__), $params);
}
