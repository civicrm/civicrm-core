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
 * File for the CiviCRM APIv3 group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Event
 * @copyright CiviCRM LLC (c) 2004-2013
 */

require_once 'CRM/Event/BAO/ParticipantStatusType.php';
require_once 'api/v3/utils.php';

/**
 * create/update participant_status
 *
 * This API is used to create new participant_status or update any of the existing
 * In case of updating existing participant_status, id of that particular participant_status must
 * be in $params array.
 *
 * @param array $params  (referance) Associative array of property
 *                       name/value pairs to insert in new 'participant_status'
 *
 * @return array   participant_status array
 * {@getfields ParticipantStatusType_create}
 * @example ParticipantStatusTypeCreate.php
 * @access public
 */
function civicrm_api3_participant_status_type_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Returns array of participant_statuss  matching a set of one or more group properties
 *
 * @param array $params  (referance) Array of one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all participant_statuss will be returned
 *
 * @return array  (referance) Array of matching participant_statuses
 * {@getfields ParticipantStatusType_get}
 * @example ParticipantStatusTypeGet.php
 * @access public
 */
function civicrm_api3_participant_status_type_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * delete an existing participant_status
 *
 * This method is used to delete any existing participant_status. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params  (reference) array containing id of the group
 *                       to be deleted
 *
 * @return array  (referance) returns flag true if successfull, error
 *                message otherwise
 * {@getfields ParticipantStatusType_delete}
 * @example ParticipantStatusTypeDelete.php
 * @access public
 */
function civicrm_api3_participant_status_type_delete($params) {
  if (CRM_Event_BAO_ParticipantStatusType::deleteParticipantStatusType($params['id'])) {
    return civicrm_api3_create_success(TRUE);
  }

  return civicrm_api3_create_error(TRUE);
}

