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
 * File for the CiviCRM APIv3 address functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Address
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Address.php 2011-02-16 ErikHommel $
 */

require_once 'CRM/Core/BAO/Address.php';

/**
 *  Add an Address for a contact
 *
 * Allowed @params array keys are:
 * {@getfields address_create}
 * {@example AddressCreate.php}
 *
 * @return array of newly created tag property values.
 * @access public
 */
function civicrm_api3_address_create(&$params) {
  /**
   * if street_parsing, street_address has to be parsed into
   * separate parts
   */
  if (array_key_exists('street_parsing', $params)) {
    if ($params['street_parsing'] == 1) {
      if (array_key_exists('street_address', $params)) {
        if (!empty($params['street_address'])) {
          $parsedItems = CRM_Core_BAO_Address::parseStreetAddress(
            $params['street_address']
          );
          if (array_key_exists('street_name', $parsedItems)) {
            $params['street_name'] = $parsedItems['street_name'];
          }
          if (array_key_exists('street_unit', $parsedItems)) {
            $params['street_unit'] = $parsedItems['street_unit'];
          }
          if (array_key_exists('street_number', $parsedItems)) {
            $params['street_number'] = $parsedItems['street_number'];
          }
          if (array_key_exists('street_number_suffix', $parsedItems)) {
            $params['street_number_suffix'] = $parsedItems['street_number_suffix'];
          }
        }
      }
    }
  }

  /**
    * create array for BAO (expects address params in as an
    * element in array 'address'
    */
  $addressBAO = CRM_Core_BAO_Address::add($params, TRUE);
  if (empty($addressBAO)) {
    return civicrm_api3_create_error("Address is not created or updated ");
  }
  else {
    $values = array();
    $values = _civicrm_api3_dao_to_array($addressBAO, $params);
    return civicrm_api3_create_success($values, $params, 'address', $addressBAO);
  }
}

/**
 * Adjust Metadata for Create action
 *
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_address_create_spec(&$params) {
  $params['location_type_id']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['country'] = array('title' => 'Name or 2-letter abbreviation of country. Looked up in civicrm_country table');
  $params['street_parsing'] = array('title' => 'optional param to indicate you want the street_address field parsed into individual params');
}

/**
 * Deletes an existing Address
 *
 * @param  array  $params
 *
 * {@getfields address_delete}
 * {@example AddressDelete.php 0}
 *
 * @return boolean | error  true if successfull, error otherwise
 * @access public
 */
function civicrm_api3_address_delete(&$params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more addresses on address_id, contact_id, street_name, city
 * or a combination of those
 *
 * @param  mixed[]  (reference ) input parameters
 *
 * {@example AddressGet.php 0}
 * @param  array $params  an associative array of name/value pairs.
 *
 * @return  array details of found addresses else error
 * {@getfields address_get}
 * @access public
 */
function civicrm_api3_address_get(&$params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Address');
}

