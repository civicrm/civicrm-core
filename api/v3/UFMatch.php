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
 * This api exposes CiviCRM user framework match.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get the contact_id given a uf_id or vice versa.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_uf_match_get($params) {
  return _civicrm_api3_basic_get('CRM_Core_BAO_UFMatch', $params);
}

/**
 * Create or update a UF Match record.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   Api result array
 */
function civicrm_api3_uf_match_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'UFMatch');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_uf_match_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['uf_id']['api.required'] = 1;
  $params['uf_name']['api.required'] = 1;
}

/**
 * Delete a UF Match record.
 *
 * @param array $params
 *
 * @return array
 *   Api result array.
 */
function civicrm_api3_uf_match_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
