<?php
/*
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
 * File for the CiviCRM APIv3 group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_LineItem
 * @copyright CiviCRM LLC (c) 20042012
 */

/**
 * Create or update a line_item
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'line_item'
 * @example LineItemCreate.php Std Create example
 *
 * @return array api result array
 * {@getfields line_item_create}
 * @access public
 */
function civicrm_api3_line_item_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_line_item_create_spec(&$params) {
  $params['entity_id']['api.required'] = 1;
  $params['qty']['api.required'] = 1;
  $params['unit_price']['api.required'] = 1;
  $params['line_total']['api.required'] = 1;
  $params['label']['api.default'] = 'line item';
}

/**
 * Returns array of line_items  matching a set of one or more group properties
 *
 * @param array $params Array of one or more valid property_name=>value pairs. If $params is set
 *  as null, all line_items will be returned (default limit is 25)
 *
 * @return array  Array of matching line_items
 * {@getfields line_item_get}
 * @access public
 */
function civicrm_api3_line_item_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * delete an existing line_item
 *
 * This method is used to delete any existing line_item. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params array containing id of the group
 *  to be deleted
 *
 * @return array API result array
 * {@getfields line_item_delete}
 * @access public
 */
function civicrm_api3_line_item_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
