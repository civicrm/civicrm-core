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
 * File for the CiviCRM APIv3 activity profile functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ActivityProfile
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: ActivityProfile.php 30486 2011-05-20 16:12:09Z rajan $
 *
 */

/**
 * Include common API util functions
 */
require_once 'api/v3/utils.php';

require_once 'CRM/Core/BAO/UFGroup.php';
require_once 'CRM/Core/BAO/UFField.php';
require_once 'CRM/Core/Permission.php';

/**
 * Retrieve Profile field values.
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to get profile field values
 *
 * @return Profile field values|CRM_Error
 *
 * @todo add example
 * @todo add test cases
 *
 */
function civicrm_api3_profile_get($params) {

  civicrm_api3_verify_mandatory($params, NULL, array('profile_id', 'contact_id'));

  if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $params['profile_id'], 'is_active')) {
    return civicrm_api3_create_error('Invalid value for profile_id');
  }

  $isContactActivityProfile = CRM_Core_BAO_UFField::checkContactActivityProfileType($params['profile_id']);

  if (CRM_Core_BAO_UFField::checkProfileType($params['profile_id']) && !$isContactActivityProfile) {
    return civicrm_api3_create_error('Can not retrieve values for profiles include fields for more than one record type.');
  }

  $profileFields = CRM_Core_BAO_UFGroup::getFields($params['profile_id'],
    FALSE,
    NULL,
    NULL,
    NULL,
    FALSE,
    NULL,
    TRUE,
    NULL,
    CRM_Core_Permission::EDIT
  );

  $values = array();
  if ($isContactActivityProfile) {
    civicrm_api3_verify_mandatory($params, NULL, array('activity_id'));

    require_once 'CRM/Profile/Form.php';
    $errors = CRM_Profile_Form::validateContactActivityProfile($params['activity_id'],
      $params['contact_id'],
      $params['profile_id']
    );
    if (!empty($errors)) {
      return civicrm_api3_create_error(array_pop($errors));
    }

    $contactFields = $activityFields = array();
    foreach ($profileFields as $fieldName => $field) {
      if (CRM_Utils_Array::value('field_type', $field) == 'Activity') {
        $activityFields[$fieldName] = $field;
      }
      else {
        $contactFields[$fieldName] = $field;
      }
    }

    CRM_Core_BAO_UFGroup::setProfileDefaults($params['contact_id'], $contactFields, $values, TRUE);

    if ($params['activity_id']) {
      CRM_Core_BAO_UFGroup::setComponentDefaults($activityFields, $params['activity_id'], 'Activity', $values, TRUE);
    }
  }
  else {
    CRM_Core_BAO_UFGroup::setProfileDefaults($params['contact_id'], $profileFields, $values, TRUE);
  }

  $result = civicrm_api3_create_success();
  $result['values'] = $values;

  return $result;
}

/**
 * Update Profile field values.
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to update profile field values
 *
 * @return Updated Contact/ Activity object|CRM_Error
 *
 * @todo add example
 * @todo add test cases
 *
 */
function civicrm_api3_profile_set($params) {

  civicrm_api3_verify_mandatory($params, NULL, array('profile_id'));

  if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $params['profile_id'], 'is_active')) {
    return civicrm_api3_create_error('Invalid value for profile_id');
  }

  $isContactActivityProfile = CRM_Core_BAO_UFField::checkContactActivityProfileType($params['profile_id']);

  if (CRM_Core_BAO_UFField::checkProfileType($params['profile_id']) && !$isContactActivityProfile) {
    return civicrm_api3_create_error('Can not retrieve values for profiles include fields for more than one record type.');
  }

  $contactParams = $activityParams = $missingParams = array();

  $profileFields = CRM_Core_BAO_UFGroup::getFields($params['profile_id'],
    FALSE,
    NULL,
    NULL,
    NULL,
    FALSE,
    NULL,
    TRUE,
    NULL,
    CRM_Core_Permission::EDIT
  );

  if ($isContactActivityProfile) {
    civicrm_api3_verify_mandatory($params, NULL, array('activity_id'));

    require_once 'CRM/Profile/Form.php';
    $errors = CRM_Profile_Form::validateContactActivityProfile($params['activity_id'],
      $params['contact_id'],
      $params['profile_id']
    );
    if (!empty($errors)) {
      return civicrm_api3_create_error(array_pop($errors));
    }
  }

  foreach ($profileFields as $fieldName => $field) {
    if (CRM_Utils_Array::value('is_required', $field)) {
      if (!CRM_Utils_Array::value($fieldName, $params) || empty($params[$fieldName])) {
        $missingParams[] = $fieldName;
      }
    }

    if (!isset($params[$fieldName])) {
      continue;
    }

    $value = $params[$fieldName];
    if ($params[$fieldName] && isset($params[$fieldName . '_id'])) {
      $value = $params[$fieldName . '_id'];
    }

    if ($isContactActivityProfile && CRM_Utils_Array::value('field_type', $field) == 'Activity') {
      $activityParams[$fieldName] = $value;
    }
    else {
      $contactParams[$fieldName] = $value;
    }
  }

  if (!empty($missingParams)) {
    return civicrm_api3_create_error("Missing required parameters for profile id {$params['profile_id']}: " . implode(', ', $missingParams));
  }

  $contactParams['version'] = 3;
  $contactParams['contact_id'] = CRM_Utils_Array::value('contact_id', $params);
  $contactParams['profile_id'] = $params['profile_id'];
  $contactParams['skip_custom'] = 1;

  $contactProfileParams = civicrm_api3_profile_apply($contactParams);
  if (CRM_Utils_Array::value('is_error', $contactProfileParams)) {
    return $contactProfileParams;
  }

  // Contact profile fields
  $profileParams = $contactProfileParams['values'];

  // If profile having activity fields
  if ($isContactActivityProfile && !empty($activityParams)) {
    $activityParams['id'] = $params['activity_id'];
    $profileParams['api.activity.create'] = $activityParams;
  }

  $groups = $tags = array();
  if (isset($profileParams['group'])) {
    $groups = $profileParams['group'];
    unset($profileParams['group']);
  }

  if (isset($profileParams['tag'])) {
    $tags = $profileParams['tag'];
    unset($profileParams['tag']);
  }

  $result = civicrm_api('contact', 'create', $profileParams);
  if (CRM_Utils_Array::value('is_error', $result)) {
    return $result;
  }

  $ufGroupDetails = array();
  $ufGroupParams = array('id' => $params['profile_id']);
  CRM_Core_BAO_UFGroup::retrieve($ufGroupParams, $ufGroupDetails);

  if (isset($profileFields['group'])) {
    CRM_Contact_BAO_GroupContact::create($groups,
      $params['contact_id'],
      FALSE,
      'Admin'
    );
  }

  if (isset($profileFields['tag'])) {
    require_once 'CRM/Core/BAO/EntityTag.php';
    CRM_Core_BAO_EntityTag::create($tags,
      'civicrm_contact',
      $params['contact_id']
    );
  }

  if (CRM_Utils_Array::value('add_to_group_id', $ufGroupDetails)) {
    $contactIds = array($params['contact_id']);
    CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds,
      $ufGroupDetails['add_to_group_id']
    );
  }

  return $result;
}

/**
 * Provide formatted values for profile fields.
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to profile field values
 *
 * @return formatted profile field values|CRM_Error
 *
 * @todo add example
 * @todo add test cases
 *
 */
function civicrm_api3_profile_apply($params) {

  civicrm_api3_verify_mandatory($params, NULL, array('profile_id'));
  require_once 'CRM/Contact/BAO/Contact.php';

  if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $params['profile_id'], 'is_active')) {
    return civicrm_api3_create_error('Invalid value for profile_id');
  }

  $profileFields = CRM_Core_BAO_UFGroup::getFields($params['profile_id'],
    FALSE,
    NULL,
    NULL,
    NULL,
    FALSE,
    NULL,
    TRUE,
    NULL,
    CRM_Core_Permission::EDIT
  );

  list($data, $contactDetails) = CRM_Contact_BAO_Contact::formatProfileContactParams($params,
    $profileFields,
    CRM_Utils_Array::value('contact_id', $params),
    $params['profile_id'],
    CRM_Utils_Array::value('contact_type', $params),
    CRM_Utils_Array::value('skip_custom', $params, FALSE)
  );

  if (empty($data)) {
    return civicrm_api3_create_error('Enable to format profile parameters.');
  }

  return civicrm_api3_create_success($data);
}

/**
 * Return UFGroup fields
 */
function civicrm_api3_profile_getfields($params) {
  $dao = _civicrm_api3_get_DAO('UFGroup');
  $file = str_replace('_', '/', $dao) . ".php";
  require_once ($file);
  $d = new $dao();
  $fields = $d->fields();
  return civicrm_api3_create_success($fields);
}

