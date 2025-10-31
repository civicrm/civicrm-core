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
class CRM_Report_Form_Case_Detail extends CRM_Report_Form {

  protected $_relField = FALSE;

  protected $_addressField = TRUE;

  protected $_activityLast = FALSE;

  protected $_activityLastCompleted = FALSE;

  protected $_includeCaseDetailExtra = FALSE;

  protected $_caseDetailExtra = [];

  protected $_customGroupExtends = [
    'Case',
    'Contact',
  ];

  protected $_caseTypeNameOrderBy = FALSE;

  /**
   * @var array
   */
  protected $caseStatuses;

  /**
   * @var array
   */
  protected $caseTypes;

  /**
   * @var array
   */
  protected $relTypes;

  /**
   * @var array
   */
  protected $caseActivityTypes;

  /**
   */
  public function __construct() {
    $this->caseStatuses = CRM_Core_OptionGroup::values('case_status');
    $this->caseTypes = CRM_Case_PseudoConstant::caseType();
    $rels = CRM_Core_PseudoConstant::relationshipType();
    foreach ($rels as $relid => $v) {
      $this->relTypes[$relid] = $v['label_b_a'];
    }

    $this->caseActivityTypes = [];
    foreach (CRM_Case_PseudoConstant::caseActivityType() as $typeDetail) {
      $this->caseActivityTypes[$typeDetail['id']] = $typeDetail['label'];
    }

    $this->_columns = [
      'civicrm_case' => [
        'dao' => 'CRM_Case_DAO_Case',
        'fields' => [
          'id' => [
            'title' => ts('Case ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'case_id' => [
            'title' => ts('Case ID'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'subject' => [
            'title' => ts('Subject'),
            'default' => TRUE,
          ],
          'start_date' => [
            'title' => ts('Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'end_date' => [
            'title' => ts('End Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'status_id' => ['title' => ts('Case Status')],
          'case_type_id' => ['title' => ts('Case Type')],
          'is_deleted' => [
            'title' => ts('Deleted?'),
            'default' => FALSE,
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ],
        ],
        'filters' => [
          'start_date' => [
            'title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'end_date' => [
            'title' => ts('End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'status_id' => [
            'title' => ts('Case Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('status_id', 'search'),
          ],
          'case_type_id' => [
            'title' => ts('Case Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('case_type_id', 'search'),
          ],
          'is_deleted' => [
            'title' => ts('Deleted?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
            'default' => 0,
          ],
        ],
        'order_bys' => [
          'start_date' => [
            'title' => ts('Start Date'),
            'default_weight' => 1,
          ],
          'end_date' => [
            'title' => ts('End Date'),
          ],
          'status_id' => [
            'title' => ts('Status'),
          ],
        ],
      ],
      'civicrm_case_type' => [
        'dao' => 'CRM_Case_DAO_Case',
        'order_bys' => [
          'case_type_title' => [
            'title' => ts('Case Type'),
            'name' => 'title',
          ],
        ],
      ],
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'client_sort_name' => [
            'name' => 'sort_name',
            'title' => ts('Client Name'),
            'required' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name' => ['title' => ts('Client Name')],
        ],
      ],
      'civicrm_relationship' => [
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => [
          'case_role' => [
            'name' => 'relationship_type_id',
            'title' => ts('Case Role(s)'),
          ],
        ],
        'filters' => [
          'case_role' => [
            'name' => 'relationship_type_id',
            'title' => ts('Case Role(s)'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->relTypes,
          ],
          'is_active' => [
            'title' => ts('Active Role?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ],
        ],
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'title' => ts('Email'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => [
            'title' => ts('Phone'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => [
          'street_address' => NULL,
          'city' => NULL,
          'state_province_id' => [
            'title' => ts('State/Province'),
          ],
          'country_id' => ['title' => ts('Country')],
        ],
        'grouping' => 'contact-fields',
        'filters' => [
          'country_id' => [
            'title' => ts('Country'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ],
          'state_province_id' => [
            'title' => ts('State/Province'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
          ],
        ],
      ],
      'civicrm_worldregion' => [
        'dao' => 'CRM_Core_DAO_Worldregion',
        'filters' => [
          'worldregion_id' => [
            'name' => 'id',
            'title' => ts('World Region'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::worldRegion(),
          ],
        ],
      ],
      'civicrm_country' => [
        'dao' => 'CRM_Core_DAO_Country',
      ],
      'civicrm_activity_last' => [
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => [
          'last_activity_activity_subject' => [
            'name' => 'subject',
            'title' => ts('Subject of the last activity in the case'),
          ],
          'last_activity_activity_type' => [
            'name' => 'activity_type_id',
            'title' => ts('Activity type of the last activity'),
          ],
          'last_activity_date_time' => [
            'name' => 'activity_date_time',
            'title' => ts('Last Action Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'filters' => [
          'last_activity_activity_type' => [
            'name' => 'activity_type_id',
            'title' => ts('Activity type of the last activity'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE),
          ],
          'last_activity_date_time' => [
            'name' => 'activity_date_time',
            'title' => ts('Last Action Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'alias' => 'civireport_activity_last',
      ],
      'civicrm_activity_last_completed' => [
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => [
          'last_completed_activity_subject' => [
            'name' => 'subject',
            'title' => ts('Subject of the last completed activity in the case'),
          ],
          'last_completed_activity_type' => [
            'name' => 'activity_type_id',
            'title' => ts('Activity type of the last completed activity'),
          ],
          'last_completed_date_time' => [
            'name' => 'activity_date_time',
            'title' => ts('Last Completed Action Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'filters' => [
          'last_completed_date_time' => [
            'name' => 'activity_date_time',
            'title' => ts('Last Completed Action Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
      ],
    ];

    $this->_options = [
      'my_cases' => [
        'title' => ts('My Cases'),
        'type' => 'checkbox',
      ],
    ];
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->caseDetailSpecialColumnsAdd();
  }

  public function caseDetailSpecialColumnsAdd() {
    $elements = [];
    $elements[] = &$this->createElement('select', 'case_activity_all_dates', NULL,
      [
        '' => ts('- select -'),
      ] + $this->caseActivityTypes
    );
    $this->addGroup($elements, 'case_detail_extra');

    $this->_caseDetailExtra = [
      'case_activity_all_dates' => [
        'title' => ts('List of all dates of activities of Type'),
        'name' => 'activity_date_time',
      ],
    ];

    $this->assign('caseDetailExtra', $this->_caseDetailExtra);
  }

  public function select() {
    // @todo - get rid of this function & use parent. Use selectWhere to setthe clause for the
    // few fields that need custom handling.
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if ($tableName == 'civicrm_relationship') {
              $this->_relField = TRUE;
            }
            if ($fieldName == 'sort_name') {
              $select[] = "GROUP_CONCAT({$field['dbAlias']}  ORDER BY {$field['dbAlias']} )
                                         as {$tableName}_{$fieldName}";
            }
            if ($tableName == 'civicrm_activity_last') {
              $this->_activityLast = TRUE;
            }
            if ($tableName == 'civicrm_activity_last_completed') {
              $this->_activityLastCompleted = TRUE;
            }

            if ($fieldName == 'case_role') {
              $select[] = "GROUP_CONCAT(DISTINCT({$field['dbAlias']}) ORDER BY {$field['dbAlias']}) as {$tableName}_{$fieldName}";
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            }

            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }

    $this->_selectClauses = $select;

    $this->_select = 'SELECT ' . implode(', ', $select) . ' ';
  }

  public function from() {

    $case = $this->_aliases['civicrm_case'];
    $conact = $this->_aliases['civicrm_contact'];

    $this->_from = "
             FROM civicrm_case $case
 LEFT JOIN civicrm_case_contact civireport_case_contact on civireport_case_contact.case_id = {$case}.id
 LEFT JOIN civicrm_contact $conact ON {$conact}.id = civireport_case_contact.contact_id
 ";
    if ($this->_relField) {
      $this->_from .= "
             LEFT JOIN  civicrm_relationship {$this->_aliases['civicrm_relationship']} ON {$this->_aliases['civicrm_relationship']}.case_id = {$case}.id
";
    }

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    if ($this->isTableSelected('civicrm_worldregion')) {
      $this->_from .= "
             LEFT JOIN civicrm_country {$this->_aliases['civicrm_country']}
                   ON {$this->_aliases['civicrm_country']}.id ={$this->_aliases['civicrm_address']}.country_id
             LEFT JOIN civicrm_worldregion {$this->_aliases['civicrm_worldregion']}
                   ON {$this->_aliases['civicrm_country']}.region_id = {$this->_aliases['civicrm_worldregion']}.id ";
    }

    // Include clause for last activity of the case
    if ($this->_activityLast) {
      $this->_from .= " LEFT JOIN civicrm_activity {$this->_aliases['civicrm_activity_last']} ON ( {$this->_aliases['civicrm_activity_last']}.id = ( SELECT max(activity_id) FROM civicrm_case_activity WHERE case_id = {$case}.id) )";
    }

    // Include clause for last completed activity of the case
    if ($this->_activityLastCompleted) {
      $this->_from .= " LEFT JOIN civicrm_activity {$this->_aliases['civicrm_activity_last_completed']} ON ( {$this->_aliases['civicrm_activity_last_completed']}.id = ( SELECT max(activity_id) FROM civicrm_case_activity cca, civicrm_activity ca WHERE ca.id = cca.activity_id AND cca.case_id = {$case}.id AND ca.status_id = 2 ) )";
    }

    if ($this->isTableSelected('civicrm_case_type')) {
      $this->_from .= "
        LEFT JOIN civicrm_case_type {$this->_aliases['civicrm_case_type']}
          ON {$this->_aliases['civicrm_case']}.case_type_id = {$this->_aliases['civicrm_case_type']}.id
      ";
    }
  }

  public function where() {
    $clauses = [];
    $this->_having = '';
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type']);
          }
          else {

            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($fieldName == 'case_type_id' &&
              !empty($this->_params['case_type_id_value'])
            ) {
              foreach ($this->_params['case_type_id_value'] as $key => $value) {
                $this->_params['case_type_id_value'][$key] = $value;
              }
            }

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

    if (isset($this->_params['options']['my_cases'])) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
      $clauses[] = "{$this->_aliases['civicrm_contact']}.id = {$userID}";
    }

    if (empty($clauses)) {
      $this->_where = 'WHERE ( 1 ) ';
    }
    else {
      $this->_where = 'WHERE ' . implode(' AND ', $clauses);
    }
  }

  public function groupBy() {
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, "{$this->_aliases['civicrm_case']}.id");
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = "select COUNT( DISTINCT( {$this->_aliases['civicrm_address']}.country_id))";
    $sql = "{$select} {$this->_from} {$this->_where}";
    $countryCount = CRM_Core_DAO::singleValueQuery($sql);

    $statistics['counts']['case'] = [
      'title' => ts('Total Number of Cases '),
      'value' => isset($statistics['counts']['rowsFound']) ? $statistics['counts']['rowsFound']['value'] : count($rows),
    ];
    $statistics['counts']['country'] = [
      'title' => ts('Total Number of Countries '),
      'value' => $countryCount,
    ];

    return $statistics;
  }

  public function caseDetailSpecialColumnProcess() {
    if (!$this->_includeCaseDetailExtra) {
      return;
    }

    $from = $select = [];
    $case = $this->_aliases['civicrm_case'];

    $activityType = $this->_params['case_detail_extra']['case_activity_all_dates'] ?? NULL;
    if ($activityType) {
      $select[] = "GROUP_CONCAT(DISTINCT(civireport_activity_all_{$activityType}.{$this->_caseDetailExtra['case_activity_all_dates']['name']}) ORDER BY civireport_activity_all_{$activityType}.{$this->_caseDetailExtra['case_activity_all_dates']['name']}) as case_activity_all_dates";

      $from[] = " LEFT JOIN civicrm_case_activity civireport_case_activity_all_{$activityType} ON ( civireport_case_activity_all_{$activityType}.case_id = {$case}.id)
                        LEFT JOIN civicrm_activity civireport_activity_all_{$activityType} ON ( civireport_activity_all_{$activityType}.id = civireport_case_activity_all_{$activityType}.activity_id AND civireport_activity_all_{$activityType}.activity_type_id = {$activityType})";

      $this->_columnHeaders['case_activity_all_dates'] = [
        'title' => $this->_caseDetailExtra['case_activity_all_dates']['title'] . ": {$this->caseActivityTypes[$activityType]}",
        'type' => $this->_caseDetailExtra['case_activity_all_dates']['type'] ?? NULL,
      ];
    }

    $this->_select .= ', ' . implode(', ', $select) . ' ';
    $this->_from .= ' ' . implode(' ', $from) . ' ';
  }

  public function postProcess() {

    $this->beginPostProcess();

    $this->checkEnabledFields();

    $this->buildQuery(TRUE);

    $this->caseDetailSpecialColumnProcess();

    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";

    $rows = $graphRows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);

    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  public function checkEnabledFields() {
    if ((isset($this->_params['case_role_value'])
        && !empty($this->_params['case_role_value'])) ||
      (isset($this->_params['is_active_value']))
    ) {
      $this->_relField = TRUE;
    }

    if (!empty($this->_params['last_completed_date_time_relative']) ||
      !empty($this->_params['last_completed_date_time_from']) ||
      !empty($this->_params['last_completed_date_time_to'])
    ) {
      $this->_activityLastCompleted = TRUE;
    }

    if (!empty($this->_params['last_activity_date_time_relative']) ||
      !empty($this->_params['last_activity_date_time_from']) ||
      !empty($this->_params['last_activity_date_time_to'])
    ) {
      $this->_activityLast = TRUE;
    }

    foreach (array_keys($this->_caseDetailExtra) as $field) {
      if (!empty($this->_params['case_detail_extra'][$field])) {
        $this->_includeCaseDetailExtra = TRUE;
        break;
      }
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
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);

    foreach ($rows as $rowNum => $row) {
      if (array_key_exists('civicrm_case_status_id', $row)) {
        if ($value = $row['civicrm_case_status_id']) {
          $rows[$rowNum]['civicrm_case_status_id'] = $this->caseStatuses[$value];

          $entryFound = TRUE;
        }
      }
      if (array_key_exists('civicrm_case_case_type_id', $row)) {
        if ($value = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $row['civicrm_case_case_type_id'])) {
          $rows[$rowNum]['civicrm_case_case_type_id'] = $this->caseTypes[$value];

          $entryFound = TRUE;
        }
      }
      if (array_key_exists('civicrm_case_subject', $row)) {
        if ($value = $row['civicrm_case_subject']) {
          $url = CRM_Utils_System::url("civicrm/case/ajax/details",
            "caseId={$row['civicrm_case_id']}&contactId={$row['civicrm_contact_id']}",
            $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_case_subject'] = "<a class=\"crm-popup\" href=\"$url\">$value</a>";
          $rows[$rowNum]['civicrm_case_subject_hover'] = ts('View Details of Case.');

          $entryFound = TRUE;
        }
      }
      if (array_key_exists('civicrm_relationship_case_role', $row)) {
        if ($value = $row['civicrm_relationship_case_role']) {
          $caseRoles = explode(',', $value);
          foreach ($caseRoles as $num => $caseRole) {
            $caseRoles[$num] = $this->relTypes[$caseRole];
          }
          $rows[$rowNum]['civicrm_relationship_case_role'] = implode('; ', $caseRoles);
        }
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_activity_last_last_activity_activity_subject', $row) &&
        empty($row['civicrm_activity_last_last_activity_activity_subject'])
      ) {
        $rows[$rowNum]['civicrm_activity_last_last_activity_activity_subject'] = ts('(no subject)');
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_activity_last_completed_last_completed_activity_subject', $row) &&
        empty($row['civicrm_activity_last_completed_last_completed_activity_subject'])
      ) {
        $rows[$rowNum]['civicrm_activity_last_completed_last_completed_activity_subject'] = ts('(no subject)');
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_contact_client_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_client_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_client_sort_name_hover'] = ts("View Contact Summary for this Contact");
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_activity_last_last_activity_activity_type', $row)) {
        if ($value = $row['civicrm_activity_last_last_activity_activity_type']) {
          $rows[$rowNum]['civicrm_activity_last_last_activity_activity_type'] = $activityTypes[$value];
        }
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_activity_last_completed_last_completed_activity_type', $row)) {
        if ($value = $row['civicrm_activity_last_completed_last_completed_activity_type']) {
          $rows[$rowNum]['civicrm_activity_last_completed_last_completed_activity_type'] = $activityTypes[$value];
        }
        $entryFound = TRUE;
      }

      if (array_key_exists('case_activity_all_dates', $row)) {
        if ($value = $row['case_activity_all_dates']) {
          $activityDates = explode(',', $value);
          foreach ($activityDates as $num => $activityDate) {
            $activityDates[$num] = CRM_Utils_Date::customFormat($activityDate);
          }
          $rows[$rowNum]['case_activity_all_dates'] = implode('; ', $activityDates);
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;
      if (!$entryFound) {
        break;
      }
    }
  }

}
