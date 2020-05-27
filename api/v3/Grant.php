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
 * This api exposes CiviCRM Grant records.
 *
 * @note Grant component must be enabled.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create/update Grant.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_grant_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Grant');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_grant_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['grant_type_id']['api.required'] = 1;
  $params['status_id']['api.required'] = 1;
  $params['amount_total']['api.required'] = 1;
  $params['status_id']['api.aliases'] = ['grant_status'];
}

/**
 * Returns array of grants matching a set of one or more properties.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   Array of matching grants
 */
function civicrm_api3_grant_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Grant');
}

/**
 * This method is used to delete an existing Grant.
 *
 * @param array $params
 *   Id of the Grant to be deleted is required.
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_grant_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
