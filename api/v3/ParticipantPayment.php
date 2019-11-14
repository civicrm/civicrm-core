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
 * This api exposes CiviCRM participant payments.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create a Event Participant Payment.
 *
 * This API is used for creating a Participant Payment of Event.
 * Required parameters: participant_id, contribution_id.
 *
 * @param array $params
 *   An associative array of name/value property values of civicrm_participant_payment.
 *
 * @return array
 */
function civicrm_api3_participant_payment_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'ParticipantPayment');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_participant_payment_create_spec(&$params) {
  $params['participant_id']['api.required'] = 1;
  $params['contribution_id']['api.required'] = 1;
}

/**
 * Deletes an existing Participant Payment.
 *
 * @param array $params
 *
 * @return array
 *   API result
 */
function civicrm_api3_participant_payment_delete($params) {
  $participant = new CRM_Event_BAO_ParticipantPayment();
  return $participant->deleteParticipantPayment($params) ? civicrm_api3_create_success() : civicrm_api3_create_error('Error while deleting participantPayment');
}

/**
 * Retrieve one or more participant payment records.
 *
 * @param array $params
 *   Input parameters.
 *
 * @return array
 *   array of properties, if error an array with an error id and error message
 */
function civicrm_api3_participant_payment_get($params) {
  return _civicrm_api3_basic_get('CRM_Event_DAO_ParticipantPayment', $params);
}
