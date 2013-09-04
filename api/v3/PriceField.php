<?php
/*
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 * @subpackage API_PriceField
 * @copyright CiviCRM LLC (c) 20042012
 */

/**
 * Create or update a price_field
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'price_field'
 * @example PriceFieldCreate.php Std Create example
 *
 * @return array api result array
 * {@getfields price_field_create}
 * @access public
 */
function civicrm_api3_price_field_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_price_field_create_spec(&$params) {
}

/**
 * Returns array of price_fields  matching a set of one or more group properties
 *
 * @param array $params Array of one or more valid property_name=>value pairs. If $params is set
 *  as null, all price_fields will be returned (default limit is 25)
 *
 * @return array  Array of matching price_fields
 * {@getfields price_field_get}
 * @access public
 */
function civicrm_api3_price_field_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * delete an existing price_field
 *
 * This method is used to delete any existing price_field. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params array containing id of the group
 *  to be deleted
 *
 * @return array  returns flag true if successfull, error message otherwise
 * {@getfields price_field_delete}
 * @access public
 */
function civicrm_api3_price_field_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
