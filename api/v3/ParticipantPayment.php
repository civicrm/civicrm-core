<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * File for the CiviCRM APIv3 participant functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Participant
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: Participant.php 30486 2010-11-02 16:12:09Z shot $
 *
 */

/**
 * Files required for this package
 */

/**
 * Create a Event Participant Payment
 *
 * This API is used for creating a Participant Payment of Event.
 * Required parameters : participant_id, contribution_id.
 *
 * @param   array  $params     an associative array of name/value property values of civicrm_participant_payment
 * @example ParticipantPaymentCreate.php
 * {@example ParticipantPaymentCreate.php 0}
 *
 * @return array of newly created payment property values.
 * {@getfields ParticipantPayment_create}
 * @access public
 */
function civicrm_api3_participant_payment_create($params) {

  $ids = array();
  if (!empty($params['id'])) {
    $ids['id'] = $params['id'];
  }
  $participantPayment = CRM_Event_BAO_ParticipantPayment::create($params, $ids);

  $payment = array();
  _civicrm_api3_object_to_array($participantPayment, $payment[$participantPayment->id]);

  return civicrm_api3_create_success($payment, $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_participant_payment_create_spec(&$params) {
  $params['participant_id']['api.required'] = 1;
  $params['contribution_id']['api.required'] = 1;
}

/**
 * Deletes an existing Participant Payment
 *
 * This API is used for deleting a Participant Payment
 *
 * @param $params
 *
 * @internal param Int $participantPaymentID Id of the Participant Payment to be deleted
 *
 * @return array API result
 * @example ParticipantPaymentDelete.php
 * {@getfields ParticipantPayment_delete}
 * @access public
 */
function civicrm_api3_participant_payment_delete($params) {
  $participant = new CRM_Event_BAO_ParticipantPayment();
  return $participant->deleteParticipantPayment($params) ? civicrm_api3_create_success() : civicrm_api3_create_error('Error while deleting participantPayment');
}

/**
 * Retrieve one / all contribution(s) / participant(s) linked to a
 * contribution.
 *
 * @param  array   $params  input parameters
 *
 * @return array  array of properties, if error an array with an error id and error message
 *  @example ParticipantPaymentGet
 * {@getfields ParticipantPayment_get}
 * @access public
 */
function civicrm_api3_participant_payment_get($params) {
  return _civicrm_api3_basic_get('CRM_Event_DAO_ParticipantPayment', $params);
}

