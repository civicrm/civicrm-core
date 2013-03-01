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
 *
 * Creating this file to make unit testing the 2 versions smoother
 * v2 series are to be deprecated but this is where the participant_payment functions should go
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Contact
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: Contact.php 30415 2010-10-29 12:02:47Z shot $
 */
require_once 'api/v2/Participant.php';

/**
 * Create a Event Participant Payment
 *
 * This API is used for creating a Participant Payment of Event.
 * Required parameters : participant_id, contribution_id.
 *
 * @param   array  $params     an associative array of name/value property values of civicrm_participant_payment
 *
 * @return array of newly created payment property values.
 * @access public
 */
function &civicrm_participant_payment_create(&$params) {
  _civicrm_initialize();
  if (!is_array($params)) {
    $error = civicrm_create_error('Params is not an array');
    return $error;
  }

  if (!isset($params['participant_id']) || !isset($params['contribution_id'])) {
    $error = civicrm_create_error('Required parameter missing');
    return $error;
  }

  $ids = array();
  if (CRM_Utils_Array::value('id', $params)) {
    $ids['id'] = $params['id'];
  }
  require_once 'CRM/Event/BAO/ParticipantPayment.php';
  $participantPayment = CRM_Event_BAO_ParticipantPayment::create($params, $ids);

  if (is_a($participantPayment, 'CRM_Core_Error')) {
    $error = civicrm_create_error("Participant payment could not be created");
    return $error;
  }
  else {
    $payment             = array();
    $payment['id']       = $participantPayment->id;
    $payment['is_error'] = 0;
  }
  return $payment;
}

/**
 * Update an existing contact participant payment
 *
 * This api is used for updating an existing contact participant payment
 * Required parameters : id of a participant_payment
 *
 * @param  Array   $params  an associative array of name/value property values of civicrm_participant_payment
 *
 * @return array of updated participant_payment property values
 * @access public
 */
function &civicrm_participant_payment_update(&$params) {
  _civicrm_initialize();
  if (!is_array($params)) {
    $error = civicrm_create_error('Params is not an array');
    return $error;
  }

  if (!isset($params['id'])) {
    $error = civicrm_create_error('Required parameter missing');
    return $error;
  }

  $ids = array();
  $ids['id'] = $params['id'];

  require_once 'CRM/Event/BAO/ParticipantPayment.php';
  $payment = CRM_Event_BAO_ParticipantPayment::create($params, $ids);

  $participantPayment = array();
  _civicrm_object_to_array($payment, $participantPayment);

  return $participantPayment;
}

/**
 * Deletes an existing Participant Payment
 *
 * This API is used for deleting a Participant Payment
 *
 * @param  Int  $participantPaymentID   Id of the Participant Payment to be deleted
 *
 * @return null if successfull, array with is_error=1 otherwise
 * @access public
 */
function civicrm_participant_payment_delete(&$params) {
  _civicrm_initialize();

  if (!is_array($params)) {
    $error = civicrm_create_error('Params is not an array');
    return $error;
  }

  if (!CRM_Utils_Array::value('id', $params)) {
    $error = civicrm_create_error('Invalid or no value for Participant payment ID');
    return $error;
  }
  require_once 'CRM/Event/BAO/ParticipantPayment.php';
  $participant = new CRM_Event_BAO_ParticipantPayment();

  return $participant->deleteParticipantPayment($params) ? civicrm_create_success() : civicrm_create_error('Error while deleting participantPayment');
}

