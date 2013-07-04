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
 * new version of civicrm apis. See blog post at
 * http://civicrm.org/node/131
 * @todo Write sth
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: Contact.php 30879 2010-11-22 15:45:55Z shot $
 *
 */

/**
 * Create or update a contact (note you should always call this via civicrm_api() & never directly)
 *
 * @param  array   $params   input parameters
 *
 * Allowed @params array keys are:
 * {@getfields contact_create}
 *
 *
 * @example ContactCreate.php Example of Create Call
 *
 * @return array  API Result Array
 *
 * @static void
 * @access public
 */
function civicrm_api3_contact_create($params) {

  $contactID = CRM_Utils_Array::value('contact_id', $params, CRM_Utils_Array::value('id', $params));
  $dupeCheck = CRM_Utils_Array::value('dupe_check', $params, FALSE);
  $values = _civicrm_api3_contact_check_params($params, $dupeCheck);
  if ($values) {
    return $values;
  }

  if (!$contactID) {
    // If we get here, we're ready to create a new contact
    if (($email = CRM_Utils_Array::value('email', $params)) && !is_array($params['email'])) {
      $defLocType = CRM_Core_BAO_LocationType::getDefault();
      $params['email'] = array(
        1 => array('email' => $email,
          'is_primary' => 1,
          'location_type_id' => ($defLocType->id) ? $defLocType->id : 1,
        ),
      );
    }
  }

  if (!empty($params['home_url'])) {
    $websiteTypes = CRM_Core_PseudoConstant::websiteType();
    $params['website'] = array(1 => array('website_type_id' => key($websiteTypes),
        'url' => $params['home_url'],
      ),
    );
  }

  if (isset($params['suffix_id']) && !(is_numeric($params['suffix_id']))) {
    $params['suffix_id'] = array_search($params['suffix_id'], CRM_Core_PseudoConstant::individualSuffix());
  }

  if (isset($params['prefix_id']) && !(is_numeric($params['prefix_id']))) {
    $params['prefix_id'] = array_search($params['prefix_id'], CRM_Core_PseudoConstant::individualPrefix());
  }

  if (isset($params['gender_id']) && !(is_numeric($params['gender_id']))) {
    $params['gender_id'] = array_search($params['gender_id'], CRM_Core_PseudoConstant::gender());
  }

  $error = _civicrm_api3_greeting_format_params($params);
  if (civicrm_error($error)) {
    return $error;
  }

  $values = array();
  $entityId = $contactID;

  if (empty($params['contact_type']) && $entityId) {
    $params['contact_type'] = CRM_Contact_BAO_Contact::getContactType($entityId);
  }

  if (!isset($params['contact_sub_type']) && $entityId) {
    $params['contact_sub_type'] = CRM_Contact_BAO_Contact::getContactSubType($entityId);
  }

  _civicrm_api3_custom_format_params($params, $values, $params['contact_type'], $entityId);

  $params = array_merge($params, $values);

  $contact = _civicrm_api3_contact_update($params, $contactID);

  if (is_a($contact, 'CRM_Core_Error')) {
    return civicrm_api3_create_error($contact->_errors[0]['message']);
  }
  else {
    $values = array();
    _civicrm_api3_object_to_array_unique_fields($contact, $values[$contact->id]);
  }

  return civicrm_api3_create_success($values, $params, 'Contact', 'create');
}

/**
 * Adjust Metadata for Create action
 *
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_contact_create_spec(&$params) {
  $params['contact_type']['api.required'] = 1;
  $params['id']['api.aliases'] = array('contact_id');
  $params['current_employer'] = array(
    'title' => 'Current Employer',
    'description' => 'Name of Current Employer',
  );
  $params['dupe_check'] = array(
    'title' => 'Check for Duplicates',
    'description' => 'Throw error if contact create matches dedupe rule',
  );
}

/**
 * Retrieve one or more contacts, given a set of search params
 *
 * @param  array  input parameters
 *
 * @return array API Result Array
 * (@getfields contact_get}
 * @static void
 * @access public
 *
 * @example ContactGet.php Standard GET example
 *
 */
function civicrm_api3_contact_get($params) {
  $options = array();
  _civicrm_api3_contact_get_supportanomalies($params, $options);
  $contacts = _civicrm_api3_get_using_query_object('contact', $params, $options);

  // CRM-7929 Quick fix by colemanw
  // TODO: Figure out what function is responsible for prepending 'individual_' to these keys
  // and sort it out there rather than going to all this trouble here.
  // Eileen's note - not sure anymore if we went the right path stripping the 'individual' here
  // as is arguable whether it makes more sense now I think it would make more sense to rename the table field  or uniquefield to have
  // the individual prefix but we are stuck with it now for apiv3 series.
  $returnContacts = array();
  if (is_array($contacts)) {
    foreach ($contacts as $cid => $contact) {
      if (is_array($contact)) {
        $returnContacts[$cid] = array();
        foreach ($contact as $key => $value) {
          $key = str_replace(array('individual_prefix', 'individual_suffix'), array('prefix', 'suffix'), $key);
          $returnContacts[$cid][$key] = $value;
        }
      }
    }
  }
  return civicrm_api3_create_success($returnContacts, $params, 'contact');
}

function civicrm_api3_contact_getcount($params) {
  $options = array();
  _civicrm_api3_contact_get_supportanomalies($params, $options);
  $count = _civicrm_api3_get_using_query_object('contact', $params, $options,1);
  return $count;
}

/**
 * Adjust Metadata for Get action
 *
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_contact_get_spec(&$params) {
  $params['contact_is_deleted']['api.default'] = 0;

  //we declare all these pseudofields as there are other undocumented fields accessible
  // via the api - but if check permissions is set we only allow declared fields
  $params['address_id']['title'] = 'Primary Address ID';
  $params['street_address']['title'] = 'Primary Address Street Address';
  $params['supplemental_address_1']['title'] = 'Primary Address Supplemental Address 1';
  $params['supplemental_address_2']['title'] = 'Primary Address Supplemental Address 2';
  $params['city']['title'] = 'Primary Address City';
  $params['postal_code_suffix']['title'] = 'Primary Address Post Code Suffix';
  $params['postal_code']['title'] = 'Primary Address Post Code';
  $params['geo_code_1']['title'] = 'Primary Address Latitude';
  $params['geo_code_2']['title'] = 'Primary Address Longitude';
  $params['state_province_id']['title'] = 'Primary Address State Province ID';
  $params['state_province_name']['title'] = 'Primary Address State Province Name';
  $params['state_province']['title'] = 'Primary Address State Province';
  $params['country_id']['title'] = 'Primary Address State Province ID';
  $params['country']['title'] = 'Primary Address country';
  $params['worldregion_id']['title'] = 'Primary Address World Region ID';
  $params['worldregion']['title'] = 'Primary Address World Region';
  $params['phone_id']['title'] = 'Primary Phone ID';
  $params['phone_type_id']['title'] = 'Primary Phone Type ID';
  $params['provider_id']['title'] = 'Primary Phone Provider ID';
  $params['email_id']['title'] = 'Primary Email ID';
  $params['email']['title'] = 'Primary Email';
  $params['on_hold']['title'] = 'Primary Email On Hold';
  $params['im']['title'] = 'Primary Instant Messanger';
  $params['im_id']['title'] = 'Primary Instant Messanger ID';
  $params['group_id']['title'] = 'Group Memberships (filter)';
  $params['group']['title'] = 'Group Memberships (filter, array)';
  $params['tag']['title'] = 'Assigned tags (filter, array)';
}

/**
 * We are supporting 'showAll' = 'all', 'trash' or 'active' for contact get
 * and for getcount
 * - hopefully some day we'll come up with a std syntax for the 3-way-boolean of
 * 0, 1 or not set
 *
 * We also support 'filter_group_id' & 'filter.group_id'
 *
 * @param array $params as passed into api get or getcount function
 * @param array $options array of options (so we can modify the filter)
 */
function _civicrm_api3_contact_get_supportanomalies(&$params, &$options) {
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
  // support for group filters
  if (array_key_exists('filter_group_id', $params)) {
    $params['filter.group_id'] = $params['filter_group_id'];
    unset($params['filter_group_id']);
  }
  // filter.group_id works both for 1,2,3 and array (1,2,3)
  if (array_key_exists('filter.group_id', $params)) {
    if (is_array($params['filter.group_id'])) {
      $groups = $params['filter.group_id'];
    }
    else $groups = explode(',', $params['filter.group_id']);
    unset($params['filter.group_id']);
    $groups = array_flip($groups);
    $groups[key($groups)] = 1;
    $options['input_params']['group'] = $groups;
  }
}

/**
 * Delete a contact with given contact id
 *
 * @param  array       $params (reference ) input parameters, contact_id element required
 *
 * @return array API Result Array
 * @access public
 *
 * @example ContactDelete.php
 * {@getfields contact_delete}
 */
function civicrm_api3_contact_delete($params) {

  $contactID = CRM_Utils_Array::value('id', $params);

  $session = CRM_Core_Session::singleton();
  if ($contactID == $session->get('userID')) {
    return civicrm_api3_create_error('This contact record is linked to the currently logged in user account - and cannot be deleted.');
  }
  $restore = CRM_Utils_Array::value('restore', $params) ? $params['restore'] : FALSE;
  $skipUndelete = CRM_Utils_Array::value('skip_undelete', $params) ? $params['skip_undelete'] : FALSE;

  // CRM-12929
  // restrict permanent delete if a contact has financial trxn associated with it
  $error = NULL;
  if ($skipUndelete && CRM_Financial_BAO_FinancialItem::checkContactPresent(array($contactID), $error)) {
    return civicrm_api3_create_error($error['_qf_default']);  
  }
  if (CRM_Contact_BAO_Contact::deleteContact($contactID, $restore, $skipUndelete)) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error('Could not delete contact');
  }
}


function _civicrm_api3_contact_check_params( &$params, $dupeCheck = true, $dupeErrorArray = false, $obsoletevalue = true, $dedupeRuleGroupID = null )
{

  switch (strtolower(CRM_Utils_Array::value('contact_type', $params))) {
    case 'household':
      civicrm_api3_verify_mandatory($params, null, array('household_name'));
      break;
    case 'organization':
      civicrm_api3_verify_mandatory($params, null, array('organization_name'));
      break;
    case 'individual':
      civicrm_api3_verify_one_mandatory($params, null, array(
        'first_name',
        'last_name',
        'email',
        'display_name',
      )
    );
    break;
  }

  if (CRM_Utils_Array::value('contact_sub_type', $params) && CRM_Utils_Array::value('contact_type', $params)) {
      if (!(CRM_Contact_BAO_ContactType::isExtendsContactType($params['contact_sub_type'], $params['contact_type']))) {
        return civicrm_api3_create_error("Invalid or Mismatched Contact SubType: " . implode(', ', (array)$params['contact_sub_type']));
      }
    }

  if ($dupeCheck) {
    // check for record already existing
    $dedupeParams = CRM_Dedupe_Finder::formatParams($params, $params['contact_type']);

    // CRM-6431
    // setting 'check_permission' here means that the dedupe checking will be carried out even if the
    // person does not have permission to carry out de-dupes
    // this is similar to the front end form
    if (isset($params['check_permission'])) {
      $dedupeParams['check_permission'] = $params['check_permission'];
    }

    $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, $params['contact_type'], 'Strict', array());

    if (count($ids) >0) {
      throw new API_Exception("Found matching contacts: ". implode(',',$ids),"duplicate",array("ids"=>$ids));
    }
  }

  //check for organisations with same name
  if (!empty($params['current_employer'])) {
    $organizationParams = array();
    $organizationParams['organization_name'] = $params['current_employer'];

    $dedupParams = CRM_Dedupe_Finder::formatParams($organizationParams, 'Organization');

    $dedupParams['check_permission'] = FALSE;
    $dupeIds = CRM_Dedupe_Finder::dupesByParams($dedupParams, 'Organization', 'Supervised');

    // check for mismatch employer name and id
    if (!empty($params['employer_id']) && !in_array($params['employer_id'], $dupeIds)) {
      throw new API_Exception('Employer name and Employer id Mismatch');
    }

    // show error if multiple organisation with same name exist
    if (empty($params['employer_id']) && (count($dupeIds) > 1)) {
      throw new API_Exception('Found more than one Organisation with same Name.');
    }
  }

  return NULL;
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
function _civicrm_api3_contact_update($params, $contactID = NULL) {
  $transaction = new CRM_Core_Transaction();

  if ($contactID) {
    $params['contact_id'] = $contactID;
  }

  $contact = CRM_Contact_BAO_Contact::create($params);

  $transaction->commit();

  return $contact;
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
function _civicrm_api3_greeting_format_params($params) {
  $greetingParams = array('', '_id', '_custom');
  foreach (array('email', 'postal', 'addressee') as $key) {
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
      return civicrm_api3_create_error(ts('Provide either %1 greeting id and/or %1 greeting or custom %1 greeting',
          array(1 => $key)
        ));
    }

    if ($greetingVal && $greetingId &&
      ($greetingId != CRM_Utils_Array::key($greetingVal, $greetings))
    ) {
      return civicrm_api3_create_error(ts('Mismatch in %1 greeting id and %1 greeting',
          array(1 => $key)
        ));
    }

    if ($greetingId) {

      if (!array_key_exists($greetingId, $greetings)) {
        return civicrm_api3_create_error(ts('Invalid %1 greeting Id', array(1 => $key)));
      }

      if (!$customGreeting && ($greetingId == array_search('Customized', $greetings))) {
        return civicrm_api3_create_error(ts('Please provide a custom value for %1 greeting',
            array(1 => $key)
          ));
      }
    }
    elseif ($greetingVal) {

      if (!in_array($greetingVal, $greetings)) {
        return civicrm_api3_create_error(ts('Invalid %1 greeting', array(1 => $key)));
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
 * Contact quick search api
 *
 * @access public
 *
 * {@example ContactGetquick.php 0}
 *
 */
function civicrm_api3_contact_quicksearch($params) {
  // kept as an alias for compatibility reasons.  CRM-11136
  return civicrm_api3_contact_getquick($params);
}

function civicrm_api3_contact_getquick($params) {
  civicrm_api3_verify_mandatory($params, NULL, array('name'));
  $name = CRM_Utils_Type::escape($params['name'], 'String');

  // get the autocomplete options from settings
  $acpref = explode(CRM_Core_DAO::VALUE_SEPARATOR,
    CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_autocomplete_options'
    )
  );

  // get the option values for contact autocomplete
  $acOptions = CRM_Core_OptionGroup::values('contact_autocomplete_options', FALSE, FALSE, FALSE, NULL, 'name');

  $list = array();
  foreach ($acpref as $value) {
    if ($value && CRM_Utils_Array::value($value, $acOptions)) {
      $list[$value] = $acOptions[$value];
    }
  }
  // If we are doing quicksearch by a field other than name, make sure that field is added to results
  if (!empty($params['field_name'])) {
    $field_name = CRM_Utils_String::munge($params['field_name']);
    // Unique name contact_id = id
    if ($field_name == 'contact_id') {
      $field_name = 'id';
    }
    // phone_numeric should be phone
    $searchField = str_replace('_numeric', '', $field_name);
    if(!in_array($searchField, $list)) {
      $list[] = $searchField;
    }
  }

  $select = $actualSelectElements = array('sort_name');
  $where  = '';
  $from   = array();
  foreach ($list as $value) {
    $suffix = substr($value, 0, 2) . substr($value, -1);
    switch ($value) {
      case 'street_address':
      case 'city':
      case 'postal_code':
        $selectText = $value;
        $value      = "address";
        $suffix     = 'sts';
      case 'phone':
      case 'email':
        $actualSelectElements[] = $select[] = ($value == 'address') ? $selectText : $value;
        $from[$value] = "LEFT JOIN civicrm_{$value} {$suffix} ON ( cc.id = {$suffix}.contact_id AND {$suffix}.is_primary = 1 ) ";
        break;

      case 'country':
      case 'state_province':
        $select[] = "{$suffix}.name as {$value}";
        $actualSelectElements[] = "{$suffix}.name";
        if (!in_array('address', $from)) {
          $from['address'] = 'LEFT JOIN civicrm_address sts ON ( cc.id = sts.contact_id AND sts.is_primary = 1) ';
        }
        $from[$value] = " LEFT JOIN civicrm_{$value} {$suffix} ON ( sts.{$value}_id = {$suffix}.id  ) ";
        break;

      default:
        if ($value != 'id') {
          $suffix = 'cc';
          if (!empty($params['field_name']) && $params['field_name'] == 'value') {
            $suffix = CRM_Utils_String::munge(CRM_Utils_Array::value('table_name', $params, 'cc'));
          }
          $actualSelectElements[] = $select[] = $suffix . '.' . $value;
        }
        break;
    }
  }

  $config = CRM_Core_Config::singleton();
  $as  = $select;
  $select = implode(', ', $select);
  if (!empty($select)) {
    $select = ", $select";
  }
  $actualSelectElements = implode(', ', $actualSelectElements);
  $selectAliases = $from;
  unset($selectAliases['address']);
  $selectAliases = implode(', ', array_keys($selectAliases));
  if (!empty($selectAliases)) {
    $selectAliases = ", $selectAliases";
  }
  $from = implode(' ', $from);
  $limit = (int) CRM_Utils_Array::value('limit', $params);
  $limit = $limit > 0 ? $limit : 10;

  // add acl clause here
  list($aclFrom, $aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause('cc');

  if ($aclWhere) {
    $where .= " AND $aclWhere ";
  }

  if (CRM_Utils_Array::value('org', $params)) {
    $where .= " AND contact_type = \"Organization\"";

    // CRM-7157, hack: get current employer details when
    // employee_id is present.
    $currEmpDetails = array();
    if (CRM_Utils_Array::value('employee_id', $params)) {
      if ($currentEmployer = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          (int) $params['employee_id'],
          'employer_id'
        )) {
        if ($config->includeWildCardInName) {
          $strSearch = "%$name%";
        }
        else {
          $strSearch = "$name%";
        }

        // get current employer details
        $dao = CRM_Core_DAO::executeQuery("SELECT cc.id as id, CONCAT_WS( ' :: ', {$actualSelectElements} ) as data, sort_name
                    FROM civicrm_contact cc {$from} WHERE cc.contact_type = \"Organization\" AND cc.id = {$currentEmployer} AND cc.sort_name LIKE '$strSearch'");
        if ($dao->fetch()) {
          $currEmpDetails = array(
            'id' => $dao->id,
            'data' => $dao->data,
          );
        }
      }
    }
  }

  //set default for current_employer or return contact with particular id
  if (CRM_Utils_Array::value('id', $params)) {
    $where .= " AND cc.id = " . (int) $params['id'];
  }

  if (CRM_Utils_Array::value('cid', $params)) {
    $where .= " AND cc.id <> " . (int) $params['cid'];
  }

  //contact's based of relationhip type
  $relType = NULL;
  if (CRM_Utils_Array::value('rel', $params)) {
    $relation = explode('_', CRM_Utils_Array::value('rel', $params));
    $relType  = CRM_Utils_Type::escape($relation[0], 'Integer');
    $rel      = CRM_Utils_Type::escape($relation[2], 'String');
  }

  if ($config->includeWildCardInName) {
    $strSearch = "%$name%";
  }
  else {
    $strSearch = "$name%";
  }
  $includeEmailFrom = $includeNickName = $exactIncludeNickName = '';
  if ($config->includeNickNameInName) {
    $includeNickName = " OR nick_name LIKE '$strSearch'";
    $exactIncludeNickName = " OR nick_name LIKE '$name'";
  }

  //CRM-10687
  if (!empty($params['field_name']) && !empty($params['table_name'])) {
    $table_name = CRM_Utils_String::munge($params['table_name']);
    $whereClause = " WHERE ( $table_name.$field_name LIKE '$strSearch')";
    $exactWhereClause = " WHERE ( $table_name.$field_name = '$name')";
    // Search by id should be exact
    if ($field_name == 'id' || $field_name == 'external_identifier') {
      $whereClause = $exactWhereClause;
    }
  }
  else {
    if ($config->includeEmailInName) {
      if (!in_array('email', $list)) {
        $includeEmailFrom = "LEFT JOIN civicrm_email eml ON ( cc.id = eml.contact_id AND eml.is_primary = 1 )";
      }
      $whereClause = " WHERE ( email LIKE '$strSearch' OR sort_name LIKE '$strSearch' $includeNickName ) {$where} ";
      $exactWhereClause = " WHERE ( email LIKE '$name' OR sort_name LIKE '$name' $exactIncludeNickName ) {$where} ";
    }
    else {
      $whereClause = " WHERE ( sort_name LIKE '$strSearch' $includeNickName ) {$where} ";
      $exactWhereClause = " WHERE ( sort_name LIKE '$name' $exactIncludeNickName ) {$where} ";
    }
  }

  $additionalFrom = '';
  if ($relType) {
    $additionalFrom = "
            INNER JOIN civicrm_relationship_type r ON (
                r.id = {$relType}
                AND ( cc.contact_type = r.contact_type_{$rel} OR r.contact_type_{$rel} IS NULL )
                AND ( cc.contact_sub_type = r.contact_sub_type_{$rel} OR r.contact_sub_type_{$rel} IS NULL )
            )";
  }

  // check if only CMS users are requested
  if (CRM_Utils_Array::value('cmsuser', $params)) {
    $additionalFrom = "
      INNER JOIN civicrm_uf_match um ON (um.contact_id=cc.id)
      ";
  }

  $orderByInner = "";
  $orderByOuter = "ORDER BY exactFirst";
  if ($config->includeOrderByClause) {
    $orderByInner = "ORDER BY sort_name";
    $orderByOuter .= ", sort_name";
  }

  //CRM-5954
  $query = "
        SELECT DISTINCT(id), data, sort_name {$selectAliases}
        FROM   (
            ( SELECT 0 as exactFirst, cc.id as id, CONCAT_WS( ' :: ', {$actualSelectElements} ) as data {$select}
            FROM   civicrm_contact cc {$from}
    {$aclFrom}
    {$additionalFrom} {$includeEmailFrom}
    {$exactWhereClause}
    LIMIT 0, {$limit} )
    UNION
    ( SELECT 1 as exactFirst, cc.id as id, CONCAT_WS( ' :: ', {$actualSelectElements} ) as data {$select}
    FROM   civicrm_contact cc {$from}
    {$aclFrom}
    {$additionalFrom} {$includeEmailFrom}
    {$whereClause}
    {$orderByInner}
    LIMIT 0, {$limit} )
) t
{$orderByOuter}
LIMIT    0, {$limit}
    ";
  // send query to hook to be modified if needed
  CRM_Utils_Hook::contactListQuery($query,
    $name,
    empty($params['context']) ? NULL : CRM_Utils_Type::escape($params['context'], 'String'),
    empty($params['id']) ? NULL : $params['id']
  );

  $dao = CRM_Core_DAO::executeQuery($query);

  $contactList = array();
  $listCurrentEmployer = TRUE;
  while ($dao->fetch()) {
    $t = array('id' => $dao->id);
    foreach ($as as $k) {
      $t[$k] = isset($dao->$k)? $dao->$k: '';
    }
    $t['data'] = $dao->data;
    $contactList[] = $t;
    if (CRM_Utils_Array::value('org', $params) &&
      !empty($currEmpDetails) &&
      $dao->id == $currEmpDetails['id']
    ) {
      $listCurrentEmployer = FALSE;
    }
  }

  //return organization name if doesn't exist in db
  if (empty($contactList)) {
    if (CRM_Utils_Array::value('org', $params)) {
      if ($listCurrentEmployer && !empty($currEmpDetails)) {
        $contactList = array(
          array(
          'data' => $currEmpDetails['data'],
            'id'   => $currEmpDetails['id']
          )
        );
      }
      else {
        $contactList = array(
          array(
            'data' => $name,
            'id'   => $name
          )
        );
      }
    }
  }

  return civicrm_api3_create_success($contactList, $params);
}

/**
 * Merges given pair of duplicate contacts.
 *
 * @param  array   $params   input parameters
 *
 * Allowed @params array keys are:
 * {int     main_id     main contact id with whom merge has to happen}
 * {int     other_id    duplicate contact which would be deleted after merge operation}
 * {string  mode        helps decide how to behave when there are conflicts.
 *                      A 'safe' value skips the merge if there are no conflicts. Does a force merge otherwise.}
 * {boolean auto_flip   wether to let api decide which contact to retain and which to delete.}
 *
 * @return array  API Result Array
 *
 * @static void
 * @access public
 */
function civicrm_api3_contact_merge($params) {
  $mode = CRM_Utils_Array::value('mode', $params, 'safe');
  $autoFlip = CRM_Utils_Array::value('auto_flip', $params, TRUE);

  $dupePairs = array(array('srcID' => CRM_Utils_Array::value('main_id', $params),
      'dstID' => CRM_Utils_Array::value('other_id', $params),
    ));
  $result = CRM_Dedupe_Merger::merge($dupePairs, array(), $mode, $autoFlip);

  if ($result['is_error'] == 0) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error($result['messages']);
  }
}

function _civicrm_api3_contact_proximity_spec(&$params) {
  $params['latitude']['api.required'] = 1;
  $params['longitude']['api.required'] = 1;
  $params['unit']['api.default'] = 'meter';
}

function civicrm_api3_contact_proximity($params) {
  $latitude  = CRM_Utils_Array::value('latitude', $params);
  $longitude = CRM_Utils_Array::value('longitude', $params);
  $distance  = CRM_Utils_Array::value('distance', $params);

  $unit = CRM_Utils_Array::value('unit', $params);

  // check and ensure that lat/long and distance are floats
  if (
    !CRM_Utils_Rule::numeric($latitude) ||
    !CRM_Utils_Rule::numeric($longitude) ||
    !CRM_Utils_Rule::numeric($distance)
  ) {
    throw new Exception(ts('Latitude, Longitude and Distance should exist and be numeric'));
  }

  if ($unit == "mile") {
    $conversionFactor = 1609.344;
  }
  else {
    $conversionFactor = 1000;
  }
  //Distance in meters
  $distance = $distance * $conversionFactor;

  $whereClause = CRM_Contact_BAO_ProximityQuery::where($latitude, $longitude, $distance);

  $query = "
SELECT    civicrm_contact.id as contact_id,
          civicrm_contact.display_name as display_name
FROM      civicrm_contact
LEFT JOIN civicrm_address ON civicrm_contact.id = civicrm_address.contact_id
WHERE     $whereClause
";

  $dao = CRM_Core_DAO::executeQuery($query);
  $contacts = array();
  while ($dao->fetch()) {
    $contacts[] = $dao->toArray();
  }

  return civicrm_api3_create_success($contacts, $params, 'contact', 'get_by_location', $dao);
}

