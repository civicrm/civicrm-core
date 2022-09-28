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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/*
 * These functions have been deprecated out of API v3 Utils folder as they are not part of the
 * API. Calling API functions directly is not supported & these functions are not called by any
 * part of the API so are not really part of the api
 *
 */

require_once 'api/v3/utils.php';

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
        'contact_type' => $params['contact_type'] ?? NULL,
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
        'contact_type' => $params['contact_type'] ?? NULL,
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
    $params['addressee'] = $values['addressee'];
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

          $htmlType = $customFields[$customFieldID]['html_type'] ?? NULL;
          if (CRM_Core_BAO_CustomField::isSerialized($customFields[$customFieldID]) && $val) {
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
  $id = $params['id'] ?? NULL;
  $externalId = $params['external_identifier'] ?? NULL;
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
