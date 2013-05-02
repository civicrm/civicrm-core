<?php
// $Id: Activity.php 45502 2013-02-08 13:32:55Z kurund $


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
 * File for the CiviCRM APIv2 activity functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Activity
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: Activity.php 45502 2013-02-08 13:32:55Z kurund $
 *
 */

/**
 * Include common API util functions
 */
require_once 'api/v2/utils.php';

require_once 'CRM/Activity/BAO/Activity.php';
require_once 'CRM/Core/DAO/OptionGroup.php';

// require these to call new function names from deprecated ones in here
require_once 'api/v2/ActivityType.php';
require_once 'api/v2/ActivityContact.php';

/**
 * Create a new Activity.
 *
 * Creates a new Activity record and returns the newly created
 * activity object (including the contact_id property). Minimum
 * required data values for the various contact_type are:
 *
 * Properties which have administratively assigned sets of values
 * If an unrecognized value is passed, an error
 * will be returned.
 *
 * Modules may invoke crm_get_contact_values($contactID) to
 * retrieve a list of currently available values for a given
 * property.
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param string $activity_type Which class of contact is being created.
 *            Valid values = 'SMS', 'Meeting', 'Event', 'PhoneCall'.
 * {@schema Activity/Activity.xml}
 *
 * @return CRM_Activity|CRM_Error Newly created Activity object
 */
function &civicrm_activity_create(&$params) {
  _civicrm_initialize();

  $errors = array();

  // check for various error and required conditions
  $errors = _civicrm_activity_check_params($params, TRUE);

  if (!empty($errors)) {
    return $errors;
  }

  // processing for custom data
  $values = array();
  _civicrm_custom_format_params($params, $values, 'Activity');
  if (!empty($values['custom'])) {
    $params['custom'] = $values['custom'];
  }

  // create activity
  $activity = CRM_Activity_BAO_Activity::create($params);

  if (!is_a($activity, 'CRM_Core_Error') && isset($activity->id)) {
    $activityArray = array('is_error' => 0);
  }
  else {
    $activityArray = array('is_error' => 1);
  }

  _civicrm_object_to_array($activity, $activityArray);

  return $activityArray;
}

/**
 *
 * @param <type> $params
 * @param <type> $returnCustom
 *
 * @return <type>
 */
function civicrm_activity_get($params, $returnCustom = FALSE) {
  _civicrm_initialize();

  $activityId = CRM_Utils_Array::value('activity_id', $params);
  if (empty($activityId)) {
    return civicrm_create_error(ts("Required parameter not found"));
  }

  if (!is_numeric($activityId)) {
    return civicrm_create_error(ts("Invalid activity Id"));
  }

  $activity = _civicrm_activity_get($activityId, $returnCustom);

  if ($activity) {
    return civicrm_create_success($activity);
  }
  else {
    return civicrm_create_error(ts('Invalid Data'));
  }
}

/**
 * Wrapper to make this function compatible with the REST API
 *
 * Obsolete now; if no one is using this, it should be removed. -- Wes Morgan
 */
function civicrm_activity_get_contact($params) {
  // TODO: Spit out deprecation warning here
  return civicrm_activities_get_contact($params);
}

/**
 * Retrieve a set of activities, specific to given input params.
 *
 * @param  array  $params (reference ) input parameters.
 * @deprecated from 3.4 - use civicrm_activity_contact_get
 *
 * @return array (reference)  array of activities / error message.
 * @access public
 */
function civicrm_activities_get_contact($params) {
  // TODO: Spit out deprecation warning here
  return civicrm_activity_contact_get($params);
}

/**
 * Update a specified activity.
 *
 * Updates activity with the values passed in the 'params' array. An
 * error is returned if an invalid id or activity Name is passed
 *
 * @param CRM_Activity $activity A valid Activity object
 * @param array       $params  Associative array of property
 *                             name/value pairs to be updated.
 *
 * @return CRM_Activity|CRM_Core_Error  Return the updated ActivtyType Object else
 *                                Error Object (if integrity violation)
 *
 * @access public
 *
 */
function &civicrm_activity_update(&$params) {
  $errors = array();
  //check for various error and required conditions
  $errors = _civicrm_activity_check_params($params);

  if (!empty($errors)) {
    return $errors;
  }

  // processing for custom data
  $values = array();
  _civicrm_custom_format_params($params, $values, 'Activity');
  if (!empty($values['custom'])) {
    $params['custom'] = $values['custom'];
  }

  $activity = CRM_Activity_BAO_Activity::create($params);
  $activityArray = array();
  _civicrm_object_to_array($activity, $activityArray);

  return $activityArray;
}

/**
 * Delete a specified Activity.
 *
 * @param CRM_Activity $activity Activity object to be deleted
 *
 * @return void|CRM_Core_Error  An error if 'activityName or ID' is invalid,
 *                         permissions are insufficient, etc.
 *
 * @access public
 *
 */
function civicrm_activity_delete(&$params) {
  _civicrm_initialize();

  $errors = array();

  //check for various error and required conditions
  $errors = _civicrm_activity_check_params($params);

  if (!empty($errors)) {
    return $errors;
  }

  if (CRM_Activity_BAO_Activity::deleteActivity($params)) {
    return civicrm_create_success();
  }
  else {
    return civicrm_create_error(ts('Could not delete activity'));
  }
}

/**
 * Retrieve a specific Activity by Id.
 *
 * @param int $activityId
 *
 * @return array (reference)  activity object
 * @access public
 */
function _civicrm_activity_get($activityId, $returnCustom = FALSE) {
  $dao = new CRM_Activity_BAO_Activity();
  $dao->id = $activityId;
  if ($dao->find(TRUE)) {
    $activity = array();
    _civicrm_object_to_array($dao, $activity);

    //also return custom data if needed.
    if ($returnCustom && !empty($activity)) {
      $customdata = civicrm_activity_custom_get(array(
        'activity_id' => $activityId,
          'activity_type_id' => $activity['activity_type_id'],
        ));
      $activity = array_merge($activity, $customdata);
    }

    return $activity;
  }
  else {
    return FALSE;
  }
}

/**
 * Function to check for required params
 *
 * @param array   $params  associated array of fields
 * @param boolean $addMode true for add mode
 *
 * @return array $error array with errors
 */
function _civicrm_activity_check_params(&$params, $addMode = FALSE) {
  // return error if we do not get any params
  if (empty($params)) {
    return civicrm_create_error(ts('Input Parameters empty'));
  }

  $contactIds = array('source' => CRM_Utils_Array::value('source_contact_id', $params),
    'assignee' => CRM_Utils_Array::value('assignee_contact_id', $params),
    'target' => CRM_Utils_Array::value('target_contact_id', $params),
  );

  foreach ($contactIds as $key => $value) {
    if (empty($value)) {
      continue;
    }
    $valueIds = array($value);
    if (is_array($value)) {
      $valueIds = array();
      foreach ($value as $id) {
        if (is_numeric($id)) {
          $valueIds[$id] = $id;
        }
      }
    }
    elseif (!is_numeric($value)) {
      return civicrm_create_error(ts('Invalid %1 Contact Id', array(
        1 => ucfirst(
              $key
            ))));
    }

    if (empty($valueIds)) {
      continue;
    }

    $sql = '
SELECT  count(*) 
  FROM  civicrm_contact 
 WHERE  id IN (' . implode(', ', $valueIds) . ' )';
    if (count($valueIds) != CRM_Core_DAO::singleValueQuery($sql)) {
      return civicrm_create_error(ts('Invalid %1 Contact Id', array(1 => ucfirst($key))));
    }
  }

  $activityIds = array('activity' => CRM_Utils_Array::value('id', $params),
    'parent' => CRM_Utils_Array::value('parent_id', $params),
    'original' => CRM_Utils_Array::value('original_id', $params),
  );

  foreach ($activityIds as $id => $value) {
    if ($value &&
      !CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $value, 'id')
    ) {
      return civicrm_create_error(ts('Invalid %1 Id', array(1 => ucfirst($id))));
    }
  }

  if (!$addMode && !isset($params['id'])) {
    return civicrm_create_error(ts('Required parameter "id" not found'));
  }

  // check for source contact id
  if ($addMode && empty($params['source_contact_id'])) {
    return civicrm_create_error(ts('Missing Source Contact'));
  }

  // check for activity subject if add mode
  if ($addMode && !isset($params['subject'])) {
    return civicrm_create_error(ts('Missing Subject'));
  }

  if (!$addMode && $params['id'] && !is_numeric($params['id'])) {
    return civicrm_create_error(ts('Invalid activity "id"'));
  }

  require_once 'CRM/Core/PseudoConstant.php';
  $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, TRUE, 'name');

  // check if activity type_id is passed in
  if ($addMode && !isset($params['activity_name']) && !isset($params['activity_type_id'])) {
    //when name AND id are both absent
    return civicrm_create_error(ts('Missing Activity Type'));
  }
  else {
    $activityName = CRM_Utils_Array::value('activity_name', $params);
    $activityTypeId = CRM_Utils_Array::value('activity_type_id', $params);

    if ($activityName) {
      $activityNameId = array_search(ucfirst($activityName), $activityTypes);

      if (!$activityNameId) {
        return civicrm_create_error(ts('Invalid Activity Name'));
      }
      elseif ($activityTypeId && ($activityTypeId != $activityNameId)) {
        return civicrm_create_error(ts('Mismatch in Activity'));
      }
      $params['activity_type_id'] = $activityNameId;
    }
    elseif ($activityTypeId &&
      !array_key_exists($activityTypeId, $activityTypes)
    ) {
      return civicrm_create_error(ts('Invalid Activity Type ID'));
    }
  }

  // check for activity status is passed in
  if (isset($params['status_id'])) {
    require_once "CRM/Core/PseudoConstant.php";
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();

    if (is_numeric($params['status_id']) && !array_key_exists($params['status_id'], $activityStatus)) {
      return civicrm_create_error(ts('Invalid Activity Status'));
    }
    elseif (!is_numeric($params['status_id'])) {
      $statusId = array_search($params['status_id'], $activityStatus);

      if (!is_numeric($statusId)) {
        return civicrm_create_error(ts('Invalid Activity Status'));
      }
    }
  }

  if (isset($params['priority_id']) && is_numeric($params['priority_id'])) {
    require_once "CRM/Core/PseudoConstant.php";
    $activityPriority = CRM_Core_PseudoConstant::priority();
    if (!array_key_exists($params['priority_id'], $activityPriority)) {
      return civicrm_create_error(ts('Invalid Priority'));
    }
  }

  // check for activity duration minutes
  if (isset($params['duration_minutes']) && !is_numeric($params['duration_minutes'])) {
    return civicrm_create_error(ts('Invalid Activity Duration (in minutes)'));
  }

  if ($addMode &&
    !CRM_Utils_Array::value('activity_date_time', $params)
  ) {
    $params['activity_date_time'] = CRM_Utils_Date::processDate(date('Y-m-d H:i:s'));
  }
  else {
    if (CRM_Utils_Array::value('activity_date_time', $params)) {
      $params['activity_date_time'] = CRM_Utils_Date::processDate($params['activity_date_time']);
    }
  }

  return NULL;
}

/**
 * Convert an email file to an activity
 */
function civicrm_activity_processemail($file, $activityTypeID, $result = array(
  )) {
  // do not parse if result array already passed (towards EmailProcessor..)
  if (empty($result)) {
    // might want to check that email is ok here
    if (!file_exists($file) ||
      !is_readable($file)
    ) {
      return CRM_Core_Error::createAPIError(ts('File %1 does not exist or is not readable',
          array(1 => $file)
        ));
    }
  }

  require_once 'CRM/Utils/Mail/Incoming.php';
  $result = CRM_Utils_Mail_Incoming::parse($file);
  if ($result['is_error']) {
    return $result;
  }

  $params = _civicrm_activity_buildmailparams($result, $activityTypeID);
  return civicrm_activity_create($params);
}

/**
 *
 * @param <type> $result
 * @param <type> $activityTypeID
 *
 * @return <type>
 */
function _civicrm_activity_buildmailparams($result, $activityTypeID) {
  // get ready for collecting data about activity to be created
  $params = array();

  $params['activity_type_id'] = $activityTypeID;
  $params['status_id'] = 2;
  $params['source_contact_id'] = $params['assignee_contact_id'] = $result['from']['id'];
  $params['target_contact_id'] = array();
  $keys = array('to', 'cc', 'bcc');
  foreach ($keys as $key) {
    if (is_array($result[$key])) {
      foreach ($result[$key] as $key => $keyValue) {
        if (!empty($keyValue['id'])) {
          $params['target_contact_id'][] = $keyValue['id'];
        }
      }
    }
  }
  $params['subject'] = $result['subject'];
  $params['activity_date_time'] = $result['date'];
  $params['details'] = $result['body'];

  for ($i = 1; $i <= 5; $i++) {
    if (isset($result["attachFile_$i"])) {
      $params["attachFile_$i"] = $result["attachFile_$i"];
    }
  }

  return $params;
}

/**
 *
 * @param <type> $file
 * @param <type> $activityTypeID
 *
 * @return <type>
 * @deprecated since 3.4 use civicrm_activity_processemail
 */
function civicrm_activity_process_email($file, $activityTypeID) {
  // TODO: Spit out deprecation warning here
  return civicrm_activity_processemail($file, $activityTypeID);
}

/**
 * @deprecated since 3.4 use civicrm_activity_type_get
 *
 * @return <type>
 */
function civicrm_activity_get_types() {
  // TODO: Spit out deprecation warning here
  return civicrm_activity_type_get();
}

/**
 * Function retrieve activity custom data.
 *
 * @param  array  $params key => value array.
 *
 * @return array  $customData activity custom data
 *
 * @access public
 */
function civicrm_activity_custom_get($params) {

  $customData = array();
  if (!CRM_Utils_Array::value('activity_id', $params)) {
    return $customData;
  }

  require_once 'CRM/Core/BAO/CustomGroup.php';
  $groupTree = &CRM_Core_BAO_CustomGroup::getTree('Activity',
    CRM_Core_DAO::$_nullObject,
    $params['activity_id'],
    NULL,
    CRM_Utils_Array::value('activity_type_id', $params)
  );
  //get the group count.
  $groupCount = 0;
  foreach ($groupTree as $key => $value) {
    if ($key === 'info') {
      continue;
    }
    $groupCount++;
  }
  $formattedGroupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree,
    $groupCount,
    CRM_Core_DAO::$_nullObject
  );
  $defaults = array();
  CRM_Core_BAO_CustomGroup::setDefaults($formattedGroupTree, $defaults);
  if (!empty($defaults)) {
    foreach ($defaults as $key => $val) {
      $customData[$key] = $val;
    }
  }

  return $customData;
}

