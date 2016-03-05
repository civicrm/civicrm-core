<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 * This api exposes CiviCRM Activity records.
 *
 * @package CiviCRM_APIv3
 */


/**
 * Creates or updates an Activity.
 *
 * @param array $params
 *   Array per getfields documentation.
 *
 * @throws API_Exception
 * @return array
 *   API result array
 */
function civicrm_api3_activity_create($params) {

  if (empty($params['id'])) {
    // an update does not require any mandatory parameters
    civicrm_api3_verify_one_mandatory($params,
      NULL,
      array(
        'activity_name',
        'activity_type_id',
        'activity_label',
      )
    );
  }

  // check for various error and required conditions
  // note that almost all the processing in there should be managed by the wrapper layer
  // & should be removed - needs testing
  $errors = _civicrm_api3_activity_check_params($params);

  // this should not be required as should throw exception rather than return errors -
  //needs testing
  if (!empty($errors)) {
    return $errors;
  }

  // processing for custom data
  $values = $activityArray = array();
  _civicrm_api3_custom_format_params($params, $values, 'Activity');

  if (!empty($values['custom'])) {
    $params['custom'] = $values['custom'];
  }

  // this should be set as a default rather than hard coded
  // needs testing
  $params['skipRecentView'] = TRUE;

  // If this is a case activity, see if there is an existing activity
  // and set it as an old revision. Also retrieve details we'll need.
  // this handling should all be moved to the BAO layer
  $case_id = '';
  $createRevision = FALSE;
  $oldActivityValues = array();
  // Lookup case id if not supplied
  if (!isset($params['case_id']) && !empty($params['id'])) {
    $params['case_id'] = CRM_Core_DAO::singleValueQuery("SELECT case_id FROM civicrm_case_activity WHERE activity_id = " . (int) $params['id']);
  }
  if (!empty($params['case_id'])) {
    $case_id = $params['case_id'];
    if (!empty($params['id'])) {
      $oldActivityParams = array('id' => $params['id']);
      if (!$oldActivityValues) {
        CRM_Activity_BAO_Activity::retrieve($oldActivityParams, $oldActivityValues);
      }
      if (empty($oldActivityValues)) {
        throw new API_Exception(ts("Unable to locate existing activity."));
      }
      else {
        $activityDAO = new CRM_Activity_DAO_Activity();
        $activityDAO->id = $params['id'];
        $activityDAO->is_current_revision = 0;
        if (!$activityDAO->save()) {
          if (is_object($activityDAO)) {
            $activityDAO->free();
          }
          throw new API_Exception(ts("Unable to revision existing case activity."));
        }
        $createRevision = TRUE;
      }
    }
  }

  $deleteActivityAssignment = FALSE;
  if (isset($params['assignee_contact_id'])) {
    $deleteActivityAssignment = TRUE;
  }

  $deleteActivityTarget = FALSE;
  if (isset($params['target_contact_id'])) {
    $deleteActivityTarget = TRUE;
  }

  // this should all be handled at the BAO layer
  $params['deleteActivityAssignment'] = CRM_Utils_Array::value('deleteActivityAssignment', $params, $deleteActivityAssignment);
  $params['deleteActivityTarget'] = CRM_Utils_Array::value('deleteActivityTarget', $params, $deleteActivityTarget);

  if ($case_id && $createRevision) {
    // This is very similar to the copy-to-case action.
    if (!CRM_Utils_Array::crmIsEmptyArray($oldActivityValues['target_contact'])) {
      $oldActivityValues['targetContactIds'] = implode(',', array_unique($oldActivityValues['target_contact']));
    }
    if (!CRM_Utils_Array::crmIsEmptyArray($oldActivityValues['assignee_contact'])) {
      $oldActivityValues['assigneeContactIds'] = implode(',', array_unique($oldActivityValues['assignee_contact']));
    }
    $oldActivityValues['mode'] = 'copy';
    $oldActivityValues['caseID'] = $case_id;
    $oldActivityValues['activityID'] = $oldActivityValues['id'];
    $oldActivityValues['contactID'] = $oldActivityValues['source_contact_id'];

    $copyToCase = CRM_Activity_Page_AJAX::_convertToCaseActivity($oldActivityValues);
    if (empty($copyToCase['error_msg'])) {
      // now fix some things that are different from copy-to-case
      // then fall through to the create below to update with the passed in params
      $params['id'] = $copyToCase['newId'];
      $params['is_auto'] = 0;
      $params['original_id'] = empty($oldActivityValues['original_id']) ? $oldActivityValues['id'] : $oldActivityValues['original_id'];
    }
    else {
      throw new API_Exception(ts("Unable to create new revision of case activity."));
    }
  }

  // create activity
  $activityBAO = CRM_Activity_BAO_Activity::create($params);

  if (isset($activityBAO->id)) {
    if ($case_id && !$createRevision) {
      // If this is a brand new case activity we need to add this
      $caseActivityParams = array('activity_id' => $activityBAO->id, 'case_id' => $case_id);
      CRM_Case_BAO_Case::processCaseActivity($caseActivityParams);
    }

    _civicrm_api3_object_to_array($activityBAO, $activityArray[$activityBAO->id]);
    return civicrm_api3_create_success($activityArray, $params, 'Activity', 'get', $activityBAO);
  }
}

/**
 * Specify Meta data for create.
 *
 * Note that this data is retrievable via the getfields function and is used for pre-filling defaults and
 * ensuring mandatory requirements are met.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_activity_create_spec(&$params) {

  // Default for source_contact_id = currently logged in user.
  $params['source_contact_id']['api.default'] = 'user_contact_id';

  $params['status_id']['api.aliases'] = array('activity_status');

  $params['assignee_contact_id'] = array(
    'name' => 'assignee_id',
    'title' => 'Activity Assignee',
    'description' => 'Contact(s) assigned to this activity.',
    'type' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  );
  $params['target_contact_id'] = array(
    'name' => 'target_id',
    'title' => 'Activity Target',
    'description' => 'Contact(s) participating in this activity.',
    'type' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  );

  $params['source_contact_id'] = array(
    'name' => 'source_contact_id',
    'title' => 'Activity Source Contact',
    'description' => 'Person who created this activity. Defaults to current user.',
    'type' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'api.default' => 'user_contact_id',
    'FKApiName' => 'Contact',
  );

  $params['case_id'] = array(
    'name' => 'case_id',
    'title' => 'Case ID',
    'description' => 'For creating an activity as part of a case.',
    'type' => 1,
    'FKClassName' => 'CRM_Case_DAO_Case',
    'FKApiName' => 'Case',
  );

}

/**
 * Gets a CiviCRM activity according to parameters.
 *
 * @param array $params
 *   Array per getfields documentation.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_activity_get($params) {
  if (!empty($params['contact_id'])) {
    $activities = CRM_Activity_BAO_Activity::getContactActivity($params['contact_id']);
    // BAO function doesn't actually return a contact ID - hack api for now & add to test so when api re-write
    // happens it won't get missed.
    foreach ($activities as $key => $activityArray) {
      $activities[$key]['id'] = $key;
    }
  }
  else {
    $sql = CRM_Utils_SQL_Select::fragment();
    $options = civicrm_api3('ActivityContact', 'getoptions', array('field' => 'record_type_id'));
    $options = $options['values'];
    $activityContactOptions = array(
      'target_contact_id' => array_search('Activity Targets', $options),
      'source_contact_id' => array_search('Activity Source', $options),
      'assignee_contact_id' => array_search('Activity Assignees', $options),
    );
    foreach ($activityContactOptions as $activityContactName => $activityContactValue) {
      if (!empty($params[$activityContactName])) {
        // If the intent is to have multiple joins -- one for each relation -- then you would
        // need different table aliases. Consider replacing 'ac' and passing in a '!alias' param,
        // with a different value for each relation.
        $sql->join(
          'activity_' . $activityContactName,
          'LEFT JOIN civicrm_activity_contact ac ON a.id = ac.activity_id AND ac.record_type_id = #typeId',
          array('typeId' => $activityContactValue)
        );
        $sql->where('ac.contact_id IN (#cid)', array(
          'cid' => $params[$activityContactName],
        ));
      }
    }
    $activities = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE, 'Activity', $sql);
  }
  $options = _civicrm_api3_get_options_from_params($params, FALSE, 'Activity', 'get');
  if ($options['is_count']) {
    return civicrm_api3_create_success($activities, $params, 'Activity', 'get');
  }

  $activities = _civicrm_api3_activity_get_formatResult($params, $activities);
  //legacy custom data get - so previous formatted response is still returned too
  return civicrm_api3_create_success($activities, $params, 'Activity', 'get');
}

/**
 * Given a list of activities, append any extra data requested about the activities.
 *
 * @note Called by civicrm-core and CiviHR
 *
 * @param array $params
 *   API request parameters.
 * @param array $activities
 *
 * @return array
 *   new activities list
 */
function _civicrm_api3_activity_get_formatResult($params, $activities) {
  $returns = CRM_Utils_Array::value('return', $params, array());
  if (!is_array($returns)) {
    $returns = str_replace(' ', '', $returns);
    $returns = explode(',', $returns);
  }
  $returns = array_fill_keys($returns, 1);

  foreach ($params as $n => $v) {
    if (substr($n, 0, 7) == 'return.') {
      $returnkey = substr($n, 7);
      $returns[$returnkey] = $v;
    }
  }

  $returns['source_contact_id'] = 1;
  foreach ($returns as $n => $v) {
    switch ($n) {
      case 'assignee_contact_id':
        foreach ($activities as $key => $activityArray) {
          $activities[$key]['assignee_contact_id'] = CRM_Activity_BAO_ActivityAssignment::retrieveAssigneeIdsByActivityId($activityArray['id']);
        }
        break;

      case 'target_contact_id':
        foreach ($activities as $key => $activityArray) {
          $activities[$key]['target_contact_id'] = CRM_Activity_BAO_ActivityTarget::retrieveTargetIdsByActivityId($activityArray['id']);
        }
        break;

      case 'source_contact_id':
        foreach ($activities as $key => $activityArray) {
          $activities[$key]['source_contact_id'] = CRM_Activity_BAO_Activity::getSourceContactID($activityArray['id']);
        }
        break;

      default:
        if (substr($n, 0, 6) == 'custom') {
          $returnProperties[$n] = $v;
        }
    }
  }
  if (!empty($activities) && (!empty($returnProperties) || !empty($params['contact_id']))) {
    foreach ($activities as $activityId => $values) {
      //@todo - should possibly load activity type id if not loaded (update with id)
      _civicrm_api3_custom_data_get($activities[$activityId], CRM_Utils_Array::value('check_permissions', $params), 'Activity', $activityId, NULL, CRM_Utils_Array::value('activity_type_id', $values));
    }
  }
  return $activities;
}


/**
 * Delete a specified Activity.
 *
 * @param array $params
 *   Array holding 'id' of activity to be deleted.
 *
 * @throws API_Exception
 *
 * @return array
 *   API result array
 */
function civicrm_api3_activity_delete($params) {

  if (CRM_Activity_BAO_Activity::deleteActivity($params)) {
    return civicrm_api3_create_success(1, $params, 'Activity', 'delete');
  }
  else {
    throw new API_Exception('Could not delete Activity');
  }
}

/**
 * Check for required params.
 *
 * @param array $params
 *   Associated array of fields.
 *
 * @throws API_Exception
 * @throws Exception
 * @return array
 *   array with errors
 */
function _civicrm_api3_activity_check_params(&$params) {

  $contactIDFields = array_intersect_key($params,
    array(
      'source_contact_id' => 1,
      'assignee_contact_id' => 1,
      'target_contact_id' => 1,
    )
  );

  // this should be handled by wrapper layer & probably the api would already manage it
  //correctly by doing post validation - ie. a failure should result in a roll-back = an error
  // needs testing
  if (!empty($contactIDFields)) {
    $contactIds = array();
    foreach ($contactIDFields as $fieldname => $contactfield) {
      if (empty($contactfield)) {
        continue;
      }
      if (is_array($contactfield)) {
        foreach ($contactfield as $contactkey => $contactvalue) {
          $contactIds[$contactvalue] = $contactvalue;
        }
      }
      else {
        $contactIds[$contactfield] = $contactfield;
      }
    }

    $sql = '
SELECT  count(*)
  FROM  civicrm_contact
 WHERE  id IN (' . implode(', ', $contactIds) . ' )';
    if (count($contactIds) != CRM_Core_DAO::singleValueQuery($sql)) {
      throw new API_Exception('Invalid Contact Id');
    }
  }

  $activityIds = array(
    'activity' => CRM_Utils_Array::value('id', $params),
    'parent' => CRM_Utils_Array::value('parent_id', $params),
    'original' => CRM_Utils_Array::value('original_id', $params),
  );

  foreach ($activityIds as $id => $value) {
    if ($value &&
      !CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $value, 'id')
    ) {
      throw new API_Exception('Invalid ' . ucfirst($id) . ' Id');
    }
  }
  // this should be handled by wrapper layer & probably the api would already manage it
  //correctly by doing pseudoconstant validation
  // needs testing
  $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'validate');
  $activityName = CRM_Utils_Array::value('activity_name', $params);
  $activityName = ucfirst($activityName);
  $activityLabel = CRM_Utils_Array::value('activity_label', $params);
  if ($activityLabel) {
    $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'create');
  }

  $activityTypeId = CRM_Utils_Array::value('activity_type_id', $params);

  if ($activityName || $activityLabel) {
    $activityTypeIdInList = array_search(($activityName ? $activityName : $activityLabel), $activityTypes);

    if (!$activityTypeIdInList) {
      $errorString = $activityName ? "Invalid Activity Name : $activityName" : "Invalid Activity Type Label";
      throw new Exception($errorString);
    }
    elseif ($activityTypeId && ($activityTypeId != $activityTypeIdInList)) {
      throw new API_Exception('Mismatch in Activity');
    }
    $params['activity_type_id'] = $activityTypeIdInList;
  }
  elseif ($activityTypeId &&
    !array_key_exists($activityTypeId, $activityTypes)
  ) {
    throw new API_Exception('Invalid Activity Type ID');
  }

  // check for activity duration minutes
  // this should be validated @ the wrapper layer not here
  // needs testing
  if (isset($params['duration_minutes']) && !is_numeric($params['duration_minutes'])) {
    throw new API_Exception('Invalid Activity Duration (in minutes)');
  }

  //if adding a new activity & date_time not set make it now
  // this should be managed by the wrapper layer & setting ['api.default'] in speces
  // needs testing
  if (empty($params['id']) && empty($params['activity_date_time'])) {
    $params['activity_date_time'] = CRM_Utils_Date::processDate(date('Y-m-d H:i:s'));
  }

  return NULL;
}

/**
 * Get parameters for activity list.
 *
 * @see _civicrm_api3_generic_getlist_params
 *
 * @param array $request
 *   API request.
 */
function _civicrm_api3_activity_getlist_params(&$request) {
  $fieldsToReturn = array(
    'activity_date_time',
    'activity_type_id',
    'subject',
    'source_contact_id',
  );
  $request['params']['return'] = array_unique(array_merge($fieldsToReturn, $request['extra']));
  $request['params']['options']['sort'] = 'activity_date_time DESC';
  $request['params'] += array(
    'is_current_revision' => 1,
    'is_deleted' => 0,
  );
}

/**
 * Get output for activity list.
 *
 * @see _civicrm_api3_generic_getlist_output
 *
 * @param array $result
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_activity_getlist_output($result, $request) {
  $output = array();
  if (!empty($result['values'])) {
    foreach ($result['values'] as $row) {
      $data = array(
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']] ? $row[$request['label_field']] : ts('(no subject)'),
        'description' => array(
          CRM_Core_Pseudoconstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $row['activity_type_id']),
        ),
      );
      if (!empty($row['activity_date_time'])) {
        $data['description'][0] .= ': ' . CRM_Utils_Date::customFormat($row['activity_date_time']);
      }
      if (!empty($row['source_contact_id'])) {
        $data['description'][] = ts('By %1', array(
          1 => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $row['source_contact_id'], 'display_name'),
        ));
      }
      // Add repeating info
      $repeat = CRM_Core_BAO_RecurringEntity::getPositionAndCount($row['id'], 'civicrm_activity');
      $data['extra']['is_recur'] = FALSE;
      if ($repeat) {
        $data['suffix'] = ts('(%1 of %2)', array(1 => $repeat[0], 2 => $repeat[1]));
        $data['extra']['is_recur'] = TRUE;
      }
      $output[] = $data;
    }
  }
  return $output;
}
