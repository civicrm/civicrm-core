<?php
// $Id$
/*
 +--------------------------------------------------------------------+
 | Project60 version 4.3                                              |
 +--------------------------------------------------------------------+
 | Copyright TTTP (c) 2004-2013                                       |
 +--------------------------------------------------------------------+
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
 * File for the CiviCRM APIv3 message_templates functions
 *
 * @package CiviCRM_SEPA
 *
 */


/**
 * @access public
 */
function civicrm_api3_message_templates_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 * 
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_message_templates_create_spec(&$params) {
  $params['msg_title']['api.required'] = 1;
  $params['is_active']['api.default'] = true;
/*  $params['entity_id']['api.required'] = 1;
  $params['entity_table']['api.default'] = "civicrm_contribution_recur";
  $params['type']['api.default'] = "R";
*/
}

/**
 * @param  array  $params
 *
 * @return boolean | error  true if successfull, error otherwise
 * {@getfields message_templates_delete}
 * @access public
 */
function civicrm_api3_message_templates_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}


function _civicrm_api3_message_templates_get_spec(&$params) {
}
/**
 * Retrieve one or more message_templatess
 *
 * @param  array input parameters
 *
 *
 * @example SepaCreditorGet.php Standard Get Example
 *
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array api result array
 * {@getfields message_templates_get}
 * @access public
 */
function civicrm_api3_message_templates_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

