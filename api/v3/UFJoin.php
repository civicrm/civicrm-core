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
 * This api exposes CiviCRM user framework join.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Takes an associative array and creates a uf join in the database.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   CRM_Core_DAO_UFJoin Array
 */
function civicrm_api3_uf_join_create($params) {

  $ufJoin = CRM_Core_BAO_UFJoin::create($params);
  _civicrm_api3_object_to_array($ufJoin, $ufJoinArray[]);
  return civicrm_api3_create_success($ufJoinArray, $params, 'UFJoin', 'create');
}

/**
 * Adjust Metadata for Create action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 *
 * @todo - suspect module, weight don't need to be required - need to test
 */
function _civicrm_api3_uf_join_create_spec(&$params) {
  $params['module']['api.required'] = 1;
  $params['weight']['api.required'] = 1;
  $params['uf_group_id']['api.required'] = 1;
}

/**
 * Get CiviCRM UF_Joins (ie joins between CMS user records & CiviCRM user record.
 *
 * @param array $params
 *   Array of name/value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_uf_join_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a CiviCRM UF_Join.
 *
 * @param array $params
 *   Array of name/value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_uf_join_delete($params) {
  return _civicrm_api3_basic_delete('CRM_Core_BAO_UFJoin', $params);
}
