<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.6                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This api exposes CiviCRM Case objects.
 * Developed by woolman.org
 *
 * @package CiviCRM_APIv3
 */


/**
 * Open a new case, add client and manager roles, and standard timeline.
 *
 * @param array $params
 *
 * @code
 * //REQUIRED:
 * 'case_type_id' => int OR
 * 'case_type' => str (provide one or the other)
 * 'contact_id' => int // case client
 * 'subject' => str
 *
 * //OPTIONAL
 * 'medium_id' => int // see civicrm option values for possibilities
 * 'creator_id' => int // case manager, default to the logged in user
 * 'status_id' => int // defaults to 1 "ongoing"
 * 'location' => str
 * 'start_date' => str datestamp // defaults to: date('YmdHis')
 * 'duration' => int // in minutes
 * 'details' => str // html format
 * @endcode
 *
 * @throws API_Exception
 * @return array
 *   api result array
 */
function civicrm_api3_case_create($params) {

  if (!empty($params['id'])) {
    return civicrm_api3_case_update($params);
  }

  civicrm_api3_verify_mandatory($params, NULL, array(
    'contact_id',
    'subject',
    array('case_type', 'case_type_id'))
  );
  _civicrm_api3_case_format_params($params);

  // If format_params didn't find what it was looking for, return error
  if (empty($params['case_type_id'])) {
    throw new API_Exception('Invalid case_type. No such case type exists.');
  }
  if (empty($params['case_type'])) {
    throw new API_Exception('Invalid case_type_id. No such case type exists.');
  }

  // Fixme: can we safely pass raw params to the BAO?
  $newParams = array(
    'case_type_id' => $params['case_type_id'],
    'creator_id' => $params['creator_id'],
    'status_id' => $params['status_id'],
    'start_date' => $params['start_date'],
    'end_date' => CRM_Utils_Array::value('end_date', $params),
    'subject' => $params['subject'],
  );

  $caseBAO = CRM_Case_BAO_Case::create($newParams);

  if (!$caseBAO) {
    throw new API_Exception('Case not created. Please check input params.');
  }

  foreach ((array) $params['contact_id'] as $cid) {
    $contactParams = array('case_id' => $caseBAO->id, 'contact_id' => $cid);
    CRM_Case_BAO_Case::addCaseToContact($contactParams);
  }

  // Initialize XML processor with $params
  $xmlProcessor = new CRM_Case_XMLProcessor_Process();
  $xmlProcessorParams = array(
    'clientID' => $params['contact_id'],
    'creatorID' => $params['creator_id'],
    'standardTimeline' => 1,
    'activityTypeName' => 'Open Case',
    'caseID' => $caseBAO->id,
    'subject' => $params['subject'],
    'location' => CRM_Utils_Array::value('location', $params),
    'activity_date_time' => $params['start_date'],
    'duration' => CRM_Utils_Array::value('duration', $params),
    'medium_id' => CRM_Utils_Array::value('medium_id', $params),
    'details' => CRM_Utils_Array::value('details', $params),
    'custom' => array(),
  );

  // Do it! :-D
  $xmlProcessor->run($params['case_type'], $xmlProcessorParams);

  // return case
  $values = array();
  _civicrm_api3_object_to_array($caseBAO, $values[$caseBAO->id]);

  return civicrm_api3_create_success($values, $params, 'Case', 'create', $caseBAO);
}

/**
 * Adjust Metadata for Get Action.
 *
 * @param array $params
 *   Parameters determined by getfields.
 */
function _civicrm_api3_case_get_spec(&$params) {
  $params['contact_id'] = array(
    'api.aliases' => array('client_id'),
    'title' => 'Case Client',
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * Adjust Metadata for Create Action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_case_create_spec(&$params) {
  $params['contact_id'] = array(
    'api.aliases' => array('client_id'),
    'title' => 'Case Client',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  );
  $params['status_id']['api.default'] = 1;
  $params['status_id']['api.aliases'] = array('case_status');
  $params['creator_id']['api.default'] = 'user_contact_id';
  $params['creator_id']['type'] = CRM_Utils_Type::T_INT;
  $params['creator_id']['title'] = 'Case Created By';
  $params['start_date']['api.default'] = 'now';
  $params['medium_id'] = array(
    'name' => 'medium_id',
    'title' => 'Activity Medium',
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * Adjust Metadata for Update action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_case_update_spec(&$params) {
  $params['id']['api.required'] = 1;
}

/**
 * Adjust Metadata for Delete action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_case_delete_spec(&$params) {
  $params['id']['api.required'] = 1;
}

/**
 * Get details of a particular case, or search for cases, depending on params.
 *
 * Please provide one (and only one) of the four get/search parameters:
 *
 * @param array $params
 *   'id' => if set, will get all available info about a case, including contacts and activities
 *
 *   // if no case_id provided, this function will use one of the following search parameters:
 *   'client_id' => finds all cases with a specific client
 *   'activity_id' => returns the case containing a specific activity
 *   'contact_id' => finds all cases associated with a contact (in any role, not just client)
 *
 * @throws API_Exception
 * @return array
 *   (get mode, case_id provided): Array with case details, case roles, case activity ids, (search mode, case_id not provided): Array of cases found
 */
function civicrm_api3_case_get($params) {
  $options = _civicrm_api3_get_options_from_params($params);
  //search by client
  if (!empty($params['contact_id'])) {
    $ids = array();
    foreach ((array) $params['contact_id'] as $cid) {
      if (is_numeric($cid)) {
        $ids = array_merge($ids, CRM_Case_BAO_Case::retrieveCaseIdsByContactId($cid, TRUE));
      }
    }
    $cases = array();
    foreach ($ids as $id) {
      if ($case = _civicrm_api3_case_read($id, $options)) {
        $cases[$id] = $case;
      }
    }
    return civicrm_api3_create_success($cases, $params, 'Case', 'get');
  }

  //search by activity
  if (!empty($params['activity_id'])) {
    if (!is_numeric($params['activity_id'])) {
      throw new API_Exception('Invalid parameter: activity_id. Must provide a numeric value.');
    }
    $caseId = CRM_Case_BAO_Case::getCaseIdByActivityId($params['activity_id']);
    if (!$caseId) {
      return civicrm_api3_create_success(array(), $params, 'Case', 'get');
    }
    $case = array($caseId => _civicrm_api3_case_read($caseId, $options));
    return civicrm_api3_create_success($case, $params, 'Case', 'get');
  }

  //search by contacts
  if (($contact = CRM_Utils_Array::value('contact_id', $params)) != FALSE) {
    if (!is_numeric($contact)) {
      throw new API_Exception('Invalid parameter: contact_id.  Must provide a numeric value.');
    }

    $sql = "
SELECT DISTINCT case_id
  FROM civicrm_relationship
 WHERE (contact_id_a = $contact
    OR contact_id_b = $contact)
   AND case_id IS NOT NULL";
    $dao = CRM_Core_DAO::executeQuery($sql);

    $cases = array();
    while ($dao->fetch()) {
      $cases[$dao->case_id] = _civicrm_api3_case_read($dao->case_id, $options);
    }
    return civicrm_api3_create_success($cases, $params, 'Case', 'get');
  }

  // For historic reasons we always return these when an id is provided
  $caseId = CRM_Utils_Array::value('id', $params);
  if ($caseId) {
    $options['return'] = array('contacts' => 1, 'activities' => 1);
  }

  $foundcases = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Case');
  $cases = array();
  foreach ($foundcases['values'] as $foundcase) {
    if ($case = _civicrm_api3_case_read($foundcase['id'], $options)) {
      $cases[$foundcase['id']] = $case;
    }
  }

  return civicrm_api3_create_success($cases, $params, 'Case', 'get');
}

/**
 * Deprecated API.
 *
 * Use activity API instead.
 *
 * @param array $params
 *
 * @throws API_Exception
 * @return array
 */
function civicrm_api3_case_activity_create($params) {
  require_once "api/v3/Activity.php";
  return civicrm_api3_activity_create($params) + array(
    'deprecated' => CRM_Utils_Array::value('activity_create', _civicrm_api3_case_deprecation()),
  );
}

/**
 * Declare deprecated api functions.
 *
 * @deprecated api notice
 * @return array
 *   Array of deprecated actions
 */
function _civicrm_api3_case_deprecation() {
  return array('activity_create' => 'Case api "activity_create" action is deprecated. Use the activity api instead.');
}

/**
 * Update a specified case.
 *
 * @param array $params
 *   //REQUIRED:
 *   'case_id' => int
 *
 *   //OPTIONAL
 *   'status_id' => int
 *   'start_date' => str datestamp
 *   'contact_id' => int // case client
 *
 * @throws API_Exception
 * @return array
 *   api result array
 */
function civicrm_api3_case_update($params) {
  //check parameters
  civicrm_api3_verify_mandatory($params, NULL, array('id'));

  // return error if modifying creator id
  if (array_key_exists('creator_id', $params)) {
    throw new API_Exception(ts('You cannot update creator id'));
  }

  $mCaseId = $origContactIds = array();

  // get original contact id and creator id of case
  if (!empty($params['contact_id'])) {
    $origContactIds = CRM_Case_BAO_Case::retrieveContactIdsByCaseId($params['id']);
    $origContactId = $origContactIds[1];
  }

  if (count($origContactIds) > 1) {
    // check valid orig contact id
    if (!empty($params['orig_contact_id']) && !in_array($params['orig_contact_id'], $origContactIds)) {
      throw new API_Exception('Invalid case contact id (orig_contact_id)');
    }
    elseif (empty($params['orig_contact_id'])) {
      throw new API_Exception('Case is linked with more than one contact id. Provide the required params orig_contact_id to be replaced');
    }
    $origContactId = $params['orig_contact_id'];
  }

  // check for same contact id for edit Client
  if (!empty($params['contact_id']) && !in_array($params['contact_id'], $origContactIds)) {
    $mCaseId = CRM_Case_BAO_Case::mergeCases($params['contact_id'], $params['case_id'], $origContactId, NULL, TRUE);
  }

  if (!empty($mCaseId[0])) {
    $params['id'] = $mCaseId[0];
  }

  $dao = new CRM_Case_BAO_Case();
  $dao->id = $params['id'];

  $dao->copyValues($params);
  $dao->save();

  $case = array();

  _civicrm_api3_object_to_array($dao, $case);
  $values[$dao->id] = $case;

  return civicrm_api3_create_success($values, $params, 'Case', 'update', $dao);
}

/**
 * Delete a specified case.
 *
 * @param array $params
 *
 * @code
 *   //REQUIRED:
 *   'id' => int
 *
 *   //OPTIONAL
 *   'move_to_trash' => bool (defaults to false)
 * @endcode
 *
 * @throws API_Exception
 * @return bool
 *   true if success, else false
 */
function civicrm_api3_case_delete($params) {
  //check parameters
  civicrm_api3_verify_mandatory($params, NULL, array('id'));

  if (CRM_Case_BAO_Case::deleteCase($params['id'], CRM_Utils_Array::value('move_to_trash', $params, FALSE))) {
    return civicrm_api3_create_success($params, $params, 'Case', 'delete');
  }
  else {
    throw new API_Exception('Could not delete case.');
  }
}

/**
 * Internal function to retrieve a case.
 *
 * @param int $caseId
 *
 * @param $options
 *
 * @return array
 *   case object
 */
function _civicrm_api3_case_read($caseId, $options) {
  $return = CRM_Utils_Array::value('return', $options, array());
  $dao = new CRM_Case_BAO_Case();
  $dao->id = $caseId;
  if ($dao->find(TRUE)) {
    $case = array();
    _civicrm_api3_object_to_array($dao, $case);
    // Legacy support for client_id - TODO: in apiv4 remove 'client_id'
    $case['client_id'] = $case['contact_id'] = $dao->retrieveContactIdsByCaseId($caseId);

    if (!empty($return['contacts'])) {
      //get case contacts
      $contacts = CRM_Case_BAO_Case::getcontactNames($caseId);
      $relations = CRM_Case_BAO_Case::getRelatedContacts($caseId);
      $case['contacts'] = array_merge($contacts, $relations);
    }
    if (!empty($return['activities'])) {
      //get case activities
      $case['activities'] = array();
      $query = "SELECT activity_id FROM civicrm_case_activity WHERE case_id = $caseId";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $case['activities'][] = $dao->activity_id;
      }
    }
    return $case;
  }
}

/**
 * Internal function to format create params for processing.
 *
 * @param array $params
 */
function _civicrm_api3_case_format_params(&$params) {
  // figure out case type id from case type and vice-versa
  $caseTypes = CRM_Case_PseudoConstant::caseType('name', FALSE);
  if (empty($params['case_type_id'])) {
    $params['case_type_id'] = array_search($params['case_type'], $caseTypes);

    // DEPRECATED: lookup by label for backward compatibility
    if (!$params['case_type_id']) {
      $caseTypeLabels = CRM_Case_PseudoConstant::caseType('title', FALSE);
      $params['case_type_id'] = array_search($params['case_type'], $caseTypeLabels);
      $params['case_type'] = $caseTypes[$params['case_type_id']];
    }
  }
  elseif (empty($params['case_type'])) {
    $params['case_type'] = $caseTypes[$params['case_type_id']];
  }
}
