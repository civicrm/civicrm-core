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
 * multiple instances - if a  single profile is passed in we will not return a normal api result array
 * in order to avoid breaking code. (This could still be confusing :-( but we have to keep the tested behaviour working
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
    $profileID = _civicrm_api3_profile_getProfileID($profileID);
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
 * Submit a set of fields against a profile.
 * Note choice of submit versus create is discussed CRM-13234 & related to the fact
 * 'profile' is being treated as a data-entry entity
 * @param array $params
 * @return array API result array
 */
function civicrm_api3_profile_submit($params) {

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
      throw new API_Exception(array_pop($errors));
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
 * metadata for submit action
 * @param array $params
 * @param array $apirequest
 */
function _civicrm_api3_profile_submit_spec(&$params, $apirequest) {
  if(isset($apirequest['params']['profile_id'])) {
    // we will return what is required for this profile
    // note the problem with simply over-riding getfields & then calling generic if needbe is we don't have the
    // api request array to pass to it.
    //@todo - it may make more sense just to pass the apiRequest to getfields
    //@todo get_options should take an array - @ the moment it is only takes 'all' - which is supported
    // by other getfields fn
    // we don't resolve state, country & county for performance reasons
    $resolveOptions = CRM_Utils_Array::value('get_options',$apirequest['params']) == 'all' ? True : False;
    $profileID = _civicrm_api3_profile_getProfileID($apirequest['params']['profile_id']);
    $params = _civicrm_api3_buildprofile_submitfields($profileID, $resolveOptions);
  }
  $params['profile_id']['api.required'] = TRUE;
}

/**
 * @deprecated - calling this function directly is deprecated as 'set' is not a clear action
 * use submit
 * Update Profile field values.
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to update profile field values
 *
 * @return Updated Contact/ Activity object|CRM_Error
 *
 *
 */
function civicrm_api3_profile_set($params) {
  return civicrm_api3('profile', 'submit', $params);
}

/**
 * @deprecated - appears to be an internal function - should not be accessible via api
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
    'return' => 'api.email.get, api.address.get, api.address.getoptions, state_province, email, first_name, last_name, middle_name, ' . implode($addressFields, ','),
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

/**
 * Here we will build  up getfields type data for all the fields in the profile. Because the integration with the
 * form layer in core is so hard-coded we are not going to attempt to re-use it
 * However, as this function is unit-tested & hence 'locked in' we can aspire to extract sharable
 * code out of the form-layer over time.
 *
 * The function deciphers which fields belongs to which entites & retrieves metadata about the entities
 * Unfortunately we have inconsistencies such as 'contribution' uses contribution_status_id
 * & participant has 'participant_status' so we have to standardise from the outside in here -
 * find the oddities, 'mask them' at this layer, add tests & work to standardise over time so we can remove this handling
 *
 * @param integer $profileID
 * @param integer $optionsBehaviour 0 = don't resolve, 1 = resolve non-aggressively, 2 = resolve aggressively - ie include country & state
 */

function _civicrm_api3_buildprofile_submitfields($profileID, $optionsBehaviour = 1) {
  static $profileFields = array();
  if(isset($profileFields[$profileID])) {
    return $profileFields[$profileID];
  }
  $fields = civicrm_api3('uf_field', 'get', array('uf_group_id' => $profileID));
  $entities = array();

  foreach ($fields['values'] as $id => $field) {
    if(!$field['is_active']) {
      continue;
    }
    list($entity, $fieldName) = _civicrm_api3_map_profile_fields_to_entity($field);
    $profileFields[$profileID][$fieldName] = array(
      'api.required' => $field['is_required'],
      'title' => $field['label'],
      'help_pre' => CRM_Utils_Array::value('help_pre', $field),
      'help_post' => CRM_Utils_Array::value('help_post', $field),
    );

    $realFieldName = $field['field_name'];
    //see function notes
    // as we build up a list of these we should be able to determine a generic approach
    //
    $hardCodedEntityFields = array(
      'state_province' => 'state_province_id',
      'country' => 'country_id',
      'participant_status' => 'status_id',
      'gender' => 'gender_id',
      'financial_type' => 'financial_type_id',
      'soft_credit' => 'soft_credit_to',
      'group' => 'group_id',
      'tag' => 'tag_id',
    );

    if(array_key_exists($realFieldName, $hardCodedEntityFields)) {
      $realFieldName = $hardCodedEntityFields[$realFieldName];
    }

    $entities[$entity][$fieldName] = $realFieldName;
  }

  foreach ($entities as $entity => $entityFields) {
    $result = civicrm_api3($entity, 'getfields', array('action' => 'create'));
    $entityGetFieldsResult = _civicrm_api3_profile_appendaliases($result['values'], $entity);
    foreach ($entityFields as $entityfield => $realName) {
      $profileFields[$profileID][$entityfield] = $entityGetFieldsResult[$realName];
      if($optionsBehaviour && !empty($entityGetFieldsResult[$realName]['pseudoconstant'])) {
        if($optionsBehaviour > 1  || !in_array($realName, array('state_province_id', 'county_id', 'country_id'))) {
          $options = civicrm_api3($entity, 'getoptions', array('field' => $realName));
          $profileFields[$profileID][$entityfield]['options'] = $options['values'];
        }
      }
      /**
       * putting this on hold -this would cause the api to set the default - but could have unexpected behaviour
      if(isset($result['values'][$realName]['default_value'])) {
        //this would be the case for a custom field with a configured default
        $profileFields[$profileID][$entityfield]['api.default'] = $result['values'][$realName]['default_value'];
      }
      */
    }
  }
  return $profileFields[$profileID];
}

/**
 * Here we map the profile fields as stored in the uf_field table to their 'real entity'
 * we also return the profile fieldname
 *
 */
function _civicrm_api3_map_profile_fields_to_entity(&$field) {
  $entity = $field['field_type'];
  $contactTypes = civicrm_api3('contact', 'getoptions', array('field' => 'contact_type'));
  if(in_array($entity, $contactTypes['values'])) {
    $entity = 'Contact';
  }
  $fieldName = $field['field_name'];
  if(!empty($field['location_type_id'])) {
    if($fieldName == 'email') {
      $entity = 'Email';
    }
    else{
      $entity = 'Address';
    }
    $fieldName .= '-' . $field['location_type_id'];
  }
  if(!empty($field['phone_type_id'])) {
    $fieldName .= '-' . $field['location_type_id'];
    $entity = 'Phone';
  }
  // @todo - sort this out!
  //here we do a hard-code list of known fields that don't map to where they are mapped to
  // not a great solution but probably if we looked in the BAO we'd find a scary switch statement
  // in a perfect world the uf_field table would hold the correct entity for each item
  // & only the relationships between entities would need to be coded
  $hardCodedEntityMappings = array(
    'street_address' => 'Address',
    'street_number' => 'Address',
    'supplemental_address_1' => 'Address',
    'supplemental_address_2' => 'Address',
    'supplemental_address_3' => 'Address',
    'postal_code' => 'Address',
    'city' => 'Address',
    'email' => 'Email',
    'state_province' => 'Address',
    'country' => 'Address',
    'county' => 'Address',
    //note that in discussions about how to restructure the api we discussed making these membership
    // fields into 'membership_payment' fields - which would entail declaring them in getfields
    // & renaming them in existing profiles
    'financial_type' => 'Contribution',
    'total_amount' => 'Contribution',
    'receive_date' => 'Contribution',
    'payment_instrument' => 'Contribution',
    'check_number' => 'Contribution',
    'contribution_status_id' => 'Contribution',
    'soft_credit' => 'Contribution',
    'group' => 'GroupContact',
    'tag' => 'EntityTag',
   );
  if(array_key_exists($fieldName, $hardCodedEntityMappings)) {
    $entity = $hardCodedEntityMappings[$fieldName];
  }
  return array($entity, $fieldName);
}

/**
 * @todo this should be handled by the api wrapper using getfields info - need to check
 * how we add a a pseudoconstant to this pseudoapi to make that work
 */
function _civicrm_api3_profile_getProfileID($profileID) {
  if(!empty($profileID) && !strtolower($profileID) == 'billing' && !is_numeric($profileID)) {
    $profileID = civicrm_api3('uf_group', 'getvalue', array('return' => 'id', 'name' => $profileID));
  }
  return $profileID;
}

/**
 * helper function to add all aliases as keys to getfields response so we can look for keys within it
 * since the relationship between profile fields & api / metadata based fields is a bit inconsistent
 * @param array $values
 *
 * e.g getfields response incl 'membership_type_id' - with api.aliases = 'membership_type'
 * returned array will include both as keys (with the same values)
 */
function _civicrm_api3_profile_appendaliases($values, $entity) {
  foreach ($values as $field => $spec) {
    if(!empty($spec['api.aliases'])) {
      foreach ($spec['api.aliases'] as $alias) {
        $values[$alias] = $spec;
      }
    }
    if(!empty($spec['uniqueName'])) {
      $values[$spec['uniqueName']] = $spec;
    }
  }
  //special case on membership & contribution - can't see how to handle in a generic way
  if(in_array($entity, array('Membership', 'Contribution'))) {
    $values['send_receipt'] = array('title' => 'Send Receipt', 'type' => 16);
  }
  return $values;
}