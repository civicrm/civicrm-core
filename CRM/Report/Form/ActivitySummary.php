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
class CRM_Report_Form_ActivitySummary extends CRM_Report_Form {

  protected $_emailField = FALSE;
  protected $_phoneField = FALSE;
  protected $_tempTableName;
  protected $_tempDurationSumTableName;

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = TRUE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'id' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'sort_name' => [
            'title' => ts('Contact Name'),
            'no_repeat' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
          ],
        ],
        'group_bys' => [
          'sort_name' => [
            'name' => 'id',
            'title' => ts('Contact'),
          ],
        ],
        'order_bys' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'title' => ts('Email'),
          ],
        ],
        'order_bys' => [
          'email' => [
            'title' => ts('Email'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'phone' => [
            'title' => ts('Phone'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_activity' => [
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => [
          'activity_type_id' => [
            'title' => ts('Activity Type'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'status_id' => [
            'title' => ts('Activity Status'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'duration' => [
            'title' => ts('Duration'),
            'default' => TRUE,
          ],
          'priority_id' => [
            'title' => ts('Priority'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'id' => [
            'title' => ts('Total Activities'),
            'required' => TRUE,
            'statistics' => [
              'count' => ts('Count'),
            ],
          ],
        ],
        'filters' => [
          'activity_date_time' => [
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'activity_type_id' => [
            'title' => ts('Activity Type'),
            'default' => 0,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE),
          ],
          'status_id' => [
            'title' => ts('Activity Status'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityStatus(),
          ],
          'priority_id' => [
            'title' => ts('Activity Priority'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id'),
          ],
        ],
        'group_bys' => [
          'activity_date_time' => [
            'title' => ts('Activity Date'),
            'frequency' => TRUE,
          ],
          'activity_type_id' => [
            'title' => ts('Activity Type'),
            'default' => TRUE,
          ],
          'status_id' => [
            'title' => ts('Activity Status'),
            'default' => TRUE,
          ],
        ],
        'order_bys' => [
          'activity_date_time' => [
            'title' => ts('Activity Date'),
          ],
          'activity_type_id' => [
            'title' => ts('Activity Type'),
          ],
        ],
        'grouping' => 'activity-fields',
        'alias' => 'activity',
      ],
    ];
    $this->_groupFilter = TRUE;

    parent::__construct();
  }

  public function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (!empty($this->_params['group_bys'][$fieldName])) {
            //include column in report when selected in group by but not in column section.
            if (empty($this->_params['fields'][$fieldName])) {
              $this->_params['fields'][$fieldName] = TRUE;
            }
            if (isset($this->_params['group_bys_freq']) && !empty($this->_params['group_bys_freq'][$fieldName])) {
              switch ($this->_params['group_bys_freq'][$fieldName]) {
                case 'YEARWEEK':
                  $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";

                  $select[] = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                  $select[] = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                  $field['title'] = 'Week';
                  break;

                case 'YEAR':
                  $select[] = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                  $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                  $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                  $field['title'] = 'Year';
                  break;

                case 'MONTH':
                  $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                  $select[] = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                  $select[] = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                  $field['title'] = 'Month';
                  break;

                case 'QUARTER':
                  $select[] = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                  $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                  $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                  $field['title'] = 'Quarter';
                  break;
              }
              $this->_interval = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title'] = $field['title'] . ' Beginning';
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['group_by'] = $this->_params['group_bys_freq'][$fieldName];

              // just to make sure these values are transferred to rows.
              // since we need that for calculation purpose,
              // e.g making subtotals look nicer or graphs
              $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = ['no_display' => TRUE];
              $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = ['no_display' => TRUE];
            }
          }
        }
      }
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) || !empty($this->_params['fields'][$fieldName])) {
            if ($tableName == 'civicrm_email' || in_array('email', CRM_Utils_Array::collect('column', $this->_params['order_bys']))) {
              $this->_emailField = TRUE;
            }
            if ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            if (!empty($field['statistics'])) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'count':
                    $select[] = "COUNT(DISTINCT({$field['dbAlias']})) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            }
            elseif ($fieldName == 'activity_type_id') {
              if (empty($this->_params['group_bys']['activity_type_id'])) {
                $select[] = "GROUP_CONCAT(DISTINCT {$field['dbAlias']}  ORDER BY {$field['dbAlias']} ) as {$tableName}_{$fieldName}";
              }
              else {
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              }
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'] ?? NULL;
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'] ?? NULL;
            }
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * Generate from clause.
   */
  public function from() {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);

    $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}

             LEFT JOIN civicrm_activity_contact target_activity
                    ON {$this->_aliases['civicrm_activity']}.id = target_activity.activity_id AND
                       target_activity.record_type_id = {$targetID}
             LEFT JOIN civicrm_activity_contact assignment_activity
                    ON {$this->_aliases['civicrm_activity']}.id = assignment_activity.activity_id AND
                       assignment_activity.record_type_id = {$assigneeID}
             LEFT JOIN civicrm_activity_contact source_activity
                    ON {$this->_aliases['civicrm_activity']}.id = source_activity.activity_id AND
                       source_activity.record_type_id = {$sourceID}
             LEFT JOIN civicrm_contact contact_civireport
                    ON target_activity.contact_id = contact_civireport.id
             LEFT JOIN civicrm_contact civicrm_contact_assignee
                    ON assignment_activity.contact_id = civicrm_contact_assignee.id
             LEFT JOIN civicrm_contact civicrm_contact_source
                    ON source_activity.contact_id = civicrm_contact_source.id
             {$this->_aclFrom}
             LEFT JOIN civicrm_option_value
                    ON ( {$this->_aliases['civicrm_activity']}.activity_type_id = civicrm_option_value.value )
             LEFT JOIN civicrm_option_group
                    ON civicrm_option_group.id = civicrm_option_value.option_group_id
             LEFT JOIN civicrm_case_activity
                    ON civicrm_case_activity.activity_id = {$this->_aliases['civicrm_activity']}.id
             LEFT JOIN civicrm_case
                    ON civicrm_case_activity.case_id = civicrm_case.id
             LEFT JOIN civicrm_case_contact
                    ON civicrm_case_contact.case_id = civicrm_case.id ";

    $this->joinPhoneFromContact();

    $this->joinEmailFromContact();
  }

  /**
   * Generate from clause for when calculating activity durations.
   */
  public function activityDurationFrom() {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    $this->_from = "
      FROM civicrm_activity {$this->_aliases['civicrm_activity']}
              LEFT JOIN civicrm_activity_contact target_activity
                     ON {$this->_aliases['civicrm_activity']}.id = target_activity.activity_id AND
                        target_activity.record_type_id = {$targetID}
              LEFT JOIN civicrm_contact contact_civireport
                     ON target_activity.contact_id = contact_civireport.id
              {$this->_aclFrom}";

    // Email table is needed if sorting by Email.
    $this->joinEmailFromContact();
  }

  /**
   * Generate where clause.
   *
   * @param bool $durationMode
   */
  public function where($durationMode = FALSE) {
    $optionGroupClause = '';
    if (!$durationMode) {
      $optionGroupClause = 'civicrm_option_group.name = "activity_type" AND ';
    }
    $this->_where = " WHERE {$optionGroupClause}
                            {$this->_aliases['civicrm_activity']}.is_test = 0 AND
                            {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                            {$this->_aliases['civicrm_activity']}.is_current_revision = 1";

    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
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

    if (empty($clauses)) {
      $this->_where .= " ";
    }
    else {
      $this->_where .= " AND " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere && !$durationMode) {
      $this->_where .= " AND ({$this->_aclWhere} OR civicrm_contact_source.is_deleted=0 OR civicrm_contact_assignee.is_deleted=0)";
    }
  }

  /**
   * Build the report query.
   *
   * @param bool $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    $this->buildGroupTempTable();
    $this->select();
    $this->from();
    $this->customDataFrom();
    $this->buildPermissionClause();
    $this->where();
    $this->groupBy();
    $this->orderBy();

    // Order by & Section columns not selected for display need to be included in SELECT.
    $unselectedColumns = array_merge($this->unselectedOrderByColumns(), $this->unselectedSectionColumns());
    foreach ($unselectedColumns as $alias => $field) {
      $clause = $this->getSelectClauseWithGroupConcatIfNotGroupedBy($field['table_name'], $field['name'], $field);
      if (!$clause) {
        $clause = "{$field['dbAlias']} as {$alias}";
      }
      $this->_select .= ", $clause ";
    }

    if ($applyLimit && empty($this->_params['charts'])) {
      $this->limit();
    }
    CRM_Utils_Hook::alterReportVar('sql', $this, $this);

    // build temporary table column names base on column headers of result
    $dbColumns = [];
    foreach ($this->_columnHeaders as $fieldName => $dontCare) {
      $dbColumns[] = $fieldName . ' VARCHAR(128)';
    }

    // Order by & Section columns not selected for display need to be included in temp table.
    foreach ($unselectedColumns as $alias => $section) {
      $dbColumns[] = $alias . ' VARCHAR(128)';
    }

    // create temp table to store main result
    $this->_tempTableName = $this->createTemporaryTable('tempTable', "
      id int unsigned NOT NULL AUTO_INCREMENT, " . implode(', ', $dbColumns) . ' , PRIMARY KEY (id)',
    TRUE);

    // build main report query
    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";
    $this->addToDeveloperTab($sql);

    // store the result in temporary table
    $insertCols = '';
    $insertQuery = "INSERT INTO {$this->_tempTableName} ( " . implode(',', array_merge(array_keys($this->_columnHeaders), array_keys($unselectedColumns))) . " )
{$sql}";
    CRM_Core_DAO::disableFullGroupByMode();
    CRM_Core_DAO::executeQuery($insertQuery);
    CRM_Core_DAO::reenableFullGroupByMode();

    // now build the query for duration sum
    $this->activityDurationFrom();
    $this->where(TRUE);
    $this->groupBy(FALSE);

    // build the query to calulate duration sum
    $sql = "SELECT SUM(activity_civireport.duration) as civicrm_activity_duration_total {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";

    // create temp table to store duration
    $this->_tempDurationSumTableName = $this->createTemporaryTable('tempDurationSumTable', "
      id int unsigned NOT NULL AUTO_INCREMENT, civicrm_activity_duration_total VARCHAR(128), PRIMARY KEY (id)",
    TRUE);

    // store the result in temporary table
    $insertQuery = "INSERT INTO {$this->_tempDurationSumTableName} (civicrm_activity_duration_total)
    {$sql}";
    CRM_Core_DAO::disableFullGroupByMode();
    CRM_Core_DAO::executeQuery($insertQuery);
    CRM_Core_DAO::reenableFullGroupByMode();

    $sql = "SELECT {$this->_tempTableName}.*,  {$this->_tempDurationSumTableName}.civicrm_activity_duration_total
    FROM {$this->_tempTableName} INNER JOIN {$this->_tempDurationSumTableName}
      ON ({$this->_tempTableName}.id = {$this->_tempDurationSumTableName}.id)";

    // finally add duration total to column headers
    $this->_columnHeaders['civicrm_activity_duration_total'] = ['no_display' => 1];

    // reset the sql building to default, which is used / called during other actions like "add to group"
    $this->from();
    $this->where();

    return $sql;
  }

  /**
   * Group the fields.
   *
   * @param bool $includeSelectCol
   */
  public function groupBy($includeSelectCol = TRUE) {
    $this->_groupBy = [];
    if (!empty($this->_params['group_bys']) &&
      is_array($this->_params['group_bys'])) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (!empty($this->_params['group_bys'][$fieldName])) {
              if (!empty($field['chart'])) {
                $this->assign('chartSupported', TRUE);
              }
              if (!empty($table['group_bys'][$fieldName]['frequency']) &&
                !empty($this->_params['group_bys_freq'][$fieldName])
              ) {

                $append = "YEAR({$field['dbAlias']}),";
                if (in_array(strtolower($this->_params['group_bys_freq'][$fieldName]),
                  ['year']
                )) {
                  $append = '';
                }
                $this->_groupBy[] = "$append {$this->_params['group_bys_freq'][$fieldName]}({$field['dbAlias']})";
                $append = TRUE;
              }
              else {
                $this->_groupBy[] = $field['dbAlias'];
              }
            }
          }
        }
      }
      $groupBy = $this->_groupBy;
      $this->_groupBy = "GROUP BY " . implode(', ', $this->_groupBy);
    }
    else {
      $groupBy = "{$this->_aliases['civicrm_activity']}.id";
      $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_activity']}.id ";
    }
    if ($includeSelectCol) {
      $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
    }
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    $contactFields = ['sort_name', 'email', 'phone'];
    if (!empty($fields['group_bys'])) {
      if (!empty($fields['group_bys']['activity_date_time'])) {
        if (!empty($fields['group_bys']['sort_name'])) {
          $errors['fields'] = ts("Please do not select GroupBy 'Activity Date' with GroupBy 'Contact'");
        }
        else {
          foreach ($fields['fields'] as $fieldName => $val) {
            if (in_array($fieldName, $contactFields)) {
              $errors['fields'] = ts("Please do not select any Contact Fields with GroupBy 'Activity Date'");
              break;
            }
          }
        }
      }
    }

    // don't allow add to group action unless contact fields are selected.
    if (isset($fields['_qf_ActivitySummary_submit_group'])) {
      $contactFieldSelected = FALSE;
      foreach ($fields['fields'] as $fieldName => $val) {
        if (in_array($fieldName, $contactFields)) {
          $contactFieldSelected = TRUE;
          break;
        }
      }

      if (!$contactFieldSelected) {
        $errors['fields'] = ts('You cannot use "Add Contacts to Group" action unless contacts fields are selected.');
      }
    }
    return $errors;
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $totalType = $totalActivity = $totalDuration = 0;

    $query = "SELECT {$this->_tempTableName}.civicrm_activity_activity_type_id,
        {$this->_tempTableName}.civicrm_activity_id_count,
        {$this->_tempDurationSumTableName}.civicrm_activity_duration_total
    FROM {$this->_tempTableName} INNER JOIN {$this->_tempDurationSumTableName}
      ON ({$this->_tempTableName}.id = {$this->_tempDurationSumTableName}.id)";

    $actDAO = CRM_Core_DAO::executeQuery($query);

    $activityTypesCount = [];
    while ($actDAO->fetch()) {
      if (!in_array($actDAO->civicrm_activity_activity_type_id, $activityTypesCount)) {
        $activityTypesCount[] = $actDAO->civicrm_activity_activity_type_id;
      }

      $totalActivity += $actDAO->civicrm_activity_id_count;
      $totalDuration += $actDAO->civicrm_activity_duration_total;
    }

    $totalType = count($activityTypesCount);

    $statistics['counts']['type'] = [
      'title' => ts('Total Types'),
      'value' => $totalType,
    ];
    $statistics['counts']['activities'] = [
      'title' => ts('Total Number of Activities'),
      'value' => $totalActivity,
    ];
    $statistics['counts']['duration'] = [
      'title' => ts('Total Duration (in Minutes)'),
      'value' => $totalDuration,
    ];
    return $statistics;
  }

  public function modifyColumnHeaders() {
    //CRM-16719 modify name of column
    if (!empty($this->_columnHeaders['civicrm_activity_status_id'])) {
      $this->_columnHeaders['civicrm_activity_status_id']['title'] = ts('Status');
    }
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
    $entryFound = FALSE;
    $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $priority = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
    $onHover = ts('View Contact Summary for this Contact');
    foreach ($rows as $rowNum => $row) {
      // make count columns point to activity detail report
      if (!empty($row['civicrm_activity_id_count'])) {
        $url = [];
        $urlParams = ['activity_type_id', 'gid', 'status_id', 'contact_id'];
        foreach ($urlParams as $field) {
          if (!empty($row['civicrm_activity_' . $field])) {
            $url[] = "{$field}_op=in&{$field}_value={$row['civicrm_activity_'.$field]}";
          }
          elseif (!empty($this->_params[$field . '_value'])) {
            $val = implode(",", $this->_params[$field . '_value']);
            $url[] = "{$field}_op=in&{$field}_value={$val}";
          }
        }
        $date_suffixes = ['relative', 'from', 'to'];
        foreach ($date_suffixes as $suffix) {
          if (!empty($this->_params['activity_date_time_' . $suffix])) {
            list($from, $to)
              = $this->getFromTo(
                CRM_Utils_Array::value("activity_date_time_relative", $this->_params),
                CRM_Utils_Array::value("activity_date_time_from", $this->_params),
                CRM_Utils_Array::value("activity_date_time_to", $this->_params)
                );
            $url[] = "activity_date_time_from={$from}&activity_date_time_to={$to}";
            break;
          }
        }
        // reset date filter on activity reports.
        $url[] = "resetDateFilter=1";
        $url = implode('&', $url);
        $url = CRM_Report_Utils_Report::getNextUrl('activity', "reset=1&force=1&{$url}",
                 $this->_absoluteUrl,
                 $this->_id,
                 $this->_drilldownReport);
        $rows[$rowNum]['civicrm_activity_id_count_link'] = $url;
        $rows[$rowNum]['civicrm_activity_id_count_hover'] = ts('List all activity(s) for this row.');
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_contact_sort_name', $row) && $this->_outputMode != 'csv') {
        if ($value = $row['civicrm_contact_id']) {

          // unset the name, email and phone fields if the contact is the same as the previous contact
          if (isset($previousContact) && $previousContact == $value) {
            $rows[$rowNum]['civicrm_contact_sort_name'] = "";

            if (array_key_exists('civicrm_email_email', $row)) {
              $rows[$rowNum]['civicrm_email_email'] = "";
            }
            if (array_key_exists('civicrm_phone_phone', $row)) {
              $rows[$rowNum]['civicrm_phone_phone'] = "";
            }
          }
          else {
            $url = CRM_Utils_System::url('civicrm/contact/view',
              'reset=1&cid=' . $value,
              $this->_absoluteUrl
            );

            $rows[$rowNum]['civicrm_contact_sort_name'] = "<a href='$url'>" . $row['civicrm_contact_sort_name'] .
              '</a>';
          }

          // store the contact ID of this contact
          $previousContact = $value;
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_activity_type_id', $row)) {
        if ($value = $row['civicrm_activity_activity_type_id']) {

          $value = explode(',', $value);
          foreach ($value as $key => $id) {
            $value[$key] = $activityType[$id];
          }

          $rows[$rowNum]['civicrm_activity_activity_type_id'] = implode(' , ', $value);
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_status_id', $row)) {
        if ($value = $row['civicrm_activity_status_id']) {
          $rows[$rowNum]['civicrm_activity_status_id'] = $activityStatus[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_priority_id', $row)) {
        if ($value = $row['civicrm_activity_priority_id']) {
          $rows[$rowNum]['civicrm_activity_priority_id'] = $priority[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_activity_duration', $row)) {
        if ($value = $row['civicrm_activity_duration']) {
          $rows[$rowNum]['civicrm_activity_duration'] = $rows[$rowNum]['civicrm_activity_duration_total'];
          $entryFound = TRUE;
        }
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
