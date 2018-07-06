<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * This api exposes CiviCRM participant status options.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create/update participant_status.
 *
 * This API is used to create new participant_status or update any of the existing
 * In case of updating existing participant_status, id of that particular participant_status must
 * be in $params array.
 *
 * @param array $params
 *   name/value pairs to insert in new 'participant_status'
 *
 * @return array
 *   participant_status array
 */
function civicrm_api3_participant_status_type_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ParticipantStatusType');
}

/**
 * Returns array of participant_statuses matching a set of one or more group properties.
 *
 * @param array $params
 *   Array of properties. If empty, all records will be returned.
 *
 * @return array
 *   Array of matching participant_statuses
 */
function civicrm_api3_participant_status_type_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete an existing participant_status.
 *
 * This method is used to delete any existing participant_status given its id.
 *
 * @param array $params
 *   [id]
 * @return array api result array
 * @throws API_Exception
 */
function civicrm_api3_participant_status_type_delete($params) {
  if (CRM_Event_BAO_ParticipantStatusType::deleteParticipantStatusType($params['id'])) {
    return civicrm_api3_create_success(TRUE);
  }

  throw new API_Exception('Could not delete participant status type id ' . $params['id']);
}
