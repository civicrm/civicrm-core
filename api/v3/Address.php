<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
function civicrm_api3_address_create($params) {
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

  if (!isset($params['fix_address']) || $params['fix_address']) {
    CRM_Core_BAO_Address::fixAddress($params);
  }

  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Address');
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
  $params['street_parsing'] = [
    'title' => 'Street Address Parsing',
    'description' => 'Optional param to indicate you want the street_address field parsed into individual params',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['skip_geocode'] = [
    'title' => 'Skip geocode',
    'description' => 'Optional param to indicate you want to skip geocoding (useful when importing a lot of addresses
      at once, the job \'Geocode and Parse Addresses\' can execute this task after the import)',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['fix_address'] = [
    'title' => ts('Fix address'),
    'description' => ts('When true, apply various fixes to the address before insert. Default true.'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  ];
  $params['world_region'] = [
    'title' => ts('World Region'),
    'name' => 'world_region',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $defaultLocation = CRM_Core_BAO_LocationType::getDefault();
  if ($defaultLocation) {
    $params['location_type_id']['api.default'] = $defaultLocation->id;
  }
}

/**
 * Adjust Metadata for Get action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_address_get_spec(&$params) {
  $params['world_region'] = [
    'title' => ts('World Region'),
    'name' => 'world_region',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
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
function civicrm_api3_address_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Address');
}
