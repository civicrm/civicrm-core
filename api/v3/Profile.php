<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * This api exposes CiviCRM profiles.
 *
 * Profiles are collections of fields used as forms, listings, search columns, etc.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Retrieve Profile field values.
 *
 * NOTE this api is not standard & since it is tested we need to honour that
 * but the correct behaviour is for it to return an id indexed array as this supports
 * multiple instances - if a  single profile is passed in we will not return a normal api result array
 * in order to avoid breaking code. (This could still be confusing :-( but we have to keep the tested behaviour working
 *
 * Note that if contact_id is empty an array of defaults is returned
 *
 * @param array $params
 *   Associative array of property name/value.
 *   pairs to get profile field values
 *
 * @return array
 * @throws \CRM_Core_Exception
 * @throws CRM_Core_Exception
 */
function civicrm_api3_profile_get($params) {
  $nonStandardLegacyBehaviour = is_numeric($params['profile_id']);
  if (!empty($params['check_permissions']) && !empty($params['contact_id']) && !1 === civicrm_api3('contact', 'getcount', ['contact_id' => $params['contact_id'], 'check_permissions' => 1])) {
    throw new CRM_Core_Exception('permission denied');
  }
  $profiles = (array) $params['profile_id'];
  $values = [];
  $ufGroupBAO = new CRM_Core_BAO_UFGroup();
  foreach ($profiles as $profileID) {
    $profileID = _civicrm_api3_profile_getProfileID($profileID);
    $values[$profileID] = [];
    if (strtolower($profileID) == 'billing') {
      $values[$profileID] = _civicrm_api3_profile_getbillingpseudoprofile($params);
      continue;
    }
    if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'is_active')) {
      throw new CRM_Core_Exception('Invalid value for profile_id : ' . $profileID);
    }

    $isContactActivityProfile = CRM_Core_BAO_UFField::checkContactActivityProfileType($profileID);

    $profileFields = CRM_Core_BAO_UFGroup::getFields($profileID,
      FALSE,
      NULL,
      NULL,
      NULL,
      FALSE,
      NULL,
      empty($params['check_permissions']),
      NULL,
      CRM_Core_Permission::EDIT
    );

    if ($isContactActivityProfile) {
      civicrm_api3_verify_mandatory($params, NULL, ['activity_id']);

      $errors = CRM_Profile_Form::validateContactActivityProfile($params['activity_id'],
      $params['contact_id'],
      $params['profile_id']
      );
      if (!empty($errors)) {
        throw new CRM_Core_Exception(array_pop($errors));
      }

      $contactFields = $activityFields = [];
      foreach ($profileFields as $fieldName => $field) {
        if (($field['field_type'] ?? NULL) === 'Activity') {
          $activityFields[$fieldName] = $field;
        }
        else {
          $contactFields[$fieldName] = $field;
          // we should return 'Primary' with & without capitalisation. it is more consistent with api to not
          // capitalise, but for form support we need it for now. Hopefully we can move away from it
          $contactFields[strtolower($fieldName)] = $field;
        }
      }

      $ufGroupBAO->setProfileDefaults($params['contact_id'], $contactFields, $values[$profileID], TRUE);

      if ($params['activity_id']) {
        $ufGroupBAO->setComponentDefaults($activityFields, $params['activity_id'], 'Activity', $values[$profileID], TRUE);
      }
    }
    elseif (!empty($params['contact_id'])) {
      $ufGroupBAO->setProfileDefaults($params['contact_id'], $profileFields, $values[$profileID], TRUE);
      foreach ($values[$profileID] as $fieldName => $field) {
        // we should return 'Primary' with & without capitalisation. it is more consistent with api to not
        // capitalise, but for form support we need it for now. Hopefully we can move away from it
        $values[$profileID][strtolower($fieldName)] = $field;
      }
    }
    else {
      $values[$profileID] = array_fill_keys(array_keys($profileFields), '');
    }
  }
  if ($nonStandardLegacyBehaviour) {
    $result = civicrm_api3_create_success();
    $result['values'] = $values[$profileID];
    return $result;
  }
  else {
    return civicrm_api3_create_success($values, $params, 'Profile', 'Get');
  }
}

/**
 * Adjust profile get function metadata.
 *
 * @param array $params
 */
function _civicrm_api3_profile_get_spec(&$params) {
  $params['profile_id']['api.required'] = TRUE;
  $params['profile_id']['title'] = 'Profile ID';
  $params['contact_id']['description'] = 'If no contact is specified an array of defaults will be returned';
  $params['contact_id']['title'] = 'Contact ID';
}

/**
 * Submit a set of fields against a profile.
 *
 * Note choice of submit versus create is discussed CRM-13234 & related to the fact
 * 'profile' is being treated as a data-entry entity
 *
 * @param array $params
 *
 * @throws CRM_Core_Exception
 * @return array
 *   API result array
 */
function civicrm_api3_profile_submit($params) {
  $profileID = _civicrm_api3_profile_getProfileID($params['profile_id']);
  if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'is_active')) {
    //@todo declare pseudoconstant & let api do this
    throw new CRM_Core_Exception('Invalid value for profile_id');
  }

  $isContactActivityProfile = CRM_Core_BAO_UFField::checkContactActivityProfileType($profileID);

  if (!empty($params['id']) && CRM_Core_BAO_UFField::checkProfileType($profileID) && !$isContactActivityProfile) {
    throw new CRM_Core_Exception('Update profiles including more than one entity not currently supported');
  }

  $contactParams = $activityParams = $missingParams = [];

  $profileFields = civicrm_api3('Profile', 'getfields', ['action' => 'submit', 'profile_id' => $profileID]);
  $profileFields = $profileFields['values'];
  if ($isContactActivityProfile) {
    civicrm_api3_verify_mandatory($params, NULL, ['activity_id']);

    $errors = CRM_Profile_Form::validateContactActivityProfile($params['activity_id'],
      $params['contact_id'],
      $profileID
    );
    if (!empty($errors)) {
      throw new CRM_Core_Exception(array_pop($errors));
    }
  }

  // Add custom greeting fields
  $greetingFields = ['email_greeting', 'postal_greeting', 'addressee'];
  foreach ($greetingFields as $greetingField) {
    if (isset($profileFields[$greetingField]) && !isset($profileFields["{$greetingField}_custom"])) {
      $profileFields["{$greetingField}_custom"] = ['name' => "{$greetingField}_custom"];
    }
  }

  foreach ($profileFields as $fieldName => $field) {
    if (!isset($params[$fieldName])) {
      continue;
    }

    $value = $params[$fieldName];
    if ($params[$fieldName] && isset($params[$fieldName . '_id'])) {
      $value = $params[$fieldName . '_id'];
    }
    $contactEntities = ['contact', 'individual', 'organization', 'household'];
    $locationEntities = ['email', 'address', 'phone', 'website', 'im'];

    $entity = strtolower($field['entity'] ?? '');
    if ($entity && !in_array($entity, array_merge($contactEntities, $locationEntities))) {
      switch ($entity) {
        case 'note':
          if ($value) {
            $contactParams['api.Note.create'] = [
              'note' => $value,
              'contact_id' => 'user_contact_id',
            ];
          }
          break;

        case 'entity_tag':
          if (!is_array($value)) {
            $value = $value ? explode(',', $value) : [];
          }
          $contactParams['api.entity_tag.replace'] = [
            'tag_id' => $value,
          ];
          break;

        default:
          $contactParams['api.' . $entity . '.create'][$fieldName] = $value;
          //@todo we are not currently declaring this option
          if (isset($params['batch_id']) && strtolower($entity) == 'contribution') {
            $contactParams['api.' . $entity . '.create']['batch_id'] = $params['batch_id'];
          }
          if (isset($params[$entity . '_id'])) {
            //todo possibly declare $entity_id in getfields ?
            $contactParams['api.' . $entity . '.create']['id'] = $params[$entity . '_id'];
          }
      }
    }
    else {
      $contactParams[_civicrm_api3_profile_translate_fieldnames_for_bao($fieldName)] = $value;
    }
  }
  if (isset($contactParams['api.contribution.create']) && isset($contactParams['api.membership.create'])) {
    $contactParams['api.membership_payment.create'] = [
      'contribution_id' => '$value.api.contribution.create.id',
      'membership_id' => '$value.api.membership.create.id',
    ];
  }

  if (isset($contactParams['api.contribution.create']) && isset($contactParams['api.participant.create'])) {
    $contactParams['api.participant_payment.create'] = [
      'contribution_id' => '$value.api.contribution.create.id',
      'participant_id' => '$value.api.participant.create.id',
    ];
  }

  $contactParams['contact_id'] = empty($params['contact_id']) ? ($params['id'] ?? NULL) : $params['contact_id'];
  $contactParams['profile_id'] = $profileID;
  $contactParams['skip_custom'] = 1;

  $contactProfileParams = civicrm_api3_profile_apply($contactParams);

  // Contact profile fields
  $profileParams = $contactProfileParams['values'];

  // If profile having activity fields
  if ($isContactActivityProfile && !empty($activityParams)) {
    $activityParams['id'] = $params['activity_id'];
    $profileParams['api.activity.create'] = $activityParams;
  }

  return civicrm_api3('contact', 'create', $profileParams);
}

/**
 * Translate field names for BAO.
 *
 * The api standards expect field names to be lower case but the BAO uses mixed case
 * so we accept 'email-primary' but pass 'email-Primary' to the BAO
 * we could make the BAO handle email-primary but this would alter the fieldname seen by hooks
 * & we would need to consider that change
 *
 * @param string $fieldName
 *   API field name.
 *
 * @return string
 *   BAO Field Name
 */
function _civicrm_api3_profile_translate_fieldnames_for_bao($fieldName) {
  $fieldName = str_replace('image_url', 'image_URL', $fieldName);
  return str_replace('primary', 'Primary', $fieldName);
}

/**
 * Metadata for submit action.
 *
 * @param array $params
 * @param array $apirequest
 */
function _civicrm_api3_profile_submit_spec(&$params, $apirequest) {
  if (isset($apirequest['params']['profile_id'])) {
    // we will return what is required for this profile
    // note the problem with simply over-riding getfields & then calling generic if needbe is we don't have the
    // api request array to pass to it.
    //@todo - it may make more sense just to pass the apiRequest to getfields
    //@todo get_options should take an array - @ the moment it is only takes 'all' - which is supported
    // by other getfields fn
    // we don't resolve state, country & county for performance reasons
    $resolveOptions = ($apirequest['params']['get_options'] ?? NULL) == 'all';
    $profileID = _civicrm_api3_profile_getProfileID($apirequest['params']['profile_id']);
    $params = _civicrm_api3_buildprofile_submitfields($profileID, $resolveOptions, $params['cache_clear'] ?? FALSE);
  }
  elseif (isset($apirequest['params']['cache_clear'])) {
    _civicrm_api3_buildprofile_submitfields(FALSE, FALSE, TRUE);
  }
  $params['profile_id']['api.required'] = TRUE;
  $params['profile_id']['title'] = 'Profile ID';
  // Profile forms submit tag values as a string; hack to get past api wrapper validation
  if (!empty($params['tag_id'])) {
    unset($params['tag_id']['pseudoconstant']);
    $params['tag_id']['type'] = CRM_Utils_Type::T_STRING;
  }
}

/**
 * Apply profile.
 *
 * @deprecated - appears to be an internal function - should not be accessible via api
 * Provide formatted values for profile fields.
 *
 * @param array $params
 *   Array of property name/value.
 *   pairs to profile field values
 *
 * @throws CRM_Core_Exception
 * @return array
 *
 * @todo add example
 * @todo add test cases
 */
function civicrm_api3_profile_apply($params) {
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
    $params['contact_id'] ?? NULL,
    $params['profile_id'],
    $params['contact_type'] ?? NULL,
    $params['skip_custom'] ?? FALSE
  );

  if (empty($data)) {
    throw new CRM_Core_Exception('Unable to format profile parameters.');
  }

  return civicrm_api3_create_success($data);
}

/**
 * Adjust Metadata for Apply action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_profile_apply_spec(&$params) {
  $params['profile_id']['api.required'] = 1;
  $params['profile_id']['title'] = 'Profile ID';
}

/**
 * Get pseudo profile 'billing'.
 *
 * This is a function to help us 'pretend' billing is a profile & treat it like it is one.
 * It gets standard credit card address fields etc
 * Note this is 'better' that the inbuilt version as it will pull in fallback values
 *  billing location -> is_billing -> primary
 *
 *  Note that that since the existing code for deriving a blank profile is not easily accessible our
 *  interim solution is just to return an empty array
 *
 * @param array $params
 *
 * @return array
 */
function _civicrm_api3_profile_getbillingpseudoprofile(&$params) {

  $locationTypeID = CRM_Core_BAO_LocationType::getBilling();

  if (empty($params['contact_id'])) {
    $config = CRM_Core_Config::singleton();
    $blanks = [
      'billing_first_name' => '',
      'billing_middle_name' => '',
      'billing_last_name' => '',
      'email-' . $locationTypeID => '',
      'billing_email-' . $locationTypeID => '',
      'billing_city-' . $locationTypeID => '',
      'billing_postal_code-' . $locationTypeID => '',
      'billing_street_address-' . $locationTypeID => '',
      'billing_country_id-' . $locationTypeID => $config->defaultContactCountry,
      'billing_state_province_id-' . $locationTypeID => $config->defaultContactStateProvince,
    ];
    return $blanks;
  }

  $addressFields = ['street_address', 'city', 'state_province_id', 'country_id', 'postal_code'];
  $result = civicrm_api3('contact', 'getsingle', [
    'id' => $params['contact_id'],
    'api.address.get.1' => ['location_type_id' => 'Billing', 'return' => $addressFields],
    // getting the is_billing required or not is an extra db call but probably cheap enough as this isn't an import api
    'api.address.get.2' => ['is_billing' => TRUE, 'return' => $addressFields],
    'api.email.get.1' => ['location_type_id' => 'Billing'],
    'api.email.get.2' => ['is_billing' => TRUE],
    'return' => 'api.email.get, api.address.get, api.address.getoptions, country, state_province, email, first_name, last_name, middle_name, ' . implode(',', $addressFields),
  ]
  );

  $values = [
    'billing_first_name' => $result['first_name'],
    'billing_middle_name' => $result['middle_name'],
    'billing_last_name' => $result['last_name'],
  ];

  if (!empty($result['api.address.get.1']['count'])) {
    foreach ($addressFields as $fieldname) {
      $values['billing_' . $fieldname . '-' . $locationTypeID] = $result['api.address.get.1']['values'][0][$fieldname] ?? '';
    }
  }
  elseif (!empty($result['api.address.get.2']['count'])) {
    foreach ($addressFields as $fieldname) {
      $values['billing_' . $fieldname . '-' . $locationTypeID] = $result['api.address.get.2']['values'][0][$fieldname] ?? '';
    }
  }
  else {
    foreach ($addressFields as $fieldname) {
      $values['billing_' . $fieldname . '-' . $locationTypeID] = $result[$fieldname] ?? '';
    }
  }

  if (!empty($result['api.email.get.1']['count'])) {
    $values['billing-email' . '-' . $locationTypeID] = $result['api.email.get.1']['values'][0]['email'];
  }
  elseif (!empty($result['api.email.get.2']['count'])) {
    $values['billing-email' . '-' . $locationTypeID] = $result['api.email.get.2']['values'][0]['email'];
  }
  else {
    $values['billing-email' . '-' . $locationTypeID] = $result['email'];
  }
  // return both variants of email to reflect inconsistencies in form layer
  $values['email' . '-' . $locationTypeID] = $values['billing-email' . '-' . $locationTypeID];
  return $values;
}

/**
 * Here we will build  up getfields type data for all the fields in the profile.
 *
 * Because the integration with the form layer in core is so hard-coded we are not going to attempt to re-use it
 * However, as this function is unit-tested & hence 'locked in' we can aspire to extract sharable
 * code out of the form-layer over time.
 *
 * The function deciphers which fields belongs to which entites & retrieves metadata about the entities
 * Unfortunately we have inconsistencies such as 'contribution' uses contribution_status_id
 * & participant has 'participant_status' so we have to standardise from the outside in here -
 * find the oddities, 'mask them' at this layer, add tests & work to standardise over time so we can remove this handling
 *
 * @param int $profileID
 * @param int $optionsBehaviour
 *   0 = don't resolve, 1 = resolve non-aggressively, 2 = resolve aggressively - ie include country & state.
 * @param bool $is_flush
 *
 * @return array|void
 */
function _civicrm_api3_buildprofile_submitfields($profileID, $optionsBehaviour, $is_flush) {
  static $profileFields = [];
  if ($is_flush) {
    $profileFields = [];
    if (empty($profileID)) {
      return NULL;
    }
  }
  if (isset($profileFields[$profileID])) {
    return $profileFields[$profileID];
  }
  $fields = civicrm_api3('uf_field', 'get', ['uf_group_id' => $profileID, 'options' => ['limit' => 0]]);
  $entities = [];
  foreach ($fields['values'] as $field) {
    if (!$field['is_active']) {
      continue;
    }
    list($entity, $fieldName) = _civicrm_api3_map_profile_fields_to_entity($field);
    $aliasArray = [];
    // Use alias to handle the inconsistency between -Primary and -primary suffixes
    if (strtolower($fieldName) != $fieldName) {
      $aliasArray['api.aliases'] = [$fieldName];
      $fieldName = strtolower($fieldName);
    }
    $profileFields[$profileID][$fieldName] = array_merge([
      'api.required' => $field['is_required'],
      'title' => $field['label'],
      'help_pre' => $field['help_pre'] ?? NULL,
      'help_post' => $field['help_post'] ?? NULL,
      'entity' => $entity,
      'weight' => $field['weight'] ?? NULL,
    ], $aliasArray);

    $ufFieldTaleFieldName = $field['field_name'];
    if (isset($entity[$ufFieldTaleFieldName]['name'])) {
      // in the case where we are dealing with an alias we map back to a name
      // this will be tested by 'membership_type_id' field
      $ufFieldTaleFieldName = $entity[$ufFieldTaleFieldName]['name'];
    }
    //see function notes
    // as we build up a list of these we should be able to determine a generic approach
    //
    $hardCodedEntityFields = [
      'state_province' => 'state_province_id',
      'country' => 'country_id',
      'county' => 'county_id',
      'participant_status' => 'status_id',
      'gender' => 'gender_id',
      'financial_type' => 'financial_type_id',
      'soft_credit' => 'soft_credit_to',
      'group' => 'group_id',
      'tag' => 'tag_id',
      'soft_credit_type' => 'soft_credit_type_id',
    ];

    if (array_key_exists($ufFieldTaleFieldName, $hardCodedEntityFields)) {
      $ufFieldTaleFieldName = $hardCodedEntityFields[$ufFieldTaleFieldName];
    }

    $entities[$entity][$fieldName] = $ufFieldTaleFieldName;
  }

  foreach ($entities as $entity => $entityFields) {
    $result = civicrm_api3($entity, 'getfields', ['action' => 'create']);
    $entityGetFieldsResult = _civicrm_api3_profile_appendaliases($result['values'], $entity);
    foreach ($entityFields as $entityfield => $realName) {
      $fieldName = strtolower($entityfield);
      if (!strstr($fieldName, '-')) {
        if (strtolower($realName) != $fieldName) {
          // we want to keep the '-' pattern for locations but otherwise
          // we are going to make the api-standard field the main / preferred name but support the db name
          // in future naming the fields in the DB to reflect the way the rest of the api / BAO / metadata works would
          // reduce code
          $fieldName = strtolower($realName);
        }
        if (isset($entityGetFieldsResult[$realName]['uniqueName'])) {
          // we won't alias the field name on here are we are using uniqueNames for the possibility of needing to differentiate
          // which entity 'status_id' belongs to
          $fieldName = $entityGetFieldsResult[$realName]['uniqueName'];
        }
        else {
          if (isset($entityGetFieldsResult[$realName]['name'])) {
            // this will sort out membership_type_id vs membership_type
            $fieldName = $entityGetFieldsResult[$realName]['name'];
          }
        }
      }
      if (!empty($entityGetFieldsResult[$realName])) {
        $profileFields[$profileID][$fieldName] = array_merge($entityGetFieldsResult[$realName], $profileFields[$profileID][$entityfield]);
      }
      if (!isset($profileFields[$profileID][$fieldName]['api.aliases'])) {
        $profileFields[$profileID][$fieldName]['api.aliases'] = [];
      }
      if ($optionsBehaviour && !empty($entityGetFieldsResult[$realName]['pseudoconstant'])) {
        if ($optionsBehaviour > 1  || !in_array($realName, ['state_province_id', 'county_id', 'country_id'])) {
          $options = civicrm_api3($entity, 'getoptions', ['field' => $realName]);
          $profileFields[$profileID][$fieldName]['options'] = $options['values'];
        }
      }

      if ($entityfield != $fieldName) {
        if (isset($profileFields[$profileID][$entityfield])) {
          unset($profileFields[$profileID][$entityfield]);
        }
        if (!in_array($entityfield, $profileFields[$profileID][$fieldName]['api.aliases'])) {
          // we will make the mixed case version (e.g. of 'Primary') an alias
          $profileFields[$profileID][$fieldName]['api.aliases'][] = $entityfield;
        }
      }
      /**
       * putting this on hold -this would cause the api to set the default - but could have unexpected behaviour
       * if (isset($result['values'][$realName]['default_value'])) {
       * //this would be the case for a custom field with a configured default
       * $profileFields[$profileID][$entityfield]['api.default'] = $result['values'][$realName]['default_value'];
       * }
       */
    }
  }
  $profileFields[$profileID] ??= [];
  uasort($profileFields[$profileID], "_civicrm_api3_order_by_weight");
  return $profileFields[$profileID];
}

/**
 * @param array $a
 * @param array $b
 *
 * @return bool
 */
function _civicrm_api3_order_by_weight($a, $b) {
  return ($b['weight'] ?? 0) < ($a['weight'] ?? 0) ? 1 : -1;
}

/**
 * Here we map the profile fields as stored in the uf_field table to their 'real entity'
 * we also return the profile fieldname
 *
 * @param array $field
 *
 * @return array
 */
function _civicrm_api3_map_profile_fields_to_entity(&$field) {
  $entity = $field['field_type'];
  // let's get the contact types and subtypes so that we can change the entity
  // of such fields to 'contact'
  $result = civicrm_api3('ContactType', 'get', [
    'return' => ["name"],
    'options' => ['limit' => 0],
  ]);

  $contactTypes = [];
  foreach ($result['values'] as $type) {
    $contactTypes[$type['id']] = $type['name'];
  }

  if (in_array($entity, $contactTypes)) {
    $entity = 'contact';
  }
  $entity = _civicrm_api_get_entity_name_from_camel($entity);
  $fieldName = $field['field_name'];

  // @todo - sort this out!
  //here we do a hard-code list of known fields that don't map to where they are mapped to
  // not a great solution but probably if we looked in the BAO we'd find a scary switch statement
  // in a perfect world the uf_field table would hold the correct entity for each item
  // & only the relationships between entities would need to be coded
  $hardCodedEntityMappings = [
    'street_address' => 'address',
    'street_number' => 'address',
    'supplemental_address_1' => 'address',
    'supplemental_address_2' => 'address',
    'supplemental_address_3' => 'address',
    'postal_code' => 'address',
    'city' => 'address',
    'email' => 'email',
    'phone' => 'phone',
    'phone_and_ext' => 'phone',
    'url' => 'website',
    'im' => 'im',
    'state_province' => 'address',
    'country' => 'address',
    'county' => 'address',
    //note that in discussions about how to restructure the api we discussed making these membership
    // fields into 'membership_payment' fields - which would entail declaring them in getfields
    // & renaming them in existing profiles
    'financial_type' => 'contribution',
    'total_amount' => 'contribution',
    'receive_date' => 'contribution',
    'payment_instrument' => 'contribution',
    'contribution_check_number' => 'contribution',
    'contribution_status_id' => 'contribution',
    'soft_credit' => 'contribution',
    'soft_credit_type' => 'contribution_soft',
    'group' => 'group_contact',
    'tag' => 'entity_tag',
    'note' => 'note',
  ];
  if (array_key_exists($fieldName, $hardCodedEntityMappings)) {
    $entity = $hardCodedEntityMappings[$fieldName];
  }

  $locationEntities = ['email', 'phone', 'address', 'im'];
  if (!empty($field['location_type_id'])) {
    $fieldName .= '-' . $field['location_type_id'];
  }
  elseif (in_array($entity, $locationEntities)) {
    $fieldName .= '-Primary';
  }
  if (!empty($field['phone_type_id'])) {
    $fieldName .= '-' . $field['phone_type_id'];
    $entity = 'phone';
  }
  if (!empty($field['website_type_id'])) {
    $fieldName .= '-' . $field['website_type_id'];
    $entity = 'website';
  }

  return [$entity, $fieldName];
}

/**
 * @todo this should be handled by the api wrapper using getfields info - need to check
 * how we add a a pseudoconstant to this pseudo api to make that work
 *
 * @param int $profileID
 *
 * @return int|string
 * @throws CRM_Core_Exception
 */
function _civicrm_api3_profile_getProfileID($profileID) {
  if (!empty($profileID) && strtolower($profileID) != 'billing' && !is_numeric($profileID)) {
    $profileID = civicrm_api3('uf_group', 'getvalue', ['return' => 'id', 'name' => $profileID]);
  }
  return $profileID;
}

/**
 * helper function to add all aliases as keys to getfields response so we can look for keys within it
 * since the relationship between profile fields & api / metadata based fields is a bit inconsistent
 *
 * @param array $values
 *
 * e.g getfields response incl 'membership_type_id' - with api.aliases = 'membership_type'
 * returned array will include both as keys (with the same values)
 * @param string $entity
 *
 * @return array
 */
function _civicrm_api3_profile_appendaliases($values, $entity) {
  foreach ($values as $field => $spec) {
    if (!empty($spec['api.aliases'])) {
      foreach ($spec['api.aliases'] as $alias) {
        $values[$alias] = $spec;
      }
    }
    if (!empty($spec['uniqueName'])) {
      $values[$spec['uniqueName']] = $spec;
    }
  }
  //special case on membership & contribution - can't see how to handle in a generic way
  if (in_array($entity, ['membership', 'contribution'])) {
    $values['send_receipt'] = ['title' => 'Send Receipt', 'type' => 16];
  }
  return $values;
}

/**
 * @deprecated api notice
 * @return array
 *   Array of deprecated actions
 */
function _civicrm_api3_profile_deprecation() {
  return [
    'apply' => 'Profile api "apply" action is deprecated in favor of "submit".',
  ];
}
