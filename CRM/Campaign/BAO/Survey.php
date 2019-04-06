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

/**
 * Class CRM_Campaign_BAO_Survey.
 */
class CRM_Campaign_BAO_Survey extends CRM_Campaign_DAO_Survey {

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Campaign_DAO_Survey|null
   */
  public static function retrieve(&$params, &$defaults) {
    $dao = new CRM_Campaign_DAO_Survey();

    $dao->copyValues($params);

    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $defaults);
      return $dao;
    }
    return NULL;
  }

  /**
   * Takes an associative array and creates a Survey object based on the
   * supplied values.
   *
   * @param array $params
   *
   * @return bool|CRM_Campaign_DAO_Survey
   */
  public static function create(&$params) {
    if (empty($params)) {
      return FALSE;
    }

    if (!empty($params['is_default'])) {
      $query = "UPDATE civicrm_survey SET is_default = 0";
      CRM_Core_DAO::executeQuery($query);
    }

    if (!(CRM_Utils_Array::value('id', $params))) {

      if (!(CRM_Utils_Array::value('created_id', $params))) {
        $session = CRM_Core_Session::singleton();
        $params['created_id'] = $session->get('userID');
      }
      if (!(CRM_Utils_Array::value('created_date', $params))) {
        $params['created_date'] = date('YmdHis');
      }

      CRM_Utils_Hook::pre('create', 'Survey', NULL, $params);
    }
    else {
      CRM_Utils_Hook::pre('edit', 'Survey', $params['id'], $params);
    }

    $dao = new CRM_Campaign_DAO_Survey();
    $dao->copyValues($params);
    $dao->save();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'Survey', $dao->id, $dao);
    }
    else {
      CRM_Utils_Hook::post('create', 'Survey', $dao->id, $dao);
    }

    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_survey', $dao->id);
    }
    return $dao;
  }

  /**
   * Retrieve surveys for dashboard.
   *
   * @param array $params
   * @param bool $onlyCount
   *
   * @return array|int
   */
  public static function getSurveySummary($params = [], $onlyCount = FALSE) {
    //build the limit and order clause.
    $limitClause = $orderByClause = $lookupTableJoins = NULL;
    if (!$onlyCount) {
      $sortParams = [
        'sort' => 'created_date',
        'offset' => 0,
        'rowCount' => 10,
        'sortOrder' => 'desc',
      ];
      foreach ($sortParams as $name => $default) {
        if (!empty($params[$name])) {
          $sortParams[$name] = $params[$name];
        }
      }

      //need to lookup tables.
      $orderOnSurveyTable = TRUE;
      if ($sortParams['sort'] == 'campaign') {
        $orderOnSurveyTable = FALSE;
        $lookupTableJoins = '
 LEFT JOIN civicrm_campaign campaign ON ( campaign.id = survey.campaign_id )';
        $orderByClause = "ORDER BY campaign.title {$sortParams['sortOrder']}";
      }
      elseif ($sortParams['sort'] == 'activity_type') {
        $orderOnSurveyTable = FALSE;
        $lookupTableJoins = "
 LEFT JOIN civicrm_option_value activity_type ON ( activity_type.value = survey.activity_type_id
                                                   OR survey.activity_type_id IS NULL )
INNER JOIN civicrm_option_group grp ON ( activity_type.option_group_id = grp.id AND grp.name = 'activity_type' )";
        $orderByClause = "ORDER BY activity_type.label {$sortParams['sortOrder']}";
      }
      elseif ($sortParams['sort'] == 'isActive') {
        $sortParams['sort'] = 'is_active';
      }
      if ($orderOnSurveyTable) {
        $orderByClause = "ORDER BY survey.{$sortParams['sort']} {$sortParams['sortOrder']}";
      }
      $limitClause = "LIMIT {$sortParams['offset']}, {$sortParams['rowCount']}";
    }

    //build the where clause.
    $queryParams = $where = [];

    //we only have activity type as a
    //difference between survey and petition.
    $petitionTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Petition');
    if ($petitionTypeID) {
      $where[] = "( survey.activity_type_id != %1 )";
      $queryParams[1] = [$petitionTypeID, 'Positive'];
    }

    if (!empty($params['title'])) {
      $where[] = "( survey.title LIKE %2 )";
      $queryParams[2] = ['%' . trim($params['title']) . '%', 'String'];
    }
    if (!empty($params['campaign_id'])) {
      $where[] = '( survey.campaign_id = %3 )';
      $queryParams[3] = [$params['campaign_id'], 'Positive'];
    }
    if (!empty($params['activity_type_id'])) {
      $typeId = $params['activity_type_id'];
      if (is_array($params['activity_type_id'])) {
        $typeId = implode(' , ', $params['activity_type_id']);
      }
      $where[] = "( survey.activity_type_id IN ( {$typeId} ) )";
    }
    $whereClause = NULL;
    if (!empty($where)) {
      $whereClause = ' WHERE ' . implode(" \nAND ", $where);
    }

    $selectClause = '
SELECT  survey.id                         as id,
        survey.title                      as title,
        survey.is_active                  as is_active,
        survey.result_id                  as result_id,
        survey.is_default                 as is_default,
        survey.campaign_id                as campaign_id,
        survey.activity_type_id           as activity_type_id,
        survey.release_frequency          as release_frequency,
        survey.max_number_of_contacts     as max_number_of_contacts,
        survey.default_number_of_contacts as default_number_of_contacts';
    if ($onlyCount) {
      $selectClause = 'SELECT COUNT(*)';
    }
    $fromClause = 'FROM  civicrm_survey survey';

    $query = "{$selectClause} {$fromClause} {$lookupTableJoins} {$whereClause} {$orderByClause} {$limitClause}";

    //return only count.
    if ($onlyCount) {
      return (int) CRM_Core_DAO::singleValueQuery($query, $queryParams);
    }

    $surveys = [];
    $properties = [
      'id',
      'title',
      'campaign_id',
      'is_active',
      'is_default',
      'result_id',
      'activity_type_id',
      'release_frequency',
      'max_number_of_contacts',
      'default_number_of_contacts',
    ];

    $survey = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($survey->fetch()) {
      foreach ($properties as $property) {
        $surveys[$survey->id][$property] = $survey->$property;
      }
    }

    return $surveys;
  }

  /**
   * Get the survey count.
   *
   */
  public static function getSurveyCount() {
    return (int) CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_survey');
  }

  /**
   * Get Surveys.
   *
   * @param bool $onlyActive
   *   Retrieve only active surveys.
   * @param bool $onlyDefault
   *   Retrieve only default survey.
   * @param bool $forceAll
   *   Retrieve all surveys.
   * @param bool $includePetition
   *   Include or exclude petitions.
   *
   */
  public static function getSurveys($onlyActive = TRUE, $onlyDefault = FALSE, $forceAll = FALSE, $includePetition = FALSE) {
    $cacheKey = 0;
    $cacheKeyParams = ['onlyActive', 'onlyDefault', 'forceAll', 'includePetition'];
    foreach ($cacheKeyParams as $param) {
      $cacheParam = $$param;
      if (!$cacheParam) {
        $cacheParam = 0;
      }
      $cacheKey .= '_' . $cacheParam;
    }

    static $surveys;

    if (!isset($surveys[$cacheKey])) {
      if (!$includePetition) {
        //we only have activity type as a
        //difference between survey and petition.
        $petitionTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'petition');

        $where = [];
        if ($petitionTypeID) {
          $where[] = "( survey.activity_type_id != {$petitionTypeID} )";
        }
      }
      if (!$forceAll && $onlyActive) {
        $where[] = '( survey.is_active  = 1 )';
      }
      if (!$forceAll && $onlyDefault) {
        $where[] = '( survey.is_default = 1 )';
      }
      $whereClause = implode(' AND ', $where);

      $query = "
SELECT  survey.id    as id,
        survey.title as title
  FROM  civicrm_survey as survey
 WHERE  {$whereClause}";
      $surveys[$cacheKey] = [];
      $survey = CRM_Core_DAO::executeQuery($query);
      while ($survey->fetch()) {
        $surveys[$cacheKey][$survey->id] = $survey->title;
      }
    }

    return $surveys[$cacheKey];
  }

  /**
   * Get Survey activity types.
   *
   * @param string $returnColumn
   * @param bool $includePetitionActivityType
   *
   * @return string
   */
  public static function getSurveyActivityType($returnColumn = 'label', $includePetitionActivityType = FALSE) {
    static $activityTypes;
    $cacheKey = "{$returnColumn}_{$includePetitionActivityType}";

    if (!isset($activityTypes[$cacheKey])) {
      $activityTypes = [];
      $campaignCompId = CRM_Core_Component::getComponentID('CiviCampaign');
      if ($campaignCompId) {
        $condition = " AND v.component_id={$campaignCompId}";
        if (!$includePetitionActivityType) {
          $condition .= " AND v.name != 'Petition'";
        }
        $activityTypes[$cacheKey] = CRM_Core_OptionGroup::values('activity_type',
          FALSE, FALSE, FALSE,
          $condition,
          $returnColumn
        );
      }
    }
    if (!empty($activityTypes[$cacheKey])) {
      return $activityTypes[$cacheKey];
    }
    else {
      return;
    }
  }

  /**
   * Get Surveys custom groups.
   *
   * @param array $surveyTypes
   *   an array of survey type id.
   *
   * @return array
   */
  public static function getSurveyCustomGroups($surveyTypes = []) {
    $customGroups = [];
    if (!is_array($surveyTypes)) {
      $surveyTypes = [$surveyTypes];
    }

    if (!empty($surveyTypes)) {
      $activityTypes = array_flip($surveyTypes);
    }
    else {
      $activityTypes = self::getSurveyActivityType();
    }

    if (!empty($activityTypes)) {
      $extendSubType = implode('[[:>:]]|[[:<:]]', array_keys($activityTypes));

      $query = "SELECT cg.id, cg.name, cg.title, cg.extends_entity_column_value
                      FROM civicrm_custom_group cg
                      WHERE cg.is_active = 1 AND cg.extends_entity_column_value REGEXP '[[:<:]]{$extendSubType}[[:>:]]'";

      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $customGroups[$dao->id]['id'] = $dao->id;
        $customGroups[$dao->id]['name'] = $dao->name;
        $customGroups[$dao->id]['title'] = $dao->title;
        $customGroups[$dao->id]['extends'] = $dao->extends_entity_column_value;
      }
    }

    return $customGroups;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Campaign_DAO_Survey', $id, 'is_active', $is_active);
  }

  /**
   * Delete the survey.
   *
   * @param int $id
   *   Survey id.
   *
   * @return mixed|null
   */
  public static function del($id) {
    if (!$id) {
      return NULL;
    }
    $reportId = CRM_Campaign_BAO_Survey::getReportID($id);
    if ($reportId) {
      CRM_Report_BAO_ReportInstance::del($reportId);
    }
    $dao = new CRM_Campaign_DAO_Survey();
    $dao->id = $id;
    return $dao->delete();
  }

  /**
   * This function retrieve contact information.
   *
   * @param array $voterIds
   * @param array $returnProperties
   *   An array of return elements.
   *
   * @return array
   *   array of contact info.
   */
  public static function voterDetails($voterIds, $returnProperties = []) {
    $voterDetails = [];
    if (!is_array($voterIds) || empty($voterIds)) {
      return $voterDetails;
    }

    if (empty($returnProperties)) {
      $autocompleteContactSearch = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
        'contact_autocomplete_options'
      );
      $returnProperties = array_fill_keys(array_merge(
        ['contact_type', 'contact_sub_type', 'sort_name'],
        array_keys($autocompleteContactSearch)
      ), 1);
    }

    $select = $from = [];
    foreach ($returnProperties as $property => $ignore) {
      $value = (in_array($property, [
        'city',
        'street_address',
      ])) ? 'address' : $property;
      switch ($property) {
        case 'sort_name':
        case 'contact_type':
        case 'contact_sub_type':
          $select[] = "$property as $property";
          $from['contact'] = 'civicrm_contact contact';
          break;

        case 'email':
        case 'phone':
        case 'city':
        case 'street_address':
          $select[] = "$property as $property";
          $from[$value] = "LEFT JOIN civicrm_{$value} {$value} ON ( contact.id = {$value}.contact_id AND {$value}.is_primary = 1 ) ";
          break;

        case 'country':
        case 'state_province':
          $select[] = "{$property}.name as $property";
          if (!in_array('address', $from)) {
            $from['address'] = 'LEFT JOIN civicrm_address address ON ( contact.id = address.contact_id AND address.is_primary = 1) ';
          }
          $from[$value] = " LEFT JOIN civicrm_{$value} {$value} ON ( address.{$value}_id = {$value}.id  ) ";
          break;
      }
    }

    //finally retrieve contact details.
    if (!empty($select) && !empty($from)) {
      $fromClause = implode(' ', $from);
      $selectClause = implode(', ', $select);
      $whereClause = "contact.id IN (" . implode(',', $voterIds) . ')';

      $query = "
  SELECT  contact.id as contactId, $selectClause
    FROM  $fromClause
   WHERE  $whereClause";

      $contact = CRM_Core_DAO::executeQuery($query);
      while ($contact->fetch()) {
        $voterDetails[$contact->contactId]['contact_id'] = $contact->contactId;
        foreach ($returnProperties as $property => $ignore) {
          $voterDetails[$contact->contactId][$property] = $contact->$property;
        }
        $image = CRM_Contact_BAO_Contact_Utils::getImage($contact->contact_sub_type ? $contact->contact_sub_type : $contact->contact_type,
          FALSE,
          $contact->contactId
        );
        $voterDetails[$contact->contactId]['contact_type'] = $image;
      }
    }

    return $voterDetails;
  }

  /**
   * This function retrieve survey related activities w/ for give voter ids.
   *
   * @param int $surveyId
   *   Survey id.
   * @param array $voterIds
   *   VoterIds.
   *
   * @param int $interviewerId
   * @param array $statusIds
   *
   * @return array
   *   array of survey activity.
   */
  public static function voterActivityDetails($surveyId, $voterIds, $interviewerId = NULL, $statusIds = []) {
    $activityDetails = [];
    if (!$surveyId ||
      !is_array($voterIds) || empty($voterIds)
    ) {
      return $activityDetails;
    }

    $whereClause = NULL;
    if (is_array($statusIds) && !empty($statusIds)) {
      $whereClause = ' AND ( activity.status_id IN ( ' . implode(',', array_values($statusIds)) . ' ) )';
    }

    $targetContactIds = ' ( ' . implode(',', $voterIds) . ' ) ';
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $params[1] = [$surveyId, 'Integer'];
    $query = "
    SELECT  activity.id, activity.status_id,
            activityTarget.contact_id as voter_id,
            activityAssignment.contact_id as interviewer_id
      FROM  civicrm_activity activity
INNER JOIN  civicrm_activity_contact activityTarget
  ON ( activityTarget.activity_id = activity.id AND activityTarget.record_type_id = $targetID )
INNER JOIN  civicrm_activity_contact activityAssignment
  ON ( activityAssignment.activity_id = activity.id AND activityAssignment.record_type_id = $assigneeID )
     WHERE  activity.source_record_id = %1
     AND  ( activity.is_deleted IS NULL OR activity.is_deleted = 0 ) ";
    if (!empty($interviewerId)) {
      $query .= "AND activityAssignment.contact_id = %2 ";
      $params[2] = [$interviewerId, 'Integer'];
    }
    $query .= "AND  activityTarget.contact_id IN {$targetContactIds}
            $whereClause";
    $activity = CRM_Core_DAO::executeQuery($query, $params);
    while ($activity->fetch()) {
      $activityDetails[$activity->voter_id] = [
        'voter_id' => $activity->voter_id,
        'status_id' => $activity->status_id,
        'activity_id' => $activity->id,
        'interviewer_id' => $activity->interviewer_id,
      ];
    }

    return $activityDetails;
  }

  /**
   * This function retrieve survey related activities.
   *
   * @param int $surveyId
   * @param int $interviewerId
   * @param array $statusIds
   * @param array $voterIds
   * @param bool $onlyCount
   *
   * @return array
   *   An array of survey activity.
   */
  public static function getSurveyActivities(
    $surveyId,
    $interviewerId = NULL,
    $statusIds = NULL,
    $voterIds = NULL,
    $onlyCount = FALSE
  ) {
    $activities = [];
    $surveyActivityCount = 0;
    if (!$surveyId) {
      return ($onlyCount) ? 0 : $activities;
    }

    $where = [];
    if (!empty($statusIds)) {
      $where[] = '( activity.status_id IN ( ' . implode(',', array_values($statusIds)) . ' ) )';
    }

    if ($interviewerId) {
      $where[] = "( activityAssignment.contact_id =  $interviewerId )";
    }

    if (!empty($voterIds)) {
      $where[] = "( activityTarget.contact_id IN ( " . implode(',', $voterIds) . " ) )";
    }

    $whereClause = NULL;
    if (!empty($where)) {
      $whereClause = ' AND ( ' . implode(' AND ', $where) . ' )';
    }

    $actTypeId = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $surveyId, 'activity_type_id');
    if (!$actTypeId) {
      return $activities;
    }

    if ($onlyCount) {
      $select = "SELECT count(activity.id)";
    }
    else {
      $select = "
    SELECT  activity.id, activity.status_id,
            activityTarget.contact_id as voter_id,
            activityAssignment.contact_id as interviewer_id,
            activity.result as result,
            activity.activity_date_time as activity_date_time,
            contact_a.display_name as voter_name";
    }

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $query = "
            $select
      FROM  civicrm_activity activity
INNER JOIN  civicrm_activity_contact activityTarget
  ON ( activityTarget.activity_id = activity.id AND activityTarget.record_type_id = $targetID )
INNER JOIN  civicrm_activity_contact activityAssignment
  ON ( activityAssignment.activity_id = activity.id AND activityAssignment.record_type_id = $assigneeID )
INNER JOIN  civicrm_contact contact_a ON ( activityTarget.contact_id = contact_a.id )
     WHERE  activity.source_record_id = %1
       AND  activity.activity_type_id = %2
       AND  ( activity.is_deleted IS NULL OR activity.is_deleted = 0 )
            $whereClause";

    $params = [
      1 => [$surveyId, 'Integer'],
      2 => [$actTypeId, 'Integer'],
    ];

    if ($onlyCount) {
      $dbCount = CRM_Core_DAO::singleValueQuery($query, $params);
      return ($dbCount) ? $dbCount : 0;
    }

    $activity = CRM_Core_DAO::executeQuery($query, $params);

    while ($activity->fetch()) {
      $activities[$activity->id] = [
        'id' => $activity->id,
        'voter_id' => $activity->voter_id,
        'voter_name' => $activity->voter_name,
        'status_id' => $activity->status_id,
        'interviewer_id' => $activity->interviewer_id,
        'result' => $activity->result,
        'activity_date_time' => $activity->activity_date_time,
      ];
    }

    return $activities;
  }

  /**
   * Retrieve survey voter information.
   *
   * @param int $surveyId
   *   Survey id.
   * @param int $interviewerId
   *   Interviewer id.
   * @param array $statusIds
   *   Survey status ids.
   *
   * @return array
   *   Survey related contact ids.
   */
  public static function getSurveyVoterInfo($surveyId, $interviewerId = NULL, $statusIds = []) {
    $voterIds = [];
    if (!$surveyId) {
      return $voterIds;
    }

    $cacheKey = $surveyId;
    if ($interviewerId) {
      $cacheKey .= "_{$interviewerId}";
    }
    if (is_array($statusIds) && !empty($statusIds)) {
      $cacheKey = "{$cacheKey}_" . implode('_', $statusIds);
    }

    static $contactIds = [];
    if (!isset($contactIds[$cacheKey])) {
      $activities = self::getSurveyActivities($surveyId, $interviewerId, $statusIds);
      foreach ($activities as $values) {
        $voterIds[$values['voter_id']] = $values;
      }
      $contactIds[$cacheKey] = $voterIds;
    }

    return $contactIds[$cacheKey];
  }

  /**
   * This function retrieve all option groups which are created as a result set.
   *
   * @param string $valueColumnName
   * @return array
   *   an array of option groups.
   */
  public static function getResultSets($valueColumnName = 'title') {
    $resultSets = [];
    $valueColumnName = CRM_Utils_Type::escape($valueColumnName, 'String');

    $query = "SELECT id, {$valueColumnName} FROM civicrm_option_group WHERE name LIKE 'civicrm_survey_%' AND is_active=1";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $resultSets[$dao->id] = $dao->$valueColumnName;
    }

    return $resultSets;
  }

  /**
   * check survey activity.
   *
   * @param int $activityId
   *   Activity id.
   * @return bool
   */
  public static function isSurveyActivity($activityId) {
    $isSurveyActivity = FALSE;
    if (!$activityId) {
      return $isSurveyActivity;
    }

    $activity = new CRM_Activity_DAO_Activity();
    $activity->id = $activityId;
    $activity->selectAdd('source_record_id, activity_type_id');
    if ($activity->find(TRUE) &&
      $activity->source_record_id
    ) {
      $surveyActTypes = self::getSurveyActivityType();
      if (array_key_exists($activity->activity_type_id, $surveyActTypes)) {
        $isSurveyActivity = TRUE;
      }
    }

    return $isSurveyActivity;
  }

  /**
   * This function retrive all response options of survey.
   *
   * @param int $surveyId
   *   Survey id.
   * @return array
   *   an array of option values
   */
  public static function getResponsesOptions($surveyId) {
    $responseOptions = [];
    if (!$surveyId) {
      return $responseOptions;
    }

    $resultId = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $surveyId, 'result_id');
    if ($resultId) {
      $responseOptions = CRM_Core_OptionGroup::valuesByID($resultId);
    }

    return $responseOptions;
  }

  /**
   * This function return all voter links with respecting permissions.
   *
   * @param int $surveyId
   * @param bool $enclosedInUL
   * @param string $extraULName
   * @return array|string
   *   $url array of permissioned links
   */
  public static function buildPermissionLinks($surveyId, $enclosedInUL = FALSE, $extraULName = 'more') {
    $menuLinks = [];
    if (!$surveyId) {
      return $menuLinks;
    }

    static $voterLinks = [];
    if (empty($voterLinks)) {
      $permissioned = FALSE;
      if (CRM_Core_Permission::check('manage campaign') ||
        CRM_Core_Permission::check('administer CiviCampaign')
      ) {
        $permissioned = TRUE;
      }

      if ($permissioned || CRM_Core_Permission::check("reserve campaign contacts")) {
        $voterLinks['reserve'] = [
          'name' => 'reserve',
          'url' => 'civicrm/survey/search',
          'qs' => 'sid=%%id%%&reset=1&op=reserve',
          'title' => ts('Reserve Respondents'),
        ];
      }
      if ($permissioned || CRM_Core_Permission::check("interview campaign contacts")) {
        $voterLinks['release'] = [
          'name' => 'interview',
          'url' => 'civicrm/survey/search',
          'qs' => 'sid=%%id%%&reset=1&op=interview&force=1',
          'title' => ts('Interview Respondents'),
        ];
      }
      if ($permissioned || CRM_Core_Permission::check("release campaign contacts")) {
        $voterLinks['interview'] = [
          'name' => 'release',
          'url' => 'civicrm/survey/search',
          'qs' => 'sid=%%id%%&reset=1&op=release&force=1',
          'title' => ts('Release Respondents'),
        ];
      }
    }

    if (CRM_Core_Permission::check('access CiviReport')) {
      $reportID = self::getReportID($surveyId);
      if ($reportID) {
        $voterLinks['report'] = [
          'name' => 'report',
          'url' => "civicrm/report/instance/{$reportID}",
          'qs' => 'reset=1',
          'title' => ts('View Survey Report'),
        ];
      }
    }

    $ids = ['id' => $surveyId];
    foreach ($voterLinks as $link) {
      if (!empty($link['qs']) &&
        !CRM_Utils_System::isNull($link['qs'])
      ) {
        $urlPath = CRM_Utils_System::url(CRM_Core_Action::replace($link['url'], $ids),
          CRM_Core_Action::replace($link['qs'], $ids)
        );
        $menuLinks[] = sprintf('<a href="%s" class="action-item crm-hover-button" title="%s">%s</a>',
          $urlPath,
          CRM_Utils_Array::value('title', $link),
          $link['title']
        );
      }
    }
    if ($enclosedInUL) {
      $extraLinksName = strtolower($extraULName);
      $allLinks = '';
      CRM_Utils_String::append($allLinks, '</li><li>', $menuLinks);
      $allLinks = "$extraULName <ul id='panel_{$extraLinksName}_xx' class='panel'><li>{$allLinks}</li></ul>";
      $menuLinks = "<span class='btn-slide crm-hover-button' id={$extraLinksName}_xx>{$allLinks}</span>";
    }

    return $menuLinks;
  }

  /**
   * Retrieve survey associated profile id.
   *
   * @param int $surveyId
   *
   * @return mixed|null
   */
  public static function getSurveyProfileId($surveyId) {
    if (!$surveyId) {
      return NULL;
    }

    static $ufIds = [];
    if (!array_key_exists($surveyId, $ufIds)) {
      //get the profile id.
      $ufJoinParams = [
        'entity_id' => $surveyId,
        'entity_table' => 'civicrm_survey',
        'module' => 'CiviCampaign',
      ];

      list($first, $second) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

      if ($first) {
        $ufIds[$surveyId] = [$first];
      }
      if ($second) {
        $ufIds[$surveyId][] = array_shift($second);
      }
    }

    return CRM_Utils_Array::value($surveyId, $ufIds);
  }

  /**
   * @param int $surveyId
   *
   * @return mixed
   */
  public static function getReportID($surveyId) {
    static $reportIds = [];

    if (!array_key_exists($surveyId, $reportIds)) {
      $query = "SELECT MAX(id) as id FROM civicrm_report_instance WHERE name = %1";
      $reportID = CRM_Core_DAO::singleValueQuery($query, [1 => ["survey_{$surveyId}", 'String']]);
      $reportIds[$surveyId] = $reportID;
    }
    return $reportIds[$surveyId];
  }

  /**
   * Decides the contact type for given survey.
   *
   * @param int $surveyId
   *
   * @return null|string
   */
  public static function getSurveyContactType($surveyId) {
    $contactType = NULL;

    //apply filter of profile type on search.
    $profileId = self::getSurveyProfileId($surveyId);
    if ($profileId) {
      $profileType = CRM_Core_BAO_UFField::getProfileType($profileId);
      if (in_array($profileType, CRM_Contact_BAO_ContactType::basicTypes())) {
        $contactType = $profileType;
      }
    }

    return $contactType;
  }

  /**
   * Get survey supportable profile types.
   */
  public static function surveyProfileTypes() {
    static $profileTypes;

    if (!isset($profileTypes)) {
      $profileTypes = array_merge(['Activity', 'Contact'], CRM_Contact_BAO_ContactType::basicTypes());
      $profileTypes = array_diff($profileTypes, ['Organization', 'Household']);
    }

    return $profileTypes;
  }

  /**
   * Get the valid survey response fields those.
   * are configured with profile and custom fields.
   *
   * @param int $surveyId
   *   Survey id.
   * @param int $surveyTypeId
   *   Survey activity type id.
   *
   * @return array
   *   an array of valid survey response fields.
   */
  public static function getSurveyResponseFields($surveyId, $surveyTypeId = NULL) {
    if (empty($surveyId)) {
      return [];
    }

    static $responseFields;
    $cacheKey = "{$surveyId}_{$surveyTypeId}";

    if (isset($responseFields[$cacheKey])) {
      return $responseFields[$cacheKey];
    }

    $responseFields[$cacheKey] = [];

    $profileId = self::getSurveyProfileId($surveyId);

    if (!$profileId) {
      return $responseFields;
    }

    if (!$surveyTypeId) {
      $surveyTypeId = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $surveyId, 'activity_type_id');
    }

    $profileFields = CRM_Core_BAO_UFGroup::getFields($profileId,
      FALSE, CRM_Core_Action::VIEW, NULL, NULL, FALSE, NULL, FALSE, NULL, CRM_Core_Permission::CREATE, 'field_name', TRUE
    );

    //don't load these fields in grid.
    $removeFields = ['File', 'RichTextEditor'];

    $supportableFieldTypes = self::surveyProfileTypes();

    // get custom fields of type survey
    $customFields = CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE, $surveyTypeId);

    foreach ($profileFields as $name => $field) {
      //get only contact and activity fields.
      //later stage we might going to consider contact type also.
      if (in_array($field['field_type'], $supportableFieldTypes)) {
        // we should allow all supported custom data for survey
        // In case of activity, allow normal activity and with subtype survey,
        // suppress custom data of other activity types
        if (CRM_Core_BAO_CustomField::getKeyID($name)) {
          if (!in_array($field['html_type'], $removeFields)) {
            if ($field['field_type'] != 'Activity') {
              $responseFields[$cacheKey][$name] = $field;
            }
            elseif (array_key_exists(CRM_Core_BAO_CustomField::getKeyID($name), $customFields)) {
              $responseFields[$cacheKey][$name] = $field;
            }
          }
        }
        else {
          $responseFields[$cacheKey][$name] = $field;
        }
      }
    }

    return $responseFields[$cacheKey];
  }

  /**
   * Get all interviewers of surveys.
   *
   * @return array
   *   an array of valid survey response fields.
   */
  public static function getInterviewers() {
    static $interviewers;

    if (isset($interviewers)) {
      return $interviewers;
    }

    $whereClause = NULL;
    $activityTypes = self::getSurveyActivityType();
    if (!empty($activityTypes)) {
      $whereClause = ' WHERE survey.activity_type_id IN ( ' . implode(' , ', array_keys($activityTypes)) . ' )';
    }

    $interviewers = [];
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);

    $query = "
    SELECT  contact.id as id,
            contact.sort_name as sort_name
      FROM  civicrm_contact contact
INNER JOIN civicrm_activity_contact assignment ON ( assignment.contact_id = contact.id AND record_type_id = $assigneeID )
INNER JOIN  civicrm_activity activity ON ( activity.id = assignment.activity_id )
INNER JOIN  civicrm_survey survey ON ( activity.source_record_id = survey.id )
            {$whereClause}";

    $interviewer = CRM_Core_DAO::executeQuery($query);
    while ($interviewer->fetch()) {
      $interviewers[$interviewer->id] = $interviewer->sort_name;
    }

    return $interviewers;
  }

  /**
   * Check and update the survey respondents.
   *
   * @param array $params
   *
   * @return array
   *   success message
   */
  public static function releaseRespondent($params) {
    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $reserveStatusId = array_search('Scheduled', $activityStatus);
    $surveyActivityTypes = CRM_Campaign_BAO_Survey::getSurveyActivityType();
    if (!empty($surveyActivityTypes) && is_array($surveyActivityTypes)) {
      $surveyActivityTypesIds = array_keys($surveyActivityTypes);
    }

    //retrieve all survey activities related to reserve action.
    $releasedCount = 0;
    if ($reserveStatusId && !empty($surveyActivityTypesIds)) {
      $query = '
    SELECT  activity.id as id,
            activity.activity_date_time as activity_date_time,
            survey.id as surveyId,
            survey.release_frequency as release_frequency
      FROM  civicrm_activity activity
INNER JOIN  civicrm_survey survey ON ( survey.id = activity.source_record_id )
     WHERE  activity.is_deleted = 0
       AND  activity.status_id = %1
       AND  activity.activity_type_id IN ( ' . implode(', ', $surveyActivityTypesIds) . ' )';
      $activity = CRM_Core_DAO::executeQuery($query, [1 => [$reserveStatusId, 'Positive']]);
      $releasedIds = [];
      while ($activity->fetch()) {
        if (!$activity->release_frequency) {
          continue;
        }
        $reservedSeconds = CRM_Utils_Date::unixTime($activity->activity_date_time);
        $releasedSeconds = $activity->release_frequency * 24 * 3600;
        $totalReservedSeconds = $reservedSeconds + $releasedSeconds;
        if ($totalReservedSeconds < time()) {
          $releasedIds[$activity->id] = $activity->id;
        }
      }

      //released respondent.
      if (!empty($releasedIds)) {
        $query = '
UPDATE  civicrm_activity
   SET  is_deleted = 1
 WHERE  id IN ( ' . implode(', ', $releasedIds) . ' )';
        CRM_Core_DAO::executeQuery($query);
        $releasedCount = count($releasedIds);
      }
    }

    $rtnMsg = [
      'is_error' => 0,
      'messages' => "Number of respondents released = {$releasedCount}",
    ];

    return $rtnMsg;
  }

  /**
   * Get options for a given field.
   * @see CRM_Core_DAO::buildOptions
   *
   * @param string $fieldName
   * @param string $context : @see CRM_Core_DAO::buildOptionsContext
   * @param array $props : whatever is known about this dao object
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    $params = [];
    // Special logic for fields whose options depend on context or properties
    switch ($fieldName) {
      case 'activity_type_id':
        $campaignCompId = CRM_Core_Component::getComponentID('CiviCampaign');
        if ($campaignCompId) {
          $params['condition'] = ["component_id={$campaignCompId}"];
        }
        break;
    }
    return CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, $params, $context);
  }

}
