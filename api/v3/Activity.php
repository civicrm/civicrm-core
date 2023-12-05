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
 * This api exposes CiviCRM Activity records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Creates or updates an Activity.
 *
 * @param array $params
 *   Array per getfields documentation.
 *
 * @throws CRM_Core_Exception
 * @return array
 *   API result array
 */
function civicrm_api3_activity_create($params) {
  $isNew = empty($params['id']);

  if (empty($params['id'])) {
    // an update does not require any mandatory parameters
    civicrm_api3_verify_one_mandatory($params,
      NULL,
      [
        'activity_name',
        'activity_type_id',
        'activity_label',
      ]
    );
  }

  // check for various error and required conditions
  // note that almost all the processing in there should be managed by the wrapper layer
  // & should be removed - needs testing
  $errors = _civicrm_api3_activity_check_params($params);

  // this should not be required as should throw exception rather than return errors -
  //needs testing
  if (!empty($errors)) {
    return $errors;
  }

  // processing for custom data
  $values = $activityArray = [];
  _civicrm_api3_custom_format_params($params, $values, 'Activity');

  if (!empty($values['custom'])) {
    $params['custom'] = $values['custom'];
  }

  // this should be set as a default rather than hard coded
  // needs testing
  $params['skipRecentView'] = TRUE;

  // In APIv3, only new activities can be filed on a case.
  if (!$isNew && isset($params['case_id'])) {
    unset($params['case_id']);
  }

  // create activity
  $activityBAO = CRM_Activity_BAO_Activity::create($params);

  if (isset($activityBAO->id)) {
    _civicrm_api3_object_to_array($activityBAO, $activityArray[$activityBAO->id]);
    return civicrm_api3_create_success($activityArray, $params, 'Activity', 'get', $activityBAO);
  }
}

/**
 * Specify Meta data for create.
 *
 * Note that this data is retrievable via the getfields function and is used for pre-filling defaults and
 * ensuring mandatory requirements are met.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_activity_create_spec(&$params) {

  $params['status_id']['api.aliases'] = ['activity_status'];

  $params['assignee_contact_id'] = [
    'name' => 'assignee_id',
    'title' => 'Activity Assignee',
    'description' => 'Contact(s) assigned to this activity.',
    'type' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  ];
  $params['target_contact_id'] = [
    'name' => 'target_id',
    'title' => 'Activity Target',
    'description' => 'Contact(s) participating in this activity.',
    'type' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  ];

  $params['source_contact_id'] = [
    'name' => 'source_contact_id',
    'title' => 'Activity Source Contact',
    'description' => 'Person who created this activity. Defaults to current user.',
    'type' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'api.default' => 'user_contact_id',
    'FKApiName' => 'Contact',
    'api.required' => TRUE,
  ];

  $params['case_id'] = [
    'name' => 'case_id',
    'title' => 'Case ID',
    'description' => 'For creating an activity as part of a case.',
    'type' => 1,
    'FKClassName' => 'CRM_Case_DAO_Case',
    'FKApiName' => 'Case',
  ];

  $params['activity_date_time']['api.default'] = 'now';

}

/**
 * Specify Metadata for get.
 *
 * @param array $params
 */
function _civicrm_api3_activity_get_spec(&$params) {
  $params['tag_id'] = [
    'title' => 'Tags',
    'description' => 'Find activities with specified tags.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Core_DAO_Tag',
    'FKApiName' => 'Tag',
    'supports_joins' => TRUE,
  ];
  $params['file_id'] = [
    'title' => 'Attached Files',
    'description' => 'Find activities with attached files.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Core_DAO_File',
    'FKApiName' => 'File',
  ];
  $params['case_id'] = [
    'title' => 'Cases',
    'description' => 'Find activities within specified cases.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Case_DAO_Case',
    'FKApiName' => 'Case',
    'supports_joins' => TRUE,
  ];
  $params['contact_id'] = [
    'title' => 'Activity Contact ID',
    'description' => 'Find activities involving this contact (as target, source, OR assignee).',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  ];
  $params['target_contact_id'] = [
    'title' => 'Target Contact ID',
    'description' => 'Find activities with specified target contact.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  ];
  $params['source_contact_id'] = [
    'title' => 'Source Contact ID',
    'description' => 'Find activities with specified source contact.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  ];
  $params['assignee_contact_id'] = [
    'title' => 'Assignee Contact ID',
    'description' => 'Find activities with specified assignee contact.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  ];
  $params['is_overdue'] = [
    'title' => 'Is Activity Overdue',
    'description' => 'Incomplete activities with a past date.',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
}

/**
 * Gets a CiviCRM activity according to parameters.
 *
 * @param array $params
 *   Array per getfields documentation.
 *
 * @return array
 *   API result array
 *
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_activity_get($params) {
  $options = _civicrm_api3_get_options_from_params($params, FALSE, 'Activity', 'get');
  $sql = CRM_Utils_SQL_Select::fragment();
  _civicrm_activity_get_handleSourceContactNameOrderBy($params, $options, $sql);

  _civicrm_api3_activity_get_extraFilters($params, $sql);

  // Handle is_overdue sort
  if (!empty($options['sort'])) {
    $sort = explode(', ', $options['sort']);

    foreach ($sort as $index => &$sortString) {
      // Get sort field and direction
      list($sortField, $dir) = array_pad(explode(' ', $sortString), 2, 'ASC');
      if ($sortField == 'is_overdue') {
        $incomplete = implode(',', array_keys(CRM_Activity_BAO_Activity::getStatusesByType(CRM_Activity_BAO_Activity::INCOMPLETE)));
        $sql->orderBy("IF((a.activity_date_time >= NOW() OR a.status_id NOT IN ($incomplete)), 0, 1) $dir", NULL, $index);
        // Replace the sort with a placeholder which will be ignored by sql
        $sortString = '(1)';
      }
    }
    $params['options']['sort'] = implode(', ', $sort);
  }

  // Ensure there's enough data for calculating is_overdue
  if (!empty($options['return']['is_overdue']) && (empty($options['return']['status_id']) || empty($options['return']['activity_date_time']))) {
    $options['return']['status_id'] = $options['return']['activity_date_time'] = 1;
    $params['return'] = array_keys($options['return']);
  }

  $activities = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, FALSE, 'Activity', $sql);
  if ($options['is_count']) {
    return civicrm_api3_create_success($activities, $params, 'Activity', 'get');
  }

  $activities = _civicrm_api3_activity_get_formatResult($params, $activities, $options);
  //legacy custom data get - so previous formatted response is still returned too
  return civicrm_api3_create_success($activities, $params, 'Activity', 'get');
}

/**
 * Handle source_contact_name as a sort parameter.
 *
 * This is passed from the activity selector - e.g search results or contact tab.
 *
 * It's a non-standard handling but this api already handles variations on handling source_contact
 * as a filter & as a field so it's in keeping with that. Source contact has a one-one relationship
 * with activity table.
 *
 * Test coverage in CRM_Activity_BAO_ActivtiyTest::testGetActivitiesforContactSummaryWithSortOptions
 *
 * @param array $params
 * @param array $options
 * @param CRM_Utils_SQL_Select $sql
 */
function _civicrm_activity_get_handleSourceContactNameOrderBy(&$params, &$options, $sql) {
  $sourceContactID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Source');
  if (!empty($options['sort'])
    && in_array($options['sort'], ['source_contact_name', 'source_contact_name desc', 'source_contact_name asc'])) {
    $order = substr($options['sort'], -4) === 'desc' ? 'desc' : 'asc';
    $sql->join(
      'source_contact',
      "LEFT JOIN
      civicrm_activity_contact ac ON (ac.activity_id = a.id AND record_type_id = #sourceContactID)
       LEFT JOIN civicrm_contact c ON c.id = ac.contact_id",
      ['sourceContactID' => $sourceContactID]
    );
    $sql->orderBy("c.display_name $order");
    unset($options['sort'], $params['options']['sort']);
  }
}

/**
 * Support filters beyond what basic_get can do.
 *
 * @param array $params
 * @param CRM_Utils_SQL_Select $sql
 * @throws \CRM_Core_Exception
 * @throws \Exception
 */
function _civicrm_api3_activity_get_extraFilters(&$params, &$sql) {
  // Filter by activity contacts
  $activityContactOptions = [
    'contact_id' => NULL,
    'target_contact_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets'),
    'source_contact_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Source'),
    'assignee_contact_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Assignees'),
  ];
  foreach ($activityContactOptions as $activityContactName => $activityContactValue) {
    if (!empty($params[$activityContactName])) {
      if (!is_array($params[$activityContactName])) {
        $params[$activityContactName] = ['=' => $params[$activityContactName]];
      }
      $clause = \CRM_Core_DAO::createSQLFilter('contact_id', $params[$activityContactName]);
      $typeClause = $activityContactValue ? 'record_type_id = #typeId AND ' : '';
      $sql->where("a.id IN (SELECT activity_id FROM civicrm_activity_contact WHERE $typeClause !clause)",
        ['#typeId' => $activityContactValue, '!clause' => $clause]
      );
    }
  }

  // Handle is_overdue filter
  // Boolean calculated field - does not support operators
  if (isset($params['is_overdue'])) {
    $incomplete = implode(',', array_keys(CRM_Activity_BAO_Activity::getStatusesByType(CRM_Activity_BAO_Activity::INCOMPLETE)));
    if ($params['is_overdue']) {
      $sql->where('a.activity_date_time < NOW()');
      $sql->where("a.status_id IN ($incomplete)");
    }
    else {
      $sql->where("(a.activity_date_time >= NOW() OR a.status_id NOT IN ($incomplete))");
    }
  }

  // Define how to handle filters on some related entities.
  // Subqueries are nice in (a) avoiding duplicates and (b) when the result
  // list is expected to be bite-sized. Joins are nice (a) with larger
  // datasets and (b) checking for non-existent relations.
  $rels = [
    'tag_id' => [
      'subquery' => 'a.id IN (SELECT entity_id FROM civicrm_entity_tag WHERE entity_table = "civicrm_activity" AND !clause)',
      'join' => '!joinType civicrm_entity_tag !alias ON (!alias.entity_table = "civicrm_activity" AND !alias.entity_id = a.id)',
      'column' => 'tag_id',
    ],
    'file_id' => [
      'subquery' => 'a.id IN (SELECT entity_id FROM civicrm_entity_file WHERE entity_table = "civicrm_activity" AND !clause)',
      'join' => '!joinType civicrm_entity_file !alias ON (!alias.entity_table = "civicrm_activity" AND !alias.entity_id = a.id)',
      'column' => 'file_id',
    ],
  ];
  if (\CRM_Core_Component::isEnabled('CiviCase')) {
    $rels['case_id'] = [
      'subquery' => 'a.id IN (SELECT activity_id FROM civicrm_case_activity WHERE !clause)',
      'join' => '!joinType civicrm_case_activity !alias ON (!alias.activity_id = a.id)',
      'column' => 'case_id',
    ];
  }
  foreach ($rels as $filter => $relSpec) {
    if (!empty($params[$filter])) {
      if (!is_array($params[$filter])) {
        $params[$filter] = ['=' => $params[$filter]];
      }
      // $mode is one of ('LEFT JOIN', 'INNER JOIN', 'SUBQUERY')
      $mode = isset($params[$filter]['IS NULL']) ? 'LEFT JOIN' : 'SUBQUERY';
      if ($mode === 'SUBQUERY') {
        $clause = \CRM_Core_DAO::createSQLFilter($relSpec['column'], $params[$filter]);
        if ($clause) {
          $sql->where($relSpec['subquery'], ['!clause' => $clause]);
        }
      }
      else {
        $alias = 'actjoin_' . $filter;
        $clause = \CRM_Core_DAO::createSQLFilter($alias . "." . $relSpec['column'], $params[$filter]);
        if ($clause) {
          $sql->join($alias, $relSpec['join'], ['!alias' => $alias, 'joinType' => $mode]);
          $sql->where($clause);
        }
      }
    }
  }
}

/**
 * Given a list of activities, append any extra data requested about the activities.
 *
 * @note Called by civicrm-core and CiviHR
 *
 * @param array $params
 *   API request parameters.
 * @param array $activities
 * @param array $options
 *   Options array (pre-processed to extract 'return' from params).
 *
 * @return array
 *   new activities list
 */
function _civicrm_api3_activity_get_formatResult($params, $activities, $options) {
  if (!$activities) {
    return $activities;
  }

  $returns = $options['return'];
  foreach ($params as $n => $v) {
    // @todo - the per-parsing on options should have already done this.
    if (substr($n, 0, 7) == 'return.') {
      $returnkey = substr($n, 7);
      $returns[$returnkey] = $v;
    }
  }

  _civicrm_api3_activity_fill_activity_contact_names($activities, $params, $returns);

  $tagGet = ['tag_id', 'entity_id'];
  $caseGet = $caseIds = [];
  foreach (array_keys($returns) as $key) {
    if (strpos($key, 'tag_id.') === 0) {
      $tagGet[] = $key;
      $returns['tag_id'] = 1;
    }
    if (strpos($key, 'case_id.') === 0) {
      $caseGet[] = str_replace('case_id.', '', $key);
      $returns['case_id'] = 1;
    }
  }

  foreach ($returns as $n => $v) {
    switch ($n) {
      case 'assignee_contact_id':
      case 'target_contact_id':
        foreach ($activities as &$activity) {
          if (!isset($activity[$n])) {
            $activity[$n] = [];
          }
        }

      case 'source_contact_id':
        break;

      case 'tag_id':
        $tags = civicrm_api3('EntityTag', 'get', [
          'entity_table' => 'civicrm_activity',
          'entity_id' => ['IN' => array_keys($activities)],
          'return' => $tagGet,
          'options' => ['limit' => 0],
        ]);
        foreach ($tags['values'] as $tag) {
          $key = (int) $tag['entity_id'];
          unset($tag['entity_id'], $tag['id']);
          $activities[$key]['tag_id'][$tag['tag_id']] = $tag;
        }
        break;

      case 'file_id':
        $dao = CRM_Core_DAO::executeQuery("SELECT entity_id, file_id FROM civicrm_entity_file WHERE entity_table = 'civicrm_activity' AND entity_id IN (%1)",
          [1 => [implode(',', array_keys($activities)), 'CommaSeparatedIntegers']]);
        while ($dao->fetch()) {
          $activities[$dao->entity_id]['file_id'][] = $dao->file_id;
        }
        break;

      case 'case_id':
        $dao = CRM_Core_DAO::executeQuery("SELECT activity_id, case_id FROM civicrm_case_activity WHERE activity_id IN (%1)",
          [1 => [implode(',', array_keys($activities)), 'CommaSeparatedIntegers']]);
        while ($dao->fetch()) {
          $activities[$dao->activity_id]['case_id'][] = $dao->case_id;
          $caseIds[$dao->case_id] = $dao->case_id;
        }
        break;

      case 'is_overdue':
        foreach ($activities as $key => $activityArray) {
          $activities[$key]['is_overdue'] = (int) CRM_Activity_BAO_Activity::isOverdue($activityArray);
        }
        break;

      default:
        if (substr($n, 0, 6) == 'custom') {
          $returnProperties[$n] = $v;
        }
    }
  }

  // Fetch case fields via the join syntax
  // Note this is limited to the first case if the activity belongs to more than one
  if ($caseGet && $caseIds) {
    $cases = civicrm_api3('Case', 'get', [
      'id' => ['IN' => $caseIds],
      'options' => ['limit' => 0],
      'check_permissions' => !empty($params['check_permissions']),
      'return' => $caseGet,
    ]);
    foreach ($activities as &$activity) {
      if (!empty($activity['case_id'])) {
        $case = $cases['values'][$activity['case_id'][0]] ?? NULL;
        if ($case) {
          foreach ($case as $key => $value) {
            if ($key != 'id') {
              $activity['case_id.' . $key] = $value;
            }
          }
        }
      }
    }
  }

  // Legacy extras
  if (!empty($params['contact_id'])) {
    $statusOptions = CRM_Activity_BAO_Activity::buildOptions('status_id', 'get');
    $typeOptions = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'validate');
    foreach ($activities as $key => &$activityArray) {
      if (!empty($activityArray['status_id'])) {
        $activityArray['status'] = $statusOptions[$activityArray['status_id']];
      }
      if (!empty($activityArray['activity_type_id'])) {
        $activityArray['activity_name'] = $typeOptions[$activityArray['activity_type_id']];
      }
    }
  }

  if (!empty($returnProperties) || !empty($params['contact_id'])) {
    foreach ($activities as $activityId => $values) {
      //@todo - should possibly load activity type id if not loaded (update with id)
      _civicrm_api3_custom_data_get($activities[$activityId], $params['check_permissions'] ?? NULL, 'Activity', $activityId, NULL, $values['activity_type_id'] ?? NULL);
    }
  }
  return $activities;
}

/**
 * Append activity contact details to activity results.
 *
 * Adds id & name of activity contacts to results array if check_permissions
 * does not block access to them.
 *
 * For historical reasons source_contact_id is always added & is not an array.
 * The others are added depending on requested return params.
 *
 * @param array $activities
 * @param array $params
 * @param array $returns
 */
function _civicrm_api3_activity_fill_activity_contact_names(&$activities, $params, $returns) {
  $contactTypes = array_flip(CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate'));
  $assigneeType = $contactTypes['Activity Assignees'];
  $targetType = $contactTypes['Activity Targets'];
  $sourceType = $contactTypes['Activity Source'];
  $typeMap = [
    $assigneeType => 'assignee',
    $sourceType => 'source',
    $targetType => 'target',
  ];

  $activityContactTypes = [$sourceType];

  if (!empty($returns['target_contact_name']) || !empty($returns['target_contact_id'])) {
    $activityContactTypes[] = $targetType;
  }
  if (!empty($returns['assignee_contact_name']) || (!empty($returns['assignee_contact_id']))) {
    $activityContactTypes[] = $assigneeType;
  }
  $activityContactParams = [
    'activity_id' => ['IN' => array_keys($activities)],
    'return' => [
      'activity_id',
      'record_type_id',
      'contact_id.display_name',
      'contact_id.sort_name',
      'contact_id',
    ],
    'options' => ['limit' => 0],
    'check_permissions' => !empty($params['check_permissions']),
  ];
  if (count($activityContactTypes) < 3) {
    $activityContactParams['record_type_id'] = ['IN' => $activityContactTypes];
  }
  $activityContacts = civicrm_api3('ActivityContact', 'get', $activityContactParams)['values'];
  foreach ($activityContacts as $activityContact) {
    $contactID = $activityContact['contact_id'];
    $recordType = $typeMap[$activityContact['record_type_id']];
    if (in_array($recordType, ['target', 'assignee'])) {
      $activities[$activityContact['activity_id']][$recordType . '_contact_id'][] = $contactID;
      $activities[$activityContact['activity_id']][$recordType . '_contact_name'][$contactID] = $activityContact['contact_id.display_name'] ?? '';
      $activities[$activityContact['activity_id']][$recordType . '_contact_sort_name'][$contactID] = $activityContact['contact_id.sort_name'] ?? '';
    }
    else {
      $activities[$activityContact['activity_id']]['source_contact_id'] = $contactID;
      $activities[$activityContact['activity_id']]['source_contact_name'] = $activityContact['contact_id.display_name'] ?? '';
      $activities[$activityContact['activity_id']]['source_contact_sort_name'] = $activityContact['contact_id.sort_name'] ?? '';
    }
  }
}

/**
 * Delete a specified Activity.
 *
 * @param array $params
 *   Array holding 'id' of activity to be deleted.
 *
 * @throws CRM_Core_Exception
 *
 * @return array
 *   API result array
 */
function civicrm_api3_activity_delete($params) {

  if (CRM_Activity_BAO_Activity::deleteActivity($params)) {
    return civicrm_api3_create_success(1, $params, 'Activity', 'delete');
  }
  else {
    throw new CRM_Core_Exception('Could not delete Activity: ' . (int) $params['id']);
  }
}

/**
 * Check for required params.
 *
 * @param array $params
 *   Associated array of fields.
 *
 * @throws CRM_Core_Exception
 * @throws Exception
 * @return array
 *   array with errors
 */
function _civicrm_api3_activity_check_params(&$params) {
  $activityIds = [
    'activity' => $params['id'] ?? NULL,
    'parent' => $params['parent_id'] ?? NULL,
    'original' => $params['original_id'] ?? NULL,
  ];

  foreach ($activityIds as $id => $value) {
    if ($value &&
      !CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $value, 'id')
    ) {
      throw new CRM_Core_Exception('Invalid ' . ucfirst($id) . ' Id');
    }
  }
  // this should be handled by wrapper layer & probably the api would already manage it
  //correctly by doing pseudoconstant validation
  // needs testing
  $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'validate');
  $activityName = $params['activity_name'] ?? NULL;
  $activityName = ucfirst($activityName ?? '');
  $activityLabel = $params['activity_label'] ?? NULL;
  if ($activityLabel) {
    $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'create');
  }

  $activityTypeId = $params['activity_type_id'] ?? NULL;

  if ($activityName || $activityLabel) {
    $activityTypeIdInList = array_search(($activityName ?: $activityLabel), $activityTypes);

    if (!$activityTypeIdInList) {
      $errorString = $activityName ? "Invalid Activity Name : $activityName" : "Invalid Activity Type Label";
      throw new Exception($errorString);
    }
    elseif ($activityTypeId && ($activityTypeId != $activityTypeIdInList)) {
      throw new CRM_Core_Exception('Mismatch in Activity');
    }
    $params['activity_type_id'] = $activityTypeIdInList;
  }
  elseif ($activityTypeId &&
    !array_key_exists($activityTypeId, $activityTypes)
  ) {
    throw new CRM_Core_Exception('Invalid Activity Type ID');
  }

  // check for activity duration minutes
  // this should be validated @ the wrapper layer not here
  // needs testing
  if (isset($params['duration_minutes']) && !is_numeric($params['duration_minutes'])) {
    throw new CRM_Core_Exception('Invalid Activity Duration (in minutes)');
  }

  return NULL;
}

/**
 * Get parameters for activity list.
 *
 * @see _civicrm_api3_generic_getlist_params
 *
 * @param array $request
 *   API request.
 */
function _civicrm_api3_activity_getlist_params(&$request) {
  $fieldsToReturn = [
    'activity_date_time',
    'activity_type_id',
    'subject',
    'source_contact_id',
    $request['id_field'],
    $request['label_field'],
  ];
  $request['params']['return'] = array_unique(array_merge($fieldsToReturn, $request['extra']));
  $request['params']['options']['sort'] = 'activity_date_time DESC';
  $request['params'] += [
    'is_current_revision' => 1,
    'is_deleted' => 0,
  ];
}

/**
 * Get output for activity list.
 *
 * @see _civicrm_api3_generic_getlist_output
 *
 * @param array $result
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_activity_getlist_output($result, $request) {
  $output = [];
  if (!empty($result['values'])) {
    foreach ($result['values'] as $row) {
      $data = [
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']] ? $row[$request['label_field']] : ts('(no subject)'),
        'description' => [
          CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $row['activity_type_id']),
        ],
      ];
      if (!empty($row['activity_date_time'])) {
        $data['description'][0] .= ': ' . CRM_Utils_Date::customFormat($row['activity_date_time']);
      }
      if (!empty($row['source_contact_id'])) {
        $data['description'][] = ts('By %1', [
          1 => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $row['source_contact_id'], 'display_name'),
        ]);
      }
      // Add repeating info
      $repeat = CRM_Core_BAO_RecurringEntity::getPositionAndCount($row['id'], 'civicrm_activity');
      $data['extra']['is_recur'] = FALSE;
      if ($repeat) {
        $data['suffix'] = ts('(%1 of %2)', [1 => $repeat[0], 2 => $repeat[1]]);
        $data['extra']['is_recur'] = TRUE;
      }
      $output[] = $data;
    }
  }
  return $output;
}
