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
 * This api exposes CiviCRM Scheduled Reminders.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get CiviCRM ActionSchedule details.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_action_schedule_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ActionSchedule');
}


/**
 * Create a new ActionSchedule.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_action_schedule_create($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, array('start_action_date', 'absolute_date'));
  if (!array_key_exists('name', $params) && !array_key_exists('id', $params)) {
    $params['name'] = CRM_Utils_String::munge($params['title']);
  }
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ActionSchedule');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_action_schedule_create_spec(&$params) {
  $params['title']['api.required'] = TRUE;
  $params['mapping_id']['api.required'] = TRUE;
  $params['entity_value']['api.required'] = TRUE;
}

/**
 * Delete an existing ActionSchedule.
 *
 * @param array $params
 *   Array containing id of the action_schedule to be deleted.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_action_schedule_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
