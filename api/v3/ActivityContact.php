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
 * This api exposes the CiviCRM ActivityContact join table.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add a record relating a contact with an activity.
 *
 * @param array $params
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_activity_contact_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ActivityContact');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_activity_contact_create_spec(&$params) {
  $params['contact_id']['api.required'] = 1;
  $params['activity_id']['api.required'] = 1;
}

/**
 * Delete an existing ActivityContact record.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_activity_contact_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a ActivityContact.
 *
 * @param array $params
 *   An associative array of name/value pairs.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_activity_contact_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
