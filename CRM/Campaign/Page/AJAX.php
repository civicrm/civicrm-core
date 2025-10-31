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

/**
 * This class contains all campaign related functions that are called using AJAX (jQuery)
 */
class CRM_Campaign_Page_AJAX {

  public static function registerInterview() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $fields = [
      'result',
      'voter_id',
      'survey_id',
      'activity_id',
      'surveyTitle',
      'interviewer_id',
      'activity_type_id',
    ];

    $params = [];
    foreach ($fields as $fld) {
      $params[$fld] = $_POST[$fld] ?? NULL;
    }
    $params['details'] = $_POST['note'] ?? NULL;
    $voterId = $params['voter_id'];
    $activityId = $params['activity_id'];

    $customKey = "field_{$voterId}_custom";
    foreach ($_POST as $key => $value) {
      if (str_contains($key, $customKey)) {
        $customFieldKey = str_replace(str_replace(substr($customKey, -6), '', $customKey), '', $key);
        $params[$customFieldKey] = $value;
      }
    }

    if (isset($_POST['field']) && !empty($_POST['field'][$voterId]) &&
      is_array($_POST['field'][$voterId])
    ) {
      foreach ($_POST['field'][$voterId] as $fieldKey => $value) {
        $params[$fieldKey] = $value;
      }
    }

    //lets pickup contat related fields.
    foreach ($_POST as $key => $value) {
      if (str_contains($key, "field_{$voterId}_") &&
        !str_contains($key, "field_{$voterId}_custom")
      ) {
        $key = substr($key, strlen("field_{$voterId}_"));
        $params[$key] = $value;
      }
    }

    $result = [
      'status' => 'fail',
      'voter_id' => $voterId,
      'activity_id' => $params['interviewer_id'],
    ];

    //time to validate custom data.
    $errors = CRM_Core_BAO_CustomField::validateCustomData($params);
    if (is_array($errors) && !empty($errors)) {
      $result['errors'] = $errors;
      CRM_Utils_JSON::output($result);
    }

    //process the response/interview data.
    $activityId = CRM_Campaign_Form_Task_Interview::registerInterview($params);
    if ($activityId) {
      $result['status'] = 'success';
    }

    CRM_Utils_JSON::output($result);
  }

  public static function loadOptionGroupDetails() {

    $id = CRM_Utils_Request::retrieve('option_group_id', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'POST');
    $status = 'fail';

    if ($id) {
      $opValues = \Civi\Api4\OptionValue::get(FALSE)
        ->addWhere('option_group_id', '=', $id)
        ->addOrderBy('weight')
        ->execute()->indexBy('id');
      foreach ($opValues as $id => $value) {
        $opValues[$id]['interval'] = $value['filter'];
      }
    }

    if (!empty($opValues)) {
      $status = 'success';
    }

    $result = [
      'status' => $status,
      'result' => $opValues,
    ];

    CRM_Utils_JSON::output($result);
  }

  public static function voterList() {
    //get the search criteria params.
    $searchCriteria = CRM_Utils_Request::retrieve('searchCriteria', 'String', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'POST');
    $searchParams = explode(',', $searchCriteria);

    $params = $searchRows = [];
    foreach ($searchParams as $param) {
      if (!empty($_POST[$param])) {
        $params[$param] = $_POST[$param];
      }
    }

    //format multi-select group and contact types.
    foreach (['group', 'contact_type'] as $param) {
      $paramValue = $params[$param] ?? NULL;
      if ($paramValue) {
        unset($params[$param]);
        $paramValue = explode(',', $paramValue);
        foreach ($paramValue as $key => $value) {
          $params[$param][$value] = 1;
        }
      }
    }

    $voterClauseParams = [];
    foreach ([
      'campaign_survey_id',
      'survey_interviewer_id',
      'campaign_search_voter_for',
    ] as $fld) {
      $voterClauseParams[$fld] = $params[$fld] ?? NULL;
    }

    $interviewerId = $surveyTypeId = $surveyId = NULL;
    $searchVoterFor = $params['campaign_search_voter_for'];
    if ($searchVoterFor == 'reserve') {
      if (!empty($params['campaign_survey_id'])) {
        $survey = new CRM_Campaign_DAO_Survey();
        $survey->id = $surveyId = $params['campaign_survey_id'];
        $survey->selectAdd('campaign_id, activity_type_id');
        $survey->find(TRUE);
        $campaignId = $survey->campaign_id;
        $surveyTypeId = $survey->activity_type_id;

        //allow voter search in sub-part of given constituents,
        //but make sure in case user does not select any group.
        //get all associated campaign groups in where filter, CRM-7406
        $groups = $params['group'] ?? NULL;
        if ($campaignId && CRM_Utils_System::isNull($groups)) {
          $campaignGroups = CRM_Campaign_BAO_Campaign::getCampaignGroups($campaignId);
          foreach ($campaignGroups as $id => $group) {
            $params['group'][$id] = 1;
          }
        }

        //apply filter of survey contact type for search.
        $contactType = CRM_Campaign_BAO_Survey::getSurveyContactType($surveyId);
        if ($contactType) {
          $params['contact_type'][$contactType] = 1;
        }

        unset($params['campaign_survey_id']);
      }
      unset($params['survey_interviewer_id']);
    }
    else {
      //get the survey status in where clause.
      $scheduledStatusId = array_search('Scheduled', CRM_Core_PseudoConstant::activityStatus('name'));
      if ($scheduledStatusId) {
        $params['survey_status_id'] = $scheduledStatusId;
      }
      //BAO/Query knows reserve/release/interview processes.
      if ($params['campaign_search_voter_for'] == 'gotv') {
        $params['campaign_search_voter_for'] = 'release';
      }
    }

    $selectorCols = [
      'sort_name',
      'street_address',
      'street_name',
      'street_number',
      'street_unit',
    ];

    // get the data table params.
    $dataTableParams = [
      'sEcho' => [
        'name' => 'sEcho',
        'type' => 'Integer',
        'default' => 0,
      ],
      'offset' => [
        'name' => 'iDisplayStart',
        'type' => 'Integer',
        'default' => 0,
      ],
      'rowCount' => [
        'name' => 'iDisplayLength',
        'type' => 'Integer',
        'default' => 25,
      ],
      'sort' => [
        'name' => 'iSortCol_0',
        'type' => 'Integer',
        'default' => 'sort_name',
      ],
      'sortOrder' => [
        'name' => 'sSortDir_0',
        'type' => 'String',
        'default' => 'asc',
      ],
    ];
    foreach ($dataTableParams as $pName => $pValues) {
      $$pName = $pValues['default'];
      if (!empty($_POST[$pValues['name']])) {
        $$pName = CRM_Utils_Type::escape($_POST[$pValues['name']], $pValues['type']);
        if ($pName == 'sort') {
          $$pName = $selectorCols[$$pName];
        }
      }
    }

    $queryParams = CRM_Contact_BAO_Query::convertFormValues($params);
    $query = new CRM_Contact_BAO_Query($queryParams,
      NULL, NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_CAMPAIGN,
      TRUE
    );

    //get the voter clause to restrict and validate search.
    $voterClause = CRM_Campaign_BAO_Query::voterClause($voterClauseParams);

    $searchCount = $query->searchQuery(0, 0, NULL,
      TRUE, FALSE,
      FALSE, FALSE,
      FALSE,
      $voterClause['whereClause'] ?? NULL,
      NULL,
      $voterClause['fromClause'] ?? NULL
    );

    $iTotal = $searchCount;

    $selectorCols = [
      'contact_type',
      'sort_name',
      'street_address',
      'street_name',
      'street_number',
      'street_unit',
    ];

    $extraVoterColName = 'is_interview_conducted';
    if ($params['campaign_search_voter_for'] == 'reserve') {
      $extraVoterColName = 'reserve_voter';
    }

    if ($searchCount > 0) {
      if ($searchCount < $offset) {
        $offset = 0;
      }

      $config = CRM_Core_Config::singleton();

      // get the result of the search
      $result = $query->searchQuery($offset, $rowCount, $sort,
        FALSE, FALSE,
        FALSE, FALSE,
        FALSE,
        $voterClause['whereClause'] ?? NULL,
        $sortOrder,
        $voterClause['fromClause'] ?? NULL
      );
      while ($result->fetch()) {
        $contactID = $result->contact_id;
        $typeImage = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?: $result->contact_type,
          FALSE,
          $result->contact_id
        );

        $searchRows[$contactID] = ['id' => $contactID];
        foreach ($selectorCols as $col) {
          $val = $result->$col;
          if ($col == 'contact_type') {
            $val = $typeImage;
          }
          $searchRows[$contactID][$col] = $val;
        }
        if ($searchVoterFor == 'reserve') {
          $voterExtraColHtml = '<input type="checkbox" id="survey_activity[' . $contactID . ']" name="survey_activity[' . $contactID . ']" value=' . $contactID . ' onClick="processVoterData( this, \'reserve\' );" />';
          $msg = ts('Respondent Reserved.');
          $voterExtraColHtml .= "&nbsp;<span id='success_msg_{$contactID}' class='ok' style='display:none;'>$msg</span>";
        }
        elseif ($searchVoterFor == 'gotv') {
          $surveyActId = $result->survey_activity_id;
          $voterExtraColHtml = '<input type="checkbox" id="survey_activity[' . $surveyActId . ']" name="survey_activity[' . $surveyActId . ']" value=' . $surveyActId . ' onClick="processVoterData( this, \'gotv\' );" />';
          $msg = ts('Vote Recorded.');
          $voterExtraColHtml .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id='success_msg_{$surveyActId}' class='ok' style='display:none;'>$msg</span>";
        }
        else {
          $surveyActId = $result->survey_activity_id;
          $voterExtraColHtml = '<input type="checkbox" id="survey_activity[' . $surveyActId . ']" name="survey_activity[' . $surveyActId . ']" value=' . $surveyActId . ' onClick="processVoterData( this, \'release\' );" />';
          $msg = ts('Vote Recorded.');
          $voterExtraColHtml .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id='success_msg_{$surveyActId}' class='ok' style='display:none;'>$msg</span>";
        }
        $searchRows[$contactID][$extraVoterColName] = $voterExtraColHtml;
      }
    }

    $selectorElements = array_merge($selectorCols, [$extraVoterColName]);

    $iFilteredTotal = $iTotal;

    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($searchRows, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  public static function processVoterData() {
    $status = NULL;
    $operation = CRM_Utils_Type::escape($_POST['operation'], 'String');
    if ($operation == 'release') {
      $activityId = CRM_Utils_Type::escape($_POST['activity_id'], 'Integer');
      $isDelete = CRM_Utils_String::strtoboolstr(CRM_Utils_Type::escape($_POST['isDelete'], 'String'));
      if ($activityId &&
        CRM_Core_DAO::setFieldValue('CRM_Activity_DAO_Activity',
          $activityId,
          'is_deleted',
          $isDelete
        )
      ) {
        $status = 'success';
      }
    }
    elseif ($operation == 'reserve') {
      $activityId = NULL;
      $createActivity = TRUE;
      if (!empty($_POST['activity_id'])) {
        $activityId = CRM_Utils_Type::escape($_POST['activity_id'], 'Integer');
        if ($activityId) {
          $createActivity = FALSE;
          $activityUpdated = CRM_Core_DAO::setFieldValue('CRM_Activity_DAO_Activity',
            $activityId,
            'is_deleted',
            0
          );
          if ($activityUpdated) {
            $status = 'success';
          }
        }
      }
      if ($createActivity) {
        $ids = [
          'source_record_id',
          'source_contact_id',
          'target_contact_id',
          'assignee_contact_id',
        ];
        $activityParams = [];
        foreach ($ids as $id) {
          $val = $_POST[$id] ?? NULL;
          if (!$val) {
            $createActivity = FALSE;
            break;
          }
          $activityParams[$id] = CRM_Utils_Type::escape($val, 'Integer');
        }
      }
      if ($createActivity) {
        $isReserved = CRM_Utils_String::strtoboolstr(CRM_Utils_Type::escape($_POST['isReserved'], 'String'));
        $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
        $scheduledStatusId = array_search('Scheduled', $activityStatus);
        if ($isReserved) {
          $surveyValues = [];
          $surveyParams = ['id' => $activityParams['source_record_id']];
          CRM_Core_DAO::commonRetrieve('CRM_Campaign_DAO_Survey',
            $surveyParams,
            $surveyValues,
            ['title', 'activity_type_id', 'campaign_id']
          );

          $activityTypeId = $surveyValues['activity_type_id'];

          $surveytitle = $_POST['surveyTitle'] ?? NULL;
          if (!$surveytitle) {
            $surveytitle = $surveyValues['title'];
          }

          $subject = $surveytitle . ' - ' . ts('Respondent Reservation');
          $activityParams['subject'] = $subject;
          $activityParams['status_id'] = $scheduledStatusId;
          $activityParams['skipRecentView'] = 1;
          $activityParams['activity_date_time'] = date('YmdHis');
          $activityParams['activity_type_id'] = $activityTypeId;
          $activityParams['campaign_id'] = $surveyValues['campaign_id'] ?? NULL;

          $activity = CRM_Activity_BAO_Activity::create($activityParams);
          if ($activity->id) {
            $status = 'success';
          }
        }
        else {
          //delete reserved activity for given voter.
          $voterIds = [$activityParams['target_contact_id']];
          $activities = CRM_Campaign_BAO_Survey::voterActivityDetails($activityParams['source_record_id'],
            $voterIds,
            $activityParams['source_contact_id'],
            [$scheduledStatusId]
          );
          foreach ($activities as $voterId => $values) {
            $activityId = $values['activity_id'] ?? NULL;
            if ($activityId && ($values['status_id'] == $scheduledStatusId)) {
              CRM_Core_DAO::setFieldValue('CRM_Activity_DAO_Activity',
                $activityId,
                'is_deleted',
                TRUE
              );
              $status = 'success';
              break;
            }
          }
        }
      }
    }
    elseif ($operation == 'gotv') {
      $activityId = CRM_Utils_Type::escape($_POST['activity_id'], 'Integer');
      $hasVoted = CRM_Utils_String::strtoboolstr(CRM_Utils_Type::escape($_POST['hasVoted'], 'String'));
      if ($activityId) {
        if ($hasVoted) {
          $statusValue = 2;
        }
        else {
          $statusValue = 1;
        }
        CRM_Core_DAO::setFieldValue('CRM_Activity_DAO_Activity',
          $activityId,
          'status_id',
          $statusValue
        );
        $status = 'success';
      }
    }

    CRM_Utils_JSON::output(['status' => $status]);
  }

  public static function campaignGroups() {
    $surveyId = CRM_Utils_Request::retrieve('survey_id', 'Positive',
      CRM_Core_DAO::$_nullObject, FALSE, NULL, 'POST'
    );
    $campGroups = [];
    if ($surveyId) {
      $campaignId = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $surveyId, 'campaign_id');
      if ($campaignId) {
        $campGroups = CRM_Campaign_BAO_Campaign::getCampaignGroups($campaignId);
      }
    }

    //CRM-7406 --If there is no campaign or no group associated with
    //campaign of given survey, lets allow to search across all groups.
    if (empty($campGroups)) {
      $campGroups = CRM_Core_PseudoConstant::group();
    }
    $groups = [
      [
        'value' => '',
        'title' => ts('- select -'),
      ],
    ];
    foreach ($campGroups as $grpId => $title) {
      $groups[] = [
        'value' => $grpId,
        'title' => $title,
      ];
    }
    $results = [
      'status' => 'success',
      'groups' => $groups,
    ];

    CRM_Utils_JSON::output($results);
  }

}
