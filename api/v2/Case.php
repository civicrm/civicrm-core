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
 * File for the CiviCRM APIv2 Case functions
 * Developed by woolman.org
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Case
 * @copyright CiviCRM LLC (c) 2004-2013
 *
 */

require_once 'api/v2/utils.php';
require_once 'CRM/Case/BAO/Case.php';
require_once 'CRM/Case/PseudoConstant.php';

/**
 * Open a new case, add client and manager roles, and add standard timeline
 *
 * @param  array(
    //REQUIRED:
 *                  'case_type_id'     => int OR 'case_type' => str (provide one or the other)
 *                  'contact_id'       => int // case client
 *                  'creator_id'       => int // case manager
 *                  'subject'          => str
 *                  'medium_id'        => int // see civicrm option values for possibilities
 *
 *                //OPTIONAL
 *                  'status_id'        => int // defaults to 1 "ongoing"
 *                  'location'         => str
 *                  'start_date'       => str datestamp // defaults to: date('YmdHis')
 *                  'duration'         => int // in minutes
 *                  'details'          => str // html format
 *
 * @return sucessfully opened case
 *
 * @access public
 */
function civicrm_case_create(&$params) {
  _civicrm_initialize();

  //check parameters
  $errors = _civicrm_case_check_params($params, 'create');

  if ($errors) {

    return $errors;
  }

  _civicrm_case_format_params($params, 'create');
  // If format_params didn't find what it was looking for, return error
  if (!$params['case_type_id']) {
    return civicrm_create_error(ts('Invalid case_type. No such case type exists.'));
  }
  if (!$params['case_type']) {
    return civicrm_create_error(ts('Invalid case_type_id. No such case type exists.'));
  }

  // format input with value separators
  $sep = CRM_Core_DAO::VALUE_SEPARATOR;
  $newParams = array(
    'case_type_id' => $sep . $params['case_type_id'] . $sep,
    'creator_id' => $params['creator_id'],
    'status_id' => $params['status_id'],
    'start_date' => $params['start_date'],
    'subject' => $params['subject'],
  );

  $case = CRM_Case_BAO_Case::create($newParams);

  if (!$case) {
    return civicrm_create_error(ts('Case not created. Please check your input params.'));
  }

  // Add client role
  $contactParams = array(
    'case_id' => $case->id,
    'contact_id' => $params['contact_id'],
  );

  CRM_Case_BAO_Case::addCaseToContact($contactParams);

  // Initialize XML processor with $params
  require_once 'CRM/Case/XMLProcessor/Process.php';
  $xmlProcessor = new CRM_Case_XMLProcessor_Process();
  $xmlProcessorParams = array(
    'clientID' => $params['contact_id'],
    'creatorID' => $params['creator_id'],
    'standardTimeline' => 1,
    'activityTypeName' => 'Open Case',
    'caseID' => $case->id,
    'subject' => $params['subject'],
    'location' => $params['location'],
    'activity_date_time' => $params['start_date'],
    'duration' => $params['duration'],
    'medium_id' => $params['medium_id'],
    'details' => $params['details'],
    'custom' => array(),
  );

  // Do it! :-D
  $xmlProcessor->run($params['case_type'], $xmlProcessorParams);

  // status msg
  $params['statusMsg'] = ts('Case opened successfully.');

  // return case
  $details = _civicrm_case_read($case->id);
  return civicrm_create_success($details);
}

/**
 * Get details of a particular case, or search for cases, depending on params
 *
 * Please provide one (and only one) of the four get/search parameters:
 *
 * @param array(
    'case_id'    => if set, will get all available info about a case, including contacts and activities
 *
 *                // if no case_id provided, this function will use one of the following search parameters:
 *               'client_id'   => finds all cases with a specific client
 *               'activity_id' => returns the case containing a specific activity
 *               'contact_id'  => finds all cases associated with a contact (in any role, not just client)
 *
 *
 * @return (get mode, case_id provided): Array with case details, case roles, case activity ids, (search mode, case_id not provided): Array of cases found
 * @access public
 */
function civicrm_case_get(&$params) {
  _civicrm_initialize();

  //get mode
  if ($caseId = $params['case_id']) {
    //validate param
    if (!is_numeric($caseId)) {
      return civicrm_create_error(ts('Invalid parameter: case_id. Must provide a numeric value.'));
    }

    $case = _civicrm_case_read($caseId);

    if ($case) {
      //get case contacts
      $contacts         = CRM_Case_BAO_Case::getcontactNames($caseId);
      $relations        = CRM_Case_BAO_Case::getRelatedContacts($caseId);
      $case['contacts'] = array_merge($contacts, $relations);

      //get case activities

      $query = "SELECT activity_id FROM civicrm_case_activity WHERE case_id = $caseId";
      $dao = CRM_Core_DAO::executeQuery($query);

      $case['activities'] = array();

      while ($dao->fetch()) {
        $case['activities'][] = $dao->activity_id;
      }

      return civicrm_create_success($case);
    }
    else {
      return civicrm_create_success(array());
    }
  }

  //search by client
  if ($client = $params['client_id']) {

    if (!is_numeric($client)) {
      return civicrm_create_error(ts('Invalid parameter: client_id. Must provide a numeric value.'));
    }

    $ids = CRM_Case_BAO_Case::retrieveCaseIdsByContactId($client, TRUE);

    if (empty($ids)) {

      return civicrm_create_success(array());
    }

    $cases = array();

    foreach ($ids as $id) {
      $cases[$id] = _civicrm_case_read($id);
    }
    return civicrm_create_success($cases);
  }

  //search by activity
  if ($act = $params['activity_id']) {

    if (!is_numeric($act)) {
      return civicrm_create_error(ts('Invalid parameter: activity_id. Must provide a numeric value.'));
    }

    $sql = "SELECT case_id FROM civicrm_case_activity WHERE activity_id = $act";

    $caseId = CRM_Core_DAO::singleValueQuery($sql);

    if (!$caseId) {
      return civicrm_create_success(array());
    }

    $case = array($caseId => _civicrm_case_read($caseId));

    return civicrm_create_success($case);
  }

  //search by contacts
  if ($contact = $params['contact_id']) {
    if (!is_numeric($contact)) {
      return civicrm_create_error(ts('Invalid parameter: contact_id.  Must provide a numeric value.'));
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
      $cases[$dao->case_id] = _civicrm_case_read($dao->case_id);
    }

    return civicrm_create_success($cases);
  }

  return civicrm_create_error(ts('Missing required parameter. Must provide case_id, client_id, activity_id, or contact_id.'));
}

/**
 * Create new activity for a case
 *
 * @param array(
    //REQUIRED:
 *                         'case_id'                     => int
 *                         'activity_type_id'            => int
 *                         'source_contact_id'           => int
 *                         'status_id'                   => int
 *                         'medium_id'                   => int // see civicrm option values for possibilities
 *
 *               //OPTIONAL
 *                         'subject'                     => str
 *                         'activity_date_time'          => date string // defaults to: date('YmdHis')
 *                         'details                      => str
 *
 * @return activity id
 *
 * NOTE: For other case activity functions (update, delete, etc) use the Activity API
 *
 */
function civicrm_case_activity_create(&$params) {
  _civicrm_initialize();

  //check parameters
  $errors = _civicrm_case_check_params($params, 'activity');

  _civicrm_case_format_params($params, 'activity');

  if ($errors) {
    return $errors;
  }
  require_once 'CRM/Activity/BAO/Activity.php';

  $activity = CRM_Activity_BAO_Activity::create($params);

  $caseParams = array(
    'activity_id' => $activity->id,
    'case_id' => $params['case_id'],
  );

  CRM_Case_BAO_Case::processCaseActivity($caseParams);

  return civicrm_create_success($activity->id);
}

/**
 * Update a specified case.
 *
 * @param  array(
    //REQUIRED:
 *                  'case_id'          => int
 *
 *                //OPTIONAL
 *                  'status_id'        => int
 *                  'start_date'       => str datestamp
 *                  'contact_id'       => int // case client
 *                  'creator_id'       => int // case manager
 *
 * @return Updated case
 *
 * @access public
 *
 */
function civicrm_case_update(&$params) {
  _civicrm_initialize();

  $errors = array();
  //check for various error and required conditions
  $errors = _civicrm_case_check_params($params, 'update');

  if (!empty($errors)) {
    return $errors;
  }

  // return error if modifing creator id
  if (array_key_exists('creator_id', $params)) {
    return civicrm_create_error(ts('You have no provision to update creator id'));
  }

  $mCaseId = array();
  $origContactIds = array();

  // get original contact id and creator id of case
  if ($params['contact_id']) {
    $origContactIds = CRM_Case_BAO_Case::retrieveContactIdsByCaseId($params['case_id']);
    $origContactId = $origContactIds[1];
  }

  if (count($origContactIds) > 1) {
    // check valid orig contact id
    if ($params['orig_contact_id'] && !in_array($params['orig_contact_id'], $origContactIds)) {
      return civicrm_create_error(ts('Invalid case contact id (orig_contact_id)'));
    }
    elseif (!$params['orig_contact_id']) {
      return civicrm_create_error(ts('Case is linked with more than one contact id. Provide the required params orig_contact_id to be replaced'));
    }
    $origContactId = $params['orig_contact_id'];
  }

  // check for same contact id for edit Client
  if ($params['contact_id'] && !in_array($params['contact_id'], $origContactIds)) {
    $mCaseId = CRM_Case_BAO_Case::mergeCases($params['contact_id'], $params['case_id'],
      $origContactId, NULL, TRUE
    );
  }

  if (CRM_Utils_Array::value('0', $mCaseId)) {
    $params['case_id'] = $mCaseId[0];
  }

  $dao = new CRM_Case_BAO_Case();
  $dao->id = $params['case_id'];

  $dao->copyValues($params);
  $dao->save();

  $case = array();

  _civicrm_object_to_array($dao, $case);

  return civicrm_create_success($case);
}

/**
 * Delete a specified case.
 *
 * @param  array(
    //REQUIRED:
 *                  'case_id'           => int
 *
 *                //OPTIONAL
 *                  'move_to_trash'     => bool (defaults to false)
 *
 * @return boolean: true if success, else false
 *
 * @access public
 */
function civicrm_case_delete(&$params) {
  _civicrm_initialize();

  //check parameters
  $errors = _civicrm_case_check_params($params, 'delete');

  if ($errors) {

    return $errors;
  }

  if (CRM_Case_BAO_Case::deleteCase($params['case_id'], $params['move_to_trash'])) {
    return civicrm_create_success(ts('Case Deleted'));
  }
  else {
    return civicrm_create_error(ts('Could not delete case.'));
  }
}

/***********************************/
/*                                 */


/*     INTERNAL FUNCTIONS          */


/*                                 */

/***********************************/

/**
 * Internal function to retrieve a case.
 *
 * @param int $caseId
 *
 * @return array (reference) case object
 *
 */
function _civicrm_case_read($caseId) {

  $dao = new CRM_Case_BAO_Case();
  $dao->id = $caseId;
  if ($dao->find(TRUE)) {
    $case = array();
    _civicrm_object_to_array($dao, $case);

    //handle multi-value case type
    $sep = CRM_Core_DAO::VALUE_SEPARATOR;
    $case['case_type_id'] = trim(str_replace($sep, ',', $case['case_type_id']), ',');

    return $case;
  }
  else {
    return FALSE;
  }
}

/**
 * Internal function to format params for processing
 */
function _civicrm_case_format_params(&$params, $mode) {
  switch ($mode) {
    case 'create':
      // set defaults
      if (!$params['status_id']) {
        $params['status_id'] = 1;
      }
      if (!$params['start_date']) {
        $params['start_date'] = date('YmdHis');
      }

      // figure out case type id, if not supplied
      if (!$params['case_type_id']) {
        $sql = "
SELECT  ov.value
  FROM  civicrm_option_value ov 
  JOIN  civicrm_option_group og ON og.id = ov.option_group_id
 WHERE  ov.label = %1 AND og.name = 'case_type'";

        $values = array(1 => array($params['case_type'], 'String'));
        $params['case_type_id'] = CRM_Core_DAO::singleValueQuery($sql, $values);
      }
      elseif (!$params['case_type']) {
        // figure out case type, if not supplied
        $sql = "
SELECT  ov.name
  FROM  civicrm_option_value ov
  JOIN  civicrm_option_group og ON og.id = ov.option_group_id
 WHERE  ov.value = %1 AND og.name = 'case_type'";

        $values = array(1 => array($params['case_type_id'], 'Integer'));
        $params['case_type'] = CRM_Core_DAO::singleValueQuery($sql, $values);
      }
      break;

    case 'activity':
      //set defaults
      if (!$params['activity_date_time']) {
        $params['activity_date_time'] = date('YmdHis');
      }
      break;
  }
}

/**
 * Internal function to check for valid parameters
 */
function _civicrm_case_check_params(&$params, $mode = NULL) {

  // return error if we do not get any params
  if (is_null($params) || !is_array($params) || empty($params)) {
    return civicrm_create_error(ts('Invalid or missing input parameters. Must provide an associative array.'));
  }

  switch ($mode) {
    case 'create':

      if (!$params['case_type_id'] && !$params['case_type']) {

        return civicrm_create_error(ts('Missing input parameters. Must provide case_type or case_type_id.'));
      }

      $required = array(
        'contact_id' => 'num',
        'status_id' => 'num',
        'medium_id' => 'num',
        'creator_id' => 'num',
        'subject' => 'str',
      );

      if (!$params['case_type']) {

        $required['case_type_id'] = 'num';
      }
      if (!$params['case_type_id']) {
        $required['case_type'] = 'str';
      }
      break;

    case 'activity':

      $required = array(
        'case_id' => 'num',
        'activity_type_id' => 'num',
        'source_contact_id' => 'num',
        'status_id' => 'num',
        'medium_id' => 'num',
      );
      break;

    case 'update':
    case 'delete':
      $required = array('case_id' => 'num');
      break;

    default:
      return NULL;
  }

  foreach ($required as $req => $type) {

    if (!$params[$req]) {

      return civicrm_create_error(ts('Missing required parameter: %1.', array(1 => $req)));
    }

    if ($type == 'num' && !is_numeric($params[$req])) {

      return civicrm_create_error(ts('Invalid parameter: %1. Must provide a numeric value.', array(1 => $req)));
    }

    if ($type == 'str' && !is_string($params[$req])) {

      return civicrm_create_error(ts('Invalid parameter: %1. Must provide a string.', array(1 => $req)));
    }
  }

  $caseTypes = CRM_Case_PseudoConstant::caseType();

  if (CRM_Utils_Array::value('case_type', $params) && !in_array($params['case_type'], $caseTypes)) {
    return civicrm_create_error(ts('Invalid Case Type'));
  }

  if (CRM_Utils_Array::value('case_type_id', $params)) {
    if (!array_key_exists($params['case_type_id'], $caseTypes)) {
      return civicrm_create_error(ts('Invalid Case Type Id'));
    }

    // check case type miss match error
    if (CRM_Utils_Array::value('case_type', $params) &&
      $params['case_type_id'] != array_search($params['case_type'], $caseTypes)
    ) {
      return civicrm_create_error(ts('Case type and case type id mismatch'));
    }

    $sep = CRM_Case_BAO_Case::VALUE_SEPARATOR;
    $params['case_type'] = $caseTypes[$params['case_type_id']];
    $params['case_type_id'] = $sep . $params['case_type_id'] . $sep;
  }

  // check for valid status id
  $caseStatusIds = CRM_Case_PseudoConstant::caseStatus();
  if (CRM_Utils_Array::value('status_id', $params) &&
    !array_key_exists($params['status_id'], $caseStatusIds) &&
    $mode != 'activity'
  ) {
    return civicrm_create_error(ts('Invalid Case Status Id'));
  }

  // check for valid medium id
  $encounterMedium = CRM_Core_OptionGroup::values('encounter_medium');
  if (CRM_Utils_Array::value('medium_id', $params) &&
    !array_key_exists($params['medium_id'], $encounterMedium)
  ) {
    return civicrm_create_error(ts('Invalid Case Medium Id'));
  }

  $contactIds = array('creator' => CRM_Utils_Array::value('creator_id', $params),
    'contact' => CRM_Utils_Array::value('contact_id', $params),
  );
  foreach ($contactIds as $key => $value) {
    if ($value &&
      !CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $value, 'id')
    ) {
      return civicrm_create_error(ts('Invalid %1 Id', array(1 => ucfirst($key))));
    }
  }
}

