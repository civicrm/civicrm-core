<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
