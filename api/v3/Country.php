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
 * This api exposes CiviCRM country.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add an Country for a contact.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_country_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_DAO(__FUNCTION__), $params, 'Country');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_country_create_spec(&$params) {
  $params['name']['api.required'] = 1;
}

/**
 * Deletes an existing Country.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_country_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_DAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more countryies.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   api result array
 */
function civicrm_api3_country_get($params) {

  return _civicrm_api3_basic_get(_civicrm_api3_get_DAO(__FUNCTION__), $params);
}
