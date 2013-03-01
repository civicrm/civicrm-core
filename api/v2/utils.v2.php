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
 * File for CiviCRM APIv2 utilitity functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_utils
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: utils.php 31877 2011-01-19 04:23:54Z shot $
 *
 */
require_once 'api/api.php';

/**
 * @todo Write documentation
 *
 */
function _civicrm_initialize() {
  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton();
  }

function civicrm_verify_mandatory(&$params, $daoName = NULL, $keys = array(
  )) {
  if (!is_array($params)) {
    throw new Exception('Input parameters is not an array');
  }

  if ($daoName != NULL) {
    _civicrm_check_required_fields($params, $daoName, TRUE);
  }

  foreach ($keys as $key) {
    if (!array_key_exists($key, $params)) {
      throw new Exception("Mandatory param missing: " . $key);
    }
  }
}

/**
 *
 * @param <type> $msg
 * @param <type> $data
 *
 * @return <type>
 */
function &civicrm_create_error($msg, $data = NULL) {
  return CRM_Core_Error::createAPIError($msg, $data);
}

/**
 *
 * @param <type> $result
 *
 * @return <type>
 */
function civicrm_create_success($result = 1) {

  $values = array();

  $values['is_error'] = 0;
  $values['result'] = $result;

  return $values;
}

/**
 *  function to check if an error is actually a duplicate contact error
 *
 *  @param array $error (array of) valid Error values
 *
 *  @return true if error is duplicate contact error, false otherwise
 *
 *  @access public
 */
function civicrm_duplicate($error) {
  if (is_array($error) && civicrm_error($error)) {
    $code = $error['error_message']['code'];
    if ($code == CRM_Core_Error::DUPLICATE_CONTACT) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 *
 * @param <type> $fields
 * @param <type> $params
 * @param <type> $values
 *
 * @return <type>
 */
function _civicrm_store_values(&$fields, &$params, &$values) {
  $valueFound = FALSE;

  $keys = array_intersect_key($params, $fields);
  foreach ($fields as $name => $field) {
    // ignore all ids for now
    if ($name === 'id' || substr($name, -1, 3) === '_id') {
      continue;
    }
    if (CRM_Utils_Array::value($name, $params)) {
      $values[$name] = $params[$name];
      $valueFound = TRUE;
    }
  }
  return $valueFound;
}

/**
 * Converts an object to an array
 *
 * @param  object   $dao           (reference )object to convert
 * @param  array    $dao           (reference )array
 *
 * @return array
 * @static void
 * @access public
 */
function _civicrm_object_to_array(&$dao, &$values) {
  $tmpFields = $dao->fields();
  $fields = array();
  //rebuild $fields array to fix unique name of the fields
  foreach ($tmpFields as $key => $val) {
    $fields[$val["name"]] = $val;
  }

  foreach ($fields as $key => $value) {
    if (array_key_exists($key, $dao)) {
      $values[$key] = $dao->$key;
    }
  }
}

/**
 * This function adds the contact variable in $values to the
 * parameter list $params.  For most cases, $values should have length 1.  If
 * the variable being added is a child of Location, a location_type_id must
 * also be included.  If it is a child of phone, a phone_type must be included.
 *
 * @param array  $values    The variable(s) to be added
 * @param array  $params    The structured parameter list
 *
 * @return bool|CRM_Utils_Error
 * @access public
 */
function _civicrm_add_formatted_param(&$values, &$params) {
  /* Crawl through the possible classes: 
     * Contact 
     *      Individual 
     *      Household
     *      Organization
     *          Location 
     *              Address 
     *              Email 
     *              Phone 
     *              IM 
     *      Note
     *      Custom 
     */



  /* Cache the various object fields */


  static $fields = NULL;

  if ($fields == NULL) {
    $fields = array();
  }

  //first add core contact values since for other Civi modules they are not added
  require_once 'CRM/Contact/BAO/Contact.php';
  $contactFields = CRM_Contact_DAO_Contact::fields();
  _civicrm_store_values($contactFields, $values, $params);

  if (isset($values['contact_type'])) {
    /* we're an individual/household/org property */



    $fields[$values['contact_type']] = CRM_Contact_DAO_Contact::fields();

    _civicrm_store_values($fields[$values['contact_type']], $values, $params);
    return TRUE;
  }

  if (isset($values['individual_prefix'])) {
    if (CRM_Utils_Array::value('prefix_id', $params)) {
      $prefixes         = array();
      $prefixes         = CRM_Core_PseudoConstant::individualPrefix();
      $params['prefix'] = $prefixes[$params['prefix_id']];
    }
    else {
      $params['prefix'] = $values['individual_prefix'];
    }
    return TRUE;
  }

  if (isset($values['individual_suffix'])) {
    if (CRM_Utils_Array::value('suffix_id', $params)) {
      $suffixes         = array();
      $suffixes         = CRM_Core_PseudoConstant::individualSuffix();
      $params['suffix'] = $suffixes[$params['suffix_id']];
    }
    else {
      $params['suffix'] = $values['individual_suffix'];
    }
    return TRUE;
  }

  //CRM-4575
  if (isset($values['email_greeting'])) {
    if (CRM_Utils_Array::value('email_greeting_id', $params)) {
      $emailGreetings = array();
      $emailGreetingFilter = array('contact_type' => CRM_Utils_Array::value('contact_type', $params),
        'greeting_type' => 'email_greeting',
      );
      $emailGreetings = CRM_Core_PseudoConstant::greeting($emailGreetingFilter);
      $params['email_greeting'] = $emailGreetings[$params['email_greeting_id']];
    }
    else {
      $params['email_greeting'] = $values['email_greeting'];
    }

    return TRUE;
  }

  if (isset($values['postal_greeting'])) {
    if (CRM_Utils_Array::value('postal_greeting_id', $params)) {
      $postalGreetings = array();
      $postalGreetingFilter = array('contact_type' => CRM_Utils_Array::value('contact_type', $params),
        'greeting_type' => 'postal_greeting',
      );
      $postalGreetings = CRM_Core_PseudoConstant::greeting($postalGreetingFilter);
      $params['postal_greeting'] = $postalGreetings[$params['postal_greeting_id']];
    }
    else {
      $params['postal_greeting'] = $values['postal_greeting'];
    }
    return TRUE;
  }

  if (isset($values['addressee'])) {
    if (CRM_Utils_Array::value('addressee_id', $params)) {
      $addressee = array();
      $addresseeFilter = array('contact_type' => CRM_Utils_Array::value('contact_type', $params),
        'greeting_type' => 'addressee',
      );
      $addressee = CRM_Core_PseudoConstant::addressee($addresseeFilter);
      $params['addressee'] = $addressee[$params['addressee_id']];
    }
    else {
      $params['addressee'] = $values['addressee'];
    }
    return TRUE;
  }

  if (isset($values['gender'])) {
    if (CRM_Utils_Array::value('gender_id', $params)) {
      $genders          = array();
      $genders          = CRM_Core_PseudoConstant::gender();
      $params['gender'] = $genders[$params['gender_id']];
    }
    else {
      $params['gender'] = $values['gender'];
    }
    return TRUE;
  }

  if (isset($values['preferred_communication_method'])) {
    $comm      = array();
    $preffComm = array();
    $pcm       = array();
    $pcm       = array_change_key_case(array_flip(CRM_Core_PseudoConstant::pcm()), CASE_LOWER);

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

  //format the website params.
  if (CRM_Utils_Array::value('url', $values)) {
    static $websiteFields;
    if (!is_array($websiteFields)) {
      require_once 'CRM/Core/DAO/Website.php';
      $websiteFields = CRM_Core_DAO_Website::fields();
    }
    if (!array_key_exists('website', $params) ||
      !is_array($params['website'])
    ) {
      $params['website'] = array();
    }

    $websiteCount = count($params['website']);
    _civicrm_store_values($websiteFields, $values,
      $params['website'][++$websiteCount]
    );

    return TRUE;
  }

  // get the formatted location blocks into params - w/ 3.0 format, CRM-4605
  if (CRM_Utils_Array::value('location_type_id', $values)) {
    _civicrm_add_formatted_location_blocks($values, $params);
    return TRUE;
  }

  if (isset($values['note'])) {
    /* add a note field */


    if (!isset($params['note'])) {
      $params['note'] = array();
    }
    $noteBlock = count($params['note']) + 1;

    $params['note'][$noteBlock] = array();
    if (!isset($fields['Note'])) {
      $fields['Note'] = CRM_Core_DAO_Note::fields();
    }

    // get the current logged in civicrm user
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    if ($userID) {
      $values['contact_id'] = $userID;
    }

    _civicrm_store_values($fields['Note'], $values, $params['note'][$noteBlock]);

    return TRUE;
  }

  /* Check for custom field values */


  if (!CRM_Utils_Array::value('custom', $fields)) {
    $fields['custom'] = CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $values));
  }

  foreach ($values as $key => $value) {
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      /* check if it's a valid custom field id */


      if (!array_key_exists($customFieldID, $fields['custom'])) {
        return civicrm_create_error('Invalid custom field ID');
      }
      else {
        $params[$key] = $value;
      }
    }
  }
}

/**
 * This function format location blocks w/ v3.0 format.
 *
 * @param array  $values    The variable(s) to be added
 * @param array  $params    The structured parameter list
 *
 * @return bool
 * @access public
 */
function _civicrm_add_formatted_location_blocks(&$values, &$params) {
  static $fields = NULL;
  if ($fields == NULL) {
    $fields = array();
  }

  foreach (array(
    'Phone', 'Email', 'IM', 'OpenID') as $block) {
    $name = strtolower($block);
    if (!array_key_exists($name, $values)) {
      continue;
    }

    // block present in value array.
    if (!array_key_exists($name, $params) || !is_array($params[$name])) {
      $params[$name] = array();
    }

    if (!array_key_exists($block, $fields)) {
      require_once (str_replace('_', DIRECTORY_SEPARATOR, "CRM_Core_DAO_" . $block) . ".php");
      eval('$fields[$block] =& CRM_Core_DAO_' . $block . '::fields( );');
    }

    $blockCnt = count($params[$name]);

    // copy value to dao field name.
    if ($name == 'im') {
      $values['name'] = $values[$name];
    }

    _civicrm_store_values($fields[$block], $values,
      $params[$name][++$blockCnt]
    );

    if (!CRM_Utils_Array::value('id', $params) && ($blockCnt == 1)) {
      $params[$name][$blockCnt]['is_primary'] = TRUE;
    }

    // we only process single block at a time.
    return TRUE;
  }

  // handle address fields.
  if (!array_key_exists('address', $params) || !is_array($params['address'])) {
    $params['address'] = array();
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
    require_once 'CRM/Core/DAO/Address.php';
    $fields['Address'] = CRM_Core_DAO_Address::fields();
  }
  _civicrm_store_values($fields['Address'], $values, $params['address'][$addressCnt]);

  $addressFields = array(
    'county', 'country', 'state_province',
    'supplemental_address_1', 'supplemental_address_2',
    'StateProvince.name',
  );

  foreach ($addressFields as $field) {
    if (array_key_exists($field, $values)) {
      if (!array_key_exists('address', $params)) {
        $params['address'] = array();
      }
      $params['address'][$addressCnt][$field] = $values[$field];
    }
  }
  //Handle Address Custom data
  $fields['address_custom'] = CRM_Core_BAO_CustomField::getFields('Address');
  foreach ($values as $key => $value) {
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      /* check if it's a valid custom field id */


      if (array_key_exists($customFieldID, $fields['address_custom'])) {
        $type = $fields['address_custom'][$customFieldID]['html_type'];
        _civicrm_add_custom_formatted_param($customFieldID, $key, $value, $params['address'][$addressCnt], $type);
      }
      else {
        return civicrm_create_error('Invalid custom field ID');
      }
    }
  }

  if ($addressCnt == 1) {

    $params['address'][$addressCnt]['is_primary'] = TRUE;
  }

  return TRUE;
}

/**
 * Check a formatted parameter list for required fields.  Note that this
 * function does no validation or dupe checking.
 *
 * @param array $params  Structured parameter list (as in crm_format_params)
 *
 * @return bool|CRM_core_Error  Parameter list has all required fields
 * @access public
 */
function _civicrm_required_formatted_contact(&$params) {

  if (!isset($params['contact_type'])) {
    return civicrm_create_error('No contact type specified');
  }

  switch ($params['contact_type']) {
    case 'Individual':
      if (isset($params['first_name']) && isset($params['last_name'])) {
        return civicrm_create_success(TRUE);
      }

      if (array_key_exists('email', $params) &&
        is_array($params['email']) &&
        !CRM_Utils_System::isNull($params['email'])
      ) {
        return civicrm_create_success(TRUE);
      }
      break;

    case 'Household':
      if (isset($params['household_name'])) {
        return civicrm_create_success(TRUE);
      }
      break;

    case 'Organization':
      if (isset($params['organization_name'])) {
        return civicrm_create_success(TRUE);
      }
      break;

    default:
      return civicrm_create_error('Invalid Contact Type: ' . $params['contact_type']);
  }

  return civicrm_create_error('Missing required fields');
}

/**
 *
 * @param array $params
 * @param int   $dedupeRuleGroupID - the dedupe rule ID to use if present
 *
 */
function _civicrm_duplicate_formatted_contact(&$params,
  $dedupeRuleGroupID = NULL
) {
  $id = CRM_Utils_Array::value('id', $params);
  $externalId = CRM_Utils_Array::value('external_identifier', $params);
  if ($id || $externalId) {
    $contact = new CRM_Contact_DAO_Contact();

    $contact->id = $id;
    $contact->external_identifier = $externalId;

    if ($contact->find(TRUE)) {
      if ($params['contact_type'] != $contact->contact_type) {
        return civicrm_create_error("Mismatched contact IDs OR Mismatched contact Types");
      }

      $error = CRM_Core_Error::createError("Found matching contacts: $contact->id",
        CRM_Core_Error::DUPLICATE_CONTACT,
        'Fatal', $contact->id
      );
      return civicrm_create_error($error->pop());
    }
  }
  else {
    require_once 'CRM/Dedupe/Finder.php';
    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, $params['contact_type']);
    $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams,
      $params['contact_type'],
      'Strict',
      array(),
      $dedupeRuleGroupID
    );

    if (!empty($ids)) {
      $ids = implode(',', $ids);
      $error = CRM_Core_Error::createError("Found matching contacts: $ids",
        CRM_Core_Error::DUPLICATE_CONTACT,
        'Fatal', $ids
      );
      return civicrm_create_error($error->pop());
    }
  }
  return civicrm_create_success(TRUE);
}

/**
 * Validate a formatted contact parameter list.
 *
 * @param array $params  Structured parameter list (as in crm_format_params)
 *
 * @return bool|CRM_Core_Error
 * @access public
 */
function _civicrm_validate_formatted_contact(&$params) {
  /* Look for offending email addresses */


  if (array_key_exists('email', $params)) {
    foreach ($params['email'] as $count => $values) {
      if (!is_array($values)) {
        continue;
      }
      if ($email = CRM_Utils_Array::value('email', $values)) {
        //validate each email
        if (!CRM_Utils_Rule::email($email)) {
          return civicrm_create_error('No valid email address');
        }

        //check for loc type id.
        if (!CRM_Utils_Array::value('location_type_id', $values)) {
          return civicrm_create_error('Location Type Id missing.');
        }
      }
    }
  }

  /* Validate custom data fields */


  if (array_key_exists('custom', $params) && is_array($params['custom'])) {
    foreach ($params['custom'] as $key => $custom) {
      if (is_array($custom)) {
        $valid = CRM_Core_BAO_CustomValue::typecheck(
          $custom['type'], $custom['value']
        );
        if (!$valid) {
          return civicrm_create_error('Invalid value for custom field \'' .
            $custom['name'] . '\''
          );
        }
        if ($custom['type'] == 'Date') {
          $params['custom'][$key]['value'] = str_replace('-', '', $params['custom'][$key]['value']);
        }
      }
    }
  }

  return civicrm_create_success(TRUE);
}

/**
 *
 * @param array $params
 * @param array $values
 * @param string $extends entity that this custom field extends (e.g. contribution, event, contact)
 * @param string $entityId ID of entity per $extends
 */
function _civicrm_custom_format_params(&$params, &$values, $extends, $entityId = NULL) {
  $values['custom'] = array();

  require_once 'CRM/Core/BAO/CustomField.php';
  foreach ($params as $key => $value) {
    list($customFieldID, $customValueID) = CRM_Core_BAO_CustomField::getKeyID($key, TRUE);
    if ($customFieldID) {
      CRM_Core_BAO_CustomField::formatCustomField($customFieldID, $values['custom'],
        $value, $extends, $customValueID, $entityId
      );
    }
  }
}

/**
 * This function ensures that we have the right input parameters
 *
 * We also need to make sure we run all the form rules on the params list
 * to ensure that the params are valid
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new history.
 *
 *
 * @return bool true if success false otherwise
 * @access public
 */
function _civicrm_check_required_fields(&$params, $daoName, $throwException = FALSE) {
  if (isset($params['extends'])) {
    if (($params['extends'] == 'Activity' ||
        $params['extends'] == 'Phonecall' ||
        $params['extends'] == 'Meeting' ||
        $params['extends'] == 'Group' ||
        $params['extends'] == 'Contribution'
      ) &&
      ($params['style'] == 'Tab')
    ) {
      return civicrm_create_error(ts("Can not create Custom Group in Tab for " . $params['extends']));
    }
  }

  require_once (str_replace('_', DIRECTORY_SEPARATOR, $daoName) . ".php");

  $dao = new $daoName();
  $fields = $dao->fields();

  $missing = array();
  foreach ($fields as $k => $v) {
    if ($k == 'id') {
      continue;
    }

    if (isset($v['required'])) {
      if ($v['required'] && !(isset($params[$k]))) {
        $missing[] = $k;
      }
    }
  }

  if (!empty($missing)) {
    if ($throwException) {
      throw new Exception("Required fields " . implode(',', $missing) . " for $daoName are not found");
    }
    return civicrm_create_error(ts("Required fields " . implode(',', $missing) . " for $daoName are not found"));
  }

  return TRUE;
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *
 * @param array  $create       Is the formatted Values array going to
 *                             be used for CRM_Event_BAO_Participant:create()
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_participant_formatted_param(&$params, &$values, $create = FALSE) {
  $fields = CRM_Event_DAO_Participant::fields();
  _civicrm_store_values($fields, $params, $values);

  require_once 'CRM/Core/OptionGroup.php';
  $customFields = CRM_Core_BAO_CustomField::getFields('Participant');

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    //Handling Custom Data
    _civicrm_generic_handle_custom_data($key, $value, $values, $customFields);

    switch ($key) {
      case 'participant_contact_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }
        $values['contact_id'] = $values['participant_contact_id'];
        unset($values['participant_contact_id']);
        break;

      case 'participant_register_date':
        if (!CRM_Utils_Rule::date($value)) {
          return civicrm_create_error("$key not a valid date: $value");
        }
        break;

      case 'event_title':
        $id = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Event", $value, 'id', 'title');
        $values['event_id'] = $id;
        break;

      case 'event_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_create_error("Event ID is not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_event WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_create_error("Invalid Event ID: There is no event record with event_id = $value.");
        }
        break;

      case 'participant_status':
        $values['status_id'] = $values['participant_status_id'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantStatusType', $value, 'id', 'label');
        break;

      case 'participant_status_id':
        if ((int) $value) {
          $values['status_id'] = $values[$key] = $value;
        }
        else {
          $id = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantStatusType', $value, 'id', 'label');
          $values['status_id'] = $values[$key] = $id;
        }
        break;

      case 'participant_role_id':
      case 'participant_role':
        $role = CRM_Event_PseudoConstant::participantRole();
        $participantRoles = explode(",", $value);
        foreach ($participantRoles as $k => $v) {
          $v = trim($v);
          if ($key == 'participant_role') {
            $participantRoles[$k] = CRM_Utils_Array::key($v, $role);
          }
          else {
            $participantRoles[$k] = $v;
          }
        }
        require_once 'CRM/Core/DAO.php';
        $values['role_id'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $participantRoles);
        unset($values[$key]);
        break;

      default:
        break;
    }
  }

  if (array_key_exists('participant_note', $params)) {
    $values['participant_note'] = $params['participant_note'];
  }

  if ($create) {
    // CRM_Event_BAO_Participant::create() handles register_date,
    // status_id and source. So, if $values contains
    // participant_register_date, participant_status_id or participant_source,
    // convert it to register_date, status_id or source
    $changes = array(
      'participant_register_date' => 'register_date',
      'participant_source' => 'source',
      'participant_status_id' => 'status_id',
      'participant_role_id' => 'role_id',
      'participant_fee_level' => 'fee_level',
      'participant_fee_amount' => 'fee_amount',
      'participant_id' => 'id',
    );

    foreach ($changes as $orgVal => $changeVal) {
      if (isset($values[$orgVal])) {
        $values[$changeVal] = $values[$orgVal];
        unset($values[$orgVal]);
      }
    }
  }

  return NULL;
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *                            '
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_contribute_formatted_param(&$params, &$values, $create = FALSE) {
  // copy all the contribution fields as is

  $fields = CRM_Contribute_DAO_Contribution::fields();

  _civicrm_store_values($fields, $params, $values);

  require_once 'CRM/Core/OptionGroup.php';
  $customFields = CRM_Core_BAO_CustomField::getFields('Contribution');

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    //Handling Custom Data
    _civicrm_generic_handle_custom_data($key, $value, $values, $customFields);

    switch ($key) {
      case 'contribution_contact_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }

        $values['contact_id'] = $values['contribution_contact_id'];
        unset($values['contribution_contact_id']);
        break;

      case 'contact_type':
        //import contribution record according to select contact type
        require_once 'CRM/Contact/DAO/Contact.php';
        $contactType = new CRM_Contact_DAO_Contact();
        //when insert mode check contact id or external identifire
        if ($params['contribution_contact_id'] || $params['external_identifier']) {
          if ($params['contribution_contact_id']) {
            $contactType->id = $params['contribution_contact_id'];
          }
          elseif ($params['external_identifier']) {
            $contactType->external_identifier = $params['external_identifier'];
          }
          if ($contactType->find(TRUE)) {
            if ($params['contact_type'] != $contactType->contact_type) {
              return civicrm_create_error("Contact Type is wrong: $contactType->contact_type");
            }
          }
        }
        elseif ($params['contribution_id'] || $params['trxn_id'] || $params['invoice_id']) {
          //when update mode check contribution id or trxn id or
          //invoice id
          $contactId = new CRM_Contribute_DAO_Contribution();
          if ($params['contribution_id']) {
            $contactId->id = $params['contribution_id'];
          }
          elseif ($params['trxn_id']) {
            $contactId->trxn_id = $params['trxn_id'];
          }
          elseif ($params['invoice_id']) {
            $contactId->invoice_id = $params['invoice_id'];
          }
          if ($contactId->find(TRUE)) {
            $contactType->id = $contactId->contact_id;
            if ($contactType->find(TRUE)) {
              if ($params['contact_type'] != $contactType->contact_type) {
                return civicrm_create_error("Contact Type is wrong: $contactType->contact_type");
              }
            }
          }
        }
        break;

      case 'receive_date':
      case 'cancel_date':
      case 'receipt_date':
      case 'thankyou_date':
        if (!CRM_Utils_Rule::date($value)) {
          return civicrm_create_error("$key not a valid date: $value");
        }
        break;

      case 'non_deductible_amount':
      case 'total_amount':
      case 'fee_amount':
      case 'net_amount':
        if (!CRM_Utils_Rule::money($value)) {
          return civicrm_create_error("$key not a valid amount: $value");
        }
        break;

      case 'currency':
        if (!CRM_Utils_Rule::currencyCode($value)) {
          return civicrm_create_error("currency not a valid code: $value");
        }
        break;

      case 'financial_type':
        require_once 'CRM/Contribute/PseudoConstant.php';
            $contriTypes = CRM_Contribute_PseudoConstant::financialType( );
        foreach ($contriTypes as $val => $type) {
          if (strtolower($value) == strtolower($type)) {
                    $values['financial_type_id'] = $val;
            break;
          }
        }
        if (!CRM_Utils_Array::value('financial_type_id', $values)) {
          return civicrm_create_error("Financial Type is not valid: $value");
        }
        break;

      case 'payment_instrument':
        require_once 'CRM/Core/OptionGroup.php';
        $values['payment_instrument_id'] = CRM_Core_OptionGroup::getValue('payment_instrument', $value);
        if (!CRM_Utils_Array::value('payment_instrument_id', $values)) {
          return civicrm_create_error("Payment Instrument is not valid: $value");
        }
        break;

      case 'contribution_status_id':
        require_once 'CRM/Core/OptionGroup.php';
        if (!$values['contribution_status_id'] = CRM_Core_OptionGroup::getValue('contribution_status', $value)) {
          return civicrm_create_error("Contribution Status is not valid: $value");
        }
        break;

      case 'honor_type_id':
        require_once 'CRM/Core/OptionGroup.php';
        $values['honor_type_id'] = CRM_Core_OptionGroup::getValue('honor_type', $value);
        if (!CRM_Utils_Array::value('honor_type_id', $values)) {
          return civicrm_create_error("Honor Type is not valid: $value");
        }
        break;

      case 'soft_credit':
        //import contribution record according to select contact type

        // validate contact id and external identifier.
        $contactId = CRM_Utils_Array::value('contact_id', $params['soft_credit']);
        $externalId = CRM_Utils_Array::value('external_identifier', $params['soft_credit']);
        if ($contactId || $externalId) {
          require_once 'CRM/Contact/DAO/Contact.php';
          $contact = new CRM_Contact_DAO_Contact();
          $contact->id = $contactId;
          $contact->external_identifier = $externalId;

          $errorMsg = NULL;
          if (!$contact->find(TRUE)) {
            $errorMsg = ts("No match found for specified Soft Credit contact data. Row was skipped.");
          }
          elseif ($params['contact_type'] != $contact->contact_type) {
            $errorMsg = ts("Soft Credit Contact Type is wrong: %1", array(1 => $contact->contact_type));
          }

          if ($errorMsg) {
            return civicrm_create_error($errorMsg, 'soft_credit');
          }

          // finally get soft credit contact id.
          $values['soft_credit_to'] = $contact->id;
        }
        else {
          // get the contact id from dupicate contact rule, if more than one contact is returned
          // we should return error, since current interface allows only one-one mapping

          $softParams = $params['soft_credit'];
          $softParams['contact_type'] = $params['contact_type'];

          $error = _civicrm_duplicate_formatted_contact($softParams);

          if (isset($error['error_message']['params'][0])) {
            $matchedIDs = explode(',', $error['error_message']['params'][0]);

            // check if only one contact is found
            if (count($matchedIDs) > 1) {
              return civicrm_create_error($error['error_message']['message'], 'soft_credit');
            }
            else {
              $values['soft_credit_to'] = $matchedIDs[0];
            }
          }
          else {
            return civicrm_create_error('No match found for specified Soft Credit contact data. Row was skipped.', 'soft_credit');
          }
        }
        break;

      case 'pledge_payment':
      case 'pledge_id':

        //giving respect to pledge_payment flag.
        if (!CRM_Utils_Array::value('pledge_payment', $params)) {
          continue;
        }

        //get total amount of from import fields
        $totalAmount = CRM_Utils_Array::value('total_amount', $params);

        $onDuplicate = CRM_Utils_Array::value('onDuplicate', $params);

        //we need to get contact id $contributionContactID to
        //retrieve pledge details as well as to validate pledge ID

        //first need to check for update mode
        if ($onDuplicate == CRM_Contribute_Import_Parser::DUPLICATE_UPDATE &&
          ($params['contribution_id'] || $params['trxn_id'] || $params['invoice_id'])
        ) {
          $contribution = new CRM_Contribute_DAO_Contribution();
          if ($params['contribution_id']) {
            $contribution->id = $params['contribution_id'];
          }
          elseif ($params['trxn_id']) {
            $contribution->trxn_id = $params['trxn_id'];
          }
          elseif ($params['invoice_id']) {
            $contribution->invoice_id = $params['invoice_id'];
          }

          if ($contribution->find(TRUE)) {
            $contributionContactID = $contribution->contact_id;
            if (!$totalAmount) {
              $totalAmount = $contribution->total_amount;
            }
          }
          else {
            return civicrm_create_error('No match found for specified contact in contribution data. Row was skipped.', 'pledge_payment');
          }
        }
        else {
          // first get the contact id for given contribution record.
          if (CRM_Utils_Array::value('contribution_contact_id', $params)) {
            $contributionContactID = $params['contribution_contact_id'];
          }
          elseif (CRM_Utils_Array::value('external_identifier', $params)) {
            require_once 'CRM/Contact/DAO/Contact.php';
            $contact = new CRM_Contact_DAO_Contact();
            $contact->external_identifier = $params['external_identifier'];
            if ($contact->find(TRUE)) {
              $contributionContactID = $params['contribution_contact_id'] = $values['contribution_contact_id'] = $contact->id;
            }
            else {
              return civicrm_create_error('No match found for specified contact in contribution data. Row was skipped.', 'pledge_payment');
            }
          }
          else {
            // we  need to get contribution contact using de dupe
            $error = civicrm_check_contact_dedupe($params);

            if (isset($error['error_message']['params'][0])) {
              $matchedIDs = explode(',', $error['error_message']['params'][0]);

              // check if only one contact is found
              if (count($matchedIDs) > 1) {
                return civicrm_create_error($error['error_message']['message'], 'pledge_payment');
              }
              else {
                $contributionContactID = $params['contribution_contact_id'] = $values['contribution_contact_id'] = $matchedIDs[0];
              }
            }
            else {
              return civicrm_create_error('No match found for specified contact in contribution data. Row was skipped.', 'pledge_payment');
            }
          }
        }

        if (CRM_Utils_Array::value('pledge_id', $params)) {
          if (CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge', $params['pledge_id'], 'contact_id') != $contributionContactID) {
            return civicrm_create_error('Invalid Pledge ID provided. Contribution row was skipped.', 'pledge_payment');
          }
          $values['pledge_id'] = $params['pledge_id'];
        }
        else {
          //check if there are any pledge related to this contact, with payments pending or in progress
          require_once 'CRM/Pledge/BAO/Pledge.php';
          $pledgeDetails = CRM_Pledge_BAO_Pledge::getContactPledges($contributionContactID);

          if (empty($pledgeDetails)) {
            return civicrm_create_error('No open pledges found for this contact. Contribution row was skipped.', 'pledge_payment');
          }
          elseif (count($pledgeDetails) > 1) {
            return civicrm_create_error('This contact has more than one open pledge. Unable to determine which pledge to apply the contribution to. Contribution row was skipped.', 'pledge_payment');
          }

          // this mean we have only one pending / in progress pledge
          $values['pledge_id'] = $pledgeDetails[0];
        }

        //we need to check if oldest payment amount equal to contribution amount
        require_once 'CRM/Pledge/BAO/PledgePayment.php';
        $pledgePaymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($values['pledge_id']);

        if ($pledgePaymentDetails['amount'] == $totalAmount) {
          $values['pledge_payment_id'] = $pledgePaymentDetails['id'];
        }
        else {
          return civicrm_create_error('Contribution and Pledge Payment amount mismatch for this record. Contribution row was skipped.', 'pledge_payment');
        }
        break;

      default:
        break;
    }
  }

  if (array_key_exists('note', $params)) {
    $values['note'] = $params['note'];
  }

  if ($create) {
    // CRM_Contribute_BAO_Contribution::add() handles contribution_source
    // So, if $values contains contribution_source, convert it to source
    $changes = array('contribution_source' => 'source');

    foreach ($changes as $orgVal => $changeVal) {
      if (isset($values[$orgVal])) {
        $values[$changeVal] = $values[$orgVal];
        unset($values[$orgVal]);
      }
    }
  }

  return NULL;
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @todo shouldn't it be moved to Membership.php?
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *
 * @param array  $create       Is the formatted Values array going to
 *                             be used for CRM_Member_BAO_Membership:create()
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_membership_formatted_param(&$params, &$values, $create = FALSE) {
  require_once "CRM/Member/DAO/Membership.php";
  $fields = CRM_Member_DAO_Membership::fields();

  _civicrm_store_values($fields, $params, $values);

  require_once 'CRM/Core/OptionGroup.php';
  $customFields = CRM_Core_BAO_CustomField::getFields('Membership');

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    //Handling Custom Data
    _civicrm_generic_handle_custom_data($key, $value, $values, $customFields);

    switch ($key) {
      case 'membership_contact_id':
        if (!CRM_Utils_Rule::integer($value)) {
          return civicrm_create_error("contact_id not valid: $value");
        }
        $dao     = new CRM_Core_DAO();
        $qParams = array();
        $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
          $qParams
        );
        if (!$svq) {
          return civicrm_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
        }
        $values['contact_id'] = $values['membership_contact_id'];
        unset($values['membership_contact_id']);
        break;

      case 'join_date':
      case 'membership_start_date':
      case 'membership_end_date':
        if (!CRM_Utils_Rule::date($value)) {
          return civicrm_create_error("$key not a valid date: $value");
        }
        break;

      case 'membership_type_id':
        $id = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_MembershipType", $value, 'id', 'name');
        $values[$key] = $id;
        break;

      case 'status_id':
        $id = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_MembershipStatus", $value, 'id', 'name');
        $values[$key] = $id;
        break;

      case 'member_is_test':
        $values['is_test'] = CRM_Utils_Array::value($key, $params, FALSE);
        unset($values['member_is_test']);
        break;

      default:
        break;
    }
  }

  if ($create) {
    // CRM_Member_BAO_Membership::create() handles membership_start_date,
    // membership_end_date and membership_source. So, if $values contains
    // membership_start_date, membership_end_date  or membership_source,
    // convert it to start_date, end_date or source
    $changes = array(
      'membership_start_date' => 'start_date',
      'membership_end_date' => 'end_date',
      'membership_source' => 'source',
    );

    foreach ($changes as $orgVal => $changeVal) {
      if (isset($values[$orgVal])) {
        $values[$changeVal] = $values[$orgVal];
        unset($values[$orgVal]);
      }
    }
  }

  return NULL;
}

/**
 * take the input parameter list as specified in the data model and
 * convert it into the same format that we use in QF and BAO object
 *
 * @param array  $params       Associative array of property name/value
 *                             pairs to insert in new contact.
 * @param array  $values       The reformatted properties that we can use internally
 *
 * @param array  $create       Is the formatted Values array going to
 *                             be used for CRM_Activity_BAO_Activity::create()
 *
 * @return array|CRM_Error
 * @access public
 */
function _civicrm_activity_formatted_param(&$params, &$values, $create = FALSE) {
  $fields = CRM_Activity_DAO_Activity::fields();
  _civicrm_store_values($fields, $params, $values);

  require_once 'CRM/Core/OptionGroup.php';
  $customFields = CRM_Core_BAO_CustomField::getFields('Activity');

  foreach ($params as $key => $value) {
    // ignore empty values or empty arrays etc
    if (CRM_Utils_System::isNull($value)) {
      continue;
    }

    //Handling Custom Data
    _civicrm_generic_handle_custom_data($key, $value, $values, $customFields);

    if ($key == 'target_contact_id') {
      if (!CRM_Utils_Rule::integer($value)) {
        return civicrm_create_error("contact_id not valid: $value");
      }
      $contactID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value");
      if (!$contactID) {
        return civicrm_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
      }
    }
  }
  return NULL;
}

/**
 *  Function to check duplicate contacts based on de-deupe parameters
 */
function civicrm_check_contact_dedupe(&$params) {
  static $cIndieFields = NULL;
  static $defaultLocationId = NULL;

  $contactType = $params['contact_type'];
  if ($cIndieFields == NULL) {
    require_once 'CRM/Contact/BAO/Contact.php';
    $cTempIndieFields = CRM_Contact_BAO_Contact::importableFields($contactType);
    $cIndieFields = $cTempIndieFields;

    require_once "CRM/Core/BAO/LocationType.php";
    $defaultLocation = CRM_Core_BAO_LocationType::getDefault();

    //set the value to default location id else set to 1
    if (!$defaultLocationId = (int)$defaultLocation->id) {
      $defaultLocationId = 1;
    }
  }

  require_once 'CRM/Contact/BAO/Query.php';
  $locationFields = CRM_Contact_BAO_Query::$_locationSpecificFields;

  $contactFormatted = array();
  foreach ($params as $key => $field) {
    if ($field == NULL || $field === '') {
      continue;
    }
    if (is_array($field)) {
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
          _civicrm_add_formatted_param($value, $contactFormatted);
        }
      }
      continue;
    }

    $value = array($key => $field);

    // check if location related field, then we need to add primary location type
    if (in_array($key, $locationFields)) {
      $value['location_type_id'] = $defaultLocationId;
    }
    elseif (array_key_exists($key, $cIndieFields)) {
      $value['contact_type'] = $contactType;
    }

    _civicrm_add_formatted_param($value, $contactFormatted);
  }

  $contactFormatted['contact_type'] = $contactType;

  return _civicrm_duplicate_formatted_contact($contactFormatted);
}

/**
 * Check permissions for a given API call.
 *
 * @param $api string    API method being called
 * @param $params array  params of the API call
 * @param $throw bool    whether to throw exception instead of returning false
 *
 * @return bool whether the current API user has the permission to make the call
 */
function civicrm_api_check_permission($api, $params, $throw = FALSE) {
  // return early if we’re to skip the permission check or if it’s unset
  if (!isset($params['check_permissions']) or !$params['check_permissions']) {
    return TRUE;
  }

  require_once 'CRM/Core/Permission.php';
  $requirements = array(
    'civicrm_contact_create' => array('access CiviCRM', 'add contacts'),
    'civicrm_contact_update' => array('access CiviCRM', 'add contacts'),
    'civicrm_event_create' => array('access CiviEvent'),
  );
  foreach ($requirements[$api] as $perm) {
    if (!CRM_Core_Permission::check($perm)) {
      if ($throw) {
        throw new Exception("API permission check failed for $api call; missing permission: $perm.");
      }
      else {
        return FALSE;
      }
    }
  }
  return TRUE;
}


// at some point we should unify this with
// _civicrm_custom_format_params
// seems like there are some differences that i dont understand, so taking the first
// step in a cleanup: CRM-7337
function _civicrm_generic_handle_custom_data($key, $value, &$values, &$customFields) {

  //Handling Custom Data
  if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
    $values[$key] = $value;
    $type = $customFields[$customFieldID]['html_type'];
    if ($type == 'CheckBox' || $type == 'Multi-Select') {
      $mulValues    = explode(',', $value);
      $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
      $values[$key] = array();
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
        $val   = CRM_Utils_Array::value('value', $customValue);
        $label = CRM_Utils_Array::value('label', $customValue);
        $label = strtolower($label);
        $value = strtolower(trim($value));
        if (($value == $label) || ($value == strtolower($val))) {
          $values[$key] = $val;
        }
      }
    }
  }
}

function _civicrm_add_custom_formatted_param($customFieldID, $key, $field, &$formatted, $type) {
  require_once 'CRM/Core/BAO/CustomOption.php';
  require_once 'CRM/Core/PseudoConstant.php';

  if (empty($type)) {
    return;
  }
  switch ($type) {
    case 'Text':
      $formatted[$key] = $field;
      break;

    case 'CheckBox':
    case 'AdvMulti-Select':
    case 'Multi-Select':

      $mulValues       = explode(',', $field);
      $customOption    = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
      $formatted[$key] = array();
      foreach ($mulValues as $v1) {
        foreach ($customOption as $v2) {
          if ((strtolower($v2['label']) == strtolower(trim($v1))) ||
            (strtolower($v2['value']) == strtolower(trim($v1)))
          ) {
            if ($type == 'CheckBox') {
              $formatted[$key][$v2['value']] = 1;
            }
            else {
              $formatted[$key][] = $v2['value'];
            }
          }
        }
      }
      break;

    case 'Select':
    case 'Radio':

      $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
      foreach ($customOption as $v2) {
        if ((strtolower($v2['label']) == strtolower(trim($field))) ||
          (strtolower($v2['value']) == strtolower(trim($field)))
        ) {
          $formatted[$key] = $v2['value'];
        }
      }
      break;

    case 'Multi-Select State/Province':

      $mulValues       = explode(',', $field);
      $stateAbbr       = CRM_Core_PseudoConstant::stateProvinceAbbreviation();
      $stateName       = CRM_Core_PseudoConstant::stateProvince();
      $formatted[$key] = $stateValues = array();
      foreach ($mulValues as $values) {
        if ($val = CRM_Utils_Array::key($values, $stateAbbr)) {
          $formatted[$key][] = $val;
        }
        elseif ($val = CRM_Utils_Array::key($values, $stateName)) {
          $formatted[$key][] = $val;
        }
      }
      break;

    case 'Multi-Select Country':

      $config          = CRM_Core_Config::singleton();
      $limitCodes      = $config->countryLimit();
      $mulValues       = explode(',', $field);
      $formatted[$key] = array();
      CRM_Core_PseudoConstant::populate($countryNames, 'CRM_Core_DAO_Country', TRUE, 'name', 'is_active');
      CRM_Core_PseudoConstant::populate($countryIsoCodes, 'CRM_Core_DAO_Country', TRUE, 'iso_code');
      foreach ($mulValues as $values) {
        if ($val = CRM_Utils_Array::key($values, $countryNames)) {
          $formatted[$key][] = $val;
        }
        elseif ($val = CRM_Utils_Array::key($values, $countryIsoCodes)) {
          $formatted[$key][] = $val;
        }
        elseif ($val = CRM_Utils_Array::key($values, $limitCodes)) {
          $formatted[$key][] = $val;
        }
      }
      break;
  }
}

