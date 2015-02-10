<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * File for the CiviCRM APIv3 IM functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_IM
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 */

/**
 *  Add an IM for a contact
 *
 * Allowed @params array keys are:
 * {@getfields im_create}
 *
 * @param $params
 *
 * @return array of newly created IM property values.
 * @access public
 */
function civicrm_api3_im_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_im_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
}

/**
 * Deletes an existing IM
 *
 * @param  array  $params
 * {@getfields im_delete}
 *
 * @return array API result Array
 * @access public
 * @todo convert to using Basic delete - BAO function non standard
 */
function civicrm_api3_im_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more IM
 *
 * @param  mixed[]  (reference ) input parameters
 * {@getfields im_get}
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array details of found IM
 *
 * @access public
 */
function civicrm_api3_im_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

