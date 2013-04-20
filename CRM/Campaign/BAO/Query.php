<?php

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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Campaign_BAO_Query {
  //since normal activity clause clause get collides.
  CONST
    CIVICRM_ACTIVITY = 'civicrm_survey_activity',
    CIVICRM_ACTIVITY_TARGET = 'civicrm_survey_activity_target',
    CIVICRM_ACTIVITY_ASSIGNMENT = 'civicrm_survey_activity_assignment';

  /**
   * static field for all the campaign fields
   *
   * @var array
   * @static
   */
  static $_campaignFields = NULL;

  static $_applySurveyClause = FALSE;

  /**
   * Function get the fields for campaign.
   *
   * @return array self::$_campaignFields  an associative array of campaign fields
   * @static
   */
  static function &getFields() {
    if (!isset(self::$_campaignFields)) {
      self::$_campaignFields = array();
    }

    return self::$_campaignFields;
  }

  /**
   * if survey, campaign are involved, add the specific fields.
   *
   * @return void
   * @access public
   */
  static function select(&$query) {
    self::$_applySurveyClause = FALSE;
    if (is_array($query->_params)) {
      foreach ($query->_params as $values) {
        if (!is_array($values) || count($values) != 5) {
          continue;
        }

        list($name, $op, $value, $grouping, $wildcard) = $values;
        if ($name == 'campaign_survey_id') {
          self::$_applySurveyClause = TRUE;
          break;
        }
      }
    }

    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return;
    }

    //all below tables are require to fetch  result.

    //1. get survey activity target table in.
    $query->_select['survey_activity_target_contact_id'] = 'civicrm_activity_target.target_contact_id as survey_activity_target_contact_id';
    $query->_select['survey_activity_target_id'] = 'civicrm_activity_target.id as survey_activity_target_id';
    $query->_element['survey_activity_target_id'] = 1;
    $query->_element['survey_activity_target_contact_id'] = 1;
    $query->_tables[self::CIVICRM_ACTIVITY_TARGET] = 1;
    $query->_whereTables[self::CIVICRM_ACTIVITY_TARGET] = 1;

    //2. get survey activity table in.
    $query->_select['survey_activity_id'] = 'civicrm_activity.id as survey_activity_id';
    $query->_element['survey_activity_id'] = 1;
    $query->_tables[self::CIVICRM_ACTIVITY] = 1;
    $query->_whereTables[self::CIVICRM_ACTIVITY] = 1;

    //3. get the assignee table in.
    $query->_select['survey_interviewer_id'] = 'civicrm_activity_assignment.id as survey_interviewer_id';
    $query->_element['survey_interviewer_id'] = 1;
    $query->_tables[self::CIVICRM_ACTIVITY_ASSIGNMENT] = 1;
    $query->_whereTables[self::CIVICRM_ACTIVITY_ASSIGNMENT] = 1;

    //4. get survey table.
    $query->_select['campaign_survey_id'] = 'civicrm_survey.id as campaign_survey_id';
    $query->_element['campaign_survey_id'] = 1;
    $query->_tables['civicrm_survey'] = 1;
    $query->_whereTables['civicrm_survey'] = 1;

    //5. get campaign table.
    $query->_select['campaign_id'] = 'civicrm_campaign.id as campaign_id';
    $query->_element['campaign_id'] = 1;
    $query->_tables['civicrm_campaign'] = 1;
    $query->_whereTables['civicrm_campaign'] = 1;
  }

  static function where(&$query) {
    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return;
    }

    $grouping = NULL;
    foreach (array_keys($query->_params) as $id) {
      if ($query->_mode == CRM_Contact_BAO_QUERY::MODE_CONTACTS) {
        $query->_useDistinct = TRUE;
      }

      self::whereClauseSingle($query->_params[$id], $query);
    }
  }

  static function whereClauseSingle(&$values, &$query) {
    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return;
    }

    list($name, $op, $value, $grouping, $wildcard) = $values;

    switch ($name) {
      case 'campaign_survey_id':
        $query->_qill[$grouping][] = ts('Survey - %1', array(1 => CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey', $value, 'title')));

        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_activity.source_record_id',
          $op, $value, "Integer"
        );
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_survey.id',
          $op, $value, "Integer"
        );
        return;

      case 'survey_status_id':
        $activityStatus = CRM_Core_PseudoConstant::activityStatus();

        $query->_qill[$grouping][] = ts('Survey Status - %1', array(1 => $activityStatus[$value]));
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_activity.status_id',
          $op, $value, "Integer"
        );
        return;

      case 'campaign_search_voter_for':
        if (in_array($value, array(
          'release', 'interview'))) {
          $query->_where[$grouping][] = '(civicrm_activity.is_deleted = 0 OR civicrm_activity.is_deleted IS NULL)';
        }
        return;

      case 'survey_interviewer_id':
        $surveyInterviewerName = NULL;
        foreach ($query->_params as $paramValues) {
          if (CRM_Utils_Array::value(0, $paramValues) == 'survey_interviewer_name') {
            $surveyInterviewerName = CRM_Utils_Array::value(2, $paramValues);
            break;
          }
        }
        $query->_qill[$grouping][] = ts('Survey Interviewer - %1', array(1 => $surveyInterviewerName));
        $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause('civicrm_activity_assignment.assignee_contact_id',
          $op, $value, "Integer"
        );
        return;
    }
  }

  static function from($name, $mode, $side) {
    $from = NULL;
    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return $from;
    }

    switch ($name) {
      case self::CIVICRM_ACTIVITY_TARGET:
        $from = " INNER JOIN civicrm_activity_target ON ( civicrm_activity_target.target_contact_id = contact_a.id ) ";
        break;

      case self::CIVICRM_ACTIVITY:
        $surveyActivityTypes = CRM_Campaign_PseudoConstant::activityType();
        $surveyKeys          = "(" . implode(',', array_keys($surveyActivityTypes)) . ")";
        $from                = " INNER JOIN civicrm_activity ON ( civicrm_activity.id = civicrm_activity_target.activity_id
                                 AND civicrm_activity.activity_type_id IN $surveyKeys ) ";
        break;

      case self::CIVICRM_ACTIVITY_ASSIGNMENT:
        $from = "
INNER JOIN civicrm_activity_assignment ON ( civicrm_activity.id = civicrm_activity_assignment.activity_id ) ";
        break;

      case 'civicrm_survey':
        $from = " INNER JOIN civicrm_survey ON ( civicrm_survey.id = civicrm_activity.source_record_id ) ";
        break;

      case 'civicrm_campaign':
        $from = " $side JOIN civicrm_campaign ON ( civicrm_campaign.id = civicrm_survey.campaign_id ) ";
        break;
    }

    return $from;
  }

  static function defaultReturnProperties($mode,
    $includeCustomFields = TRUE
  ) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_CAMPAIGN) {
      $properties = array(
        'contact_id' => 1,
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'display_name' => 1,
        'street_unit' => 1,
        'street_name' => 1,
        'street_number' => 1,
        'street_address' => 1,
        'city' => 1,
        'postal_code' => 1,
        'state_province' => 1,
        'country' => 1,
        'email' => 1,
        'phone' => 1,
        'survey_activity_target_id' => 1,
        'survey_activity_id' => 1,
        'survey_status_id' => 1,
        'campaign_survey_id' => 1,
        'campaign_id' => 1,
        'survey_interviewer_id' => 1,
        'survey_activity_target_contact_id' => 1,
      );
    }

    return $properties;
  }

  static function tableNames(&$tables) {}
  static function searchAction(&$row, $id) {}

  static function info(&$tables) {
    //get survey clause in force,
    //only when we have survey id.
    if (!self::$_applySurveyClause) {
      return;
    }

    $weight = end($tables);
    $tables[self::CIVICRM_ACTIVITY_TARGET] = ++$weight;
    $tables[self::CIVICRM_ACTIVITY] = ++$weight;
    $tables[self::CIVICRM_ACTIVITY_ASSIGNMENT] = ++$weight;
    $tables['civicrm_survey'] = ++$weight;
    $tables['civicrm_campaign'] = ++$weight;
  }

  /**
   * add all the elements shared between,
   * normal voter search and voter listing (GOTV form)
   *
   * @access public
   *
   * @return void
   * @static
   */
  static function buildSearchForm(&$form) {

    $attributes = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Address');
    $className = CRM_Utils_System::getClassName($form);

    $form->add('text', 'sort_name', ts('Contact Name'),
      CRM_Core_DAO::getAttribute('CRM_Contact_DAO_Contact', 'sort_name')
    );
    $form->add('text', 'street_name', ts('Street Name'), $attributes['street_name']);
    $form->add('text', 'street_number', ts('Street Number'), $attributes['street_number']);
    $form->add('text', 'street_unit', ts('Street Unit'), $attributes['street_unit']);
    $form->add('text', 'street_address', ts('Street Address'), $attributes['street_address']);
    $form->add('text', 'city', ts('City'), $attributes['city']);
    $form->add('text', 'postal_code', ts('Zip / Postal Code'), $attributes['postal_code']);

    $contactTypes = CRM_Contact_BAO_ContactType::getSelectElements();
    $form->add('select', 'contact_type', ts('Contact Type(s)'), $contactTypes, FALSE,
      array('id' => 'contact_type', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );
    $groups = CRM_Core_PseudoConstant::group();
    $form->add('select', 'group', ts('Groups'), $groups, FALSE,
      array('id' => 'group', 'multiple' => 'multiple', 'title' => ts('- select -'))
    );

    $showInterviewer = FALSE;
    if (CRM_Core_Permission::check('administer CiviCampaign')) {
      $showInterviewer = TRUE;
    }
    $form->assign('showInterviewer', $showInterviewer);

    if ($showInterviewer ||
      $className == 'CRM_Campaign_Form_Gotv'
    ) {
      //autocomplete url
      $dataUrl = CRM_Utils_System::url('civicrm/ajax/rest',
        'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&reset=1',
        FALSE, NULL, FALSE
      );

      $form->assign('dataUrl', $dataUrl);
      $form->add('text', 'survey_interviewer_name', ts('Interviewer'));
      $form->add('hidden', 'survey_interviewer_id', '', array('id' => 'survey_interviewer_id'));

      $userId = NULL;
      if (isset($form->_interviewerId) && $form->_interviewerId) {
        $userId = $form->_interviewerId;
      }
      if (!$userId) {
        $session = CRM_core_Session::singleton();
        $userId = $session->get('userID');
      }
      if ($userId) {
        $defaults = array();
        $defaults['survey_interviewer_id'] = $userId;
        $defaults['survey_interviewer_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $userId,
          'sort_name',
          'id'
        );
        $form->setDefaults($defaults);
      }
    }

    //build ward and precinct custom fields.
    $query = '
    SELECT  fld.id, fld.label
      FROM  civicrm_custom_field fld
INNER JOIN  civicrm_custom_group grp on fld.custom_group_id = grp.id
     WHERE  grp.name = %1';
    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array('Voter_Info', 'String')));
    $customSearchFields = array();
    while ($dao->fetch()) {
      foreach (array(
        'ward', 'precinct') as $name) {
        if (stripos($name, $dao->label) !== FALSE) {
          $fieldId = $dao->id;
          $fieldName = 'custom_' . $dao->id;
          $customSearchFields[$name] = $fieldName;
          CRM_Core_BAO_CustomField::addQuickFormElement($form, $fieldName, $fieldId, FALSE, FALSE);
          break;
        }
      }
    }
    $form->assign('customSearchFields', $customSearchFields);

    $surveys = CRM_Campaign_BAO_Survey::getSurveys();

    if (empty($surveys) &&
      ($className == 'CRM_Campaign_Form_Search')
    ) {
      CRM_Core_Error::statusBounce(ts('Could not find survey for %1 respondents.',
          array(1 => $form->get('op'))
        ),
        CRM_Utils_System::url('civicrm/survey/add',
          'reset=1&action=add'
        )
      );
    }

    //CRM-7406 --
    //If survey had associated campaign and
    //campaign has some contact groups, don't
    //allow to search the contacts those are not
    //in given campaign groups ( ie not in constituents )
    $groupJs = NULL;
    if ($form->get('searchVoterFor') == 'reserve') {
      $groupJs = array('onChange' => "buildCampaignGroups( );return false;");
    }
    $form->add('select', 'campaign_survey_id', ts('Survey'), $surveys, TRUE, $groupJs);
  }

  /*
   * Retrieve all valid voter ids,
   * and build respective clause to restrict search.
   *
   * @param  array  $criteria an array
   * @return $voterClause as a string
   * @static
   */
  static public function voterClause($params) {
    $voterClause = array();
    $fromClause = $whereClause = NULL;
    if (!is_array($params) || empty($params)) {
      return $voterClause;
    }
    $surveyId       = CRM_Utils_Array::value('campaign_survey_id', $params);
    $interviewerId  = CRM_Utils_Array::value('survey_interviewer_id', $params);
    $searchVoterFor = CRM_Utils_Array::value('campaign_search_voter_for', $params);

    //get the survey activities.
    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $status = array('Scheduled');
    if ($searchVoterFor == 'reserve') {
      $status[] = 'Completed';
    }

    $completedStatusId = NULL;
    foreach ($status as $name) {
      if ($statusId = array_search($name, $activityStatus)) {
        $statusIds[] = $statusId;
        if ($name == 'Completed') {
          $completedStatusId = $statusId;
        }
      }
    }

    $voterActValues = CRM_Campaign_BAO_Survey::getSurveyVoterInfo($surveyId, NULL, $statusIds);

    if (!empty($voterActValues)) {
      $operator = 'IN';
      $voterIds = array_keys($voterActValues);
      if ($searchVoterFor == 'reserve') {
        $operator = 'NOT IN';
        //filter out recontact survey contacts.
        $recontactInterval = CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Survey',
          $surveyId, 'recontact_interval'
        );
        $recontactInterval = unserialize($recontactInterval);
        if ($surveyId &&
          is_array($recontactInterval) &&
          !empty($recontactInterval)
        ) {
          $voterIds = array();
          foreach ($voterActValues as $values) {
            $numOfDays = CRM_Utils_Array::value($values['result'], $recontactInterval);
            if ($numOfDays &&
              $values['status_id'] == $completedStatusId
            ) {
              $recontactIntSeconds = $numOfDays * 24 * 3600;
              $actDateTimeSeconds  = CRM_Utils_Date::unixTime($values['activity_date_time']);
              $totalSeconds        = $recontactIntSeconds + $actDateTimeSeconds;
              //don't consider completed survey activity
              //unless it fulfill recontact interval criteria.
              if ($totalSeconds <= time()) {
                continue;
              }
            }
            $voterIds[$values['voter_id']] = $values['voter_id'];
          }
        }
      }

      //lets dump these ids in tmp table and
      //use appropriate join depend on operator.
      if (!empty($voterIds)) {
        $voterIdCount = count($voterIds);

        //create temporary table to store voter ids.
        $tempTableName = CRM_Core_DAO::createTempTableName('civicrm_survey_respondent');
        CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS {$tempTableName}");

        $query = "
     CREATE TEMPORARY TABLE {$tempTableName} (
            id int unsigned NOT NULL AUTO_INCREMENT,
            survey_contact_id int unsigned NOT NULL,
  PRIMARY KEY ( id )
);
";
        CRM_Core_DAO::executeQuery($query);

        $batch = 100;
        $insertedCount = 0;
        do {
          $processIds = $voterIds;
          $insertIds = array_splice($processIds, $insertedCount, $batch);
          if (!empty($insertIds)) {
            $insertSQL = "INSERT IGNORE INTO {$tempTableName}( survey_contact_id )
                     VALUES (" . implode('),(', $insertIds) . ');';
            CRM_Core_DAO::executeQuery($insertSQL);
          }
          $insertedCount += $batch;
        } while ($insertedCount < $voterIdCount);

        if ($operator == 'IN') {
          $fromClause = " INNER JOIN {$tempTableName} ON ( {$tempTableName}.survey_contact_id = contact_a.id )";
        }
        else {
          $fromClause = " LEFT JOIN {$tempTableName} ON ( {$tempTableName}.survey_contact_id = contact_a.id )";
          $whereClause = "( {$tempTableName}.survey_contact_id IS NULL )";
        }
      }
    }
    $voterClause = array(
      'fromClause' => $fromClause,
      'whereClause' => $whereClause,
    );

    return $voterClause;
  }

  /**
   * Build the campaign clause for component serach.
   *
   **/
  public static function componentSearchClause(&$params, &$query) {
    $op        = CRM_Utils_Array::value('op', $params, '=');
    $campaign  = CRM_Utils_Array::value('campaign', $params);
    $tableName = CRM_Utils_Array::value('tableName', $params);
    $grouping  = CRM_Utils_Array::value('grouping', $params);
    if (CRM_Utils_System::isNull($campaign) || empty($tableName)) {
      return;
    }

    // fixme - what is the purpose of this code? $campaign should be
    // an integer, not an array
    if (is_array($campaign)) {
      foreach (array(
        'current_campaign', 'past_campaign') as $ignore) {
        $index = array_search($ignore, $campaign);
        if ($index !== FALSE)unset($campaign[$index]);
      }
    }

    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    $campaignIds = $campaignTitles = array();
    if (is_array($campaign)) {
      foreach ($campaign as $campId) {
        $campaignIds[$campId] = $campId;
        $campaignTitles[$campId] = $allCampaigns[$campId];
      }
      if (count($campaignIds) > 1) {
        $op = 'IN';
        $campaignIds = '(' . implode(',', $campaignIds) . ')';
      }
      else {
        $campaignIds = reset($campaignIds);
      }
    }
    else {
      $campaignIds = $campaign;
      if (array_key_exists($campaignIds, $allCampaigns)) {
        $campaignTitles[$campaignIds] = $allCampaigns[$campaignIds];
      }
    }
    $query->_qill[$grouping][] = ts('Campaigns %1',
      array(1 => $op)
    ) . ' ' . implode(' ' . ts('or') . ' ', $campaignTitles);
    $query->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("{$tableName}.campaign_id",
      $op,
      $campaignIds,
      'Integer'
    );
    $query->_tables[$tableName] = $query->_whereTables[$tableName] = 1;
  }
}

