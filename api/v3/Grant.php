<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * File for the CiviCRM APIv3 group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Grant
 * @copyright CiviCRM LLC (c) 2004-2013
 */

require_once 'CRM/Grant/BAO/Grant.php';

/**
 * create/update grant
 *
 * This API is used to create new grant or update any of the existing
 * In case of updating existing grant, id of that particular grant must
 * be in $params array.
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'grant'
 *
 * @return array   grant array
 * {@getfields grant_create}
 * @access public
 */
function civicrm_api3_grant_create($params) {
  $values = array();
  _civicrm_api3_custom_format_params($params, $values, 'Grant');
  $params = array_merge($values, $params);
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'grant');
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_grant_create_spec(&$params) {
  $params['grant_type_id']['api.required'] = 1;
}

/**
 * Returns array of grants  matching a set of one or more group properties
 *
 * @param array $params  (referance) Array of one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all grants will be returned
 *
 * @return array  (referance) Array of matching grants
 * {@getfields grant_get}
 * @access public
 */
function civicrm_api3_grant_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * delete an existing grant
 *
 * This method is used to delete any existing grant. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params   array containing id of the group
 *                       to be deleted
 *
 * @return array  API Result Array
 * {@getfields grant_delete}
 * @access public
 */
function civicrm_api3_grant_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

