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
 * This api exposes CiviCRM contacts.
 *
 * Contacts are the main entity in CiviCRM and this api is more robust than most.
 *   - Get action allows all params supported by advanced search.
 *   - Create action allows creating several related entities at once (e.g. email).
 *   - Create allows checking for duplicate contacts.
 * Use getfields to list the full range of parameters and options supported by each action.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a Contact.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_contact_create($params) {
  $contactID = CRM_Utils_Array::value('contact_id', $params, CRM_Utils_Array::value('id', $params));

  if ($contactID && !empty($params['check_permissions']) && !CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::EDIT)) {
    throw new \Civi\API\Exception\UnauthorizedException('Permission denied to modify contact record');
  }

  if (!empty($params['dupe_check'])) {
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($params, $params['contact_type'], 'Unsupervised', [], $params['check_permission']);
    if (count($ids) > 0) {
      throw new API_Exception("Found matching contacts: " . implode(',', $ids), "duplicate", ["ids" => $ids]);
    }
  }

  $values = _civicrm_api3_contact_check_params($params);
  if ($values) {
    return $values;
  }

  if (array_key_exists('api_key', $params) && !empty($params['check_permissions'])) {
    if (CRM_Core_Permission::check('edit api keys') || CRM_Core_Permission::check('administer CiviCRM')) {
      // OK
    }
    elseif ($contactID && CRM_Core_Permission::check('edit own api keys') && CRM_Core_Session::singleton()->get('userID') == $contactID) {
      // OK
    }
    else {
      throw new \Civi\API\Exception\UnauthorizedException('Permission denied to modify api key');
    }
  }

  if (!$contactID) {
    // If we get here, we're ready to create a new contact
    if (($email = CRM_Utils_Array::value('email', $params)) && !is_array($params['email'])) {
      $defLocType = CRM_Core_BAO_LocationType::getDefault();
      $params['email'] = [
        1 => [
          'email' => $email,
          'is_primary' => 1,
          'location_type_id' => ($defLocType->id) ? $defLocType->id : 1,
        ],
      ];
    }
  }

  if (!empty($params['home_url'])) {
    $websiteTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id');
    $params['website'] = [
      1 => [
        'website_type_id' => key($websiteTypes),
        'url' => $params['home_url'],
      ],
    ];
  }

  _civicrm_api3_greeting_format_params($params);

  $values = [];

  if (empty($params['contact_type']) && $contactID) {
    $params['contact_type'] = CRM_Contact_BAO_Contact::getContactType($contactID);
  }

  if (!isset($params['contact_sub_type']) && $contactID) {
    $params['contact_sub_type'] = CRM_Contact_BAO_Contact::getContactSubType($contactID);
  }

  _civicrm_api3_custom_format_params($params, $values, $params['contact_type'], $contactID);

  $params = array_merge($params, $values);
  //@todo we should just call basic_create here - but need to make contact:create accept 'id' on the bao
  $contact = _civicrm_api3_contact_update($params, $contactID);

  if (is_a($contact, 'CRM_Core_Error')) {
    throw new API_Exception($contact->_errors[0]['message']);
  }
  else {
    $values = [];
    _civicrm_api3_object_to_array_unique_fields($contact, $values[$contact->id]);
  }

  $values = _civicrm_api3_contact_formatResult($params, $values);

  return civicrm_api3_create_success($values, $params, 'Contact', 'create');
}

/**
 * Adjust Metadata for Create action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contact_create_spec(&$params) {
  $params['contact_type']['api.required'] = 1;
  $params['id']['api.aliases'] = ['contact_id'];
  $params['current_employer'] = [
    'title' => 'Current Employer',
    'description' => 'Name of Current Employer',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['dupe_check'] = [
    'title' => 'Check for Duplicates',
    'description' => 'Throw error if contact create matches dedupe rule',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['skip_greeting_processing'] = [
    'title' => 'Skip Greeting processing',
    'description' => 'Do not process greetings, (these can be done by scheduled job and there may be a preference to do so for performance reasons)',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => 0,
  ];
  $params['prefix_id']['api.aliases'] = [
    'individual_prefix',
    'individual_prefix_id',
  ];
  $params['suffix_id']['api.aliases'] = [
    'individual_suffix',
    'individual_suffix_id',
  ];
  $params['gender_id']['api.aliases'] = ['gender'];
}

/**
 * Retrieve one or more contacts, given a set of search params.
 *
 * @param array $params
 *
 * @return array
 *   API Result Array
 */
function civicrm_api3_contact_get($params) {
  $options = [];
  _civicrm_api3_contact_get_supportanomalies($params, $options);
  $contacts = _civicrm_api3_get_using_query_object('Contact', $params, $options);
  $contacts = _civicrm_api3_contact_formatResult($params, $contacts);
  return civicrm_api3_create_success($contacts, $params, 'Contact');
}

/**
 * Filter the result.
 *
 * @param array $result
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function _civicrm_api3_contact_formatResult($params, $result) {
  $apiKeyPerms = ['edit api keys', 'administer CiviCRM'];
  $allowApiKey = empty($params['check_permissions']) || CRM_Core_Permission::check([$apiKeyPerms]);
  if (!$allowApiKey) {
    if (is_array($result)) {
      // Single-value $result
      if (isset($result['api_key'])) {
        unset($result['api_key']);
      }

      // Multi-value $result
      foreach ($result as $key => $row) {
        if (is_array($row)) {
          unset($result[$key]['api_key']);
        }
      }
    }
  }
  return $result;
}

/**
 * Get number of contacts matching the supplied criteria.
 *
 * @param array $params
 *
 * @return int
 */
function civicrm_api3_contact_getcount($params) {
  $options = [];
  _civicrm_api3_contact_get_supportanomalies($params, $options);
  $count = _civicrm_api3_get_using_query_object('Contact', $params, $options, 1);
  return (int) $count;
}

/**
 * Adjust Metadata for Get action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contact_get_spec(&$params) {
  $params['contact_is_deleted']['api.default'] = 0;

  // We declare all these pseudoFields as there are other undocumented fields accessible
  // via the api - but if check permissions is set we only allow declared fields
  $params['address_id'] = [
    'title' => 'Primary Address ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['street_address'] = [
    'title' => 'Primary Address Street Address',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['supplemental_address_1'] = [
    'title' => 'Primary Address Supplemental Address 1',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['supplemental_address_2'] = [
    'title' => 'Primary Address Supplemental Address 2',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['supplemental_address_3'] = [
    'title' => 'Primary Address Supplemental Address 3',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['current_employer'] = [
    'title' => 'Current Employer',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['city'] = [
    'title' => 'Primary Address City',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['postal_code_suffix'] = [
    'title' => 'Primary Address Post Code Suffix',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['postal_code'] = [
    'title' => 'Primary Address Post Code',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['geo_code_1'] = [
    'title' => 'Primary Address Latitude',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['geo_code_2'] = [
    'title' => 'Primary Address Longitude',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['state_province_id'] = [
    'title' => 'Primary Address State Province ID',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'table' => 'civicrm_state_province',
    ],
  ];
  $params['state_province_name'] = [
    'title' => 'Primary Address State Province Name',
    'type' => CRM_Utils_Type::T_STRING,
    'pseudoconstant' => [
      'table' => 'civicrm_state_province',
    ],
  ];
  $params['state_province'] = [
    'title' => 'Primary Address State Province',
    'type' => CRM_Utils_Type::T_STRING,
    'pseudoconstant' => [
      'table' => 'civicrm_state_province',
    ],
  ];
  $params['country_id'] = [
    'title' => 'Primary Address Country ID',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'table' => 'civicrm_country',
    ],
  ];
  $params['country'] = [
    'title' => 'Primary Address country',
    'type' => CRM_Utils_Type::T_STRING,
    'pseudoconstant' => [
      'table' => 'civicrm_country',
    ],
  ];
  $params['worldregion_id'] = [
    'title' => 'Primary Address World Region ID',
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'table' => 'civicrm_world_region',
    ],
  ];
  $params['worldregion'] = [
    'title' => 'Primary Address World Region',
    'type' => CRM_Utils_Type::T_STRING,
    'pseudoconstant' => [
      'table' => 'civicrm_world_region',
    ],
  ];
  $params['phone_id'] = [
    'title' => 'Primary Phone ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['phone'] = [
    'title' => 'Primary Phone',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['phone_type_id'] = [
    'title' => 'Primary Phone Type ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['provider_id'] = [
    'title' => 'Primary Phone Provider ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['email_id'] = [
    'title' => 'Primary Email ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['email'] = [
    'title' => 'Primary Email',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['on_hold'] = [
    'title' => 'Primary Email On Hold',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['im'] = [
    'title' => 'Primary Instant Messenger',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['im_id'] = [
    'title' => 'Primary Instant Messenger ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['group'] = [
    'title' => 'Group',
    'pseudoconstant' => [
      'table' => 'civicrm_group',
    ],
  ];
  $params['tag'] = [
    'title' => 'Tags',
    'pseudoconstant' => [
      'table' => 'civicrm_tag',
    ],
  ];
  $params['uf_user'] = [
    'title' => 'CMS User',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $params['birth_date_low'] = [
    'name' => 'birth_date_low',
    'type' => CRM_Utils_Type::T_DATE,
    'title' => ts('Birth Date is equal to or greater than'),
  ];
  $params['birth_date_high'] = [
    'name' => 'birth_date_high',
    'type' => CRM_Utils_Type::T_DATE,
    'title' => ts('Birth Date is equal to or less than'),
  ];
  $params['deceased_date_low'] = [
    'name' => 'deceased_date_low',
    'type' => CRM_Utils_Type::T_DATE,
    'title' => ts('Deceased Date is equal to or greater than'),
  ];
  $params['deceased_date_high'] = [
    'name' => 'deceased_date_high',
    'type' => CRM_Utils_Type::T_DATE,
    'title' => ts('Deceased Date is equal to or less than'),
  ];
}

/**
 * Support for historical oddities.
 *
 * We are supporting 'showAll' = 'all', 'trash' or 'active' for Contact get
 * and for getcount
 * - hopefully some day we'll come up with a std syntax for the 3-way-boolean of
 * 0, 1 or not set
 *
 * We also support 'filter_group_id' & 'filter.group_id'
 *
 * @param array $params
 *   As passed into api get or getcount function.
 * @param array $options
 *   Array of options (so we can modify the filter).
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
    else {
      $groups = explode(',', $params['filter.group_id']);
    }
    unset($params['filter.group_id']);
    $options['input_params']['group'] = $groups;
  }
  if (isset($params['group'])) {
    $groups = $params['group'];
    $groupsByTitle = CRM_Core_PseudoConstant::group();
    $groupsByName = CRM_Contact_BAO_GroupContact::buildOptions('group_id', 'validate');
    $allGroups = array_merge(array_flip($groupsByTitle), array_flip($groupsByName));
    if (is_array($groups) && in_array(key($groups), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
      // Get the groups array.
      $groupsArray = $groups[key($groups)];
      foreach ($groupsArray as &$group) {
        if (!is_numeric($group) && !empty($allGroups[$group])) {
          $group = $allGroups[$group];
        }
      }
      // Now reset the $groups array with the ids not the titles.
      $groups[key($groups)] = $groupsArray;
    }
    // handle format like 'group' => array('title1', 'title2').
    elseif (is_array($groups)) {
      foreach ($groups as $k => &$group) {
        if (!is_numeric($group) && !empty($allGroups[$group])) {
          $group = $allGroups[$group];
        }
        if (!is_numeric($k) && !empty($allGroups[$k])) {
          unset($groups[$k]);
          $groups[$allGroups[$k]] = $group;
        }
      }
    }
    elseif (!is_numeric($groups) && !empty($allGroups[$groups])) {
      $groups = $allGroups[$groups];
    }
    $params['group'] = $groups;
  }
}

/**
 * Delete a Contact with given contact_id.
 *
 * @param array $params
 *   input parameters per getfields
 *
 * @throws \Civi\API\Exception\UnauthorizedException
 * @return array
 *   API Result Array
 */
function civicrm_api3_contact_delete($params) {
  $contactID = CRM_Utils_Array::value('id', $params);

  if (!empty($params['check_permissions']) && !CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::DELETE)) {
    throw new \Civi\API\Exception\UnauthorizedException('Permission denied to modify contact record');
  }

  $session = CRM_Core_Session::singleton();
  if ($contactID == $session->get('userID')) {
    return civicrm_api3_create_error('This contact record is linked to the currently logged in user account - and cannot be deleted.');
  }
  $restore = !empty($params['restore']) ? $params['restore'] : FALSE;
  $skipUndelete = !empty($params['skip_undelete']) ? $params['skip_undelete'] : FALSE;

  // CRM-12929
  // restrict permanent delete if a contact has financial trxn associated with it
  $error = NULL;
  if ($skipUndelete && CRM_Financial_BAO_FinancialItem::checkContactPresent([$contactID], $error)) {
    return civicrm_api3_create_error($error['_qf_default']);
  }
  if (CRM_Contact_BAO_Contact::deleteContact($contactID, $restore, $skipUndelete,
    CRM_Utils_Array::value('check_permissions', $params))) {
    return civicrm_api3_create_success();
  }
  else {
    return civicrm_api3_create_error('Could not delete contact');
  }
}


/**
 * Check parameters passed in.
 *
 * This function is on it's way out.
 *
 * @param array $params
 *
 * @return null
 * @throws API_Exception
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_contact_check_params(&$params) {

  switch (strtolower(CRM_Utils_Array::value('contact_type', $params))) {
    case 'household':
      civicrm_api3_verify_mandatory($params, NULL, ['household_name']);
      break;

    case 'organization':
      civicrm_api3_verify_mandatory($params, NULL, ['organization_name']);
      break;

    case 'individual':
      civicrm_api3_verify_one_mandatory($params, NULL, [
          'first_name',
          'last_name',
          'email',
          'display_name',
        ]
      );
      break;
  }

  if (!empty($params['contact_sub_type']) && !empty($params['contact_type'])) {
    if (!(CRM_Contact_BAO_ContactType::isExtendsContactType($params['contact_sub_type'], $params['contact_type']))) {
      throw new API_Exception("Invalid or Mismatched Contact Subtype: " . implode(', ', (array) $params['contact_sub_type']));
    }
  }

  // The BAO no longer supports the legacy param "current_employer" so here is a shim for api backward-compatability
  if (!empty($params['current_employer'])) {
    $organizationParams = [
      'organization_name' => $params['current_employer'],
    ];

    $dupeIds = CRM_Contact_BAO_Contact::getDuplicateContacts($organizationParams, 'Organization', 'Supervised', [], FALSE);

    // check for mismatch employer name and id
    if (!empty($params['employer_id']) && !in_array($params['employer_id'], $dupeIds)) {
      throw new API_Exception('Employer name and Employer id Mismatch');
    }

    // show error if multiple organisation with same name exist
    if (empty($params['employer_id']) && (count($dupeIds) > 1)) {
      throw new API_Exception('Found more than one Organisation with same Name.');
    }

    if ($dupeIds) {
      $params['employer_id'] = $dupeIds[0];
    }
    else {
      $result = civicrm_api3('Contact', 'create', [
        'organization_name' => $params['current_employer'],
        'contact_type' => 'Organization',
      ]);
      $params['employer_id'] = $result['id'];
    }
  }

  return NULL;
}

/**
 * Helper function for Contact create.
 *
 * @param array $params
 *   (reference ) an assoc array of name/value pairs.
 * @param int $contactID
 *   If present the contact with that ID is updated.
 *
 * @return CRM_Contact_BAO_Contact|CRM_Core_Error
 */
function _civicrm_api3_contact_update($params, $contactID = NULL) {
  //@todo - doesn't contact create support 'id' which is already set- check & remove
  if ($contactID) {
    $params['contact_id'] = $contactID;
  }

  return CRM_Contact_BAO_Contact::create($params);
}

/**
 * Validate the addressee or email or postal greetings.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @throws API_Exception
 */
function _civicrm_api3_greeting_format_params($params) {
  $greetingParams = ['', '_id', '_custom'];
  foreach (['email', 'postal', 'addressee'] as $key) {
    $greeting = '_greeting';
    if ($key == 'addressee') {
      $greeting = '';
    }

    $formatParams = FALSE;
    // Unset display value from params.
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
    $filter = [
      'greeting_type' => "{$key}{$greeting}",
    ];

    $greetings = CRM_Core_PseudoConstant::greeting($filter);
    $greetingId = CRM_Utils_Array::value("{$key}{$greeting}_id", $params);
    $greetingVal = CRM_Utils_Array::value("{$key}{$greeting}", $params);
    $customGreeting = CRM_Utils_Array::value("{$key}{$greeting}_custom", $params);

    if (!$greetingId && $greetingVal) {
      $params["{$key}{$greeting}_id"] = CRM_Utils_Array::key($params["{$key}{$greeting}"], $greetings);
    }

    if ($customGreeting && $greetingId &&
      ($greetingId != array_search('Customized', $greetings))
    ) {
      throw new API_Exception(ts('Provide either %1 greeting id and/or %1 greeting or custom %1 greeting',
        [1 => $key]
      ));
    }

    if ($greetingVal && $greetingId &&
      ($greetingId != CRM_Utils_Array::key($greetingVal, $greetings))
    ) {
      throw new API_Exception(ts('Mismatch in %1 greeting id and %1 greeting',
        [1 => $key]
      ));
    }

    if ($greetingId) {
      if (!$customGreeting && ($greetingId == array_search('Customized', $greetings))) {
        throw new API_Exception(ts('Please provide a custom value for %1 greeting',
          [1 => $key]
        ));
      }
    }
    elseif ($greetingVal) {

      if (!in_array($greetingVal, $greetings)) {
        throw new API_Exception(ts('Invalid %1 greeting', [1 => $key]));
      }

      $greetingId = CRM_Utils_Array::key($greetingVal, $greetings);
    }

    if ($customGreeting) {
      $greetingId = CRM_Utils_Array::key('Customized', $greetings);
    }

    $customValue = isset($params['contact_id']) ? CRM_Core_DAO::getFieldValue(
      'CRM_Contact_DAO_Contact',
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
 * Adjust Metadata for Get action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_contact_getquick_spec(&$params) {
  $params['name']['api.required'] = TRUE;
  $params['name']['title'] = ts('String to search on');
  $params['name']['type'] = CRM_Utils_Type::T_STRING;
  $params['field']['type'] = CRM_Utils_Type::T_STRING;
  $params['field']['title'] = ts('Field to search on');
  $params['field']['options'] = [
    '',
    'id',
    'contact_id',
    'external_identifier',
    'first_name',
    'last_name',
    'job_title',
    'postal_code',
    'street_address',
    'email',
    'city',
    'phone_numeric',
  ];
  $params['table_name']['type'] = CRM_Utils_Type::T_STRING;
  $params['table_name']['title'] = ts('Table alias to search on');
  $params['table_name']['api.default'] = 'cc';
}

/**
 * Old Contact quick search api.
 *
 * @deprecated
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_contact_getquick($params) {
  $name = CRM_Utils_Type::escape(CRM_Utils_Array::value('name', $params), 'String');
  $table_name = CRM_Utils_String::munge($params['table_name']);
  // get the autocomplete options from settings
  $acpref = explode(CRM_Core_DAO::VALUE_SEPARATOR,
    CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_autocomplete_options'
    )
  );

  $table_names = [
    'email' => 'eml',
    'phone_numeric' => 'phe',
    'street_address' => 'sts',
    'city' => 'sts',
    'postal_code' => 'sts',
  ];

  // get the option values for contact autocomplete
  $acOptions = CRM_Core_OptionGroup::values('contact_autocomplete_options', FALSE, FALSE, FALSE, NULL, 'name');

  $list = $from = [];
  foreach ($acpref as $value) {
    if ($value && !empty($acOptions[$value])) {
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
    if (isset($table_names[$field_name])) {
      $table_name = $table_names[$field_name];
    }
    elseif (strpos($field_name, 'custom_') === 0) {
      $customField = civicrm_api3('CustomField', 'getsingle', [
        'id' => substr($field_name, 7),
        'return' => [
          'custom_group_id.table_name',
          'column_name',
          'data_type',
          'option_group_id',
          'html_type',
        ],
      ]);
      $field_name = $customField['column_name'];
      $table_name = CRM_Utils_String::munge($customField['custom_group_id.table_name']);
      $from[$field_name] = "LEFT JOIN `$table_name` ON cc.id = `$table_name`.entity_id";
      if (CRM_Core_BAO_CustomField::hasOptions($customField)) {
        $customOptionsWhere = [];
        $customFieldOptions = CRM_Contact_BAO_Contact::buildOptions('custom_' . $customField['id'], 'search');
        $isMultivalueField = CRM_Core_BAO_CustomField::isSerialized($customField);
        $sep = CRM_Core_DAO::VALUE_SEPARATOR;
        foreach ($customFieldOptions as $optionKey => $optionLabel) {
          if (mb_stripos($optionLabel, $name) !== FALSE) {
            $customOptionsWhere[$optionKey] = "$table_name.$field_name " . ($isMultivalueField ? "LIKE '%{$sep}{$optionKey}{$sep}%'" : "= '$optionKey'");
          }
        }
      }
    }
    // phone_numeric should be phone
    $searchField = str_replace('_numeric', '', $field_name);
    if (!in_array($searchField, $list)) {
      $list[] = $searchField;
    }
  }
  else {
    // Set field name to first name for exact match checking.
    $field_name = 'sort_name';
  }

  $select = $actualSelectElements = ['sort_name'];
  $where = '';
  foreach ($list as $value) {
    $suffix = substr($value, 0, 2) . substr($value, -1);
    switch ($value) {
      case 'street_address':
      case 'city':
      case 'postal_code':
        $selectText = $value;
        $value = "address";
        $suffix = 'sts';
      case 'phone':
      case 'email':
        $actualSelectElements[] = $select[] = ($value == 'address') ? $selectText : $value;
        if ($value == 'phone') {
          $actualSelectElements[] = $select[] = 'phone_ext';
        }
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
        if ($value == 'id') {
          $actualSelectElements[] = 'cc.id';
        }
        elseif ($value != 'sort_name') {
          $suffix = 'cc';
          if ($field_name == $value) {
            $suffix = $table_name;
          }
          $actualSelectElements[] = $select[] = $suffix . '.' . $value;
        }
        break;
    }
  }

  $config = CRM_Core_Config::singleton();
  $as = $select;
  $select = implode(', ', $select);
  if (!empty($select)) {
    $select = ", $select";
  }
  $actualSelectElements = implode(', ', $actualSelectElements);
  $from = implode(' ', $from);
  $limit = (int) CRM_Utils_Array::value('limit', $params);
  $limit = $limit > 0 ? $limit : Civi::settings()->get('search_autocomplete_count');

  // add acl clause here
  list($aclFrom, $aclWhere) = CRM_Contact_BAO_Contact_Permission::cacheClause('cc');

  if ($aclWhere) {
    $where .= " AND $aclWhere ";
  }
  $isPrependWildcard = \Civi::settings()->get('includeWildCardInName');

  if (!empty($params['org'])) {
    $where .= " AND contact_type = \"Organization\"";

    // CRM-7157, hack: get current employer details when
    // employee_id is present.
    $currEmpDetails = [];
    if (!empty($params['employee_id'])) {
      if ($currentEmployer = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        (int) $params['employee_id'],
        'employer_id'
      )) {
        if ($isPrependWildcard) {
          $strSearch = "%$name%";
        }
        else {
          $strSearch = "$name%";
        }

        // get current employer details
        $dao = CRM_Core_DAO::executeQuery("SELECT cc.id as id, CONCAT_WS( ' :: ', {$actualSelectElements} ) as data, sort_name
                    FROM civicrm_contact cc {$from} WHERE cc.contact_type = \"Organization\" AND cc.id = {$currentEmployer} AND cc.sort_name LIKE '$strSearch'");
        if ($dao->fetch()) {
          $currEmpDetails = [
            'id' => $dao->id,
            'data' => $dao->data,
          ];
        }
      }
    }
  }

  if (!empty($params['contact_sub_type'])) {
    $contactSubType = CRM_Utils_Type::escape($params['contact_sub_type'], 'String');
    $where .= " AND cc.contact_sub_type = '{$contactSubType}'";
  }

  if (!empty($params['contact_type'])) {
    $contactType = CRM_Utils_Type::escape($params['contact_type'], 'String');
    $where .= " AND cc.contact_type LIKE '{$contactType}'";
  }

  // Set default for current_employer or return contact with particular id
  if (!empty($params['id'])) {
    $where .= " AND cc.id = " . (int) $params['id'];
  }

  if (!empty($params['cid'])) {
    $where .= " AND cc.id <> " . (int) $params['cid'];
  }

  // Contact's based of relationhip type
  $relType = NULL;
  if (!empty($params['rel'])) {
    $relation = explode('_', CRM_Utils_Array::value('rel', $params));
    $relType = CRM_Utils_Type::escape($relation[0], 'Integer');
    $rel = CRM_Utils_Type::escape($relation[2], 'String');
  }

  if ($isPrependWildcard) {
    $strSearch = "%$name%";
  }
  else {
    $strSearch = "$name%";
  }
  $includeEmailFrom = $includeNickName = '';
  if ($config->includeNickNameInName) {
    $includeNickName = " OR nick_name LIKE '$strSearch'";
  }

  if (isset($customOptionsWhere)) {
    $customOptionsWhere = $customOptionsWhere ?: [0];
    $whereClause = " WHERE (" . implode(' OR ', $customOptionsWhere) . ") $where";
  }
  elseif (!empty($params['field_name']) && !empty($params['table_name']) && $params['field_name'] != 'sort_name') {
    $whereClause = " WHERE ( $table_name.$field_name LIKE '$strSearch') {$where}";
    // Search by id should be exact
    if ($field_name == 'id' || $field_name == 'external_identifier') {
      $whereClause = " WHERE ( $table_name.$field_name = '$name') {$where}";
    }
  }
  else {
    $whereClause = " WHERE ( sort_name LIKE '$strSearch' $includeNickName ) {$where} ";
    if ($config->includeEmailInName) {
      if (!in_array('email', $list)) {
        $includeEmailFrom = "LEFT JOIN civicrm_email eml ON ( cc.id = eml.contact_id AND eml.is_primary = 1 )";
      }
      $emailWhere = " WHERE email LIKE '$strSearch'";
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
  if (!empty($params['cmsuser'])) {
    $additionalFrom = "
      INNER JOIN civicrm_uf_match um ON (um.contact_id=cc.id)
      ";
  }
  $orderBy = _civicrm_api3_quicksearch_get_order_by($name, $isPrependWildcard, $field_name);

  //CRM-5954
  $query = "
        SELECT DISTINCT(id), data, sort_name, exactFirst
        FROM   (
            ( SELECT IF($table_name.$field_name = '{$name}', 0, 1) as exactFirst, cc.id as id, CONCAT_WS( ' :: ',
            {$actualSelectElements} )
             as data
            {$select}
            FROM   civicrm_contact cc {$from}
    {$aclFrom}
    {$additionalFrom}
    {$whereClause}
    {$orderBy}
    LIMIT 0, {$limit} )
    ";

  if (!empty($emailWhere)) {
    $query .= "
      UNION (
        SELECT IF($table_name.$field_name = '{$name}', 0, 1) as exactFirst, cc.id as id, CONCAT_WS( ' :: ',
          {$actualSelectElements} )
          as data
          {$select}
          FROM   civicrm_contact cc {$from}
        {$aclFrom}
        {$additionalFrom} {$includeEmailFrom}
        {$emailWhere} AND cc.is_deleted = 0 " . ($aclWhere ? " AND $aclWhere " : '') . "
        {$orderBy}
      LIMIT 0, {$limit}
      )
    ";
  }
  $query .= ") t
    {$orderBy}
    LIMIT    0, {$limit}
  ";

  // send query to hook to be modified if needed
  CRM_Utils_Hook::contactListQuery($query,
    $name,
    empty($params['context']) ? NULL : CRM_Utils_Type::escape($params['context'], 'String'),
    empty($params['id']) ? NULL : $params['id']
  );

  $dao = CRM_Core_DAO::executeQuery($query);

  $contactList = [];
  $listCurrentEmployer = TRUE;
  while ($dao->fetch()) {
    $t = ['id' => $dao->id];
    foreach ($as as $k) {
      $t[$k] = isset($dao->$k) ? $dao->$k : '';
    }
    $t['data'] = $dao->data;
    // Replace keys with values when displaying fields from an option list
    if (!empty($customOptionsWhere)) {
      $data = explode(' :: ', $dao->data);
      $pos = count($data) - 1;
      $customValue = array_intersect(CRM_Utils_Array::explodePadded($data[$pos]), array_keys($customOptionsWhere));
      $data[$pos] = implode(', ', array_intersect_key($customFieldOptions, array_flip($customValue)));
      $t['data'] = implode(' :: ', $data);
    }
    $contactList[] = $t;
    if (!empty($params['org']) &&
      !empty($currEmpDetails) &&
      $dao->id == $currEmpDetails['id']
    ) {
      $listCurrentEmployer = FALSE;
    }
  }

  //return organization name if doesn't exist in db
  if (empty($contactList)) {
    if (!empty($params['org'])) {
      if ($listCurrentEmployer && !empty($currEmpDetails)) {
        $contactList = [
          [
            'data' => $currEmpDetails['data'],
            'id' => $currEmpDetails['id'],
          ],
        ];
      }
      else {
        $contactList = [
          [
            'data' => $name,
            'id' => $name,
          ],
        ];
      }
    }
  }

  return civicrm_api3_create_success($contactList, $params, 'Contact', 'getquick');
}

/**
 * Get the order by string for the quicksearch query.
 *
 * Get the order by string. The string might be
 *  - sort name if there is no search value provided and the site is configured
 *    to search by sort name
 *  - empty if there is no search value provided and the site is not configured
 *    to search by sort name
 *  - exactFirst and then sort name if a search value is provided and the site is configured
 *    to search by sort name
 *  - exactFirst if a search value is provided and the site is not configured
 *    to search by sort name
 *
 * exactFirst means 'yes if the search value exactly matches the searched field. else no'.
 * It is intended to prioritise exact matches for the entered string so on a first name search
 * for 'kath' contacts with a first name of exactly Kath rise to the top.
 *
 * On short strings it is expensive. Per CRM-19547 there is still an open question
 * as to whether we should only do exactMatch on a minimum length or on certain fields.
 *
 * However, we have mitigated this somewhat by not doing an exact match search on
 * empty strings, non-wildcard sort-name searches and email searches where there is
 * no @ after the first character.
 *
 * For the user it is further mitigated by the fact they just don't know the
 * slower queries are firing. If they type 'smit' slowly enough 4 queries will trigger
 * but if the first 3 are slow the first result they see may be off the 4th query.
 *
 * @param string $name
 * @param bool $isPrependWildcard
 * @param string $field_name
 *
 * @return string
 */
function _civicrm_api3_quicksearch_get_order_by($name, $isPrependWildcard, $field_name) {
  $skipExactMatch = ($name === '%');
  if ($field_name === 'email' && !strpos('@', $name)) {
    $skipExactMatch = TRUE;
  }

  if (!\Civi::settings()->get('includeOrderByClause')) {
    return $skipExactMatch ? '' : "ORDER BY exactFirst";
  }
  if ($skipExactMatch || (!$isPrependWildcard && $field_name === 'sort_name')) {
    // If there is no wildcard then sorting by exactFirst would have the same
    // effect as just a sort_name search, but slower.
    return "ORDER BY sort_name";
  }

  return "ORDER BY exactFirst, sort_name";
}

/**
 * Declare deprecated api functions.
 *
 * @deprecated api notice
 * @return array
 *   Array of deprecated actions
 */
function _civicrm_api3_contact_deprecation() {
  return ['getquick' => 'The "getquick" action is deprecated in favor of "getlist".'];
}

/**
 * Merges given pair of duplicate contacts.
 *
 * @param array $params
 *   Allowed array keys are:
 *   -int main_id: main contact id with whom merge has to happen
 *   -int other_id: duplicate contact which would be deleted after merge operation
 *   -string mode: "safe" skips the merge if there are no conflicts. Does a force merge otherwise.
 *
 * @return array
 *   API Result Array
 * @throws API_Exception
 */
function civicrm_api3_contact_merge($params) {
  if (($result = CRM_Dedupe_Merger::merge(
    [['srcID' => $params['to_remove_id'], 'dstID' => $params['to_keep_id']]],
    [],
    $params['mode'],
    FALSE,
    CRM_Utils_Array::value('check_permissions', $params)
    )) != FALSE) {

    return civicrm_api3_create_success($result, $params);
  }
  throw new API_Exception('Merge failed');
}

/**
 * Adjust metadata for contact_merge api function.
 *
 * @param array $params
 */
function _civicrm_api3_contact_merge_spec(&$params) {
  $params['to_remove_id'] = [
    'title' => 'ID of the contact to merge & remove',
    'description' => ts('Wow - these 2 params are the logical reverse of what I expect - but what to do?'),
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
    'api.aliases' => ['main_id'],
  ];
  $params['to_keep_id'] = [
    'title' => 'ID of the contact to keep',
    'description' => ts('Wow - these 2 params are the logical reverse of what I expect - but what to do?'),
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
    'api.aliases' => ['other_id'],
  ];
  $params['mode'] = [
    // @todo need more detail on what this means.
    'title' => 'Dedupe mode',
    'api.default' => 'safe',
  ];
}

/**
 * Get the ultimate contact a contact was merged to.
 *
 * @param array $params
 *
 * @return array
 *   API Result Array
 * @throws API_Exception
 */
function civicrm_api3_contact_getmergedto($params) {
  $contactID = _civicrm_api3_contact_getmergedto($params);
  if ($contactID) {
    $values = [$contactID => ['id' => $contactID]];
  }
  else {
    $values = [];
  }
  return civicrm_api3_create_success($values, $params);
}

/**
 * Get the contact our contact was finally merged to.
 *
 * If the contact has been merged multiple times the crucial parent activity will have
 * wound up on the ultimate contact so we can figure out the final resting place of the
 * contact with only 2 activities even if 50 merges took place.
 *
 * @param array $params
 *
 * @return int|false
 */
function _civicrm_api3_contact_getmergedto($params) {
  $contactID = FALSE;
  $deleteActivity = civicrm_api3('ActivityContact', 'get', [
    'contact_id' => $params['contact_id'],
    'activity_id.activity_type_id' => 'Contact Deleted By Merge',
    'is_deleted' => 0,
    'is_test' => $params['is_test'],
    'record_type_id' => 'Activity Targets',
    'return' => ['activity_id.parent_id'],
    'sequential' => 1,
    'options' => [
      'limit' => 1,
      'sort' => 'activity_id.activity_date_time DESC',
    ],
  ])['values'];
  if (!empty($deleteActivity)) {
    $contactID = civicrm_api3('ActivityContact', 'getvalue', [
      'activity_id' => $deleteActivity[0]['activity_id.parent_id'],
      'record_type_id' => 'Activity Targets',
      'return' => 'contact_id',
    ]);
  }
  return $contactID;
}

/**
 * Adjust metadata for contact_merge api function.
 *
 * @param array $params
 */
function _civicrm_api3_contact_getmergedto_spec(&$params) {
  $params['contact_id'] = [
    'title' => ts('ID of contact to find ultimate contact for'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => TRUE,
  ];
  $params['is_test'] = [
    'title' => ts('Get test deletions rather than live?'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => 0,
  ];
}

/**
 * Get the ultimate contact a contact was merged to.
 *
 * @param array $params
 *
 * @return array
 *   API Result Array
 * @throws API_Exception
 */
function civicrm_api3_contact_getmergedfrom($params) {
  $contacts = _civicrm_api3_contact_getmergedfrom($params);
  return civicrm_api3_create_success($contacts, $params);
}

/**
 * Get all the contacts merged into our contact.
 *
 * @param array $params
 *
 * @return array
 */
function _civicrm_api3_contact_getmergedfrom($params) {
  $activities = [];
  $deleteActivities = civicrm_api3('ActivityContact', 'get', [
    'contact_id' => $params['contact_id'],
    'activity_id.activity_type_id' => 'Contact Merged',
    'is_deleted' => 0,
    'is_test' => $params['is_test'],
    'record_type_id' => 'Activity Targets',
    'return' => 'activity_id',
  ])['values'];

  foreach ($deleteActivities as $deleteActivity) {
    $activities[] = $deleteActivity['activity_id'];
  }
  if (empty($activities)) {
    return [];
  }

  $activityContacts = civicrm_api3('ActivityContact', 'get', [
    'activity_id.parent_id' => ['IN' => $activities],
    'record_type_id' => 'Activity Targets',
    'return' => 'contact_id',
  ])['values'];
  $contacts = [];
  foreach ($activityContacts as $activityContact) {
    $contacts[$activityContact['contact_id']] = ['id' => $activityContact['contact_id']];
  }
  return $contacts;
}

/**
 * Adjust metadata for contact_merge api function.
 *
 * @param array $params
 */
function _civicrm_api3_contact_getmergedfrom_spec(&$params) {
  $params['contact_id'] = [
    'title' => ts('ID of contact to find ultimate contact for'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => TRUE,
  ];
  $params['is_test'] = [
    'title' => ts('Get test deletions rather than live?'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => 0,
  ];
}

/**
 * Adjust metadata for contact_proximity api function.
 *
 * @param array $params
 */
function _civicrm_api3_contact_proximity_spec(&$params) {
  $params['latitude'] = [
    'title' => 'Latitude',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['longitude'] = [
    'title' => 'Longitude',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
  ];

  $params['unit'] = [
    'title' => 'Unit of Measurement',
    'api.default' => 'meter',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Get contacts by proximity.
 *
 * @param array $params
 *
 * @return array
 * @throws Exception
 */
function civicrm_api3_contact_proximity($params) {
  $latitude = CRM_Utils_Array::value('latitude', $params);
  $longitude = CRM_Utils_Array::value('longitude', $params);
  $distance = CRM_Utils_Array::value('distance', $params);

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
  $contacts = [];
  while ($dao->fetch()) {
    $contacts[] = $dao->toArray();
  }

  return civicrm_api3_create_success($contacts, $params, 'Contact', 'get_by_location', $dao);
}


/**
 * Get parameters for getlist function.
 *
 * @see _civicrm_api3_generic_getlist_params
 *
 * @param array $request
 */
function _civicrm_api3_contact_getlist_params(&$request) {
  // get the autocomplete options from settings
  $acpref = explode(CRM_Core_DAO::VALUE_SEPARATOR,
    CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_autocomplete_options'
    )
  );

  // get the option values for contact autocomplete
  $acOptions = CRM_Core_OptionGroup::values('contact_autocomplete_options', FALSE, FALSE, FALSE, NULL, 'name');

  $list = [];
  foreach ($acpref as $value) {
    if ($value && !empty($acOptions[$value])) {
      $list[] = $acOptions[$value];
    }
  }
  // If we are doing quicksearch by a field other than name, make sure that field is added to results
  $field_name = CRM_Utils_String::munge($request['search_field']);
  // Unique name contact_id = id
  if ($field_name == 'contact_id') {
    $field_name = 'id';
  }
  // phone_numeric should be phone
  $searchField = str_replace('_numeric', '', $field_name);
  if (!in_array($searchField, $list)) {
    $list[] = $searchField;
  }
  $request['description_field'] = $list;
  $list[] = 'contact_type';
  $request['params']['return'] = array_unique(array_merge($list, $request['extra']));
  $request['params']['options']['sort'] = 'sort_name';
  // Contact api doesn't support array(LIKE => 'foo') syntax
  if (!empty($request['input'])) {
    $request['params'][$request['search_field']] = $request['input'];
    // Temporarily override wildcard setting
    if (Civi::settings()->get('includeWildCardInName') != $request['add_wildcard']) {
      Civi::$statics['civicrm_api3_contact_getlist']['override_wildcard'] = !$request['add_wildcard'];
      Civi::settings()->set('includeWildCardInName', $request['add_wildcard']);
    }
  }
}

/**
 * Get output for getlist function.
 *
 * @see _civicrm_api3_generic_getlist_output
 *
 * @param array $result
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_contact_getlist_output($result, $request) {
  $output = [];
  if (!empty($result['values'])) {
    $addressFields = array_intersect([
      'street_address',
      'city',
      'state_province',
      'country',
    ],
      $request['params']['return']);
    foreach ($result['values'] as $row) {
      $data = [
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']],
        'description' => [],
      ];
      foreach ($request['description_field'] as $item) {
        if (!strpos($item, '_name') && !in_array($item, $addressFields) && !empty($row[$item])) {
          $data['description'][] = $row[$item];
        }
      }
      $address = [];
      foreach ($addressFields as $item) {
        if (!empty($row[$item])) {
          $address[] = $row[$item];
        }
      }
      if ($address) {
        $data['description'][] = implode(' ', $address);
      }
      if (!empty($request['image_field'])) {
        $data['image'] = isset($row[$request['image_field']]) ? $row[$request['image_field']] : '';
      }
      else {
        $data['icon_class'] = $row['contact_type'];
      }
      $output[] = $data;
    }
  }
  // Restore wildcard override by _civicrm_api3_contact_getlist_params
  if (isset(Civi::$statics['civicrm_api3_contact_getlist']['override_wildcard'])) {
    Civi::settings()->set('includeWildCardInName', Civi::$statics['civicrm_api3_contact_getlist']['override_wildcard']);
    unset(Civi::$statics['civicrm_api3_contact_getlist']['override_wildcard']);
  }
  return $output;
}

/**
 * Check for duplicate contacts.
 *
 * @param array $params
 *   Params per getfields metadata.
 *
 * @return array
 *   API formatted array
 */
function civicrm_api3_contact_duplicatecheck($params) {
  $dupes = CRM_Contact_BAO_Contact::getDuplicateContacts(
    $params['match'],
    $params['match']['contact_type'],
    $params['rule_type'],
    CRM_Utils_Array::value('exclude', $params, []),
    CRM_Utils_Array::value('check_permissions', $params),
    CRM_Utils_Array::value('dedupe_rule_id', $params)
  );
  $values = [];
  if ($dupes && !empty($params['return'])) {
    return civicrm_api3('Contact', 'get', [
      'return' => $params['return'],
      'id' => ['IN' => $dupes],
      'options' => CRM_Utils_Array::value('options', $params),
      'sequential' => CRM_Utils_Array::value('sequential', $params),
      'check_permissions' => CRM_Utils_Array::value('check_permissions', $params),
    ]);
  }
  foreach ($dupes as $dupe) {
    $values[$dupe] = ['id' => $dupe];
  }
  return civicrm_api3_create_success($values, $params, 'Contact', 'duplicatecheck');
}

/**
 * Declare metadata for contact dedupe function.
 *
 * @param $params
 */
function _civicrm_api3_contact_duplicatecheck_spec(&$params) {
  $params['dedupe_rule_id'] = [
    'title' => 'Dedupe Rule ID (optional)',
    'description' => 'This will default to the built in unsupervised rule',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['rule_type'] = [
    'title' => 'Dedupe Rule Type',
    'description' => 'If no rule id specified, pass "Unsupervised" or "Supervised"',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => 'Unsupervised',
  ];
  // @todo declare 'match' parameter. We don't have a standard for type = array yet.
}
