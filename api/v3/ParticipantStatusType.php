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
 * @throws CRM_Core_Exception
 */
function civicrm_api3_participant_status_type_delete($params) {
  if (CRM_Event_BAO_ParticipantStatusType::deleteParticipantStatusType($params['id'])) {
    return civicrm_api3_create_success(TRUE);
  }

  throw new CRM_Core_Exception('Could not delete participant status type id ' . $params['id']);
}
