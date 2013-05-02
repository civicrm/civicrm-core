<?php
// $Id: Contact.php 45502 2013-02-08 13:32:55Z kurund $


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
 * new version of civicrm apis. See blog post at
 * http://civicrm.org/node/131
 * @todo Write sth
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Contact
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: Contact.php 45502 2013-02-08 13:32:55Z kurund $
 *
 */

/**
 * Include common API util functions
 */
require_once 'api/v2/utils.php';
require_once 'CRM/Contact/BAO/Contact.php';

/**
 * @todo Write sth
 *
 * @param  array   $params           (reference ) input parameters
 *
 * Allowed @params array keys are:
 * {@schema Contact/Contact.xml}
 * {@schema Core/Address.xml}}
 *
 * @return array (reference )        contact_id of created or updated contact
 *
 * @static void
 * @access public
 */
function civicrm_contact_create(&$params) {
  // call update and tell it to create a new contact
  _civicrm_initialize();
  $errorScope = CRM_Core_TemporaryErrorScope::useException();
  try {
    civicrm_api_check_permission(__FUNCTION__, $params, TRUE);
    $create_new = TRUE;
    return civicrm_contact_update($params, $create_new);
  }
  catch(Exception$e) {
    return civicrm_create_error($e->getMessage());
  }
}

/**
 * @todo Write sth
 * @todo Serious FIXMES in the code! File issues.
 */
function civicrm_contact_update(&$params, $create_new = FALSE) {
  _civicrm_initialize();
  try {
    civicrm_api_check_permission(__FUNCTION__, $params, TRUE);
  }
  catch(Exception$e) {
    return civicrm_create_error($e->getMessage());
  }
  require_once 'CRM/Utils/Array.php';
  $entityId = CRM_Utils_Array::value('contact_id', $params, NULL);
  if (!CRM_Utils_Array::value('contact_type', $params) &&
    $entityId
  ) {
    $params['contact_type'] = CRM_Contact_BAO_Contact::getContactType($entityId);
  }
  $dupeCheck = CRM_Utils_Array::value('dupe_check', $params, FALSE);
  $values = civicrm_contact_check_params($params, $dupeCheck);
  if ($values) {
    return $values;
  }

  if ($create_new) {
    // Make sure nothing is screwed up before we create a new contact
    if (!empty($entityId)) {
      return civicrm_create_error('Cannot create new contact when contact_id is present');
    }
    if (empty($params['contact_type'])) {
      return civicrm_create_error('Contact Type not specified');
    }

    // If we get here, we're ready to create a new contact
    if (($email = CRM_Utils_Array::value('email', $params)) && !is_array($params['email'])) {
      require_once 'CRM/Core/BAO/LocationType.php';
      $defLocType = CRM_Core_BAO_LocationType::getDefault();
      $params['email'] = array(
        1 => array('email' => $email,
          'is_primary' => 1,
          'location_type_id' => ($defLocType->id) ? $defLocType->id : 1,
        ),
      );
    }
  }

  if ($homeUrl = CRM_Utils_Array::value('home_url', $params)) {
    require_once 'CRM/Core/PseudoConstant.php';
    $websiteTypes = CRM_Core_PseudoConstant::websiteType();
    $params['website'] = array(1 => array('website_type_id' => key($websiteTypes),
        'url' => $homeUrl,
      ),
    );
  }
  // FIXME: Some legacy support cruft, should get rid of this in 3.1
  $change = array(
    'individual_prefix' => 'prefix',
    'prefix' => 'prefix_id',
    'individual_suffix' => 'suffix',
    'suffix' => 'suffix_id',
    'gender' => 'gender_id',
  );

  foreach ($change as $field => $changeAs) {
    if (array_key_exists($field, $params)) {
      $params[$changeAs] = $params[$field];
      unset($params[$field]);
    }
  }
  // End legacy support cruft

  if (isset($params['suffix_id']) &&
    !(is_numeric($params['suffix_id']))
  ) {
    $params['suffix_id'] = array_search($params['suffix_id'], CRM_Core_PseudoConstant::individualSuffix());
  }

  if (isset($params['prefix_id']) &&
    !(is_numeric($params['prefix_id']))
  ) {
    $params['prefix_id'] = array_search($params['prefix_id'], CRM_Core_PseudoConstant::individualPrefix());
  }

  if (isset($params['gender_id'])
    && !(is_numeric($params['gender_id']))
  ) {
    $params['gender_id'] = array_search($params['gender_id'], CRM_Core_PseudoConstant::gender());
  }

  $error = _civicrm_greeting_format_params($params);
  if (civicrm_error($error)) {
    return $error;
  }

  $values = array();

  if (!($csType = CRM_Utils_Array::value('contact_sub_type', $params)) &&
    $entityId
  ) {
    require_once 'CRM/Contact/BAO/Contact.php';
    $csType = CRM_Contact_BAO_Contact::getContactSubType($entityId);
  }

  $customValue = civicrm_contact_check_custom_params($params, $csType);

  if ($customValue) {
    return $customValue;
  }
  _civicrm_custom_format_params($params, $values, $params['contact_type'], $entityId);

  $params = array_merge($params, $values);

  $contact = &_civicrm_contact_update($params, $entityId);

  if (is_a($contact, 'CRM_Core_Error')) {
    return civicrm_create_error($contact->_errors[0]['message']);
  }
  else {
    $values = array();
    $values['contact_id'] = $contact->id;
    $values['is_error'] = 0;
  }

  return $values;
}

/**
 * Add or update a contact. If a dupe is found, check for
 * ignoreDupe flag to ignore or return error
 *
 * @deprecated deprecated since version 2.2.3; use civicrm_contact_create or civicrm_contact_update instead
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        contact_id of created or updated contact
 * @static void
 * @access public
 */
function &civicrm_contact_add(&$params) {
  _civicrm_initialize();

  $contactID = CRM_Utils_Array::value('contact_id', $params);

  if (!empty($contactID)) {
    $result = civicrm_contact_update($params);
  }
  else {
    $result = civicrm_contact_create($params);
  }
  return $result;
}

/**
 * Validate the addressee or email or postal greetings
 *
 * @param  $params                   Associative array of property name/value
 *                                   pairs to insert in new contact.
 *
 * @return array (reference )        null on success, error message otherwise
 *
 * @access public
 */
function _civicrm_greeting_format_params(&$params) {
  $greetingParams = array('', '_id', '_custom');
  foreach (array(
    'email', 'postal', 'addressee') as $key) {
    $greeting = '_greeting';
    if ($key == 'addressee') {
      $greeting = '';
    }

    $formatParams = FALSE;
    // unset display value from params.
    if (isset($params["{$key}{$greeting}_display"])) {
      unset($params["{$key}{$greeting}_display"]);
    }

    // check if greetings are present in present
    foreach ($greetingParams as $greetingValues) {
      if (array_key_exists("{$key}{$greeting}{$greetingValues}", $params)) {
        $formatParams = TRUE;
        break;
      }
    }

    if (!$formatParams) {

      continue;
    }

    // format params
    if (CRM_Utils_Array::value('contact_type', $params) == 'Organization' && $key != 'addressee') {
      return civicrm_create_error(ts('You cannot use email/postal greetings for contact type %1.',
          array(1 => $params['contact_type'])
        ));
    }

    $nullValue = FALSE;
    $filter = array(
      'contact_type' => $params['contact_type'],
      'greeting_type' => "{$key}{$greeting}",
    );

    $greetings      = CRM_Core_PseudoConstant::greeting($filter);
    $greetingId     = CRM_Utils_Array::value("{$key}{$greeting}_id", $params);
    $greetingVal    = CRM_Utils_Array::value("{$key}{$greeting}", $params);
    $customGreeting = CRM_Utils_Array::value("{$key}{$greeting}_custom", $params);

    if (!$greetingId && $greetingVal) {
      $params["{$key}{$greeting}_id"] = CRM_Utils_Array::key($params["{$key}{$greeting}"], $greetings);
    }

    if ($customGreeting && $greetingId &&
      ($greetingId != array_search('Customized', $greetings))
    ) {
      return civicrm_create_error(ts('Provide either %1 greeting id and/or %1 greeting or custom %1 greeting',
          array(1 => $key)
        ));
    }

    if ($greetingVal && $greetingId &&
      ($greetingId != CRM_Utils_Array::key($greetingVal, $greetings))
    ) {
      return civicrm_create_error(ts('Mismatch in %1 greeting id and %1 greeting',
          array(1 => $key)
        ));
    }

    if ($greetingId) {

      if (!array_key_exists($greetingId, $greetings)) {
        return civicrm_create_error(ts('Invalid %1 greeting Id', array(1 => $key)));
      }

      if (!$customGreeting && ($greetingId == array_search('Customized', $greetings))) {
        return civicrm_create_error(ts('Please provide a custom value for %1 greeting',
            array(1 => $key)
          ));
      }
    }
    elseif ($greetingVal) {

      if (!in_array($greetingVal, $greetings)) {
        return civicrm_create_error(ts('Invalid %1 greeting', array(1 => $key)));
      }

      $greetingId = CRM_Utils_Array::key($greetingVal, $greetings);
    }

    if ($customGreeting) {
      $greetingId = CRM_Utils_Array::key('Customized', $greetings);
    }

    $customValue = $params['contact_id'] ? CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
      $params['contact_id'],
      "{$key}{$greeting}_custom"
    ) : FALSE;

    if (array_key_exists("{$key}{$greeting}_id", $params) && empty($params["{$key}{$greeting}_id"])) {
      $nullValue = TRUE;
    }
    elseif (array_key_exists("{$key}{$greeting}", $params) && empty($params["{$key}{$greeting}"])) {
      $nullValue = TRUE;
    }
    elseif ($customValue && array_key_exists("{$key}{$greeting}_custom", $params)
      && empty($params["{$key}{$greeting}_custom"])
    ) {
      $nullValue = TRUE;
    }

    $params["{$key}{$greeting}_id"] = $greetingId;

    if (!$customValue && !$customGreeting && array_key_exists("{$key}{$greeting}_custom", $params)) {
      unset($params["{$key}{$greeting}_custom"]);
    }

    if ($nullValue) {
      $params["{$key}{$greeting}_id"] = '';
      $params["{$key}{$greeting}_custom"] = '';
    }

    if (isset($params["{$key}{$greeting}"])) {
      unset($params["{$key}{$greeting}"]);
    }
  }
}

/**
 * Retrieve one or more contacts, given a set of search params
 *
 * @param  mixed[]  (reference ) input parameters
 * @param  bool  follow the pre-2.2.3 behavior of this function
 *
 * @return array (reference )        array of properties, if error an array with an error id and error message
 * @static void
 * @access public
 */
function civicrm_contact_get(&$params, $deprecated_behavior = FALSE) {
  _civicrm_initialize();

  if ($deprecated_behavior) {
    return _civicrm_contact_get_deprecated($params);
  }

  // fix for CRM-7384 cater for soft deleted contacts
  $params['contact_is_deleted'] = 0;
  if (isset($params['showAll'])) {
    if (strtolower($params['showAll']) == "active") {
      $params['contact_is_deleted'] = 0;
    }
    if (strtolower($params['showAll']) == "trash") {
      $params['contact_is_deleted'] = 1;
    }
    if (strtolower($params['showAll']) == "all" && isset($params['contact_is_deleted'])) {
      unset($params['contact_is_deleted']);
    }
  }

  $inputParams      = array();
  $returnProperties = array();
  $otherVars        = array('sort', 'offset', 'rowCount', 'smartGroupCache');

  $sort            = NULL;
  $offset          = 0;
  $rowCount        = 25;
  $smartGroupCache = FALSE;
  foreach ($params as $n => $v) {
    if (substr($n, 0, 6) == 'return') {
      $returnProperties[substr($n, 7)] = $v;
    }
    elseif (in_array($n, $otherVars)) {
      $$n = $v;
    }
    else {
      $inputParams[$n] = $v;
    }
  }

  if (empty($returnProperties)) {
    $returnProperties = NULL;
  }

  require_once 'CRM/Contact/BAO/Query.php';
  $newParams = CRM_Contact_BAO_Query::convertFormValues($inputParams);
  list($contacts, $options) = CRM_Contact_BAO_Query::apiQuery($newParams,
    $returnProperties,
    NULL,
    $sort,
    $offset,
    $rowCount,
    $smartGroupCache
  );
  return $contacts;
}

/**
 * Retrieve a specific contact, given a set of search params
 *
 * @deprecated deprecated since version 2.2.3
 *
 * @param  array   $params           (reference ) input parameters
 *
 * @return array (reference )        array of properties, if error an array with an error id and error message
 * @static void
 * @access public
 */
function _civicrm_contact_get_deprecated(&$params) {
  $values = array();
  if (empty($params)) {
    return civicrm_create_error(ts('No input parameters present'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameters is not an array'));
  }

  $contacts = &civicrm_contact_search($params);
  if (civicrm_error($contacts)) {
    return $contacts;
  }

  if (count($contacts) != 1 &&
    !CRM_Utils_Array::value('returnFirst', $params)
  ) {
    return civicrm_create_error(ts('%1 contacts matching input params', array(1 => count($contacts))));
  }
  elseif (count($contacts) == 0) {
    return civicrm_create_error(ts('No contacts match the input params'));
  }

  $contacts = array_values($contacts);
  return $contacts[0];
}

/**
 * Delete a contact with given contact id
 *
 * @param  array   	  $params (reference ) input parameters, contact_id element required
 *
 * @return boolean        true if success, else false
 * @static void
 * @access public
 */
function civicrm_contact_delete(&$params) {
  require_once 'CRM/Contact/BAO/Contact.php';

  $contactID = CRM_Utils_Array::value('contact_id', $params);
  if (!$contactID) {
    return civicrm_create_error(ts('Could not find contact_id in input parameters'));
  }

  $session = CRM_Core_Session::singleton();
  if ($contactID == $session->get('userID')) {
    return civicrm_create_error(ts('This contact record is linked to the currently logged in user account - and cannot be deleted.'));
  }
  $restore = CRM_Utils_Array::value('restore', $params) ? $params['restore'] : FALSE;
  $skipUndelete = CRM_Utils_Array::value('skip_undelete', $params) ? $params['skip_undelete'] : FALSE;
  if (CRM_Contact_BAO_Contact::deleteContact($contactID, $restore, $skipUndelete)) {
    return civicrm_create_success();
  }
  else {
    return civicrm_create_error(ts('Could not delete contact'));
  }
}

/**
 * Retrieve a set of contacts, given a set of input params
 *
 * @deprecated deprecated since version 2.2.3
 *
 * @param  array   $params           (reference ) input parameters
 * @param array    $returnProperties Which properties should be included in the
 *                                   returned Contact object. If NULL, the default
 *                                   set of properties will be included.
 *
 * @return array (reference )        array of contacts, if error an array with an error id and error message
 * @static void
 * @access public
 */
function &civicrm_contact_search(&$params) {
  _civicrm_initialize();

  $inputParams = $returnProperties = array();
  $otherVars = array('sort', 'offset', 'rowCount', 'smartGroupCache');

  $sort            = NULL;
  $offset          = 0;
  $rowCount        = 25;
  $smartGroupCache = FALSE;
  foreach ($params as $n => $v) {
    if (substr($n, 0, 6) == 'return') {
      $returnProperties[substr($n, 7)] = $v;
    }
    elseif (in_array($n, $otherVars)) {
      $$n = $v;
    }
    else {
      $inputParams[$n] = $v;
    }
  }

  // explicitly suppress all deleted contacts
  // this is fixed in api v3
  // CRM-8809
  $inputParams['contact_is_deleted'] = 0;

  if (empty($returnProperties)) {
    $returnProperties = NULL;
  }

  require_once 'CRM/Contact/BAO/Query.php';
  $newParams = CRM_Contact_BAO_Query::convertFormValues($inputParams);
  list($contacts, $options) = CRM_Contact_BAO_Query::apiQuery($newParams,
    $returnProperties,
    NULL,
    $sort,
    $offset,
    $rowCount,
    $smartGroupCache
  );
  return $contacts;
}

/**
 * Ensure that we have the right input parameters
 *
 * @todo We also need to make sure we run all the form rules on the params list
 *       to ensure that the params are valid
 *
 * @param array   $params          Associative array of property name/value
 *                                 pairs to insert in new contact.
 * @param boolean $dupeCheck       Should we check for duplicate contacts
 * @param boolean $dupeErrorArray  Should we return values of error
 *                                 object in array foramt
 * @param boolean $requiredCheck   Should we check if required params
 *                                 are present in params array
 * @param int   $dedupeRuleGroupID - the dedupe rule ID to use if present
 *
 * @return null on success, error message otherwise
 * @access public
 */
function civicrm_contact_check_params(&$params,
  $dupeCheck         = TRUE,
  $dupeErrorArray    = FALSE,
  $requiredCheck     = TRUE,
  $dedupeRuleGroupID = NULL
) {
  if ($requiredCheck) {
    $required = array(
      'Individual' => array(
        array('first_name', 'last_name'),
        'email',
      ),
      'Household' => array(
        'household_name',
      ),
      'Organization' => array(
        'organization_name',
      ),
    );

    // cannot create a contact with empty params
    if (empty($params)) {
      return civicrm_create_error('Input Parameters empty');
    }

    if (!array_key_exists('contact_type', $params)) {
      return civicrm_create_error('Contact Type not specified');
    }

    // contact_type has a limited number of valid values
    $fields = CRM_Utils_Array::value($params['contact_type'], $required);
    if ($fields == NULL) {
      return civicrm_create_error("Invalid Contact Type: {$params['contact_type']}");
    }

    if ($csType = CRM_Utils_Array::value('contact_sub_type', $params)) {
      if (!(CRM_Contact_BAO_ContactType::isExtendsContactType($csType, $params['contact_type']))) {
        return civicrm_create_error("Invalid or Mismatched Contact SubType: " . implode(', ', (array)$csType));
      }
    }

    if (!CRM_Utils_Array::value('contact_id', $params)) {
      $valid = FALSE;
      $error = '';
      foreach ($fields as $field) {
        if (is_array($field)) {
          $valid = TRUE;
          foreach ($field as $element) {
            if (!CRM_Utils_Array::value($element, $params)) {
              $valid = FALSE;
              $error .= $element;
              break;
            }
          }
        }
        else {
          if (CRM_Utils_Array::value($field, $params)) {
            $valid = TRUE;
          }
        }
        if ($valid) {
          break;
        }
      }

      if (!$valid) {
        return civicrm_create_error("Required fields not found for {$params['contact_type']} : $error");
      }
    }
  }

  if ($dupeCheck) {
    // check for record already existing
    require_once 'CRM/Dedupe/Finder.php';
    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, $params['contact_type']);

    // CRM-6431
    // setting 'check_permission' here means that the dedupe checking will be carried out even if the
    // person does not have permission to carry out de-dupes
    // this is similar to the front end form
    if (isset($params['check_permission'])) {
      $dedupeParams['check_permission'] = $fields['check_permission'];
    }

    $ids = implode(',',
      CRM_Dedupe_Finder::dupesByParams($dedupeParams,
        $params['contact_type'],
        'Strict',
        array(),
        $dedupeRuleGroupID
      )
    );

    if ($ids != NULL) {
      if ($dupeErrorArray) {
        $error = CRM_Core_Error::createError("Found matching contacts: $ids",
          CRM_Core_Error::DUPLICATE_CONTACT,
          'Fatal', $ids
        );
        return civicrm_create_error($error->pop());
      }

      return civicrm_create_error("Found matching contacts: $ids", array($ids));
    }
  }

  //check for organisations with same name
  if (CRM_Utils_Array::value('current_employer', $params)) {
    $organizationParams = array();
    $organizationParams['organization_name'] = $params['current_employer'];

    require_once 'CRM/Dedupe/Finder.php';
    $dedupParams = CRM_Dedupe_Finder::formatParams($organizationParams, 'Organization');

    $dedupParams['check_permission'] = FALSE;
    $dupeIds = CRM_Dedupe_Finder::dupesByParams($dedupParams, 'Organization', 'Fuzzy');

    // check for mismatch employer name and id
    if (CRM_Utils_Array::value('employer_id', $params)
      && !in_array($params['employer_id'], $dupeIds)
    ) {
      return civicrm_create_error('Employer name and Employer id Mismatch');
    }

    // show error if multiple organisation with same name exist
    if (!CRM_Utils_Array::value('employer_id', $params)
      && (count($dupeIds) > 1)
    ) {
      return civicrm_create_error('Found more than one Organisation with same Name.');
    }
  }

  return NULL;
}

/**
 * @todo What does this do? If it's still useful, figure out where it should live and what it should be named.
 *
 * @deprecated deprecated since version 2.2.3
 */
function civicrm_replace_contact_formatted($contactId, &$params, &$fields) {
  //$contact = civcrm_get_contact(array('contact_id' => $contactId));

  $delContact = array('contact_id' => $contactId);

  civicrm_contact_delete($delContact);

  $cid = CRM_Contact_BAO_Contact::createProfileContact($params, $fields,
    NULL, NULL, NULL,
    $params['contact_type']
  );
  return civicrm_create_success($cid);
}

/**
 * Takes an associative array and creates a contact object and all the associated
 * derived objects (i.e. individual, location, email, phone etc)
 *
 * @param array $params (reference ) an assoc array of name/value pairs
 * @param  int     $contactID        if present the contact with that ID is updated
 *
 * @return object CRM_Contact_BAO_Contact object
 * @access public
 * @static
 */
function _civicrm_contact_update(&$params, $contactID = NULL) {
  require_once 'CRM/Core/Transaction.php';
  $transaction = new CRM_Core_Transaction();

  if ($contactID) {
    $params['contact_id'] = $contactID;
  }
  require_once 'CRM/Contact/BAO/Contact.php';

  $contact = CRM_Contact_BAO_Contact::create($params);

  $transaction->commit();

  return $contact;
}

/**
 * @todo Move this to ContactFormat.php
 * @deprecated
 */
function civicrm_contact_format_create(&$params) {
  _civicrm_initialize();

  CRM_Core_DAO::freeResult();

  // return error if we have no params
  if (empty($params)) {
    return civicrm_create_error('Input Parameters empty');
  }

  $error = _civicrm_required_formatted_contact($params);
  if (civicrm_error($error)) {
    return $error;
  }

  $error = _civicrm_validate_formatted_contact($params);
  if (civicrm_error($error)) {
    return $error;
  }

  //get the prefix id etc if exists
  require_once 'CRM/Contact/BAO/Contact.php';
  CRM_Contact_BAO_Contact::resolveDefaults($params, TRUE);

  require_once 'CRM/Import/Parser.php';
  if (CRM_Utils_Array::value('onDuplicate', $params) != CRM_Import_Parser::DUPLICATE_NOCHECK) {
    CRM_Core_Error::reset();
    $error = _civicrm_duplicate_formatted_contact($params);
    if (civicrm_error($error)) {
      return $error;
    }
  }

  $contact = CRM_Contact_BAO_Contact::create($params,
    CRM_Utils_Array::value('fixAddress', $params)
  );

  _civicrm_object_to_array($contact, $contactArray);
  return $contactArray;
}

/**
 * Returns the number of Contact objects which match the search criteria specified in $params.
 *
 * @deprecated deprecated since version 2.2.3; civicrm_contact_get now returns a record_count value
 *
 * @param array  $params
 *
 * @return int
 * @access public
 */
function civicrm_contact_search_count(&$params) {
  // convert the params to new format
  require_once 'CRM/Contact/Form/Search.php';
  $newP = CRM_Contact_BAO_Query::convertFormValues($params);
  $query = new CRM_Contact_BAO_Query($newP);
  return $query->searchQuery(0, 0, NULL, TRUE);
}

/**
 * Ensure that we have the right input parameters for custom data
 *
 * @param array   $params          Associative array of property name/value
 *                                 pairs to insert in new contact.
 * @param string  $csType          contact subtype if exists/passed.
 *
 * @return null on success, error message otherwise
 * @access public
 */
function civicrm_contact_check_custom_params($params, $csType = NULL) {
  empty($csType) ? $onlyParent = TRUE : $onlyParent = FALSE;

  require_once 'CRM/Core/BAO/CustomField.php';
  $customFields = CRM_Core_BAO_CustomField::getFields($params['contact_type'], FALSE, FALSE, $csType, NULL, $onlyParent);

  foreach ($params as $key => $value) {
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
      /* check if it's a valid custom field id */


      if (!array_key_exists($customFieldID, $customFields)) {

        $errorMsg = ts("Invalid Custom Field Contact Type: {$params['contact_type']}");
        if ($csType) {
          $errorMsg .= ts(" or Mismatched SubType: " . implode(', ', (array)$csType));
        }
        return civicrm_create_error($errorMsg);
      }
    }
  }
}

