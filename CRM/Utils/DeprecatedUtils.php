<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/*
 * These functions have been deprecated out of API v3 Utils folder as they are not part of the
 * API. Calling API functions directly is not supported & these functions are not called by any
 * part of the API so are not really part of the api
 *
 */

require_once 'api/v3/utils.php';

/**
 * Check duplicate contacts based on de-dupe parameters.
 *
 * @param array $params
 *
 * @return array
 */
function _civicrm_api3_deprecated_check_contact_dedupe($params) {
  static $cIndieFields = NULL;
  static $defaultLocationId = NULL;

  $contactType = $params['contact_type'];
  if ($cIndieFields == NULL) {
    require_once 'CRM/Contact/BAO/Contact.php';
    $cTempIndieFields = CRM_Contact_BAO_Contact::importableFields($contactType);
    $cIndieFields = $cTempIndieFields;

    require_once "CRM/Core/BAO/LocationType.php";
    $defaultLocation = CRM_Core_BAO_LocationType::getDefault();

    // set the value to default location id else set to 1
    if (!$defaultLocationId = (int) $defaultLocation->id) {
      $defaultLocationId = 1;
    }
  }

  require_once 'CRM/Contact/BAO/Query.php';
  $locationFields = CRM_Contact_BAO_Query::$_locationSpecificFields;

  $contactFormatted = [];
  foreach ($params as $key => $field) {
    if ($field == NULL || $field === '') {
      continue;
    }
    // CRM-17040, Considering only primary contact when importing contributions. So contribution inserts into primary contact
    // instead of soft credit contact.
    if (is_array($field) && $key != "soft_credit") {
      foreach ($field as $value) {
        $break = FALSE;
        if (is_array($value)) {
          foreach ($value as $name => $testForEmpty) {
            if ($name !== 'phone_type' &&
              ($testForEmpty === '' || $testForEmpty == NULL)
            ) {
              $break = TRUE;
              break;
            }
          }
        }
        else {
          $break = TRUE;
        }
        if (!$break) {
          _civicrm_api3_deprecated_add_formatted_param($value, $contactFormatted);
        }
      }
      continue;
    }

    $value = [$key => $field];

    // check if location related field, then we need to add primary location type
    if (in_array($key, $locationFields)) {
      $value['location_type_id'] = $defaultLocationId;
    }
    elseif (array_key_exists($key, $cIndieFields)) {
      $value['contact_type'] = $contactType;
    }

    _civicrm_api3_deprecated_add_formatted_param($value, $contactFormatted);
  }

  $contactFormatted['contact_type'] = $contactType;

  return _civicrm_api3_deprecated_duplicate_formatted_contact($contactFormatted);
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array $params
 *   Associative array of property name/value.
 *                             pairs to insert in new contact.
 * @param array $values
 *   The reformatted properties that we can use internally.
 *
 * @param array|bool $create Is the formatted Values array going to
 *                             be used for CRM_Activity_BAO_Activity::create()
 *
 * @return array|CRM_Error
 */
function _civicrm_api3_deprecated_activity_formatted_param(&$params, &$values, $create = FALSE) {
  // copy all the activity fields as is
  $fields = CRM_Activity_DAO_Activity::fields();
  _civicrm_api3_store_values($fields, $params, $values);

  require_once 'CRM/Core/OptionGroup.php';
  $customFields = CRM_Core_BAO_CustomField::getFields('Activity');

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    //Handling Custom Data
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      $values[$key] = $value;
      $type = $customFields[$customFieldID]['html_type'];
      if ($type == 'CheckBox' || $type == 'Multi-Select') {
        $mulValues = explode(',', $value);
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        $values[$key] = [];
        foreach ($mulValues as $v1) {
          foreach ($customOption as $customValueID => $customLabel) {
            $customValue = $customLabel['value'];
            if ((strtolower(trim($customLabel['label'])) == strtolower(trim($v1))) ||
              (strtolower(trim($customValue)) == strtolower(trim($v1)))
            ) {
              if ($type == 'CheckBox') {
                $values[$key][$customValue] = 1;
              }
              else {
                $values[$key][] = $customValue;
              }
            }
          }
        }
      }
      elseif ($type == 'Select' || $type == 'Radio') {
        $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
        foreach ($customOption as $customFldID => $customValue) {
          $val = CRM_Utils_Array::value('value', $customValue);
          $label = CRM_Utils_Array::value('label', $customValue);
          $label = strtolower($label);
          $value = strtolower(trim($value));
          if (($value == $label) || ($value == strtolower($val))) {
            $values[$key] = $val;
          }
        }
      }
    }

    if ($key == 'target_contact_id') {
      if (!CRM_Utils_Rule::integer($value)) {
        return civicrm_api3_create_error("contact_id not valid: $value");
      }
      $contactID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value");
      if (!$contactID) {
        return civicrm_api3_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
      }
    }
  }
  return NULL;
}

/**
 * This function adds the contact variable in $values to the
 * parameter list $params.  For most cases, $values should have length 1.  If
 * the variable being added is a child of Location, a location_type_id must
 * also be included.  If it is a child of phone, a phone_type must be included.
 *
 * @param array $values
 *   The variable(s) to be added.
 * @param array $params
 *   The structured parameter list.
 *
 * @return bool|CRM_Utils_Error
 */
function _civicrm_api3_deprecated_add_formatted_param(&$values, &$params) {
  // Crawl through the possible classes:
  // Contact
  //      Individual
  //      Household
  //      Organization
  //          Location
  //              Address
  //              Email
  //              Phone
  //              IM
  //      Note
  //      Custom

  // Cache the various object fields
  static $fields = NULL;

  if ($fields == NULL) {
    $fields = [];
  }

  // first add core contact values since for other Civi modules they are not added
  require_once 'CRM/Contact/BAO/Contact.php';
  $contactFields = CRM_Contact_DAO_Contact::fields();
  _civicrm_api3_store_values($contactFields, $values, $params);

  if (isset($values['contact_type'])) {
    // we're an individual/household/org property

    $fields[$values['contact_type']] = CRM_Contact_DAO_Contact::fields();

    _civicrm_api3_store_values($fields[$values['contact_type']], $values, $params);
    return TRUE;
  }

  if (isset($values['individual_prefix'])) {
    if (!empty($params['prefix_id'])) {
      $prefixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
      $params['prefix'] = $prefixes[$params['prefix_id']];
    }
    else {
      $params['prefix'] = $values['individual_prefix'];
    }
    return TRUE;
  }

  if (isset($values['individual_suffix'])) {
    if (!empty($params['suffix_id'])) {
      $suffixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');
      $params['suffix'] = $suffixes[$params['suffix_id']];
    }
    else {
      $params['suffix'] = $values['individual_suffix'];
    }
    return TRUE;
  }

  // CRM-4575
  if (isset($values['email_greeting'])) {
    if (!empty($params['email_greeting_id'])) {
      $emailGreetingFilter = [
        'contact_type' => CRM_Utils_Array::value('contact_type', $params),
        'greeting_type' => 'email_greeting',
      ];
      $emailGreetings = CRM_Core_PseudoConstant::greeting($emailGreetingFilter);
      $params['email_greeting'] = $emailGreetings[$params['email_greeting_id']];
    }
    else {
      $params['email_greeting'] = $values['email_greeting'];
    }

    return TRUE;
  }

  if (isset($values['postal_greeting'])) {
    if (!empty($params['postal_greeting_id'])) {
      $postalGreetingFilter = [
        'contact_type' => CRM_Utils_Array::value('contact_type', $params),
        'greeting_type' => 'postal_greeting',
      ];
      $postalGreetings = CRM_Core_PseudoConstant::greeting($postalGreetingFilter);
      $params['postal_greeting'] = $postalGreetings[$params['postal_greeting_id']];
    }
    else {
      $params['postal_greeting'] = $values['postal_greeting'];
    }
    return TRUE;
  }

  if (isset($values['addressee'])) {
    if (!empty($params['addressee_id'])) {
      $addresseeFilter = [
        'contact_type' => CRM_Utils_Array::value('contact_type', $params),
        'greeting_type' => 'addressee',
      ];
      $addressee = CRM_Core_PseudoConstant::addressee($addresseeFilter);
      $params['addressee'] = $addressee[$params['addressee_id']];
    }
    else {
      $params['addressee'] = $values['addressee'];
    }
    return TRUE;
  }

  if (isset($values['gender'])) {
    if (!empty($params['gender_id'])) {
      $genders = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
      $params['gender'] = $genders[$params['gender_id']];
    }
    else {
      $params['gender'] = $values['gender'];
    }
    return TRUE;
  }

  if (!empty($values['preferred_communication_method'])) {
    $comm = [];
    $pcm = array_change_key_case(array_flip(CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'preferred_communication_method')), CASE_LOWER);

    $preffComm = explode(',', $values['preferred_communication_method']);
    foreach ($preffComm as $v) {
      $v = strtolower(trim($v));
      if (array_key_exists($v, $pcm)) {
        $comm[$pcm[$v]] = 1;
      }
    }

    $params['preferred_communication_method'] = $comm;
    return TRUE;
  }

  // format the website params.
  if (!empty($values['url'])) {
    static $websiteFields;
    if (!is_array($websiteFields)) {
      require_once 'CRM/Core/DAO/Website.php';
      $websiteFields = CRM_Core_DAO_Website::fields();
    }
    if (!array_key_exists('website', $params) ||
      !is_array($params['website'])
    ) {
      $params['website'] = [];
    }

    $websiteCount = count($params['website']);
    _civicrm_api3_store_values($websiteFields, $values,
      $params['website'][++$websiteCount]
    );

    return TRUE;
  }

  // get the formatted location blocks into params - w/ 3.0 format, CRM-4605
  if (!empty($values['location_type_id'])) {
    static $fields = NULL;
    if ($fields == NULL) {
      $fields = [];
    }

    foreach ([
      'Phone',
      'Email',
      'IM',
      'OpenID',
      'Phone_Ext',
    ] as $block) {
      $name = strtolower($block);
      if (!array_key_exists($name, $values)) {
        continue;
      }

      if ($name == 'phone_ext') {
        $block = 'Phone';
      }

      // block present in value array.
      if (!array_key_exists($name, $params) || !is_array($params[$name])) {
        $params[$name] = [];
      }

      if (!array_key_exists($block, $fields)) {
        $className = "CRM_Core_DAO_$block";
        $fields[$block] =& $className::fields();
      }

      $blockCnt = count($params[$name]);

      // copy value to dao field name.
      if ($name == 'im') {
        $values['name'] = $values[$name];
      }

      _civicrm_api3_store_values($fields[$block], $values,
        $params[$name][++$blockCnt]
      );

      if (empty($params['id']) && ($blockCnt == 1)) {
        $params[$name][$blockCnt]['is_primary'] = TRUE;
      }

      // we only process single block at a time.
      return TRUE;
    }

    // handle address fields.
    if (!array_key_exists('address', $params) || !is_array($params['address'])) {
      $params['address'] = [];
    }

    $addressCnt = 1;
    foreach ($params['address'] as $cnt => $addressBlock) {
      if (CRM_Utils_Array::value('location_type_id', $values) ==
        CRM_Utils_Array::value('location_type_id', $addressBlock)
      ) {
        $addressCnt = $cnt;
        break;
      }
      $addressCnt++;
    }

    if (!array_key_exists('Address', $fields)) {
      $fields['Address'] = CRM_Core_DAO_Address::fields();
    }

    // Note: we doing multiple value formatting here for address custom fields, plus putting into right format.
    // The actual formatting (like date, country ..etc) for address custom fields is taken care of while saving
    // the address in CRM_Core_BAO_Address::create method
    if (!empty($values['location_type_id'])) {
      static $customFields = [];
      if (empty($customFields)) {
        $customFields = CRM_Core_BAO_CustomField::getFields('Address');
      }
      // make a copy of values, as we going to make changes
      $newValues = $values;
      foreach ($values as $key => $val) {
        $customFieldID = CRM_Core_BAO_CustomField::getKeyID($key);
        if ($customFieldID && array_key_exists($customFieldID, $customFields)) {
          // mark an entry in fields array since we want the value of custom field to be copied
          $fields['Address'][$key] = NULL;

          $htmlType = CRM_Utils_Array::value('html_type', $customFields[$customFieldID]);
          switch ($htmlType) {
            case 'CheckBox':
            case 'Multi-Select':
              if ($val) {
                $mulValues = explode(',', $val);
                $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
                $newValues[$key] = [];
                foreach ($mulValues as $v1) {
                  foreach ($customOption as $v2) {
                    if ((strtolower($v2['label']) == strtolower(trim($v1))) ||
                      (strtolower($v2['value']) == strtolower(trim($v1)))
                    ) {
                      if ($htmlType == 'CheckBox') {
                        $newValues[$key][$v2['value']] = 1;
                      }
                      else {
                        $newValues[$key][] = $v2['value'];
                      }
                    }
                  }
                }
              }
              break;
          }
        }
      }
      // consider new values
      $values = $newValues;
    }

    _civicrm_api3_store_values($fields['Address'], $values, $params['address'][$addressCnt]);

    $addressFields = [
      'county',
      'country',
      'state_province',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'StateProvince.name',
    ];

    foreach ($addressFields as $field) {
      if (array_key_exists($field, $values)) {
        if (!array_key_exists('address', $params)) {
          $params['address'] = [];
        }
        $params['address'][$addressCnt][$field] = $values[$field];
      }
    }

    if ($addressCnt == 1) {

      $params['address'][$addressCnt]['is_primary'] = TRUE;
    }
    return TRUE;
  }

  if (isset($values['note'])) {
    // add a note field
    if (!isset($params['note'])) {
      $params['note'] = [];
    }
    $noteBlock = count($params['note']) + 1;

    $params['note'][$noteBlock] = [];
    if (!isset($fields['Note'])) {
      $fields['Note'] = CRM_Core_DAO_Note::fields();
    }

    // get the current logged in civicrm user
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    if ($userID) {
      $values['contact_id'] = $userID;
    }

    _civicrm_api3_store_values($fields['Note'], $values, $params['note'][$noteBlock]);

    return TRUE;
  }

  // Check for custom field values

  if (empty($fields['custom'])) {
    $fields['custom'] = &CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $values),
      FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE
    );
  }

  foreach ($values as $key => $value) {
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      // check if it's a valid custom field id

      if (!array_key_exists($customFieldID, $fields['custom'])) {
        return civicrm_api3_create_error('Invalid custom field ID');
      }
      else {
        $params[$key] = $value;
      }
    }
  }
}

/**
 *
 * @param array $params
 *
 * @return array
 *   <type>
 */
function _civicrm_api3_deprecated_duplicate_formatted_contact($params) {
  $id = CRM_Utils_Array::value('id', $params);
  $externalId = CRM_Utils_Array::value('external_identifier', $params);
  if ($id || $externalId) {
    $contact = new CRM_Contact_DAO_Contact();

    $contact->id = $id;
    $contact->external_identifier = $externalId;

    if ($contact->find(TRUE)) {
      if ($params['contact_type'] != $contact->contact_type) {
        return civicrm_api3_create_error("Mismatched contact IDs OR Mismatched contact Types");
      }

      $error = CRM_Core_Error::createError("Found matching contacts: $contact->id",
        CRM_Core_Error::DUPLICATE_CONTACT,
        'Fatal', $contact->id
      );
      return civicrm_api3_create_error($error->pop());
    }
  }
  else {
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($params, $params['contact_type'], 'Unsupervised');

    if (!empty($ids)) {
      $ids = implode(',', $ids);
      $error = CRM_Core_Error::createError("Found matching contacts: $ids",
        CRM_Core_Error::DUPLICATE_CONTACT,
        'Fatal', $ids
      );
      return civicrm_api3_create_error($error->pop());
    }
  }
  return civicrm_api3_create_success(TRUE);
}

/**
 * Validate a formatted contact parameter list.
 *
 * @param array $params
 *   Structured parameter list (as in crm_format_params).
 *
 * @return bool|CRM_Core_Error
 */
function _civicrm_api3_deprecated_validate_formatted_contact(&$params) {
  // Look for offending email addresses

  if (array_key_exists('email', $params)) {
    foreach ($params['email'] as $count => $values) {
      if (!is_array($values)) {
        continue;
      }
      if ($email = CRM_Utils_Array::value('email', $values)) {
        // validate each email
        if (!CRM_Utils_Rule::email($email)) {
          return civicrm_api3_create_error('No valid email address');
        }

        // check for loc type id.
        if (empty($values['location_type_id'])) {
          return civicrm_api3_create_error('Location Type Id missing.');
        }
      }
    }
  }

  // Validate custom data fields
  if (array_key_exists('custom', $params) && is_array($params['custom'])) {
    foreach ($params['custom'] as $key => $custom) {
      if (is_array($custom)) {
        foreach ($custom as $fieldId => $value) {
          $valid = CRM_Core_BAO_CustomValue::typecheck(CRM_Utils_Array::value('type', $value),
            CRM_Utils_Array::value('value', $value)
          );
          if (!$valid && $value['is_required']) {
            return civicrm_api3_create_error('Invalid value for custom field \'' .
              CRM_Utils_Array::value('name', $custom) . '\''
            );
          }
          if (CRM_Utils_Array::value('type', $custom) == 'Date') {
            $params['custom'][$key][$fieldId]['value'] = str_replace('-', '', $params['custom'][$key][$fieldId]['value']);
          }
        }
      }
    }
  }

  return civicrm_api3_create_success(TRUE);
}

/**
 * @deprecated - this is part of the import parser not the API & needs to be moved on out
 *
 * @param array $params
 * @param $onDuplicate
 *
 * @return array|bool
 *   <type>
 */
function _civicrm_api3_deprecated_create_participant_formatted($params, $onDuplicate) {
  require_once 'CRM/Event/Import/Parser.php';
  if ($onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK) {
    CRM_Core_Error::reset();
    $error = _civicrm_api3_deprecated_participant_check_params($params, TRUE);
    if (civicrm_error($error)) {
      return $error;
    }
  }
  require_once "api/v3/Participant.php";
  return civicrm_api3_participant_create($params);
}

/**
 *
 * @param array $params
 *
 * @param bool $checkDuplicate
 *
 * @return array|bool
 *   <type>
 */
function _civicrm_api3_deprecated_participant_check_params($params, $checkDuplicate = FALSE) {

  // check if participant id is valid or not
  if (!empty($params['id'])) {
    $participant = new CRM_Event_BAO_Participant();
    $participant->id = $params['id'];
    if (!$participant->find(TRUE)) {
      return civicrm_api3_create_error(ts('Participant  id is not valid'));
    }
  }
  require_once 'CRM/Contact/BAO/Contact.php';
  // check if contact id is valid or not
  if (!empty($params['contact_id'])) {
    $contact = new CRM_Contact_BAO_Contact();
    $contact->id = $params['contact_id'];
    if (!$contact->find(TRUE)) {
      return civicrm_api3_create_error(ts('Contact id is not valid'));
    }
  }

  // check that event id is not an template
  if (!empty($params['event_id'])) {
    $isTemplate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'is_template');
    if (!empty($isTemplate)) {
      return civicrm_api3_create_error(ts('Event templates are not meant to be registered.'));
    }
  }

  $result = [];
  if ($checkDuplicate) {
    if (CRM_Event_BAO_Participant::checkDuplicate($params, $result)) {
      $participantID = array_pop($result);

      $error = CRM_Core_Error::createError("Found matching participant record.",
        CRM_Core_Error::DUPLICATE_PARTICIPANT,
        'Fatal', $participantID
      );

      return civicrm_api3_create_error($error->pop(),
        [
          'contactID' => $params['contact_id'],
          'participantID' => $participantID,
        ]
      );
    }
  }
  return TRUE;
}

/**
 * @param array $params
 * @param bool $dupeCheck
 * @param int $dedupeRuleGroupID
 *
 * @return array|null
 */
function _civicrm_api3_deprecated_contact_check_params(
  &$params,
  $dupeCheck = TRUE,
  $dedupeRuleGroupID = NULL) {

  $requiredCheck = TRUE;

  if (isset($params['id']) && is_numeric($params['id'])) {
    $requiredCheck = FALSE;
  }
  if ($requiredCheck) {
    if (isset($params['id'])) {
      $required = ['Individual', 'Household', 'Organization'];
    }
    $required = [
      'Individual' => [
        ['first_name', 'last_name'],
        'email',
      ],
      'Household' => [
        'household_name',
      ],
      'Organization' => [
        'organization_name',
      ],
    ];

    // contact_type has a limited number of valid values
    if (empty($params['contact_type'])) {
      return civicrm_api3_create_error("No Contact Type");
    }
    $fields = CRM_Utils_Array::value($params['contact_type'], $required);
    if ($fields == NULL) {
      return civicrm_api3_create_error("Invalid Contact Type: {$params['contact_type']}");
    }

    if ($csType = CRM_Utils_Array::value('contact_sub_type', $params)) {
      if (!(CRM_Contact_BAO_ContactType::isExtendsContactType($csType, $params['contact_type']))) {
        return civicrm_api3_create_error("Invalid or Mismatched Contact Subtype: " . implode(', ', (array) $csType));
      }
    }

    if (empty($params['contact_id']) && !empty($params['id'])) {
      $valid = FALSE;
      $error = '';
      foreach ($fields as $field) {
        if (is_array($field)) {
          $valid = TRUE;
          foreach ($field as $element) {
            if (empty($params[$element])) {
              $valid = FALSE;
              $error .= $element;
              break;
            }
          }
        }
        else {
          if (!empty($params[$field])) {
            $valid = TRUE;
          }
        }
        if ($valid) {
          break;
        }
      }

      if (!$valid) {
        return civicrm_api3_create_error("Required fields not found for {$params['contact_type']} : $error");
      }
    }
  }

  if ($dupeCheck) {
    // @todo switch to using api version
    // $dupes = civicrm_api3('Contact', 'duplicatecheck', (array('match' => $params, 'dedupe_rule_id' => $dedupeRuleGroupID)));
    // $ids = $dupes['count'] ? implode(',', array_keys($dupes['values'])) : NULL;
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($params, $params['contact_type'], 'Unsupervised', [], CRM_Utils_Array::value('check_permissions', $params), $dedupeRuleGroupID);
    if ($ids != NULL) {
      $error = CRM_Core_Error::createError("Found matching contacts: " . implode(',', $ids),
        CRM_Core_Error::DUPLICATE_CONTACT,
        'Fatal', $ids
      );
      return civicrm_api3_create_error($error->pop());
    }
  }

  // check for organisations with same name
  if (!empty($params['current_employer'])) {
    $organizationParams = ['organization_name' => $params['current_employer']];
    $dupeIds = CRM_Contact_BAO_Contact::getDuplicateContacts($organizationParams, 'Organization', 'Supervised', [], FALSE);

    // check for mismatch employer name and id
    if (!empty($params['employer_id']) && !in_array($params['employer_id'], $dupeIds)
    ) {
      return civicrm_api3_create_error('Employer name and Employer id Mismatch');
    }

    // show error if multiple organisation with same name exist
    if (empty($params['employer_id']) && (count($dupeIds) > 1)
    ) {
      return civicrm_api3_create_error('Found more than one Organisation with same Name.');
    }
  }

  return NULL;
}

/**
 * @param $result
 * @param int $activityTypeID
 *
 * @return array
 *   <type> $params
 */
function _civicrm_api3_deprecated_activity_buildmailparams($result, $activityTypeID) {
  // get ready for collecting data about activity to be created
  $params = [];

  $params['activity_type_id'] = $activityTypeID;

  $params['status_id'] = 'Completed';
  if (!empty($result['from']['id'])) {
    $params['source_contact_id'] = $params['assignee_contact_id'] = $result['from']['id'];
  }
  $params['target_contact_id'] = [];
  $keys = ['to', 'cc', 'bcc'];
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

  $numAttachments = Civi::settings()->get('max_attachments_backend') ?? CRM_Core_BAO_File::DEFAULT_MAX_ATTACHMENTS_BACKEND;
  for ($i = 1; $i <= $numAttachments; $i++) {
    if (isset($result["attachFile_$i"])) {
      $params["attachFile_$i"] = $result["attachFile_$i"];
    }
  }

  return $params;
}
