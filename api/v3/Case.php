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
 * This api exposes CiviCRM Case objects.
 * Developed by woolman.org
 *
 * @package CiviCRM_APIv3
 */

/**
 * Open a new case, add client and manager roles, and standard timeline.
 *
 * @param array $params
 *
 * @code
 * // REQUIRED for create:
 * 'case_type_id' => int OR
 * 'case_type' => str (provide one or the other)
 * 'contact_id' => int // case client
 * 'subject' => str
 * // REQUIRED for update:
 * 'id' => case Id
 *
 * //OPTIONAL
 * 'medium_id' => int // see civicrm option values for possibilities
 * 'creator_id' => int // case manager, default to the logged in user
 * 'status_id' => int // defaults to 1 "ongoing"
 * 'location' => str
 * 'start_date' => str datestamp // defaults to: date('YmdHis')
 * 'duration' => int // in minutes
 * 'details' => str // html format
 * @endcode
 *
 * @throws API_Exception
 * @return array
 *   api result array
 */
function civicrm_api3_case_create($params) {
  _civicrm_api3_case_format_params($params);

  if (empty($params['id'])) {
    // Creating a new case, so make sure we have the necessary parameters
    civicrm_api3_verify_mandatory($params, NULL, [
      'contact_id',
      'subject',
      ['case_type', 'case_type_id'],
    ]);
  }
  else {
    // Update an existing case
    // FIXME: Some of this logic should move to the BAO object?
    // FIXME: Should we check if case with ID actually exists?

    if (array_key_exists('creator_id', $params)) {
      throw new API_Exception('You cannot update creator id');
    }

    $mergedCaseIds = $origContactIds = [];

    // If a contact ID is specified we need to make sure this is the main contact ID for the case (and update if necessary)
    if (!empty($params['contact_id'])) {
      $origContactIds = CRM_Case_BAO_Case::retrieveContactIdsByCaseId($params['id']);

      // Get the original contact ID for the case
      // FIXME: Refactor as separate method to get contactId
      if (count($origContactIds) > 1) {
        // Multiple original contact IDs. Need to specify which one to use as a parameter
        if (empty($params['orig_contact_id'])) {
          throw new API_Exception('Case is linked with more than one contact id. Provide the required params orig_contact_id to be replaced');
        }
        if (!empty($params['orig_contact_id']) && !in_array($params['orig_contact_id'], $origContactIds)) {
          throw new API_Exception('Invalid case contact id (orig_contact_id)');
        }
        $origContactId = $params['orig_contact_id'];
      }
      else {
        // Only one original contact ID
        $origContactId = CRM_Utils_Array::first($origContactIds);
      }

      // Get the specified main contact ID for the case
      $mainContactId = CRM_Utils_Array::first($params['contact_id']);

      // If the main contact ID is not in the list of original contact IDs for the case we need to change the main contact ID for the case
      // This means we'll end up with a new case ID
      if (!in_array($mainContactId, $origContactIds)) {
        $mergedCaseIds = CRM_Case_BAO_Case::mergeCases($mainContactId, $params['id'], $origContactId, NULL, TRUE);
        // If we merged cases then the first element will contain the case ID of the merged case - update that one
        $params['id'] = CRM_Utils_Array::first($mergedCaseIds);
      }
    }
  }

  // Create/update the case
  $caseBAO = CRM_Case_BAO_Case::create($params);

  if (!$caseBAO) {
    throw new API_Exception('Case not created. Please check input params.');
  }

  if (isset($params['contact_id']) && !isset($params['id'])) {
    foreach ((array) $params['contact_id'] as $cid) {
      $contactParams = ['case_id' => $caseBAO->id, 'contact_id' => $cid];
      CRM_Case_BAO_CaseContact::create($contactParams);
    }
  }

  if (!isset($params['id'])) {
    // As the API was not passed an id we have created a new case.
    // Only run the xmlProcessor for new cases to get all configuration for the new case.
    _civicrm_api3_case_create_xmlProcessor($params, $caseBAO);
  }

  // return case
  $values = [];
  _civicrm_api3_object_to_array($caseBAO, $values[$caseBAO->id]);

  return civicrm_api3_create_success($values, $params, 'Case', 'create', $caseBAO);
}

/**
 * When creating a new case, run the xmlProcessor to get all necessary params/configuration
 *  for the new case, as cases use an xml file to store their configuration.
 *
 * @param $params
 * @param $caseBAO
 *
 * @throws \Exception
 */
function _civicrm_api3_case_create_xmlProcessor($params, $caseBAO) {
  // Format params for xmlProcessor
  if (isset($caseBAO->id)) {
    $params['id'] = $caseBAO->id;
  }

  // Initialize XML processor with $params
  $xmlProcessor = new CRM_Case_XMLProcessor_Process();
  $xmlProcessorParams = [
    'clientID' => CRM_Utils_Array::value('contact_id', $params),
    'creatorID' => CRM_Utils_Array::value('creator_id', $params),
    'standardTimeline' => 1,
    'activityTypeName' => 'Open Case',
    'caseID' => CRM_Utils_Array::value('id', $params),
    'subject' => CRM_Utils_Array::value('subject', $params),
    'location' => CRM_Utils_Array::value('location', $params),
    'activity_date_time' => CRM_Utils_Array::value('start_date', $params),
    'duration' => CRM_Utils_Array::value('duration', $params),
    'medium_id' => CRM_Utils_Array::value('medium_id', $params),
    'details' => CRM_Utils_Array::value('details', $params),
    'custom' => [],
    'relationship_end_date' => CRM_Utils_Array::value('end_date', $params),
  ];

  // Do it! :-D
  $xmlProcessor->run($params['case_type'], $xmlProcessorParams);
}

/**
 * Adjust Metadata for Get Action.
 *
 * @param array $params
 *   Parameters determined by getfields.
 */
function _civicrm_api3_case_get_spec(&$params) {
  $params['contact_id'] = [
    'api.aliases' => ['client_id'],
    'title' => 'Case Client',
    'description' => 'Contact id of one or more clients to retrieve cases for',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['activity_id'] = [
    'title' => 'Case Activity',
    'description' => 'Id of an activity in the case',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['tag_id'] = [
    'title' => 'Tags',
    'description' => 'Find cases with specified tags.',
    'type' => 1,
    'FKClassName' => 'CRM_Core_DAO_Tag',
    'FKApiName' => 'Tag',
    'supports_joins' => TRUE,
  ];
}

/**
 * Adjust Metadata for Create Action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_case_create_spec(&$params) {
  $params['contact_id'] = [
    'api.aliases' => ['client_id'],
    'title' => 'Case Client',
    'description' => 'Contact id of case client(s)',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
    'FKApiName' => 'Contact',
  ];
  $params['status_id']['api.default'] = 1;
  $params['status_id']['api.aliases'] = ['case_status'];
  $params['creator_id']['api.default'] = 'user_contact_id';
  $params['creator_id']['type'] = CRM_Utils_Type::T_INT;
  $params['creator_id']['title'] = 'Case Created By';
  $params['start_date']['api.default'] = 'now';
  $params['medium_id'] = [
    'name' => 'medium_id',
    'title' => 'Activity Medium',
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Adjust Metadata for Update action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_case_update_spec(&$params) {
  $params['id']['api.required'] = 1;
}

/**
 * Adjust Metadata for Delete action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_case_delete_spec(&$params) {
  $params['id']['api.required'] = 1;
}

/**
 * Get details of a particular case, or search for cases, depending on params.
 *
 * Please provide one (and only one) of the four get/search parameters:
 *
 * @param array $params
 *   'id' => if set, will get all available info about a case, including contacts and activities
 *
 *   // if no case_id provided, this function will use one of the following search parameters:
 *   'client_id' => finds all cases with a specific client
 *   'activity_id' => returns the case containing a specific activity
 *   'contact_id' => finds all cases associated with a contact (in any role, not just client)
 * $params CRM_Utils_SQL_Select $sql
 *   Other apis wishing to wrap & extend this one can pass in a $sql object with extra clauses
 *
 * @throws API_Exception
 * @return array
 *   (get mode, case_id provided): Array with case details, case roles, case activity ids, (search mode, case_id not provided): Array of cases found
 */
function civicrm_api3_case_get($params, $sql = NULL) {
  $options = _civicrm_api3_get_options_from_params($params);
  if (!is_a($sql, 'CRM_Utils_SQL_Select')) {
    $sql = CRM_Utils_SQL_Select::fragment();
  }

  // Add clause to search by client
  if (!empty($params['contact_id'])) {
    // Legacy support - this field historically supports a nonstandard format of array(1,2,3) as a synonym for array('IN' => array(1,2,3))
    if (is_array($params['contact_id'])) {
      $operator = CRM_Utils_Array::first(array_keys($params['contact_id']));
      if (!in_array($operator, \CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $params['contact_id'] = ['IN' => $params['contact_id']];
      }
    }
    else {
      $params['contact_id'] = ['=' => $params['contact_id']];
    }
    $clause = CRM_Core_DAO::createSQLFilter('contact_id', $params['contact_id']);
    $sql->where("a.id IN (SELECT case_id FROM civicrm_case_contact WHERE $clause)");
  }

  // Order by case contact (primary client)
  // Ex: "contact_id", "contact_id.display_name", "contact_id.sort_name DESC".
  if (!empty($options['sort']) && strpos($options['sort'], 'contact_id') !== FALSE) {
    $sort = explode(', ', $options['sort']);
    $contactSort = NULL;
    foreach ($sort as $index => &$sortString) {
      if (strpos($sortString, 'contact_id') === 0) {
        $contactSort = $sortString;
        $sortString = '(1)';
        // Get sort field and direction
        list($sortField, $dir) = array_pad(explode(' ', $contactSort), 2, 'ASC');
        list(, $sortField) = array_pad(explode('.', $sortField), 2, 'id');
        // Validate inputs
        if (!array_key_exists($sortField, CRM_Contact_DAO_Contact::fieldKeys()) || ($dir != 'ASC' && $dir != 'DESC')) {
          throw new API_Exception("Unknown field specified for sort. Cannot order by '$contactSort'");
        }
        $sql->orderBy("case_contact.$sortField $dir", NULL, $index);
      }
    }
    // Remove contact sort params so the basic_get function doesn't see them
    $params['options']['sort'] = implode(', ', $sort);
    unset($params['option_sort'], $params['option.sort'], $params['sort']);
    // Add necessary joins to the first case client
    if ($contactSort) {
      $sql->join('ccc', 'LEFT JOIN (SELECT * FROM civicrm_case_contact WHERE id IN (SELECT MIN(id) FROM civicrm_case_contact GROUP BY case_id)) AS ccc ON ccc.case_id = a.id');
      $sql->join('case_contact', 'LEFT JOIN civicrm_contact AS case_contact ON ccc.contact_id = case_contact.id AND case_contact.is_deleted <> 1');
    }
  }

  // Add clause to search by activity
  if (!empty($params['activity_id'])) {
    if (!CRM_Utils_Rule::positiveInteger($params['activity_id'])) {
      throw new API_Exception('Invalid parameter: activity_id. Must provide a numeric value.');
    }
    $activityId = $params['activity_id'];
    $originalId = CRM_Core_DAO::getFieldValue('CRM_Activity_BAO_Activity', $activityId, 'original_id');
    if ($originalId) {
      $activityId .= ',' . $originalId;
    }
    $sql
      ->join('civicrm_case_activity', 'INNER JOIN civicrm_case_activity ON civicrm_case_activity.case_id = a.id')
      ->where("civicrm_case_activity.activity_id IN ($activityId)");
  }

  // Clause to search by tag
  if (!empty($params['tag_id'])) {
    $dummySpec = [];
    _civicrm_api3_validate_integer($params, 'tag_id', $dummySpec, 'Case');
    if (!is_array($params['tag_id'])) {
      $params['tag_id'] = ['=' => $params['tag_id']];
    }
    $clause = \CRM_Core_DAO::createSQLFilter('tag_id', $params['tag_id']);
    if ($clause) {
      $sql->where('a.id IN (SELECT entity_id FROM civicrm_entity_tag WHERE entity_table = "civicrm_case" AND !clause)', ['!clause' => $clause]);
    }
  }

  $cases = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), ['sequential' => 0] + $params, TRUE, 'Case', $sql);

  if (empty($options['is_count']) && !empty($cases['values'])) {
    // For historic reasons we return these by default only when fetching a case by id
    if (!empty($params['id']) && is_numeric($params['id']) && empty($options['return'])) {
      $options['return'] = [
        'contacts' => 1,
        'activities' => 1,
        'contact_id' => 1,
      ];
    }

    _civicrm_api3_case_read($cases['values'], $options);

    // We disabled sequential to keep the list indexed for case_read(). Now add it back.
    if (!empty($params['sequential'])) {
      $cases['values'] = array_values($cases['values']);
    }
  }

  return $cases;
}

/**
 * Deprecated API.
 *
 * Use activity API instead.
 *
 * @param array $params
 *
 * @throws API_Exception
 * @return array
 */
function civicrm_api3_case_activity_create($params) {
  require_once "api/v3/Activity.php";
  return civicrm_api3_activity_create($params) + [
    'deprecated' => CRM_Utils_Array::value('activity_create', _civicrm_api3_case_deprecation()),
  ];
}

/**
 * Add a timeline to a case.
 *
 * @param array $params
 *
 * @throws API_Exception
 * @return array
 */
function civicrm_api3_case_addtimeline($params) {
  $caseType = CRM_Case_BAO_Case::getCaseType($params['case_id'], 'name');
  $xmlProcessor = new CRM_Case_XMLProcessor_Process();
  $xmlProcessorParams = [
    'clientID' => CRM_Case_BAO_Case::getCaseClients($params['case_id']),
    'creatorID' => $params['creator_id'],
    'standardTimeline' => 0,
    'activity_date_time' => $params['activity_date_time'],
    'caseID' => $params['case_id'],
    'caseType' => $caseType,
    'activitySetName' => $params['timeline'],
  ];
  $xmlProcessor->run($caseType, $xmlProcessorParams);
  return civicrm_api3_create_success();
}

/**
 * Adjust Metadata for addtimeline action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_case_addtimeline_spec(&$params) {
  $params['case_id'] = [
    'title' => 'Case ID',
    'description' => 'Id of case to update',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $params['timeline'] = [
    'title' => 'Timeline',
    'description' => 'Name of activity set',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $params['activity_date_time'] = [
    'api.default' => 'now',
    'title' => 'Activity date time',
    'description' => 'Timeline start date',
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $params['creator_id'] = [
    'api.default' => 'user_contact_id',
    'title' => 'Activity creator',
    'description' => 'Contact id of timeline creator',
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * Merge 2 cases.
 *
 * @param array $params
 *
 * @throws API_Exception
 * @return array
 */
function civicrm_api3_case_merge($params) {
  $clients1 = CRM_Case_BAO_Case::getCaseClients($params['case_id_1']);
  $clients2 = CRM_Case_BAO_Case::getCaseClients($params['case_id_2']);
  CRM_Case_BAO_Case::mergeCases($clients1[0], $params['case_id_1'], $clients2[0], $params['case_id_2']);
  return civicrm_api3_create_success();
}

/**
 * Adjust Metadata for merge action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_case_merge_spec(&$params) {
  $params['case_id_1'] = [
    'title' => 'Case ID 1',
    'description' => 'Id of main case',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $params['case_id_2'] = [
    'title' => 'Case ID 2',
    'description' => 'Id of second case',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
}

/**
 * Declare deprecated api functions.
 *
 * @deprecated api notice
 * @return array
 *   Array of deprecated actions
 */
function _civicrm_api3_case_deprecation() {
  return ['activity_create' => 'Case api "activity_create" action is deprecated. Use the activity api instead.'];
}

/**
 * @deprecated Update a specified case.  Use civicrm_api3_case_create() instead.
 *
 * @param array $params
 *   //REQUIRED:
 *   'case_id' => int
 *
 *   //OPTIONAL
 *   'status_id' => int
 *   'start_date' => str datestamp
 *   'contact_id' => int // case client
 *
 * @throws API_Exception
 * @return array
 *   api result array
 */
function civicrm_api3_case_update($params) {
  if (!isset($params['case_id']) && isset($params['id'])) {
    $params['case_id'] = $params['id'];
  }

  //check parameters
  civicrm_api3_verify_mandatory($params, NULL, ['id']);

  // return error if modifying creator id
  if (array_key_exists('creator_id', $params)) {
    throw new API_Exception(ts('You cannot update creator id'));
  }

  $mCaseId = $origContactIds = [];

  // get original contact id and creator id of case
  if (!empty($params['contact_id'])) {
    $origContactIds = CRM_Case_BAO_Case::retrieveContactIdsByCaseId($params['id']);
    $origContactId = CRM_Utils_Array::first($origContactIds);
  }

  if (count($origContactIds) > 1) {
    // check valid orig contact id
    if (!empty($params['orig_contact_id']) && !in_array($params['orig_contact_id'], $origContactIds)) {
      throw new API_Exception('Invalid case contact id (orig_contact_id)');
    }
    elseif (empty($params['orig_contact_id'])) {
      throw new API_Exception('Case is linked with more than one contact id. Provide the required params orig_contact_id to be replaced');
    }
    $origContactId = $params['orig_contact_id'];
  }

  // check for same contact id for edit Client
  if (!empty($params['contact_id']) && !in_array($params['contact_id'], $origContactIds)) {
    $mCaseId = CRM_Case_BAO_Case::mergeCases($params['contact_id'], $params['case_id'], $origContactId, NULL, TRUE);
  }

  if (!empty($mCaseId[0])) {
    $params['id'] = $mCaseId[0];
  }

  $dao = new CRM_Case_BAO_Case();
  $dao->id = $params['id'];

  $dao->copyValues($params);
  $dao->save();

  $case = [];
  _civicrm_api3_object_to_array($dao, $case);

  return civicrm_api3_create_success([$dao->id => $case], $params, 'Case', 'update', $dao);
}

/**
 * Delete a specified case.
 *
 * @param array $params
 *
 * @code
 *   //REQUIRED:
 *   'id' => int
 *
 *   //OPTIONAL
 *   'move_to_trash' => bool (defaults to false)
 * @endcode
 *
 * @throws API_Exception
 * @return mixed
 */
function civicrm_api3_case_delete($params) {
  //check parameters
  civicrm_api3_verify_mandatory($params, NULL, ['id']);

  if (CRM_Case_BAO_Case::deleteCase($params['id'], CRM_Utils_Array::value('move_to_trash', $params, FALSE))) {
    return civicrm_api3_create_success($params, $params, 'Case', 'delete');
  }
  else {
    throw new API_Exception('Could not delete case.');
  }
}

/**
 * Case.restore API specification
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 */
function _civicrm_api3_case_restore_spec(&$spec) {
  $result = civicrm_api3('Case', 'getfields', ['api_action' => 'delete']);
  $spec = ['id' => $result['values']['id']];
}

/**
 * Restore a specified case from the trash.
 *
 * @param array $params
 * @throws API_Exception
 * @return mixed
 */
function civicrm_api3_case_restore($params) {
  if (CRM_Case_BAO_Case::restoreCase($params['id'])) {
    return civicrm_api3_create_success($params, $params, 'Case', 'restore');
  }
  else {
    throw new API_Exception('Could not restore case.');
  }
}

/**
 * Augment case results with extra data.
 *
 * @param array $cases
 * @param array $options
 */
function _civicrm_api3_case_read(&$cases, $options) {
  foreach ($cases as &$case) {
    if (empty($options['return']) || !empty($options['return']['contact_id'])) {
      // Legacy support for client_id - TODO: in apiv4 remove 'client_id'
      // FIXME: Historically we return a 1-based array. Changing that risks breaking API clients that
      //   have been hardcoded to index "1", instead of the first array index (eg. using reset(), foreach etc)
      $case['client_id'] = $case['contact_id'] = CRM_Case_BAO_Case::retrieveContactIdsByCaseId($case['id'], NULL, 1);
    }
    if (!empty($options['return']['contacts'])) {
      //get case contacts
      $contacts = CRM_Case_BAO_Case::getcontactNames($case['id']);
      $relations = CRM_Case_BAO_Case::getRelatedContacts($case['id']);
      $case['contacts'] = array_unique(array_merge($contacts, $relations), SORT_REGULAR);
    }
    if (!empty($options['return']['activities'])) {
      // add case activities array - we'll populate them in bulk below
      $case['activities'] = [];
    }
    // Properly render this joined field
    if (!empty($options['return']['case_type_id.definition'])) {
      if (!empty($case['case_type_id.definition'])) {
        list($xml) = CRM_Utils_XML::parseString($case['case_type_id.definition']);
      }
      else {
        $caseTypeId = !empty($case['case_type_id']) ? $case['case_type_id'] : CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $case['id'], 'case_type_id');
        $caseTypeName = !empty($case['case_type_id.name']) ? $case['case_type_id.name'] : CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $caseTypeId, 'name');
        $xml = CRM_Case_XMLRepository::singleton()->retrieve($caseTypeName);
      }
      $case['case_type_id.definition'] = [];
      if ($xml) {
        $case['case_type_id.definition'] = CRM_Case_BAO_CaseType::convertXmlToDefinition($xml);
      }
    }
  }
  // Bulk-load activities
  if (!empty($options['return']['activities'])) {
    $query = "SELECT case_id, activity_id FROM civicrm_case_activity WHERE case_id IN (%1)";
    $params = [1 => [implode(',', array_keys($cases)), 'String', CRM_Core_DAO::QUERY_FORMAT_NO_QUOTES]];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $cases[$dao->case_id]['activities'][] = $dao->activity_id;
    }
  }
  // Bulk-load tags. Supports joins onto the tag entity.
  $tagGet = ['tag_id', 'entity_id'];
  foreach (array_keys($options['return']) as $key) {
    if (strpos($key, 'tag_id.') === 0) {
      $tagGet[] = $key;
      $options['return']['tag_id'] = 1;
    }
  }
  if (!empty($options['return']['tag_id'])) {
    $tags = civicrm_api3('EntityTag', 'get', [
      'entity_table' => 'civicrm_case',
      'entity_id' => ['IN' => array_keys($cases)],
      'return' => $tagGet,
      'options' => ['limit' => 0],
    ]);
    foreach ($tags['values'] as $tag) {
      $key = (int) $tag['entity_id'];
      unset($tag['entity_id'], $tag['id']);
      $cases[$key]['tag_id'][$tag['tag_id']] = $tag;
    }
  }
}

/**
 * Internal function to format create params for processing.
 *
 * @param array $params
 */
function _civicrm_api3_case_format_params(&$params) {
  // Format/include custom params
  $values = [];
  _civicrm_api3_custom_format_params($params, $values, 'Case');
  $params = array_merge($params, $values);

  // A single or multiple contact_id (client_id) can be passed as a value or array.
  // Convert single value to array here to simplify processing in later functions which expect an array.
  if (isset($params['contact_id'])) {
    if (!is_array($params['contact_id'])) {
      $params['contact_id'] = [$params['contact_id']];
    }
  }

  // DEPRECATED: case_id - use id parameter instead.
  if (!isset($params['id']) && isset($params['case_id'])) {
    $params['id'] = $params['case_id'];
  }

  // When creating a new case, either case_type_id or case_type must be specified.
  if (empty($params['case_type_id']) && empty($params['case_type'])) {
    // If both case_type_id and case_type are empty we are updating a case so return here.
    return;
  }

  // We are creating a new case
  // figure out case_type_id from case_type and vice-versa
  $caseTypes = CRM_Case_PseudoConstant::caseType('name', FALSE);
  if (empty($params['case_type_id'])) {
    $params['case_type_id'] = array_search($params['case_type'], $caseTypes);

    // DEPRECATED: lookup by label for backward compatibility
    if (!$params['case_type_id']) {
      $caseTypeLabels = CRM_Case_PseudoConstant::caseType('title', FALSE);
      $params['case_type_id'] = array_search($params['case_type'], $caseTypeLabels);
      $params['case_type'] = $caseTypes[$params['case_type_id']];
    }
  }
  elseif (empty($params['case_type'])) {
    $params['case_type'] = $caseTypes[$params['case_type_id']];
  }
}

/**
 * It actually works a lot better to use the CaseContact api instead of the Case api
 * for entityRef fields so we can perform the necessary joins,
 * so we pass off getlist requests to the CaseContact api.
 *
 * @param array $params
 * @return mixed
 */
function civicrm_api3_case_getList($params) {
  require_once 'api/v3/Generic/Getlist.php';
  require_once 'api/v3/CaseContact.php';
  //CRM:19956 - Assign case_id param if both id and case_id is passed to retrieve the case
  if (!empty($params['id']) && !empty($params['params']) && !empty($params['params']['case_id'])) {
    $params['params']['case_id'] = ['IN' => $params['id']];
    unset($params['id']);
  }
  $params['id_field'] = 'case_id';
  $params['label_field'] = $params['search_field'] = 'contact_id.sort_name';
  $params['description_field'] = [
    'case_id',
    'case_id.case_type_id.title',
    'case_id.subject',
    'case_id.status_id',
    'case_id.start_date',
  ];
  $apiRequest = [
    'version' => 3,
    'entity' => 'CaseContact',
    'action' => 'getlist',
    'params' => $params,
  ];
  return civicrm_api3_generic_getList($apiRequest);
}

/**
 * Needed due to the above override
 * @param $params
 * @param $apiRequest
 */
function _civicrm_api3_case_getlist_spec(&$params, $apiRequest) {
  require_once 'api/v3/Generic/Getlist.php';
  _civicrm_api3_generic_getlist_spec($params, $apiRequest);
}
