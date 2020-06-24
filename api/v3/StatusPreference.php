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
 * This api exposes CiviCRM Status Preferences.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a Status Preference.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_status_preference_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'StatusPreference');
}

/**
 * Get an Acl.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved Acl property values.
 */
function civicrm_api3_status_preference_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an Acl.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_status_preference_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_status_preference_create_spec(&$params) {
  $params['name']['api.required'] = 1;
  // Status Preference can be integer OR a string.
  $params['ignore_severity']['type'] = 2;
}
