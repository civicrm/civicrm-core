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
class CRM_Report_Form_Case_TimeSpent extends CRM_Report_Form {

  /**
   * @var array
   * @internal
   */
  public $activityTypes;

  /**
   * @var array
   * @internal
   */
  public $activityStatuses;

  /**
   * @var array
   * @internal
   */
  public $has_grouping;

  /**
   * @var array
   * @internal
   */
  public $has_activity_type;

  /**
   * Class constructor.
   */
  public function __construct() {

    $this->activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
    asort($this->activityTypes);
    $this->activityStatuses = CRM_Core_PseudoConstant::activityStatus();

    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'id' => [
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'sort_name' => [
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
        ],
        'order_bys' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'default_weight' => '1',
          ],
        ],
      ],
      'civicrm_activity' => [
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => [
          'activity_type_id' => [
            'title' => ts('Activity Type'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'activity_date_time' => [
            'title' => ts('Activity Date'),
            'default' => TRUE,
          ],
          'status_id' => [
            'title' => ts('Activity Status'),
            'default' => FALSE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'id' => [
            'title' => ts('Activity ID'),
            'default' => TRUE,
          ],
          'duration' => [
            'title' => ts('Duration'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_INT,
          ],
          'subject' => [
            'title' => ts('Activity Subject'),
            'default' => FALSE,
          ],
        ],
        'filters' => [
          'activity_date_time' => [
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'subject' => [
            'title' => ts('Activity Subject'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ],
          'activity_type_id' => [
            'title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityTypes,
          ],
          'status_id' => [
            'title' => ts('Activity Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityStatuses,
          ],
        ],
        'order_bys' => [
          'subject' => [
            'title' => ts('Activity Subject'),
          ],
          'activity_type_id' => [
            'title' => ts('Activity Type'),
          ],
          'activity_date_time' => [
            'title' => ts('Activity Date'),
          ],
          'status_id' => [
            'title' => ts('Activity Status'),
          ],
        ],
        'grouping' => 'case-fields',
      ],
      'civicrm_activity_source' => [
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => [
          'contact_id' => [
            'title' => ts('Contact ID'),
            'default' => TRUE,
            'no_display' => TRUE,
          ],
        ],
        'group_bys' => [
          'contact_id' => [
            'title' => ts('Totals Only'),
            'default' => TRUE,
          ],
        ],
        'grouping' => 'activity-fields',
      ],
      'civicrm_case_activity' => [
        'dao' => 'CRM_Case_DAO_CaseActivity',
        'fields' => [
          'case_id' => [
            'title' => ts('Case ID'),
            'default' => FALSE,
          ],
        ],
        'filters' => [
          'case_id_filter' => [
            'name' => 'case_id',
            'title' => ts('Cases?'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => [
              1 => ts('Exclude non-case'),
              2 => ts('Exclude cases'),
              3 => ts('Include Both'),
            ],
            'default' => 3,
          ],
        ],
      ],
    ];

    parent::__construct();
  }

  public function select() {
    $select = [];
    $this->_columnHeaders = [];

    $this->has_grouping = !empty($this->_params['group_bys']);
    $this->has_activity_type = FALSE;

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            (!empty($this->_params['fields'][$fieldName]) &&
              ((!$this->has_grouping) ||
                !in_array($fieldName, ['case_id', 'subject', 'status_id']))
            )
          ) {

            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'] ?? NULL;

            if ($fieldName == 'activity_type_id') {
              $this->has_activity_type = TRUE;
            }

            if ($fieldName == 'duration' && $this->has_grouping) {
              $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}";
            }
            elseif ($fieldName == 'activity_date_time' && $this->has_grouping) {
              $select[] = "EXTRACT(YEAR_MONTH FROM {$field['dbAlias']}) AS {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = ts('Month/Year');
            }
            elseif ($tableName == 'civicrm_activity' && $fieldName == 'id' &&
              $this->has_grouping
            ) {
              $select[] = "COUNT({$field['dbAlias']}) AS {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = ts('# Activities');
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            }
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {

    $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}
             LEFT JOIN civicrm_activity_contact {$this->_aliases['civicrm_activity_source']}
                    ON {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_source']}.activity_id
             LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                    ON {$this->_aliases['civicrm_activity_source']}.contact_id = {$this->_aliases['civicrm_contact']}.id
             LEFT JOIN civicrm_case_activity {$this->_aliases['civicrm_case_activity']}
                    ON {$this->_aliases['civicrm_case_activity']}.activity_id = {$this->_aliases['civicrm_activity']}.id
";
  }

  public function where() {
    $this->_where = " WHERE {$this->_aliases['civicrm_activity']}.is_current_revision = 1 AND
                                {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_test = 0";
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to);
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op) {
              // handle special case
              if ($fieldName == 'case_id_filter') {
                $choice = $this->_params["{$fieldName}_value"] ?? NULL;
                if ($choice == 1) {
                  $clause = "({$this->_aliases['civicrm_case_activity']}.id Is Not Null)";
                }
                elseif ($choice == 2) {
                  $clause = "({$this->_aliases['civicrm_case_activity']}.id Is Null)";
                }
              }
              else {
                $clause = $this->whereClause($field,
                  $op,
                  $this->_params["{$fieldName}_value"] ?? NULL,
                  $this->_params["{$fieldName}_min"] ?? NULL,
                  $this->_params["{$fieldName}_max"] ?? NULL
                );
              }
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
  }

  public function groupBy() {
    $this->_groupBy = '';
    if ($this->has_grouping) {
      $groupBy = [
        "{$this->_aliases['civicrm_contact']}.id",
        "civicrm_activity_activity_date_time",
      ];
      if ($this->has_activity_type) {
        $groupBy[] = "{$this->_aliases['civicrm_activity']}.activity_type_id";
      }

      $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
    }
  }

  public function postProcess() {
    parent::postProcess();
  }

  /**
   * @param $fields
   * @param $files
   * @param self $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    if (!empty($fields['group_bys']) &&
      (!array_key_exists('id', $fields['fields']) ||
        !array_key_exists('activity_date_time', $fields['fields']) ||
        !array_key_exists('duration', $fields['fields']))
    ) {
      $errors['fields'] = ts('To view totals please select all of activity id, date and duration.');
    }
    return $errors;
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
    foreach ($rows as $rowNum => $row) {

      if (isset($row['civicrm_activity_activity_type_id'])) {
        $entryFound = TRUE;
        $val = $row['civicrm_activity_activity_type_id'];
        $rows[$rowNum]['civicrm_activity_activity_type_id'] = $this->activityTypes[$val] ?? '';
      }

      if (isset($row['civicrm_activity_status_id'])) {
        $entryFound = TRUE;
        $val = $row['civicrm_activity_status_id'];
        $rows[$rowNum]['civicrm_activity_status_id'] = $this->activityStatuses[$val] ?? '';
      }

      // The next two make it easier to make pivot tables after exporting to Excel
      if (isset($row['civicrm_activity_duration'])) {
        $entryFound = TRUE;
        $rows[$rowNum]['civicrm_activity_duration'] = (int) $row['civicrm_activity_duration'];
      }

      if (isset($row['civicrm_case_activity_case_id'])) {
        $entryFound = TRUE;
        $rows[$rowNum]['civicrm_case_activity_case_id'] = (int) $row['civicrm_case_activity_case_id'];
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
