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
class CRM_Report_Form_Campaign_SurveyDetails extends CRM_Report_Form {

  /**
   * @var array
   */
  protected $surveyResponseFields = [];

  protected $_locationBasedPhoneField = FALSE;

  protected $_summary = NULL;
  protected $_customGroupGroupBy = FALSE;
  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Household',
    'Organization',
    'Activity',
  ];
  public $_drilldownReport = ['contact/detail' => 'Link to Detail Report'];

  private static $_surveyRespondentStatus;

  // Survey Question titles are overridden when in print or pdf mode to
  /**
   * say Q1, Q2 instead of the full title - to save space.
   * @var array
   */
  private $_columnTitleOverrides = [];

  /**
   */

  /**
   */
  public function __construct() {
    //filter options for survey activity status.
    $responseStatus = ['' => ts('- Any -')];
    self::$_surveyRespondentStatus = [];
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
    $resultOptions = [];
    foreach ($optionGroups as $gid => $name) {
      if ($name) {
        $value = [];
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

    $this->_columns = [
      'civicrm_activity_contact' => [
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => ['contact_id' => ['title' => ts('Interviewer Name')]],
        'filters' => [
          'contact_id' => [
            'name' => 'contact_id',
            'title' => ts('Interviewer Name'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => [
              '' => ts('- any interviewer -'),
            ] + $allSurveyInterviewers,
          ],
        ],
        'grouping' => 'survey-interviewer-fields',
      ],
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'id' => [
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'sort_name' => [
            'title' => ts('Respondent Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => ts('Respondent Name'),
            'operator' => 'like',
          ],
        ],
        'grouping' => 'contact-fields',
        'order_bys' => [
          'sort_name' => [
            'title' => ts('Respondent Name'),
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => [
            'name' => 'phone',
            'title' => ts('Phone'),
          ],
        ],
        'grouping' => 'location-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'name' => 'email',
            'title' => ts('Email'),
          ],
        ],
        'grouping' => 'location-fields',
      ],
    ] + $this->getAddressColumns() +
    [
      'civicrm_activity' => [
        'dao' => 'CRM_Activity_DAO_Activity',
        'alias' => 'survey_activity',
        'fields' => [
          'survey_id' => [
            'name' => 'source_record_id',
            'title' => ts('Survey'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Campaign_BAO_Survey::getSurveys(),
          ],
          'survey_response' => [
            'name' => 'survey_response',
            'title' => ts('Survey Responses'),
          ],
          'details' => [
            'name' => 'details',
            'title' => ts('Note'),
            'type' => 1,
          ],
          'result' => [
            'name' => 'result',
            'required' => TRUE,
            'title' => ts('Survey Result'),
          ],
          'activity_date_time' => [
            'name' => 'activity_date_time',
            'title' => ts('Date'),
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          ],
        ],
        'filters' => [
          'survey_id' => [
            'name' => 'source_record_id',
            'title' => ts('Survey'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Campaign_BAO_Survey::getSurveys(),
          ],
          'status_id' => [
            'name' => 'status_id',
            'title' => ts('Respondent Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $responseStatus,
          ],
          'result' => [
            'title' => ts('Survey Result'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $resultOptions,
          ],
          'activity_date_time' => [
            'title' => ts('Date'),
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'grouping' => 'survey-activity-fields',
        'order_bys' => [
          'activity_date_time' => [
            'title' => ts('Date'),
          ],
        ],
      ],
    ];
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = [];

    //add the survey response fields.
    $this->_addSurveyResponseColumns();

    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (!isset($table['fields'])) {
        continue;
      }
      foreach ($table['fields'] as $fieldName => $field) {
        if (!empty($field['required']) ||
          !empty($this->_params['fields'][$fieldName]) ||
          !empty($field['is_required'])
        ) {

          $fieldsName = CRM_Utils_Array::value(1, explode('_', $tableName));
          if ($fieldsName && property_exists($this, "_$fieldsName" . 'Field')) {
            $this->{"_$fieldsName" . 'Field'} = TRUE;
          }

          //need to pickup custom data/survey response fields.
          if ($fieldName == 'survey_response') {
            continue;
          }

          $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";

          // Set default title
          $title = $field['title'] ?? NULL;
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
      foreach ($this->surveyResponseFields as $key => $value) {
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
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                $this->_params["{$fieldName}_value"] ?? NULL,
                $this->_params["{$fieldName}_min"] ?? NULL,
                $this->_params["{$fieldName}_max"] ?? NULL
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
      ($this->_formValues['report_footer'] ?? '');
  }

  /**
   * @return bool|mixed|null|string
   */
  private function _surveyCoverSheet() {
    $coverSheet = NULL;
    $surveyIds = $this->_params['survey_id_value'] ?? NULL;
    if (CRM_Utils_System::isNull($surveyIds)) {
      return $coverSheet;
    }

    $fieldIds = [];

    $surveyResponseFields = [];
    foreach ($this->_columns as $tableName => $values) {
      if (!is_array($values['fields'])) {
        continue;
      }
      foreach ($values['fields'] as $name => $field) {
        if (!empty($field['isSurveyResponseField'])) {
          $fldId = substr($name, 7);
          $fieldIds[$fldId] = $fldId;
          $title = $field['label'] ?? $field['title'];
          $surveyResponseFields[$name] = [
            'id' => $fldId,
            'title' => $title,
            'name' => "{$tableName}_{$name}",
          ];
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
      $options = [];
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
    $surveyResultFields = [];
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
   * @param array $rows
   */
  private function _formatSurveyResult(&$rows) {
    $surveyIds = $this->_params['survey_id_value'] ?? NULL;
    if (CRM_Utils_System::isNull($surveyIds) ||
      empty($this->_params['fields']['result']) ||
      !in_array($this->_outputMode, ['print', 'pdf'])
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
    $resultSet = [];
    while ($result->fetch()) {
      $resultSet[$result->id][$result->value] = $result->label;
    }

    $statusId = $this->_params['status_id_value'] ?? NULL;
    $respondentStatus = self::$_surveyRespondentStatus[$statusId] ?? NULL;

    $surveyId = $surveyIds[0] ?? NULL;
    foreach ($rows as & $row) {
      if (!empty($row['civicrm_activity_survey_id'])) {
        $surveyId = $row['civicrm_activity_survey_id'];
      }
      $result = $resultSet[$surveyId] ?? [];
      $resultLabel = $row['civicrm_activity_result'] ?? NULL;
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
   * @param array $rows
   */
  private function _formatSurveyResponseData(&$rows) {
    $surveyIds = $this->_params['survey_id_value'] ?? NULL;
    if (CRM_Utils_System::isNull($surveyIds) ||
      empty($this->_params['fields']['survey_response'])
    ) {
      return;
    }

    $surveyResponseFields = [];
    $surveyResponseFieldIds = [];
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
    $statusId = $this->_params['status_id_value'] ?? NULL;
    $respondentStatus = self::$_surveyRespondentStatus[$statusId] ?? NULL;

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

    $responseFields = [];
    $fieldValueMap = [];
    $properties = [
      'id',
      'data_type',
      'html_type',
      'column_name',
      'option_group_id',
    ];

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
        if (in_array($this->_outputMode, ['print', 'pdf'])) {
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
          in_array($this->_outputMode, ['print', 'pdf'])
        ) {
          $optGrpId = $responseFields[$name]['option_group_id'] ?? NULL;
          $options = $fieldValueMap[$optGrpId] ?? [];
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
    $surveyIds = $this->_params['survey_id_value'] ?? NULL;
    if (CRM_Utils_System::isNull($surveyIds) ||
      empty($this->_params['fields']['survey_response'])
    ) {
      return;
    }

    $responseFields = [];
    foreach ($surveyIds as $surveyId) {
      $responseFields += CRM_Campaign_BAO_Survey::getSurveyResponseFields($surveyId);
      $this->surveyResponseFields = $responseFields;
    }
    foreach ($responseFields as $key => $value) {
      if (substr($key, 0, 5) == 'phone' && !empty($value['location_type_id'])) {
        $fName = str_replace('-', '_', $key);
        $this->_columns["civicrm_{$fName}"] = [
          'dao' => 'CRM_Core_DAO_Phone',
          'alias' => "phone_civireport_{$fName}",
          'fields' => [
            $fName => array_merge($value, [
              'is_required' => '1',
              'alias' => "phone_civireport_{$fName}",
              'dbAlias' => "phone_civireport_{$fName}.phone",
              'no_display' => TRUE,
            ]),
          ],
        ];
        $this->_aliases["civicrm_phone_{$fName}"] = $this->_columns["civicrm_{$fName}"]['alias'];
        $this->_locationBasedPhoneField = TRUE;
      }
    }
    $responseFieldIds = [];
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
      if (empty($this->_columns[$resTable]['fields'])) {
        $this->_columns[$resTable]['fields'] = [];
      }

      if (in_array($this->_outputMode, ['print', 'pdf'])) {
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
      $field = [
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
      ];

      $this->_columns[$resTable]['fields'][$fieldName] = $field;
      $this->_aliases[$resTable] = $this->_columns[$resTable]['alias'];
    }
  }

}
