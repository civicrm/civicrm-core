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
 * This api exposes CiviCRM PCP records.
 *
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a survey.
 *
 * @param array $params
 *          Array per getfields metadata.
 *
 * @return array api result array
 */
function civicrm_api3_pcp_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Pcp');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *          Array of parameters determined by getfields.
 */
function _civicrm_api3_pcp_create_spec(&$params) {
  $params['title']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['page_id']['api.required'] = 1;
  $params['pcp_block_id']['api.required'] = 1;
}

/**
 * Returns array of pcps matching a set of one or more properties.
 *
 * @param array $params
 *          Array per getfields
 *
 * @return array Array of matching pcps
 */
function civicrm_api3_pcp_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Pcp');
}

/**
 * Delete an existing PCP.
 *
 * This method is used to delete any existing PCP given its id.
 *
 * @param array $params
 *          [id]
 *
 * @return array api result array
 */
function civicrm_api3_pcp_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
