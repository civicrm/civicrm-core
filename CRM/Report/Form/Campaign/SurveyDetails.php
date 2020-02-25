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
 * $Id$
 *
 */
class CRM_Report_Form_Campaign_SurveyDetails extends CRM_Report_Form {

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_locationBasedPhoneField = FALSE;

  protected $_summary = NULL;
  protected $_customGroupGroupBy = FALSE;
  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
    'Household',
    'Organization',
    'Activity',
  );
  public $_drilldownReport = array('contact/detail' => 'Link to Detail Report');

  private static $_surveyRespondentStatus;

  // Survey Question titles are overridden when in print or pdf mode to
  /**
   * say Q1, Q2 instead of the full title - to save space.
   * @var array
   */
  private $_columnTitleOverrides = array();

  /**
   */

  /**
   */
  public function __construct() {
    //filter options for survey activity status.
    $responseStatus = array('' => '- Any -');
    self::$_surveyRespondentStatus = array();
    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    if ($statusId = array_search('Scheduled', $activityStatus)) {
      $responseStatus[$statusId] = ts('Reserved');
      self::$_surveyRespondentStatus[$statusId] = 'Reserved';
    }
    if ($statusId = array_search('Completed', $activityStatus)) {
      $responseStatus[$statusId] = ts('Interviewed');
      self::$_surveyRespondentStatus[$statusId] = 'Interviewed';
    }

    $optionGroups = CRM_Campaign_BAO_Survey::getResultSets('name');
    $resultOptions = array();
    foreach ($optionGroups as $gid => $name) {
      if ($name) {
        $value = array();
        $value = CRM_Core_OptionGroup::values($name);
        if (!empty($value)) {
          $value = array_combine($value, $value);
        }
        $resultOptions = $resultOptions + $value;
      }
    }
    asort($resultOptions);

    //get all interviewers.
    $allSurveyInterviewers = CRM_Campaign_BAO_Survey::getInterviewers();

    $this->_columns = array(
      'civicrm_activity_contact' => array(
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => array('contact_id' => array('title' => ts('Interviewer Name'))),
        'filters' => array(
          'contact_id' => array(
            'name' => 'contact_id',
            'title' => ts('Interviewer Name'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array(
              '' => ts('- any interviewer -'),
            ) + $allSurveyInterviewers,
          ),
        ),
        'grouping' => 'survey-interviewer-fields',
      ),
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'id' => array(
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'sort_name' => array(
            'title' => ts('Respondent Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => ts('Respondent Name'),
            'operator' => 'like',
          ),
        ),
        'grouping' => 'contact-fields',
        'order_bys' => array(
          'sort_name' => array(
            'title' => ts('Respondent Name'),
            'required' => TRUE,
          ),
        ),
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'phone' => array(
            'name' => 'phone',
            'title' => ts('Phone'),
          ),
        ),
        'grouping' => 'location-fields',
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => array(
            'name' => 'email',
            'title' => ts('Email'),
          ),
        ),
        'grouping' => 'location-fields',
      ),
    ) + $this->getAddressColumns() +
    array(
      'civicrm_activity' => array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'alias' => 'survey_activity',
        'fields' => array(
          'survey_id' => array(
            'name' => 'source_record_id',
            'title' => ts('Survey'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Campaign_BAO_Survey::getSurveys(),
          ),
          'survey_response' => array(
            'name' => 'survey_response',
            'title' => ts('Survey Responses'),
          ),
          'details' => array(
            'name' => 'details',
            'title' => ts('Note'),
            'type' => 1,
          ),
          'result' => array(
            'name' => 'result',
            'required' => TRUE,
            'title' => ts('Survey Result'),
          ),
          'activity_date_time' => array(
            'name' => 'activity_date_time',
            'title' => ts('Date'),
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          ),
        ),
        'filters' => array(
          'survey_id' => array(
            'name' => 'source_record_id',
            'title' => ts('Survey'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Campaign_BAO_Survey::getSurveys(),
          ),
          'status_id' => array(
            'name' => 'status_id',
            'title' => ts('Respondent Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $responseStatus,
          ),
          'result' => array(
            'title' => ts('Survey Result'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $resultOptions,
          ),
          'activity_date_time' => array(
            'title' => ts('Date'),
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
        'grouping' => 'survey-activity-fields',
        'order_bys' => array(
          'activity_date_time' => array(
            'title' => ts('Date'),
          ),
        ),
      ),
    );
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = array();

    //add the survey response fields.
    $this->_addSurveyResponseColumns();

    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (!isset($table['fields'])) {
        continue;
      }
      foreach ($table['fields'] as $fieldName => $field) {
        if (!empty($field['required']) ||
          !empty($this->_params['fields'][$fieldName]) ||
          CRM_Utils_Array::value('is_required', $field)
        ) {

          $fieldsName = CRM_Utils_Array::value(1, explode('_', $tableName));
          if ($fieldsName) {
            $this->{"_$fieldsName" . 'Field'} = TRUE;
          }

          //need to pickup custom data/survey response fields.
          if ($fieldName == 'survey_response') {
            continue;
          }

          $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";

          // Set default title
          $title = CRM_Utils_Array::value('title', $field);
          // Check for an override.
          if (!empty($this->_columnTitleOverrides["{$tableName}_{$fieldName}"])) {
            $title = $this->_columnTitleOverrides["{$tableName}_{$fieldName}"];
          }
          $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $title;

          $this->_selectAliases[] = "{$tableName}_{$fieldName}";
        }
      }
    }

    $this->_select = "SELECT " . implode(",\n", $select) . " ";
  }

  public function from() {
    $this->_from = " FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom} ";
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    //get the activity table joins.
    $this->_from .= " INNER JOIN civicrm_activity_contact civicrm_activity_target ON
                      ( {$this->_aliases['civicrm_contact']}.id = civicrm_activity_target.contact_id AND civicrm_activity_target.record_type_id = {$targetID}) \n";
    $this->_from .= " INNER JOIN civicrm_activity {$this->_aliases['civicrm_activity']} ON
                      ( {$this->_aliases['civicrm_activity']}.id = civicrm_activity_target.activity_id )\n";
    $this->_from .= " INNER JOIN civicrm_activity_contact activity_contact_civireport ON
                      ( {$this->_aliases['civicrm_activity']}.id = activity_contact_civireport.activity_id  AND activity_contact_civireport.record_type_id = {$assigneeID} )\n";

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    if ($this->_locationBasedPhoneField) {
      foreach ($this->_surveyResponseFields as $key => $value) {
        if (substr($key, 0, 5) == 'phone' && !empty($value['location_type_id'])
        ) {
          $fName = str_replace('-', '_', $key);
          $this->_from .= "LEFT JOIN civicrm_phone " .
            $this->_aliases["civicrm_phone_{$fName}"] .
            " ON {$this->_aliases['civicrm_contact']}.id = " .
            $this->_aliases["civicrm_phone_{$fName}"] . ".contact_id AND " .
            $this->_aliases["civicrm_phone_{$fName}"] .
            ".location_type_id = {$value['location_type_id']} AND " .
            $this->_aliases["civicrm_phone_{$fName}"] .
            ".phone_type_id = {$value['phone_type_id']}\n";
        }
      }
    }
  }

  public function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    //apply survey activity types filter.
    $surveyActivityTypes = CRM_Campaign_BAO_Survey::getSurveyActivityType();
    if (!empty($surveyActivityTypes)) {
      $clauses[] = "( {$this->_aliases['civicrm_activity']}.activity_type_id IN ( " .
        implode(' , ', array_keys($surveyActivityTypes)) . ' ) )';
    }

    // always filter out deleted activities (so contacts that have been released
    // don't show up in the report).
    $clauses[] = "( {$this->_aliases['civicrm_activity']}.is_deleted = 0 )";

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  public function compileContent() {
    $coverSheet = $this->_surveyCoverSheet() .
        "<div style=\"page-break-after: always\"></div>";
    $templateFile = $this->getHookedTemplateFileName();
    return $coverSheet .
      CRM_Core_Form::$_template->fetch($templateFile) .
      CRM_Utils_Array::value('report_footer', $this->_formValues);
  }

  /**
   * @return bool|mixed|null|string
   */
  private function _surveyCoverSheet() {
    $coverSheet = NULL;
    $surveyIds = CRM_Utils_Array::value('survey_id_value', $this->_params);
    if (CRM_Utils_System::isNull($surveyIds)) {
      return $coverSheet;
    }

    $fieldIds = array();

    $surveyResponseFields = array();
    foreach ($this->_columns as $tableName => $values) {
      if (!is_array($values['fields'])) {
        continue;
      }
      foreach ($values['fields'] as $name => $field) {
        if (!empty($field['isSurveyResponseField'])) {
          $fldId = substr($name, 7);
          $fieldIds[$fldId] = $fldId;
          $title = CRM_Utils_Array::value('label', $field, $field['title']);
          $surveyResponseFields[$name] = array(
            'id' => $fldId,
            'title' => $title,
            'name' => "{$tableName}_{$name}",
          );
        }
      }
    }

    //now pickup all options.
    if (!empty($fieldIds)) {
      $query = '
    SELECT  field.id as id,
            val.label as label,
            val.value as value
      FROM  civicrm_custom_field field
INNER JOIN  civicrm_option_value val ON ( val.option_group_id = field.option_group_id )
     WHERE  field.id IN (' . implode(' , ', $fieldIds) . ' )
  Order By  val.weight';
      $field = CRM_Core_DAO::executeQuery($query);
      $options = array();
      while ($field->fetch()) {
        $name = "custom_{$field->id}";
        $surveyResponseFields[$name]['options'][$field->value] = $field->label;
      }
    }

    //get the result values.
    $query = '
    SELECT  survey.id as id,
            survey.title as title,
            val.label as label,
            val.value as value
      FROM  civicrm_survey survey
INNER JOIN  civicrm_option_value val ON ( val.option_group_id = survey.result_id )
     WHERE  survey.id IN ( ' . implode(' , ', array_values($surveyIds)) . ' )
  Order By  val.weight';
    $resultSet = CRM_Core_DAO::executeQuery($query);
    $surveyResultFields = array();
    while ($resultSet->fetch()) {
      $surveyResultFields[$resultSet->id]['title'] = $resultSet->title;
      $surveyResultFields[$resultSet->id]['options'][$resultSet->value] = $resultSet->label;
    }

    $this->assign('surveyResultFields', $surveyResultFields);
    $this->assign('surveyResponseFields', $surveyResponseFields);

    $templateFile = 'CRM/Report/Form/Campaign/SurveyCoverSheet.tpl';
    $coverSheet = CRM_Core_Form::$_template->fetch($templateFile);

    return $coverSheet;
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {

    $this->_formatSurveyResult($rows);
    $this->_formatSurveyResponseData($rows);

    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' .
          $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_activity_contact_contact_id', $row)) {
        $rows[$rowNum]['civicrm_activity_contact_contact_id'] = CRM_Utils_Array::value($row['civicrm_activity_contact_contact_id'],
          CRM_Campaign_BAO_Survey::getInterviewers()
        );
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_activity_survey_id', $row)) {
        $rows[$rowNum]['civicrm_activity_survey_id'] = CRM_Utils_Array::value($row['civicrm_activity_survey_id'],
          CRM_Campaign_BAO_Survey::getSurveys()
        );
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * @param $rows
   */
  private function _formatSurveyResult(&$rows) {
    $surveyIds = CRM_Utils_Array::value('survey_id_value', $this->_params);
    if (CRM_Utils_System::isNull($surveyIds) ||
      empty($this->_params['fields']['result']) ||
      !in_array($this->_outputMode, array('print', 'pdf'))
    ) {
      return;
    }

    //swap the survey result label w/ value.
    $query = '
    SELECT  survey.id as id,
            val.label as label,
            val.value as value
      FROM  civicrm_option_value val
INNER JOIN  civicrm_option_group grp ON ( grp.id = val.option_group_id )
INNER JOIN  civicrm_survey survey ON ( survey.result_id = grp.id )
     WHERE  survey.id IN (' . implode(' , ', array_values($surveyIds)) . ' )
  Order By  val.weight';

    $result = CRM_Core_DAO::executeQuery($query);
    $resultSet = array();
    while ($result->fetch()) {
      $resultSet[$result->id][$result->value] = $result->label;
    }

    $statusId = CRM_Utils_Array::value('status_id_value', $this->_params);
    $respondentStatus = CRM_Utils_Array::value($statusId, self::$_surveyRespondentStatus);

    $surveyId = CRM_Utils_Array::value(0, $surveyIds);
    foreach ($rows as & $row) {
      if (!empty($row['civicrm_activity_survey_id'])) {
        $surveyId = $row['civicrm_activity_survey_id'];
      }
      $result = CRM_Utils_Array::value($surveyId, $resultSet, array());
      $resultLabel = CRM_Utils_Array::value('civicrm_activity_result', $row);
      if ($respondentStatus == 'Reserved') {
        $row['civicrm_activity_result'] = implode(' | ', array_keys($result));
      }
      elseif ($resultLabel) {
        $resultValue = array_search($resultLabel, $result);
        if ($resultValue) {
          $row['civicrm_activity_result'] = $resultValue;
        }
      }
    }
  }

  /**
   * @param $rows
   */
  private function _formatSurveyResponseData(&$rows) {
    $surveyIds = CRM_Utils_Array::value('survey_id_value', $this->_params);
    if (CRM_Utils_System::isNull($surveyIds) ||
      empty($this->_params['fields']['survey_response'])
    ) {
      return;
    }

    $surveyResponseFields = array();
    $surveyResponseFieldIds = array();
    foreach ($this->_columns as $tableName => $values) {
      if (!is_array($values['fields'])) {
        continue;
      }
      foreach ($values['fields'] as $name => $field) {
        if (!empty($field['isSurveyResponseField'])) {
          $fldId = substr($name, 7);
          $surveyResponseFields[$name] = "{$tableName}_{$name}";
          $surveyResponseFieldIds[$fldId] = $fldId;
        }
      }
    }

    if (empty($surveyResponseFieldIds)) {
      return;
    }

    $hasResponseData = FALSE;
    foreach ($surveyResponseFields as $fldName) {
      foreach ($rows as $row) {
        if (!empty($row[$fldName])) {
          $hasResponseData = TRUE;
          break;
        }
      }
    }

    //do check respondent status.
    $statusId = CRM_Utils_Array::value('status_id_value', $this->_params);
    $respondentStatus = CRM_Utils_Array::value($statusId, self::$_surveyRespondentStatus);

    if (!$hasResponseData &&
      ($respondentStatus != 'Reserved')
    ) {
      return;
    }

    //start response data formatting.
    $query = '
    SELECT  cf.id,
            cf.data_type,
            cf.html_type,
            cg.table_name,
            cf.column_name,
            ov.value, ov.label,
            cf.option_group_id
      FROM  civicrm_custom_field cf
INNER JOIN  civicrm_custom_group cg ON ( cg.id = cf.custom_group_id )
 LEFT JOIN  civicrm_option_value ov ON ( cf.option_group_id = ov.option_group_id )
     WHERE  cf.id IN ( ' . implode(' , ', $surveyResponseFieldIds) . ' )
  Order By  ov.weight';

    $responseFields = array();
    $fieldValueMap = array();
    $properties = array(
      'id',
      'data_type',
      'html_type',
      'column_name',
      'option_group_id',
    );

    $responseField = CRM_Core_DAO::executeQuery($query);
    while ($responseField->fetch()) {
      $reponseFldName = $responseField->table_name . '_custom_' .
        $responseField->id;
      foreach ($properties as $prop) {
        $responseFields[$reponseFldName][$prop] = $responseField->$prop;
      }
      if ($responseField->option_group_id) {
        //show value for print and pdf.
        $value = $responseField->label;
        if (in_array($this->_outputMode, array(
          'print',
          'pdf',
        ))) {
          $value = $responseField->value;
        }
        $fieldValueMap[$responseField->option_group_id][$responseField->value] = $value;
      }
    }

    //actual data formatting.
    $hasData = FALSE;
    foreach ($rows as & $row) {
      if (!is_array($row)) {
        continue;
      }

      foreach ($row as $name => & $value) {
        if (!array_key_exists($name, $responseFields)) {
          continue;
        }
        $hasData = TRUE;
        if ($respondentStatus == 'Reserved' &&
          in_array($this->_outputMode, array('print', 'pdf'))
        ) {
          $optGrpId = CRM_Utils_Array::value('option_group_id', $responseFields[$name]);
          $options = CRM_Utils_Array::value($optGrpId, $fieldValueMap, array());
          $value = implode(' | ', array_keys($options));
        }
        else {
          $value = CRM_Core_BAO_CustomField::displayValue($value, $responseFields[$name]['id']);
        }
      }

      if (!$hasData) {
        break;
      }
    }
  }

  private function _addSurveyResponseColumns() {
    $surveyIds = CRM_Utils_Array::value('survey_id_value', $this->_params);
    if (CRM_Utils_System::isNull($surveyIds) ||
      empty($this->_params['fields']['survey_response'])
    ) {
      return;
    }

    $responseFields = array();
    foreach ($surveyIds as $surveyId) {
      $responseFields += CRM_Campaign_BAO_Survey::getSurveyResponseFields($surveyId);
      $this->_surveyResponseFields = $responseFields;
    }
    foreach ($responseFields as $key => $value) {
      if (substr($key, 0, 5) == 'phone' && !empty($value['location_type_id'])) {
        $fName = str_replace('-', '_', $key);
        $this->_columns["civicrm_{$fName}"] = array(
          'dao' => 'CRM_Core_DAO_Phone',
          'alias' => "phone_civireport_{$fName}",
          'fields' => array(
            $fName => array_merge($value, array(
              'is_required' => '1',
              'alias' => "phone_civireport_{$fName}",
              'dbAlias' => "phone_civireport_{$fName}.phone",
              'no_display' => TRUE,
            )),
          ),
        );
        $this->_aliases["civicrm_phone_{$fName}"] = $this->_columns["civicrm_{$fName}"]['alias'];
        $this->_locationBasedPhoneField = TRUE;
      }
    }
    $responseFieldIds = array();
    foreach (array_keys($responseFields) as $key) {
      $cfId = CRM_Core_BAO_CustomField::getKeyID($key);
      if ($cfId) {
        $responseFieldIds[$cfId] = $cfId;
      }
    }
    if (empty($responseFieldIds)) {
      return;
    }

    $query = '
     SELECT  cg.extends,
             cf.data_type,
             cf.html_type,
             cg.table_name,
             cf.column_name,
             cf.time_format,
             cf.id as cfId,
             cf.option_group_id
       FROM  civicrm_custom_group cg
INNER  JOIN  civicrm_custom_field cf ON ( cg.id = cf.custom_group_id )
      WHERE  cf.id IN ( ' . implode(' , ', $responseFieldIds) .
      ' )  ORDER BY cf.weight';
    $response = CRM_Core_DAO::executeQuery($query);
    $fieldCnt = 1;
    while ($response->fetch()) {
      $resTable = $response->table_name;
      $fieldName = "custom_{$response->cfId}";

      //need to check does these custom data already included.

      if (!array_key_exists($resTable, $this->_columns)) {
        $this->_columns[$resTable]['dao'] = 'CRM_Contact_DAO_Contact';
        $this->_columns[$resTable]['extends'] = $response->extends;
      }
      if (empty($this->_columns[$resTable]['alias'])) {
        $this->_columns[$resTable]['alias'] = "{$resTable}_survey_response";
      }
      if (!is_array(CRM_Utils_Array::value('fields', $this->_columns[$resTable]))) {
        $this->_columns[$resTable]['fields'] = array();
      }

      if (in_array($this->_outputMode, array(
        'print',
        'pdf',
      ))) {
        $this->_columnTitleOverrides["{$resTable}_{$fieldName}"] = 'Q' . $fieldCnt;
        $fieldCnt++;
      }

      if (array_key_exists($fieldName, $this->_columns[$resTable]['fields'])) {
        $this->_columns[$resTable]['fields'][$fieldName]['required'] = TRUE;
        $this->_columns[$resTable]['fields'][$fieldName]['isSurveyResponseField'] = TRUE;
        continue;
      }

      $title = $responseFields[$fieldName]['title'];
      $fldType = 'CRM_Utils_Type::T_STRING';
      if ($response->time_format) {
        $fldType = CRM_Utils_Type::T_TIMESTAMP;
      }
      $field = array(
        'name' => $response->column_name,
        'type' => $fldType,
        'title' => $title,
        'label' => $responseFields[$fieldName]['title'],
        'dataType' => $response->data_type,
        'htmlType' => $response->html_type,
        'required' => TRUE,
        'alias' => ($response->data_type == 'ContactReference') ? $this->_columns[$resTable]['alias'] .
        '_contact' : $this->_columns[$resTable]['alias'],
        'dbAlias' => $this->_columns[$resTable]['alias'] . '.' .
        $response->column_name,
        'no_display' => TRUE,
        'isSurveyResponseField' => TRUE,
      );

      $this->_columns[$resTable]['fields'][$fieldName] = $field;
      $this->_aliases[$resTable] = $this->_columns[$resTable]['alias'];
    }
  }

}
