<?php

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
 * File for the CiviCRM APIv3 for Scheduled Reminders
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ActionSchedule
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 */

/**
 * Get CiviCRM Action Schedule details
 * {@getfields action_schedule_create}
 *
 */
function civicrm_api3_action_schedule_get($params) {
  $bao = new CRM_Core_BAO_ActionSchedule();
  _civicrm_api3_dao_set_filter($bao, $params, true, 'ActionSchedule');
  $actionSchedules = _civicrm_api3_dao_to_array($bao, $params, true,'ActionSchedule');

  return civicrm_api3_create_success($actionSchedules, $params, 'action_schedule', 'get', $bao);
}


/**
 * Create a new Action Schedule
 *
 * @param array $params
 *
 * @return array
 *
 * {@getfields action_schedule_create}
 */
function civicrm_api3_action_schedule_create($params) {
  if (!CRM_Utils_Array::value('id', $params)) {
    // an update does not require any mandatory parameters
    civicrm_api3_verify_one_mandatory($params,
      NULL,
      array(
        'title','mapping_id', 'entity_status', 'entity_value',
      )
    );
  }

  $ids = array();
  if (isset($params['id']) && !CRM_Utils_Rule::integer($params['id'])) {
    return civicrm_api3_create_error('Invalid value for ID');
  }

  if (!array_key_exists('name', $params) && !array_key_exists('id', $params)) {
    $params['name'] = CRM_Utils_String::munge($params['title']);
  }

  $actionSchedule = new CRM_Core_BAO_ActionSchedule();
  $actionSchedule = CRM_Core_BAO_ActionSchedule::add($params, $ids);

  $actSchedule = array();

  _civicrm_api3_object_to_array($actionSchedule, $actSchedule[$actionSchedule->id]);

  return civicrm_api3_create_success($actSchedule, $params, 'action_schedule', 'create', $actionSchedule);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_action_schedule_create_spec(&$params) {
  unset($params['version']);
}

/**
 * delete an existing action_schedule
 *
 *
 * @param array $params  (reference) array containing id of the action_schedule
 *                       to be deleted
 *
 * @return array  (referance) returns flag true if successfull, error
 *                message otherwise
 *
 * @access public
 */
function civicrm_api3_action_schedule_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}


