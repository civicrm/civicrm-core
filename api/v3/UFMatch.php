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
