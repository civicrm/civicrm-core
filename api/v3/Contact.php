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
 * @return array
 *   API Result Array
 *
 * @throws \CRM_Core_Exception
 * @throws CRM_Core_Exception
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_contact_create($params) {
  $contactID = $params['contact_id'] ?? $params['id'] ?? NULL;

  if ($contactID && !empty($params['check_permissions']) && !CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::EDIT)) {
    throw new \Civi\API\Exception\UnauthorizedException('Permission denied to modify contact record');
  }

  if (!empty($params['dupe_check'])) {
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($params, $params['contact_type'], 'Unsupervised', [], $params['check_permission']);
    if (count($ids) > 0) {
      throw new CRM_Core_Exception("Found matching contacts: " . implode(',', $ids), "duplicate", ["ids" => $ids]);
    }
  }

  $values = _civicrm_api3_contact_check_params($params);
  if ($values) {
    return $values;
  }

  if (!$contactID) {
    // If we get here, we're ready to create a new contact
    $email = $params['email'] ?? NULL;
    if ($email && !is_array($params['email'])) {
      $defLocType = CRM_Core_BAO_LocationType::getDefault();
      $params['email'] = [
        1 => [
          'email' => $email,
          'is_primary' => 1,
          'location_type_id' => $defLocType->id ?: 1,
        ],
      ];
    }
  }

  if (!empty($params['home_url'])) {
    $websiteTypes = CRM_Core_DAO_Website::buildOptions('website_type_id');
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
    if (!$params['contact_type']) {
      throw new CRM_Core_Exception('Contact id ' . $contactID . ' not found.');
    }
  }

  if (!isset($params['contact_sub_type']) && $contactID) {
    $params['contact_sub_type'] = CRM_Contact_BAO_Contact::getContactSubType($contactID);
  }

  _civicrm_api3_custom_format_params($params, $values, $params['contact_type'], $contactID);

  $params = array_merge($params, $values);
  //@todo we should just call basic_create here - but need to make contact:create accept 'id' on the bao
  $contact = _civicrm_api3_contact_update($params, $contactID);

  if (is_a($contact, 'CRM_Core_Error')) {
    throw new CRM_Core_Exception($contact->_errors[0]['message']);
  }
  else {
    $values = [];
    _civicrm_api3_object_to_array_unique_fields($contact, $values[$contact->id]);
  }

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
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_contact_get($params) {
  $options = [];
  _civicrm_api3_contact_get_supportanomalies($params, $options);
  $contacts = _civicrm_api3_get_using_query_object('Contact', $params, $options);
  if (!empty($params['check_permissions'])) {
    CRM_Contact_BAO_Contact::unsetProtectedFields($contacts);
  }
  return civicrm_api3_create_success($contacts, $params, 'Contact');
}

/**
 * Get number of contacts matching the supplied criteria.
 *
 * @param array $params
 *
 * @return int
 * @throws \CRM_Core_Exception
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
  if (!empty($params['email']) && !is_array($params['email'])) {
    // Fix this to be in array format so the query object does not add LIKE
    // I think there is a better fix that I will do for master.
    $params['email'] = ['=' => $params['email']];
  }
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
 * @return array
 *   API Result Array
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_contact_delete($params) {
  $contactID = (int) $params['id'];

  if (!empty($params['check_permissions']) && !CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::DELETE)) {
    throw new \Civi\API\Exception\UnauthorizedException('Permission denied to modify contact record');
  }

  if ($contactID == CRM_Core_Session::getLoggedInContactID()) {
    throw new CRM_Core_Exception('This contact record is linked to the currently logged in user account - and cannot be deleted.');
  }
  $restore = !empty($params['restore']);
  $skipUndelete = !empty($params['skip_undelete']);

  // CRM-12929
  // restrict permanent delete if a contact has financial trxn associated with it
  $error = NULL;
  if ($skipUndelete && CRM_Financial_BAO_FinancialItem::checkContactPresent([$contactID], $error)) {
    throw new CRM_Core_Exception($error['_qf_default']);
  }
  if (CRM_Contact_BAO_Contact::deleteContact($contactID, $restore, $skipUndelete, $params['check_permissions'] ?? FALSE)) {
    return civicrm_api3_create_success();
  }
  throw new CRM_Core_Exception('Could not delete contact');
}

/**
 * Check parameters passed in.
 *
 * This function is on it's way out.
 *
 * @param array $params
 *
 * @return null
 * @throws CRM_Core_Exception
 * @throws CRM_Core_Exception
 */
function _civicrm_api3_contact_check_params(&$params) {

  switch (strtolower($params['contact_type'] ?? '')) {
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
      ]);
      break;
  }

  if (!empty($params['contact_sub_type']) && !empty($params['contact_type'])) {
    if (!(CRM_Contact_BAO_ContactType::isExtendsContactType($params['contact_sub_type'], $params['contact_type']))) {
      throw new CRM_Core_Exception("Invalid or Mismatched Contact Subtype: " . implode(', ', (array) $params['contact_sub_type']));
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
      throw new CRM_Core_Exception('Employer name and Employer id Mismatch');
    }

    // show error if multiple organisation with same name exist
    if (empty($params['employer_id']) && (count($dupeIds) > 1)) {
      throw new CRM_Core_Exception('Found more than one Organisation with same Name.');
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
 *
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
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
 * @throws CRM_Core_Exception
 * @throws \CRM_Core_Exception
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
    $greetingId = $params["{$key}{$greeting}_id"] ?? NULL;
    $greetingVal = $params["{$key}{$greeting}"] ?? NULL;
    $customGreeting = $params["{$key}{$greeting}_custom"] ?? NULL;

    if (!$greetingId && $greetingVal) {
      $params["{$key}{$greeting}_id"] = CRM_Utils_Array::key($params["{$key}{$greeting}"], $greetings);
    }

    if ($customGreeting && $greetingId &&
      ($greetingId != array_search('Customized', $greetings))
    ) {
      throw new CRM_Core_Exception(ts('Provide either %1 greeting id and/or %1 greeting or custom %1 greeting',
        [1 => $key]
      ));
    }

    if ($greetingVal && $greetingId &&
      ($greetingId != CRM_Utils_Array::key($greetingVal, $greetings))
    ) {
      throw new CRM_Core_Exception(ts('Mismatch in %1 greeting id and %1 greeting',
        [1 => $key]
      ));
    }

    if ($greetingId) {
      if (!$customGreeting && ($greetingId == array_search('Customized', $greetings))) {
        throw new CRM_Core_Exception(ts('Please provide a custom value for %1 greeting',
          [1 => $key]
        ));
      }
    }
    elseif ($greetingVal) {

      if (!in_array($greetingVal, $greetings)) {
        throw new CRM_Core_Exception(ts('Invalid %1 greeting', [1 => $key]));
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
 * Merges given pair of duplicate contacts.
 *
 * @param array $params
 *   Allowed array keys are:
 *   -int to_keep_id: main contact id with whom merge has to happen
 *   -int to_remove_id: duplicate contact which would be deleted after merge operation
 *   -string mode: "safe" skips the merge if there are no conflicts. Does a force merge otherwise.
 *
 * @return array
 *   API Result Array
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_contact_merge($params) {
  if (($result = CRM_Dedupe_Merger::merge(
    [['srcID' => $params['to_remove_id'], 'dstID' => $params['to_keep_id']]],
    [],
    $params['mode'],
    FALSE,
    $params['check_permissions'] ?? FALSE
    )) != FALSE) {

    return civicrm_api3_create_success($result, $params);
  }
  throw new CRM_Core_Exception('Merge failed');
}

/**
 * Adjust metadata for contact_merge api function.
 *
 * @param array $params
 */
function _civicrm_api3_contact_merge_spec(&$params) {
  $params['to_remove_id'] = [
    'title' => ts('ID of the contact to merge & remove'),
    'description' => ts('Wow - these 2 aliased params are the logical reverse of what I expect - but what to do?'),
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
    'api.aliases' => ['main_id'],
  ];
  $params['to_keep_id'] = [
    'title' => ts('ID of the contact to keep'),
    'description' => ts('Wow - these 2 aliased params are the logical reverse of what I expect - but what to do?'),
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
    'api.aliases' => ['other_id'],
  ];
  $params['mode'] = [
    'title' => ts('Dedupe mode'),
    'description' => ts("In 'safe' mode conflicts will result in no merge. In 'aggressive' mode the merge will still proceed (hook dependent)"),
    'api.default' => 'safe',
    'options' => ['safe' => ts('Abort on unhandled conflict'), 'aggressive' => ts('Proceed on unhandled conflict. Note hooks may change handling here.')],
  ];
}

/**
 * Determines if given pair of contaacts have conflicts that would affect merging them.
 *
 * @param array $params
 *   Allowed array keys are:
 *   -int main_id: main contact id with whom merge has to happen
 *   -int other_id: duplicate contact which would be deleted after merge operation
 *   -string mode: "safe" skips the merge if there are no conflicts. Does a force merge otherwise.
 *
 * @return array
 *   API Result Array
 *
 * @throws \CRM_Core_Exception
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_contact_get_merge_conflicts($params) {
  $migrationInfo = [];
  $result = [];
  foreach ((array) $params['mode'] as $mode) {
    $result[$mode] = CRM_Dedupe_Merger::getConflicts(
      $migrationInfo,
      (int) $params['to_remove_id'], (int) $params['to_keep_id'],
      $mode
    );
  }
  return civicrm_api3_create_success($result, $params);
}

/**
 * Adjust metadata for contact_merge api function.
 *
 * @param array $params
 */
function _civicrm_api3_contact_get_merge_conflicts_spec(&$params) {
  $params['to_remove_id'] = [
    'title' => ts('ID of the contact to merge & remove'),
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['to_keep_id'] = [
    'title' => ts('ID of the contact to keep'),
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['mode'] = [
    'title' => ts('Dedupe mode'),
    'description' => ts("'safe' or 'aggressive'  - these modes map to the merge actions & may affect resolution done by hooks "),
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
 *
 * @throws \CRM_Core_Exception
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
 *
 * @throws \CRM_Core_Exception
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
 *
 * @throws \CRM_Core_Exception
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
 *
 * @throws \CRM_Core_Exception
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
  $latitude = $params['latitude'] ?? NULL;
  $longitude = $params['longitude'] ?? NULL;
  $distance = $params['distance'] ?? NULL;

  $unit = $params['unit'] ?? NULL;

  // check and ensure that lat/long and distance are floats
  if (
    // We should just declare the data type correctly in the _spec function
    // and leave this to the api layer, but reluctant to make changes to
    // apiv3 now.
    !is_numeric($latitude) ||
    !is_numeric($longitude) ||
    !is_numeric($distance)
  ) {
    throw new CRM_Core_Exception(ts('Latitude, Longitude and Distance should exist and be numeric'));
  }

  if ($unit === 'mile') {
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
  if ($field_name === 'contact_id') {
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
    if (Civi::settings()->get('includeWildCardInName') !== $request['add_wildcard']) {
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
        $data['image'] = $row[$request['image_field']] ?? '';
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
 *
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_contact_duplicatecheck($params) {
  if (!isset($params['match']) || !is_array($params['match'])) {
    throw new \CRM_Core_Exception('Duplicate check must include criteria to check against (missing or invalid $params[\'match\']).');
  }
  if (!isset($params['match']['contact_type']) || !is_string($params['match']['contact_type'])) {
    throw new \CRM_Core_Exception('Duplicate check must include a contact type. (missing or invalid $params[\'match\'][\'contact_type\'])');
  }
  $dupes = CRM_Contact_BAO_Contact::getDuplicateContacts(
    $params['match'],
    $params['match']['contact_type'],
    $params['rule_type'] ?? '',
    $params['exclude'] ?? [],
    $params['check_permissions'] ?? FALSE,
    $params['dedupe_rule_id'] ?? NULL
  );
  $values = [];
  if ($dupes && !empty($params['return'])) {
    return civicrm_api3('Contact', 'get', [
      'return' => $params['return'],
      'id' => ['IN' => $dupes],
      'options' => $params['options'] ?? NULL,
      'sequential' => $params['sequential'] ?? NULL,
      'check_permissions' => $params['check_permissions'] ?? NULL,
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
 * @param array $params
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
