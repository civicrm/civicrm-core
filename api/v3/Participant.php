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
 * File for the CiviCRM APIv3 participant functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Participant
 *
 * @copyright CiviCRM LLC (c) 2004-2013
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
  if (CRM_Utils_Array::value('event_id', $params)) {
    $isTemplate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'is_template');
    if (!empty($isTemplate)) {
      return civicrm_api3_create_error(ts('Event templates are not meant to be registered'));
    }
  }

  $value = array();
  _civicrm_api3_custom_format_params($params, $values, 'Participant');
  $params = array_merge($values, $params);

  $participantBAO = CRM_Event_BAO_Participant::create($params);

  if(empty($params['price_set_id']) && empty($params['id']) && CRM_Utils_Array::value('fee_level', $params)){
    _civicrm_api3_participant_createlineitem($params, $participantBAO);
  }
  _civicrm_api3_object_to_array($participantBAO, $participant[$participantBAO->id]);

  return civicrm_api3_create_success($participant, $params, 'participant', 'create', $participantBAO);
}

/**
 * Create a default participant line item
 */
function _civicrm_api3_participant_createlineitem(&$params, $participant){
  $sql = "
SELECT      ps.id AS setID, pf.id AS priceFieldID, pfv.id AS priceFieldValueID
FROM  civicrm_price_set_entity cpse
LEFT JOIN civicrm_price_set ps ON cpse.price_set_id = ps.id AND cpse.entity_id = {$params['event_id']} AND cpse.entity_table = 'civicrm_event'
LEFT JOIN   civicrm_price_field pf ON pf.`price_set_id` = ps.id
LEFT JOIN   civicrm_price_field_value pfv ON pfv.price_field_id = pf.id and pfv.label = '{$params['fee_level']}'
where ps.id is not null
";

  $dao = CRM_Core_DAO::executeQuery($sql);
  if ($dao->fetch()) {
    $amount = CRM_Utils_Array::value('fee_amount', $params, 0);
    $lineItemparams = array(
      'price_field_id' => $dao->priceFieldID,
      'price_field_value_id' => $dao->priceFieldValueID,
      'entity_table' => 'civicrm_participant',
      'entity_id' => $participant->id,
      'label' => $params['fee_level'],
      'qty' => 1,
      'participant_count' => 0,
      'unit_price' => $amount,
      'line_total' => $amount,
      'version' => 3,
    );
    civicrm_api('line_item', 'create', $lineItemparams);
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

  $options          = _civicrm_api3_get_options_from_params($params, TRUE,'participant','get');
  $sort             = CRM_Utils_Array::value('sort', $options, NULL);
  $offset           = CRM_Utils_Array::value('offset', $options);
  $rowCount         = CRM_Utils_Array::value('limit', $options);
  $smartGroupCache  = CRM_Utils_Array::value('smartGroupCache', $params);
  $inputParams      = CRM_Utils_Array::value('input_params', $options, array());
  $returnProperties = CRM_Utils_Array::value('return', $options, NULL);

  if (empty($returnProperties)) {
    $returnProperties = CRM_Event_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_EVENT);
  }
  $newParams = CRM_Contact_BAO_Query::convertFormValues($inputParams);
  $query = new CRM_Contact_BAO_Query($newParams, $returnProperties, NULL,
    FALSE, FALSE, CRM_Contact_BAO_Query::MODE_EVENT
  );
  list($select, $from, $where, $having) = $query->query();

  $sql = "$select $from $where $having";

  if (!empty($sort)) {
    $sql .= " ORDER BY $sort ";
  }
  $sql .= " LIMIT $offset, $rowCount ";
  $dao = CRM_Core_DAO::executeQuery($sql);

  $participant = array();
  while ($dao->fetch()) {
    $participant[$dao->participant_id] = $query->store($dao);
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
}

/**
 * Deletes an existing contact participant
 *
 * This API is used for deleting a contact participant
 *
 * @param  array  $params Array containing  Id of the contact participant to be deleted
 *
 * {@getfields participant_delete}
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

