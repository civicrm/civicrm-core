<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @throws API_Exception
 * @return array
 *   API result array
 */
function civicrm_api3_activity_create($params) {
  $isNew = empty($params['id']);

  if (empty($params['id'])) {
    // an update does not require any mandatory parameters
    civicrm_api3_verify_one_mandatory($params,
      NULL,
      array(
        'activity_name',
        'activity_type_id',
        'activity_label',
      )
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
  $values = $activityArray = array();
  _civicrm_api3_custom_format_params($params, $values, 'Activity');

  if (!empty($values['custom'])) {
    $params['custom'] = $values['custom'];
  }

  // this should be set as a default rather than hard coded
  // needs testing
  $params['skipRecentView'] = TRUE;

  // If this is a case activity, see if there is an existing activity
  // and set it as an old revision. Also retrieve details we'll need.
  // this handling should all be moved to the BAO layer
  $case_id = '';
  $createRevision = FALSE;
  $oldActivityValues = array();
  // Lookup case id if not supplied
  if (!isset($params['case_id']) && !empty($params['id'])) {
    $params['case_id'] = CRM_Core_DAO::singleValueQuery("SELECT case_id FROM civicrm_case_activity WHERE activity_id = " . (int) $params['id']);
  }
  if (!empty($params['case_id'])) {
    $case_id = $params['case_id'];
    if (!empty($params['id']) && Civi::settings()->get('civicaseActivityRevisions')) {
      $oldActivityParams = array('id' => $params['id']);
      if (!$oldActivityValues) {
        CRM_Activity_BAO_Activity::retrieve($oldActivityParams, $oldActivityValues);
      }
      if (empty($oldActivityValues)) {
        throw new API_Exception(ts("Unable to locate existing activity."));
      }
      else {
        $activityDAO = new CRM_Activity_DAO_Activity();
        $activityDAO->id = $params['id'];
        $activityDAO->is_current_revision = 0;
        if (!$activityDAO->save()) {
          if (is_object($activityDAO)) {
            $activityDAO->free();
          }
          throw new API_Exception(ts("Unable to revision existing case activity."));
        }
        $createRevision = TRUE;
      }
    }
  }

  $deleteActivityAssignment = FALSE;
  if (isset($params['assignee_contact_id'])) {
    $deleteActivityAssignment = TRUE;
  }

  $deleteActivityTarget = FALSE;
  if (isset($params['target_contact_id'])) {
    $deleteActivityTarget = TRUE;
  }

  // this should all be handled at the BAO layer
  $params['deleteActivityAssignment'] = CRM_Utils_Array::value('deleteActivityAssignment', $params, $deleteActivityAssignment);
  $params['deleteActivityTarget'] = CRM_Utils_Array::value('deleteActivityTarget', $params, $deleteActivityTarget);

  if ($case_id && $createRevision) {
    // This is very similar to the copy-to-case action.
    if (!CRM_Utils_Array::crmIsEmptyArray($oldActivityValues['target_contact'])) {
      $oldActivityValues['targetContactIds'] = implode(',', array_unique($oldActivityValues['target_contact']));
    }
    if (!CRM_Utils_Array::crmIsEmptyArray($oldActivityValues['assignee_contact'])) {
      $oldActivityValues['assigneeContactIds'] = implode(',', array_unique($oldActivityValues['assignee_contact']));
    }
    $oldActivityValues['mode'] = 'copy';
    $oldActivityValues['caseID'] = $case_id;
    $oldActivityValues['activityID'] = $oldActivityValues['id'];
    $oldActivityValues['contactID'] = $oldActivityValues['source_contact_id'];

    $copyToCase = CRM_Activity_Page_AJAX::_convertToCaseActivity($oldActivityValues);
    if (empty($copyToCase['error_msg'])) {
      // now fix some things that are different from copy-to-case
      // then fall through to the create below to update with the passed in params
      $params['id'] = $copyToCase['newId'];
      $params['is_auto'] = 0;
      $params['original_id'] = empty($oldActivityValues['original_id']) ? $oldActivityValues['id'] : $oldActivityValues['original_id'];
    }
    else {
      throw new API_Exception(ts("Unable to create new revision of case activity."));
    }
  }

  // create activity
  $activityBAO = CRM_Activity_BAO_Activity::create($params);

  if (isset($activityBAO->id)) {
    if ($case_id && $isNew && !$createRevision) {
      // If this is a brand new case activity, add to case(s)
      foreach ((array) $case_id as $singleCaseId) {
        $caseActivityParams = array('activity_id' => $activityBAO->id, 'case_id' => $singleCaseId);
        CRM_Case_BAO_Case::processCaseActivity($caseActivityParams);
      }
    }

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

  $params['status_id']['api.aliases'] = array('activity_status');

  $params['assignee_contact_id'] = array(
    'name' => 'assignee_id',
    'title' => 'Activity Assignee',
    'description' => 'Contact(s) assigned to this activity.',
    'type' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  );
  $params['target_contact_id'] = array(
    'name' => 'target_id',
    'title' => 'Activity Target',
    'description' => 'Contact(s) participating in this activity.',
    'type' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  );

  $params['source_contact_id'] = array(
    'name' => 'source_contact_id',
    'title' => 'Activity Source Contact',
    'description' => 'Person who created this activity. Defaults to current user.',
    'type' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'api.default' => 'user_contact_id',
    'FKApiName' => 'Contact',
    'api.required' => TRUE,
  );

  $params['case_id'] = array(
    'name' => 'case_id',
    'title' => 'Case ID',
    'description' => 'For creating an activity as part of a case.',
    'type' => 1,
    'FKClassName' => 'CRM_Case_DAO_Case',
    'FKApiName' => 'Case',
  );

}

/**
 * Specify Metadata for get.
 *
 * @param array $params
 */
function _civicrm_api3_activity_get_spec(&$params) {
  $params['tag_id'] = array(
    'title' => 'Tags',
    'description' => 'Find activities with specified tags.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Core_DAO_Tag',
    'FKApiName' => 'Tag',
    'supports_joins' => TRUE,
  );
  $params['file_id'] = array(
    'title' => 'Attached Files',
    'description' => 'Find activities with attached files.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Core_DAO_File',
    'FKApiName' => 'File',
  );
  $params['case_id'] = array(
    'title' => 'Cases',
    'description' => 'Find activities within specified cases.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Case_DAO_Case',
    'FKApiName' => 'Case',
    'supports_joins' => TRUE,
  );
  $params['contact_id'] = array(
    'title' => 'Activity Contact ID',
    'description' => 'Find activities involving this contact (as target, source, OR assignee).',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  );
  $params['target_contact_id'] = array(
    'title' => 'Target Contact ID',
    'description' => 'Find activities with specified target contact.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  );
  $params['source_contact_id'] = array(
    'title' => 'Source Contact ID',
    'description' => 'Find activities with specified source contact.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  );
  $params['assignee_contact_id'] = array(
    'title' => 'Assignee Contact ID',
    'description' => 'Find activities with specified assignee contact.',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
    'FKApiName' => 'Contact',
  );
  $params['is_overdue'] = array(
    'title' => 'Is Activity Overdue',
    'description' => 'Incomplete activities with a past date.',
    'type' => CRM_Utils_Type::T_BOOLEAN,
  );
}

/**
 * Gets a CiviCRM activity according to parameters.
 *
 * @param array $params
 *   Array per getfields documentation.
 *
 * @return array API result array
 *   API result array
 *
 * @throws \API_Exception
 * @throws \CiviCRM_API3_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_activity_get($params) {
  $options = _civicrm_api3_get_options_from_params($params, FALSE, 'Activity', 'get');
  $sql = CRM_Utils_SQL_Select::fragment();

  if (empty($params['target_contact_id']) && empty($params['source_contact_id'])
    && empty($params['assignee_contact_id']) &&
    !empty($params['check_permissions']) && !CRM_Core_Permission::check('view all activities')
    && !CRM_Core_Permission::check('view all contacts')
  ) {
    // Force join on the activity contact table.
    // @todo get this & other acl filters to work, remove check further down.
    //$params['contact_id'] = array('IS NOT NULL' => TRUE);
  }

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
  if (!empty($params['check_permissions']) && !CRM_Core_Permission::check('view all activities')) {
    // @todo get this to work at the query level - see contact_id join above.
    foreach ($activities as $activity) {
      if (!CRM_Activity_BAO_Activity::checkPermission($activity['id'], CRM_Core_Action::VIEW)) {
        unset($activities[$activity['id']]);
      }
    }
  }
  if ($options['is_count']) {
    return civicrm_api3_create_success($activities, $params, 'Activity', 'get');
  }

  $activities = _civicrm_api3_activity_get_formatResult($params, $activities, $options);
  //legacy custom data get - so previous formatted response is still returned too
  return civicrm_api3_create_success($activities, $params, 'Activity', 'get');
}

/**
 * Support filters beyond what basic_get can do.
 *
 * @param array $params
 * @param CRM_Utils_SQL_Select $sql
 * @throws \CiviCRM_API3_Exception
 * @throws \Exception
 */
function _civicrm_api3_activity_get_extraFilters(&$params, &$sql) {
  // Filter by activity contacts
  $recordTypes = civicrm_api3('ActivityContact', 'getoptions', array('field' => 'record_type_id'));
  $recordTypes = $recordTypes['values'];
  $activityContactOptions = array(
    'contact_id' => NULL,
    'target_contact_id' => array_search('Activity Targets', $recordTypes),
    'source_contact_id' => array_search('Activity Source', $recordTypes),
    'assignee_contact_id' => array_search('Activity Assignees', $recordTypes),
  );
  foreach ($activityContactOptions as $activityContactName => $activityContactValue) {
    if (!empty($params[$activityContactName])) {
      if (!is_array($params[$activityContactName])) {
        $params[$activityContactName] = array('=' => $params[$activityContactName]);
      }
      $clause = \CRM_Core_DAO::createSQLFilter('contact_id', $params[$activityContactName]);
      $typeClause = $activityContactValue ? 'record_type_id = #typeId AND ' : '';
      $sql->where("a.id IN (SELECT activity_id FROM civicrm_activity_contact WHERE $typeClause !clause)",
        array('#typeId' => $activityContactValue, '!clause' => $clause)
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
  $rels = array(
    'tag_id' => array(
      'subquery' => 'a.id IN (SELECT entity_id FROM civicrm_entity_tag WHERE entity_table = "civicrm_activity" AND !clause)',
      'join' => '!joinType civicrm_entity_tag !alias ON (!alias.entity_table = "civicrm_activity" AND !alias.entity_id = a.id)',
      'column' => 'tag_id',
    ),
    'file_id' => array(
      'subquery' => 'a.id IN (SELECT entity_id FROM civicrm_entity_file WHERE entity_table = "civicrm_activity" AND !clause)',
      'join' => '!joinType civicrm_entity_file !alias ON (!alias.entity_table = "civicrm_activity" AND !alias.entity_id = a.id)',
      'column' => 'file_id',
    ),
    'case_id' => array(
      'subquery' => 'a.id IN (SELECT activity_id FROM civicrm_case_activity WHERE !clause)',
      'join' => '!joinType civicrm_case_activity !alias ON (!alias.activity_id = a.id)',
      'column' => 'case_id',
    ),
  );
  foreach ($rels as $filter => $relSpec) {
    if (!empty($params[$filter])) {
      if (!is_array($params[$filter])) {
        $params[$filter] = array('=' => $params[$filter]);
      }
      // $mode is one of ('LEFT JOIN', 'INNER JOIN', 'SUBQUERY')
      $mode = isset($params[$filter]['IS NULL']) ? 'LEFT JOIN' : 'SUBQUERY';
      if ($mode === 'SUBQUERY') {
        $clause = \CRM_Core_DAO::createSQLFilter($relSpec['column'], $params[$filter]);
        if ($clause) {
          $sql->where($relSpec['subquery'], array('!clause' => $clause));
        }
      }
      else {
        $alias = 'actjoin_' . $filter;
        $clause = \CRM_Core_DAO::createSQLFilter($alias . "." . $relSpec['column'], $params[$filter]);
        if ($clause) {
          $sql->join($alias, $relSpec['join'], array('!alias' => $alias, 'joinType' => $mode));
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
    if (substr($n, 0, 7) == 'return.') {
      $returnkey = substr($n, 7);
      $returns[$returnkey] = $v;
    }
  }

  $returns['source_contact_id'] = 1;
  if (!empty($returns['target_contact_name'])) {
    $returns['target_contact_id'] = 1;
  }
  if (!empty($returns['assignee_contact_name'])) {
    $returns['assignee_contact_id'] = 1;
  }

  $tagGet = array('tag_id', 'entity_id');
  $caseGet = $caseIds = array();
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
        foreach ($activities as $key => $activityArray) {
          $cids = $activities[$key]['assignee_contact_id'] = CRM_Activity_BAO_ActivityAssignment::retrieveAssigneeIdsByActivityId($activityArray['id']);
          if ($cids && !empty($returns['assignee_contact_name'])) {
            foreach ($cids as $cid) {
              $activities[$key]['assignee_contact_name'][$cid] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'display_name');
            }
          }
        }
        break;

      case 'target_contact_id':
        foreach ($activities as $key => $activityArray) {
          $cids = $activities[$key]['target_contact_id'] = CRM_Activity_BAO_ActivityTarget::retrieveTargetIdsByActivityId($activityArray['id']);
          if ($cids && !empty($returns['target_contact_name'])) {
            foreach ($cids as $cid) {
              $activities[$key]['target_contact_name'][$cid] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'display_name');
            }
          }
        }
        break;

      case 'source_contact_id':
        foreach ($activities as $key => $activityArray) {
          $cid = $activities[$key]['source_contact_id'] = CRM_Activity_BAO_Activity::getSourceContactID($activityArray['id']);
          if ($cid && !empty($returns['source_contact_name'])) {
            $activities[$key]['source_contact_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $cid, 'display_name');
          }
        }
        break;

      case 'tag_id':
        $tags = civicrm_api3('EntityTag', 'get', array(
          'entity_table' => 'civicrm_activity',
          'entity_id' => array('IN' => array_keys($activities)),
          'return' => $tagGet,
          'options' => array('limit' => 0),
        ));
        foreach ($tags['values'] as $tag) {
          $key = (int) $tag['entity_id'];
          unset($tag['entity_id'], $tag['id']);
          $activities[$key]['tag_id'][$tag['tag_id']] = $tag;
        }
        break;

      case 'file_id':
        $dao = CRM_Core_DAO::executeQuery("SELECT entity_id, file_id FROM civicrm_entity_file WHERE entity_table = 'civicrm_activity' AND entity_id IN (%1)",
          array(1 => array(implode(',', array_keys($activities)), 'String', CRM_Core_DAO::QUERY_FORMAT_NO_QUOTES)));
        while ($dao->fetch()) {
          $activities[$dao->entity_id]['file_id'][] = $dao->file_id;
        }
        break;

      case 'case_id':
        $dao = CRM_Core_DAO::executeQuery("SELECT activity_id, case_id FROM civicrm_case_activity WHERE activity_id IN (%1)",
          array(1 => array(implode(',', array_keys($activities)), 'String', CRM_Core_DAO::QUERY_FORMAT_NO_QUOTES)));
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
    $cases = civicrm_api3('Case', 'get', array(
      'id' => array('IN' => $caseIds),
      'options' => array('limit' => 0),
      'check_permissions' => !empty($params['check_permissions']),
      'return' => $caseGet,
    ));
    foreach ($activities as &$activity) {
      if (!empty($activity['case_id'])) {
        $case = CRM_Utils_Array::value($activity['case_id'][0], $cases['values']);
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
      _civicrm_api3_custom_data_get($activities[$activityId], CRM_Utils_Array::value('check_permissions', $params), 'Activity', $activityId, NULL, CRM_Utils_Array::value('activity_type_id', $values));
    }
  }
  return $activities;
}


/**
 * Delete a specified Activity.
 *
 * @param array $params
 *   Array holding 'id' of activity to be deleted.
 *
 * @throws API_Exception
 *
 * @return array
 *   API result array
 */
function civicrm_api3_activity_delete($params) {

  if (CRM_Activity_BAO_Activity::deleteActivity($params)) {
    return civicrm_api3_create_success(1, $params, 'Activity', 'delete');
  }
  else {
    throw new API_Exception('Could not delete Activity: ' . (int) $params['id']);
  }
}

/**
 * Check for required params.
 *
 * @param array $params
 *   Associated array of fields.
 *
 * @throws API_Exception
 * @throws Exception
 * @return array
 *   array with errors
 */
function _civicrm_api3_activity_check_params(&$params) {
  $activityIds = array(
    'activity' => CRM_Utils_Array::value('id', $params),
    'parent' => CRM_Utils_Array::value('parent_id', $params),
    'original' => CRM_Utils_Array::value('original_id', $params),
  );

  foreach ($activityIds as $id => $value) {
    if ($value &&
      !CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $value, 'id')
    ) {
      throw new API_Exception('Invalid ' . ucfirst($id) . ' Id');
    }
  }
  // this should be handled by wrapper layer & probably the api would already manage it
  //correctly by doing pseudoconstant validation
  // needs testing
  $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'validate');
  $activityName = CRM_Utils_Array::value('activity_name', $params);
  $activityName = ucfirst($activityName);
  $activityLabel = CRM_Utils_Array::value('activity_label', $params);
  if ($activityLabel) {
    $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'create');
  }

  $activityTypeId = CRM_Utils_Array::value('activity_type_id', $params);

  if ($activityName || $activityLabel) {
    $activityTypeIdInList = array_search(($activityName ? $activityName : $activityLabel), $activityTypes);

    if (!$activityTypeIdInList) {
      $errorString = $activityName ? "Invalid Activity Name : $activityName" : "Invalid Activity Type Label";
      throw new Exception($errorString);
    }
    elseif ($activityTypeId && ($activityTypeId != $activityTypeIdInList)) {
      throw new API_Exception('Mismatch in Activity');
    }
    $params['activity_type_id'] = $activityTypeIdInList;
  }
  elseif ($activityTypeId &&
    !array_key_exists($activityTypeId, $activityTypes)
  ) {
    throw new API_Exception('Invalid Activity Type ID');
  }

  // check for activity duration minutes
  // this should be validated @ the wrapper layer not here
  // needs testing
  if (isset($params['duration_minutes']) && !is_numeric($params['duration_minutes'])) {
    throw new API_Exception('Invalid Activity Duration (in minutes)');
  }

  //if adding a new activity & date_time not set make it now
  // this should be managed by the wrapper layer & setting ['api.default'] in speces
  // needs testing
  if (empty($params['id']) && empty($params['activity_date_time'])) {
    $params['activity_date_time'] = CRM_Utils_Date::processDate(date('Y-m-d H:i:s'));
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
  $fieldsToReturn = array(
    'activity_date_time',
    'activity_type_id',
    'subject',
    'source_contact_id',
  );
  $request['params']['return'] = array_unique(array_merge($fieldsToReturn, $request['extra']));
  $request['params']['options']['sort'] = 'activity_date_time DESC';
  $request['params'] += array(
    'is_current_revision' => 1,
    'is_deleted' => 0,
  );
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
  $output = array();
  if (!empty($result['values'])) {
    foreach ($result['values'] as $row) {
      $data = array(
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']] ? $row[$request['label_field']] : ts('(no subject)'),
        'description' => array(
          CRM_Core_Pseudoconstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $row['activity_type_id']),
        ),
      );
      if (!empty($row['activity_date_time'])) {
        $data['description'][0] .= ': ' . CRM_Utils_Date::customFormat($row['activity_date_time']);
      }
      if (!empty($row['source_contact_id'])) {
        $data['description'][] = ts('By %1', array(
          1 => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $row['source_contact_id'], 'display_name'),
        ));
      }
      // Add repeating info
      $repeat = CRM_Core_BAO_RecurringEntity::getPositionAndCount($row['id'], 'civicrm_activity');
      $data['extra']['is_recur'] = FALSE;
      if ($repeat) {
        $data['suffix'] = ts('(%1 of %2)', array(1 => $repeat[0], 2 => $repeat[1]));
        $data['extra']['is_recur'] = TRUE;
      }
      $output[] = $data;
    }
  }
  return $output;
}
