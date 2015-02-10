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
 * Create an Event Participant
 *
 * This API is used for creating a participants in an event.
 * Required parameters : event_id AND contact_id for new creation
 *                     : participant as name/value with participantid for edit
 *
 * @param   array  $params     an associative array of name/value property values of civicrm_participant
 *
 * @return array apiresult
 * {@getfields participant_create}
 * @access public
 */
function civicrm_api3_participant_create($params) {
  //check that event id is not an template - should be done @ BAO layer
  if (!empty($params['event_id'])) {
    $isTemplate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'is_template');
    if (!empty($isTemplate)) {
      return civicrm_api3_create_error(ts('Event templates are not meant to be registered'));
    }
  }

  $value = array();
  _civicrm_api3_custom_format_params($params, $values, 'Participant');
  $params = array_merge($values, $params);

  $participantBAO = CRM_Event_BAO_Participant::create($params);

  if(empty($params['price_set_id']) && empty($params['id']) && !empty($params['fee_level'])){
    _civicrm_api3_participant_createlineitem($params, $participantBAO);
  }
  _civicrm_api3_object_to_array($participantBAO, $participant[$participantBAO->id]);

  return civicrm_api3_create_success($participant, $params, 'participant', 'create', $participantBAO);
}

/**
 * Create a default participant line item
 */
function _civicrm_api3_participant_createlineitem(&$params, $participant){
  // it is possible that a fee level contains information about multiple
  // price field values.

  $priceFieldValueDetails = CRM_Utils_Array::explodePadded(
    $params["fee_level"]);

  foreach($priceFieldValueDetails as $detail) {
    if (preg_match('/- ([0-9]+)$/', $detail, $matches)) {
      // it is possible that a price field value is payd for multiple times.
      // (FIXME: if the price field value ends in minus followed by whitespace
      // and a number, things will go wrong.)

      $qty = $matches[1];
      preg_match('/^(.*) - [0-9]+$/', $detail, $matches);
      $label = $matches[1];
    }
    else {
      $label = $detail;
      $qty = 1;
    }

    $sql = "
      SELECT      ps.id AS setID, pf.id AS priceFieldID, pfv.id AS priceFieldValueID, pfv.amount AS amount
      FROM  civicrm_price_set_entity cpse
      LEFT JOIN civicrm_price_set ps ON cpse.price_set_id = ps.id AND cpse.entity_id = %1 AND cpse.entity_table = 'civicrm_event'
      LEFT JOIN   civicrm_price_field pf ON pf.`price_set_id` = ps.id
      LEFT JOIN   civicrm_price_field_value pfv ON pfv.price_field_id = pf.id
      where ps.id is not null and pfv.label = %2
    ";

    $qParams = array(
      1 => array($params['event_id'], 'Integer'),
      2 => array($label, 'String'),
    );

    $dao = CRM_Core_DAO::executeQuery($sql, $qParams);
    if ($dao->fetch()) {
      $lineItemparams = array(
        'price_field_id' => $dao->priceFieldID,
        'price_field_value_id' => $dao->priceFieldValueID,
        'entity_table' => 'civicrm_participant',
        'entity_id' => $participant->id,
        'label' => $label,
        'qty' => $qty,
        'participant_count' => 0,
        'unit_price' => $dao->amount,
        'line_total' => $qty*$dao->amount,
        'version' => 3,
      );
      civicrm_api('line_item', 'create', $lineItemparams);
    }

  }
}


/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_participant_create_spec(&$params) {
  $params['status_id']['api.default'] = "1";
  $params['register_date']['api.default'] = "now";
  $params['event_id']['api.required'] = 1;
  $params['contact_id']['api.required'] = 1;
  // These are for the sake of search builder options - can be removed if that is fixed
  $params['role_id']['api.aliases'] = array('participant_role');
  $params['status_id']['api.aliases'] = array('participant_status');
}

/**
 * Retrieve a specific participant, given a set of input params
 * If more than one matching participant exists, return an error, unless
 * the client has requested to return the first found contact
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        array of properties, if error an array with an error id and error message
 * {@getfields participant_get}
 * @access public
 */
function civicrm_api3_participant_get($params) {
  $mode = CRM_Contact_BAO_Query::MODE_EVENT;
  $entity = 'participant';

  list($dao, $query) = _civicrm_api3_get_query_object($params, $mode, $entity);

  $participant = array();
  while ($dao->fetch()) {
    $participant[$dao->participant_id] = $query->store($dao);
    //@todo - is this required - contribution & pledge use the same query but don't self-retrieve custom data
    _civicrm_api3_custom_data_get($participant[$dao->participant_id], 'Participant', $dao->participant_id, NULL);
  }

  return civicrm_api3_create_success($participant, $params, 'participant', 'get', $dao);
}

/**
 * Adjust Metadata for Get action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_participant_get_spec(&$params) {
  $params['participant_test']['api.default'] = 0;
  $params['participant_test']['title'] = 'Get Test Participants';
}

/**
 * Deletes an existing contact participant
 *
 * This API is used for deleting a contact participant
 *
 * @param  array $params Array containing  Id of the contact participant to be deleted
 *
 * {@getfields participant_delete}
 * @throws Exception
 * @return array
 * @access public
 */
function civicrm_api3_participant_delete($params) {

  $result = CRM_Event_BAO_Participant::deleteParticipant($params['id']);

  if ($result) {
    return civicrm_api3_create_success();
  }
  else {
    throw new Exception('Error while deleting participant');
  }
}

