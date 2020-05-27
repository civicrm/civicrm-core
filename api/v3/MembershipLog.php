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
 * This api exposes CiviCRM MembershipLog records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * API to Create or update a MembershipLog.
 *
 * @param array $params
 *   Values of MembershipLog.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_membership_log_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MembershipLog');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_membership_log_create_spec(&$params) {
  $params['membership_id']['api.required'] = TRUE;
}

/**
 * Get a Membership Log.
 *
 * This api is used for finding an existing membership log.
 *
 * @param array $params
 *   An associative array of name/value property values of civicrm_membership_log.
 * {getfields MembershipLog_get}
 *
 * @return array
 *   API result array
 */
function civicrm_api3_membership_log_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Deletes an existing membership log.
 *
 * This API is used for deleting a membership log
 * Required parameters : id of a membership log
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_membership_log_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
