<?php

/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.4                                                |
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

/**
 * Retrieve Profile field values.
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to get profile field values
 *
 * @return Profile field values|CRM_Error
 *
 * NOTE this api is not standard & since it is tested we need to honour that
 * but the correct behaviour is for it to return an id indexed array as this supports
 * multiple instances
 *
 * Note that if contact_id is empty an array of defaults is returned
 *
 */
function civicrm_api3_profile_get($params) {
  $nonStandardLegacyBehaviour = is_numeric($params['profile_id']) ?  TRUE : FALSE;
  if(!empty($params['check_permissions']) && !empty($params['contact_id']) && !1 === civicrm_api3('contact', 'getcount', array('contact_id' => $params['contact_id'], 'check_permissions' => 1))) {
    throw new API_Exception('permission denied');
  }
  $profiles = (array) $params['profile_id'];
  $values = array();
  foreach ($profiles as $profileID) {
    $values[$profileID] = array();
    if (strtolower($profileID) == 'billing') {
      $values[$profileID] = _civicrm_api3_profile_getbillingpseudoprofile($params);
      continue;
    }
    if(!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'is_active')) {
      throw new API_Exception('Invalid value for profile_id : ' . $profileID);
    }

    $isContactActivityProfile = CRM_Core_BAO_UFField::checkContactActivityProfileType($profileID);

    $profileFields = CRM_Core_BAO_UFGroup::getFields($profileID,
      FALSE,
      NULL,
      NULL,
      NULL,
      FALSE,
      NULL,
      empty($params['check_permissions']) ? FALSE : TRUE,
      NULL,
      CRM_Core_Permission::EDIT
    );


  if ($isContactActivityProfile) {
    civicrm_api3_verify_mandatory($params, NULL, array('activity_id'));

    $errors = CRM_Profile_Form::validateContactActivityProfile($params['activity_id'],
      $params['contact_id'],
      $params['profile_id']
    );
    if (!empty($errors)) {
      throw new API_Exception(array_pop($errors));
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

    CRM_Core_BAO_UFGroup::setProfileDefaults($params['contact_id'], $contactFields, $values[$profileID], TRUE);

    if ($params['activity_id']) {
      CRM_Core_BAO_UFGroup::setComponentDefaults($activityFields, $params['activity_id'], 'Activity', $values[$profileID], TRUE);
    }
  }
  elseif(!empty($params['contact_id'])) {
    CRM_Core_BAO_UFGroup::setProfileDefaults($params['contact_id'], $profileFields, $values[$profileID], TRUE);
  }
  else{
    $values[$profileID] = array_fill_keys(array_keys($profileFields), '');
  }
  }
  if($nonStandardLegacyBehaviour) {
    $result = civicrm_api3_create_success();
    $result['values'] = $values[$profileID];
    return $result;
  }
  else {
    return civicrm_api3_create_success($values, $params, 'Profile', 'Get');
  }
}

function _civicrm_api3_profile_get_spec(&$params) {
  $params['profile_id']['api.required'] = TRUE;
  $params['contact_id']['description'] = 'If no contact is specified an array of defaults will be returned';
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
    throw new API_Exception('Invalid value for profile_id');
  }

  $isContactActivityProfile = CRM_Core_BAO_UFField::checkContactActivityProfileType($params['profile_id']);

  if (CRM_Core_BAO_UFField::checkProfileType($params['profile_id']) && !$isContactActivityProfile) {
    throw new API_Exception('Can not retrieve values for profiles include fields for more than one record type.');
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
    throw new API_Exception("Missing required parameters for profile id {$params['profile_id']}: " . implode(', ', $missingParams));
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

  return civicrm_api3('contact', 'create', $profileParams);

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
  $d = new $dao();
  $fields = $d->fields();
  return civicrm_api3_create_success($fields);
}

/**
 * This is a function to help us 'pretend' billing is a profile & treat it like it is one.
 * It gets standard credit card address fields etc
 * Note this is 'better' that the inbuilt version as it will pull in fallback values
 *  billing location -> is_billing -> primary
 *
 *  Note that that since the existing code for deriving a blank profile is not easily accessible our
 *  interim solution is just to return an empty array
 */
function _civicrm_api3_profile_getbillingpseudoprofile(&$params) {
  $addressFields = array('street_address', 'city', 'state_province_id', 'country_id', 'postal_code');
  $locations = civicrm_api3('address', 'getoptions', array('field' => 'location_type_id'));
  $locationTypeID = array_search('Billing', $locations['values']);

  if(empty($params['contact_id'])) {
    $blanks =  array(
      'billing_first_name' => '',
      'billing_middle_name' => '',
      'billing_last_name' => '',
    );
    foreach ($addressFields as $field) {
      $blanks['billing_' . $field . '_' . $locationTypeID] = '';
    }
    return $blanks;
  }
  $result = civicrm_api3('contact', 'getsingle', array(
    'id' => $params['contact_id'],
    'api.address.get.1' => array('location_type_id' => 'Billing',  'return' => $addressFields),
    // getting the is_billing required or not is an extra db call but probably cheap enough as this isn't an import api
    'api.address.get.2' => array('is_billing' => True, 'return' => $addressFields),
    'api.email.get.1' => array('location_type_id' => 'Billing',),
    'api.email.get.2' => array('is_billing' => True,),
    'return' => 'api.email.get, api.address.get, api.address.getoptions, email, first_name, last_name, middle_name,' . implode($addressFields, ','),
   )
  );

  $values = array(
    'billing_first_name' => $result['first_name'],
    'billing_middle_name' => $result['middle_name'],
    'billing_last_name' => $result['last_name'],
  );

  if(!empty($result['api.address.get.1']['count'])) {
    foreach ($addressFields as $fieldname) {
      $values['billing_' . $fieldname . '-' . $locationTypeID] = isset($result['api.address.get.1']['values'][0][$fieldname])  ? $result['api.address.get.1']['values'][0][$fieldname] : '';
    }
  }
  elseif(!empty($result['api.address.get.2']['count'])) {
    foreach ($addressFields as $fieldname) {
      $values['billing_' . $fieldname . '-' . $locationTypeID] = isset($result['api.address.get.2']['values'][0][$fieldname])  ? $result['api.address.get.2']['values'][0][$fieldname] : '';
    }
  }
  else{
    foreach ($addressFields as $fieldname) {
      $values['billing_' . $fieldname . '-' . $locationTypeID] = isset($result[$fieldname]) ? $result[$fieldname] : '';
    }
  }

  if(!empty($result['api.email.get.1']['count'])) {
    $values['billing-email'. '-' . $locationTypeID] = $result['api.email.get.1']['values'][0]['email'];
  }
  elseif(!empty($result['api.email.get.2']['count'])) {
    $values['billing-email'. '-' . $locationTypeID] = $result['api.email.get.2']['values'][0]['email'];
  }
  else{
    $values['billing-email'. '-' . $locationTypeID] = $result['email'];
  }
  // return both variants of email to reflect inconsistencies in form layer
  $values['email'. '-' . $locationTypeID] = $values['billing-email'. '-' . $locationTypeID];
  return $values;
}
