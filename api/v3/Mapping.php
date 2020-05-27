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
 * This api exposes CiviCRM Mapping records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add a Mapping.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mapping_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Mapping');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $spec
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mapping_create_spec(&$spec) {
  $spec['name']['api.required'] = 1;
}

/**
 * Deletes an existing Mapping.
 *
 * @param array $params
 *
 * @return array
 *   API result Array
 */
function civicrm_api3_mapping_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more Mappings.
 *
 * @param array $params
 *   An associative array of name/value pairs.
 *
 * @return array
 *   details of found Mappings
 */
function civicrm_api3_mapping_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
