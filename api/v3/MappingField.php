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
 * This api exposes CiviCRM MappingField records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add a Mapping Field.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mapping_field_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MappingField');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mapping_field_create_spec(&$params) {
  $params['mapping_id']['api.required'] = 1;
}

/**
 * Deletes an existing Mapping Field.
 *
 * @param array $params
 *
 * @return array
 *   API result Array
 */
function civicrm_api3_mapping_field_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more Mapping Fields.
 *
 * @param array $params
 *   An associative array of name/value pairs.
 *
 * @return array
 *   details of found Mapping Fields
 */
function civicrm_api3_mapping_field_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
