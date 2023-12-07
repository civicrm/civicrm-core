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
class CRM_Report_Form_Case_Demographics extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;
  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * @var bool
   * @see https://issues.civicrm.org/jira/browse/CRM-19170
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
          'sort_name' => [
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'gender_id' => [
            'title' => ts('Gender'),
            'default' => TRUE,
          ],
          'birth_date' => [
            'title' => ts('Birthdate'),
            'default' => FALSE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'operatorType' => CRM_Report_Form::OP_STRING,
          ],
          'contact_type' => [
            'title' => ts('Contact Type'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => [
              '' => ts('-select-'),
              'Individual' => ts('Individual'),
              'Organization' => ts('Organization'),
              'Household' => ts('Household'),
            ],
            'default' => 'Individual',
          ],
          'id' => [
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
          ],
        ],
        'order_bys' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'default_weight' => '1',
            'dbAlias' => 'civicrm_contact_sort_name',
          ],
        ],
        'grouping' => 'contact-fields',
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
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'grouping' => 'contact-fields',
        'fields' => [
          'street_address' => ['default' => FALSE],
          'city' => ['default' => TRUE],
          'postal_code' => NULL,
          'state_province_id' => [
            'title' => ts('State/Province'),
          ],
          'country_id' => [
            'title' => ts('Country'),
            'default' => FALSE,
          ],
        ],
        /*
                          'filters'   => array(
                             'country_id' =>                                 array( 'title'   => ts( 'Country' ),
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options' => CRM_Core_PseudoConstant::country( ),
                                        ),
                                 'state_province_id' =>                                 array( 'title'   => ts( 'State/Province' ),
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options' => CRM_Core_PseudoConstant::stateProvince( ), ),
                                 ),
         */
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => ['phone' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_activity' => [
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => [
          'id' => [
            'title' => ts('Activity ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_case' => [
        'dao' => 'CRM_Case_DAO_Case',
        'fields' => [
          'id' => [
            'title' => ts('Case ID'),
            'required' => TRUE,
          ],
          'start_date' => [
            'title' => ts('Case Start'),
            'default' => TRUE,
          ],
          'end_date' => [
            'title' => ts('Case End'),
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'case_id_filter' => [
            'name' => 'id',
            'title' => ts('Cases?'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => [
              1 => ts('Exclude non-case'),
              2 => ts('Exclude cases'),
              3 => ts('Include Both'),
            ],
            'default' => 3,
          ],
          'start_date' => [
            'title' => ts('Case Start'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'end_date' => [
            'title' => ts('Case End'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'order_bys' => [
          'id' => [
            'title' => ts('Case ID'),
            'default_weight' => '2',
            'dbAlias' => 'civicrm_case_id',
          ],
        ],
      ],
    ];

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;

    $open_case_val = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case');
    $crmDAO = &CRM_Core_DAO::executeQuery("SELECT cg.table_name, cg.extends AS ext, cf.label, cf.column_name FROM civicrm_custom_group cg INNER JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id
where (cg.extends='Contact' OR cg.extends='Individual' OR cg.extends_entity_column_value='$open_case_val') AND cg.is_active=1 AND cf.is_active=1 ORDER BY cg.table_name");
    $curTable = '';
    $curExt = '';
    $curFields = [];
    while ($crmDAO->fetch()) {
      if ($curTable == '') {
        $curTable = $crmDAO->table_name;
        $curExt = $crmDAO->ext;
      }
      elseif ($curTable != $crmDAO->table_name) {
        // dummy DAO
        $this->_columns[$curTable] = [
          'dao' => 'CRM_Contact_DAO_Contact',
          'fields' => $curFields,
          'ext' => $curExt,
        ];
        $curTable = $crmDAO->table_name;
        $curExt = $crmDAO->ext;
        $curFields = [];
      }

      $curFields[$crmDAO->column_name] = ['title' => $crmDAO->label];
    }
    if (!empty($curFields)) {
      // dummy DAO
      $this->_columns[$curTable] = [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => $curFields,
        'ext' => $curExt,
      ];
    }

    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param self $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = [];
    return $errors;
  }

  public function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']}
            LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND
                      {$this->_aliases['civicrm_address']}.is_primary = 1 )
            LEFT JOIN civicrm_case_contact ccc ON ccc.contact_id = {$this->_aliases['civicrm_contact']}.id
            LEFT JOIN civicrm_case {$this->_aliases['civicrm_case']} ON {$this->_aliases['civicrm_case']}.id = ccc.case_id
            LEFT JOIN civicrm_case_activity cca ON cca.case_id = {$this->_aliases['civicrm_case']}.id
            LEFT JOIN civicrm_activity {$this->_aliases['civicrm_activity']} ON {$this->_aliases['civicrm_activity']}.id = cca.activity_id
        ";

    foreach ($this->_columns as $t => $c) {
      if (substr($t, 0, 13) == 'civicrm_value' ||
        substr($t, 0, 12) == 'custom_value'
      ) {
        $this->_from .= " LEFT JOIN $t {$this->_aliases[$t]} ON {$this->_aliases[$t]}.entity_id = ";
        $this->_from .= ($c['ext'] ==
          'Activity') ? "{$this->_aliases['civicrm_activity']}.id" : "{$this->_aliases['civicrm_contact']}.id";
      }
    }

    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

  }

  public function where() {
    $clauses = [];
    $this->_having = '';
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($field['operatorType'] & CRM_Report_Form::OP_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to, CRM_Utils_Type::T_DATE);
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op) {
              // handle special case
              if ($fieldName == 'case_id_filter') {
                $choice = $this->_params["{$fieldName}_value"] ?? NULL;
                if ($choice == 1) {
                  $clause = "({$this->_aliases['civicrm_case']}.id Is Not Null)";
                }
                elseif ($choice == 2) {
                  $clause = "({$this->_aliases['civicrm_case']}.id Is Null)";
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

    $clauses[] = "(({$this->_aliases['civicrm_case']}.is_deleted = 0) OR ({$this->_aliases['civicrm_case']}.is_deleted Is Null))";
    $clauses[] = "(({$this->_aliases['civicrm_activity']}.is_deleted = 0) OR ({$this->_aliases['civicrm_activity']}.is_deleted Is Null))";
    $clauses[] = "(({$this->_aliases['civicrm_activity']}.is_current_revision = 1) OR ({$this->_aliases['civicrm_activity']}.is_deleted Is Null))";

    $this->_where = "WHERE " . implode(' AND ', $clauses);
  }

  public function groupBy() {
    $groupBy = ["{$this->_aliases['civicrm_contact']}.id", "{$this->_aliases['civicrm_case']}.id"];
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
  }

  public function postProcess() {

    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);
    $rows = $graphRows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
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
      // make count columns point to detail report
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      // handle custom fields
      foreach ($row as $k => $r) {
        if (substr($k, 0, 13) == 'civicrm_value' ||
          substr($k, 0, 12) == 'custom_value'
        ) {
          if ($r || $r == '0') {
            if ($newval = $this->getCustomFieldLabel($k, $r)) {
              $rows[$rowNum][$k] = $newval;
            }
          }
          $entryFound = TRUE;
        }
      }

      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;
      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * @param string $fname
   * @param string $val
   *
   * @return null|string
   */
  public function getCustomFieldLabel($fname, $val) {
    $query = "
SELECT v.label
  FROM civicrm_custom_group cg INNER JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id
  INNER JOIN civicrm_option_group g ON cf.option_group_id = g.id
  INNER JOIN civicrm_option_value v ON g.id = v.option_group_id
  WHERE CONCAT(cg.table_name, '_', cf.column_name) = %1 AND v.value = %2";
    $params = [
      1 => [$fname, 'String'],
      2 => [$val, 'String'],
    ];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

}
