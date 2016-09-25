<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * This api exposes CiviCRM Address records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add an Address for a contact.
 *
 * FIXME: Should be using basic_create util
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_address_create(&$params) {
  _civicrm_api3_check_edit_permissions('CRM_Core_BAO_Address', $params);
  /**
   * If street_parsing, street_address has to be parsed into
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

  if (!isset($params['check_permissions'])) {
    $params['check_permissions'] = 0;
  }

  /**
   * Create array for BAO (expects address params in as an
   * element in array 'address'
   */
  $addressBAO = CRM_Core_BAO_Address::add($params, TRUE);
  if (empty($addressBAO)) {
    return civicrm_api3_create_error("Address is not created or updated ");
  }
  else {
    $values = _civicrm_api3_dao_to_array($addressBAO, $params);
    return civicrm_api3_create_success($values, $params, 'Address', $addressBAO);
  }
}

/**
 * Adjust Metadata for Create action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_address_create_spec(&$params) {
  $params['location_type_id']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  $params['street_parsing'] = array(
    'title' => 'Street Address Parsing',
    'description' => 'Optional param to indicate you want the street_address field parsed into individual params',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
  $params['skip_geocode'] = array(
    'title' => 'Skip geocode',
    'description' => 'Optional param to indicate you want to skip geocoding (useful when importing a lot of addresses
      at once, the job \'Geocode and Parse Addresses\' can execute this task after the import)',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
  $params['world_region'] = array(
    'title' => ts('World Region'),
    'name' => 'world_region',
    'type' => CRM_Utils_Type::T_TEXT,
  );
}

/**
 * Adjust Metadata for Get action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_address_get_spec(&$params) {
  $params['world_region'] = array(
    'title' => ts('World Region'),
    'name' => 'world_region',
    'type' => CRM_Utils_Type::T_TEXT,
  );
}

/**
 * Delete an existing Address.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_address_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more addresses.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_address_get(&$params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Address');
}
