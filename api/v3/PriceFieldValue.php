<?php
/*
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
 * @subpackage API_PriceFieldValue
 * @copyright CiviCRM LLC (c) 20042012
 */

/**
 * Create or update a price_field_value
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'price_field_value'
 * @example PriceFieldValueCreate.php Std Create example
 *
 * @return array api result array
 * {@getfields price_field_value_create}
 * @access public
 */
function civicrm_api3_price_field_value_create($params) {
  $ids = array();
  if(!empty($params['id'])){
    $ids['id'] = $params['id'];
  }

  $bao = CRM_Price_BAO_FieldValue::create($params, $ids);

  $values = array();
  _civicrm_api3_object_to_array($bao, $values[$bao->id]);
  return civicrm_api3_create_success($values, $params, 'price_field_value', 'create', $bao);

}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_price_field_value_create_spec(&$params) {
}

/**
 * Returns array of price_field_values  matching a set of one or more group properties
 *
 * @param array $params Array of one or more valid property_name=>value pairs. If $params is set
 *  as null, all price_field_values will be returned (default limit is 25)
 *
 * @return array  Array of matching price_field_values
 * {@getfields price_field_value_get}
 * @access public
 */
function civicrm_api3_price_field_value_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * delete an existing price_field_value
 *
 * This method is used to delete any existing price_field_value. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params array containing id of the group
 *  to be deleted
 *
 * @return array  returns flag true if successfull, error message otherwise
 * {@getfields price_field_value_delete}
 * @access public
 */
function civicrm_api3_price_field_value_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
