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
class CRM_Report_Form_Case_Summary extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_relField = FALSE;
  protected $_exposeContactID = FALSE;

  protected $_customGroupExtends = ['Case'];

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->case_types = CRM_Case_PseudoConstant::caseType();
    $this->case_statuses = CRM_Core_OptionGroup::values('case_status');
    $rels = CRM_Core_PseudoConstant::relationshipType();
    foreach ($rels as $relid => $v) {
      $this->rel_types[$relid] = $v['label_b_a'];
    }

    $this->deleted_labels = [
      '' => ts('- select -'),
      0 => ts('No'),
      1 => ts('Yes'),
    ];

    $this->_columns = [
      'civicrm_c2' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'client_name' => [
            'name' => 'sort_name',
            'title' => ts('Contact Name'),
            'required' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'order_bys' => [
          'client_name' => [
            'title' => ts('Contact Name'),
            'name' => 'sort_name',
          ],
        ],
        'grouping'  => 'case-fields',
      ],
      'civicrm_case' => [
        'dao' => 'CRM_Case_DAO_Case',
        'fields' => [
          'id' => [
            'title' => ts('Case ID'),
            'required' => TRUE,
          ],
          'subject' => [
            'title' => ts('Case Subject'),
            'default' => TRUE,
          ],
          'status_id' => [
            'title' => ts('Status'),
            'default' => TRUE,
          ],
          'case_type_id' => [
            'title' => ts('Case Type'),
            'default' => TRUE,
          ],
          'start_date' => [
            'title' => ts('Start Date'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'end_date' => [
            'title' => ts('End Date'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'duration' => [
            'title' => ts('Duration (Days)'),
            'default' => FALSE,
          ],
          'is_deleted' => [
            'title' => ts('Deleted?'),
            'default' => FALSE,
            'type' => CRM_Utils_Type::T_INT,
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
          'case_type_id' => [
            'title' => ts('Case Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('case_type_id', 'search'),
          ],
          'status_id' => [
            'title' => ts('Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('status_id', 'search'),
          ],
          'is_deleted' => [
            'title' => ts('Deleted?'),
            'type' => CRM_Report_Form::OP_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->deleted_labels,
            'default' => 0,
          ],
        ],
        'order_bys'  => [
          'start_date' => [
            'title' => ts('Start Date'),
          ],
          'end_date' => [
            'title' => ts('End Date'),
          ],
          'status_id' => [
            'title' => ts('Status'),
          ],
        ],
        'grouping'  => 'case-fields',
      ],
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Staff Member'),
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => ts('Staff Member'),
          ],
        ],
      ],
      'civicrm_relationship' => [
        'dao' => 'CRM_Contact_DAO_Relationship',
        'filters' => [
          'relationship_type_id' => [
            'title' => ts('Staff Relationship'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->rel_types,
          ],
          'is_active' => [
            'title' => ts('Active Relationship?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
            //MV dev/core#603, not set default values Yes/No, this cause issue when relationship fields are not selected
            // 'default' => TRUE,
            'options' => ['' => ts('- Select -')] + CRM_Core_SelectValues::boolean(),
          ],
        ],
      ],
      'civicrm_relationship_type' => [
        'dao' => 'CRM_Contact_DAO_RelationshipType',
        'fields' => [
          'label_b_a' => [
            'title' => ts('Relationship'),
            'default' => TRUE,
          ],
        ],
      ],
      'civicrm_case_contact' => [
        'dao' => 'CRM_Case_DAO_CaseContact',
      ],
    ];

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

            if ($tableName == 'civicrm_relationship_type') {
              $this->_relField = TRUE;
            }

            if ($fieldName == 'duration') {
              $select[] = "IF({$table['fields']['end_date']['dbAlias']} Is Null, '', DATEDIFF({$table['fields']['end_date']['dbAlias']}, {$table['fields']['start_date']['dbAlias']})) as {$tableName}_{$fieldName}";
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
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
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = [];
    if (empty($fields['relationship_type_id_value']) &&
      (array_key_exists('sort_name', $fields['fields']) ||
        array_key_exists('label_b_a', $fields['fields']))
    ) {
      $errors['fields'] = ts('Either filter on at least one relationship type, or de-select Staff Member and Relationship from the list of fields.');
    }
    if ((!empty($fields['relationship_type_id_value']) ||
        !empty($fields['sort_name_value'])) &&
      (!array_key_exists('sort_name', $fields['fields']) ||
        !array_key_exists('label_b_a', $fields['fields']))
    ) {
      $errors['fields'] = ts('To filter on Staff Member or Relationship, please also select Staff Member and Relationship from the list of fields.');
    }
    return $errors;
  }

  public function from() {

    $cc = $this->_aliases['civicrm_case'];
    $c = $this->_aliases['civicrm_contact'];
    $c2 = $this->_aliases['civicrm_c2'];
    $cr = $this->_aliases['civicrm_relationship'];
    $crt = $this->_aliases['civicrm_relationship_type'];
    $ccc = $this->_aliases['civicrm_case_contact'];

    foreach ($this->_columns['civicrm_relationship']['filters'] as $fieldName => $field) {
      if (!empty($this->_params[$fieldName . '_op']) && isset($this->_params[$fieldName . '_value'])) {
        $this->_relField = TRUE;
        break;
      }
    }

    if ($this->_relField) {
      $this->_from = "
            FROM civicrm_contact $c
inner join civicrm_relationship $cr on {$c}.id = ${cr}.contact_id_b
inner join civicrm_case $cc on ${cc}.id = ${cr}.case_id
inner join civicrm_relationship_type $crt on ${crt}.id=${cr}.relationship_type_id
inner join civicrm_case_contact $ccc on ${ccc}.case_id = ${cc}.id
inner join civicrm_contact $c2 on ${c2}.id=${ccc}.contact_id
";
    }
    else {
      $this->_from = "
            FROM civicrm_case $cc
inner join civicrm_case_contact $ccc on ${ccc}.case_id = ${cc}.id
inner join civicrm_contact $c2 on ${c2}.id=${ccc}.contact_id
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
          if (CRM_Utils_Array::value("operatorType", $field) & CRM_Report_Form::OP_DATE
          ) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to,
              CRM_Utils_Array::value('type', $field)
            );
          }
          else {

            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($fieldName == 'case_type_id') {
              $value = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
              if (!empty($value)) {
                $operator = '';
                if ($op == 'notin') {
                  $operator = 'NOT';
                }

                $regexp = "[[:cntrl:]]*" . implode('[[:>:]]*|[[:<:]]*', $value) . "[[:cntrl:]]*";
                $clause = "{$field['dbAlias']} {$operator} REGEXP '{$regexp}'";
              }
              $op = NULL;
            }

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
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }
  }

  public function groupBy() {
    $this->_groupBy = "";
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
      if (array_key_exists('civicrm_case_status_id', $row)) {
        if ($value = $row['civicrm_case_status_id']) {
          $rows[$rowNum]['civicrm_case_status_id'] = $this->case_statuses[$value];
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_case_case_type_id', $row) &&
        !empty($rows[$rowNum]['civicrm_case_case_type_id'])
      ) {
        $value = $row['civicrm_case_case_type_id'];
        $typeIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
        $value = [];
        foreach ($typeIds as $typeId) {
          if ($typeId) {
            $value[$typeId] = $this->case_types[$typeId];
          }
        }
        $rows[$rowNum]['civicrm_case_case_type_id'] = implode(', ', $value);
        $entryFound = TRUE;
      }

      // convert Case ID and Subject to links to Manage Case
      if (array_key_exists('civicrm_case_id', $row) &&
        !empty($rows[$rowNum]['civicrm_c2_id'])
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/case",
          'reset=1&action=view&cid=' . $row['civicrm_c2_id'] . '&id=' .
          $row['civicrm_case_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_case_id_link'] = $url;
        $rows[$rowNum]['civicrm_case_id_hover'] = ts("Manage Case");
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_case_subject', $row) &&
        !empty($rows[$rowNum]['civicrm_c2_id'])
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/case",
          'reset=1&action=view&cid=' . $row['civicrm_c2_id'] . '&id=' .
          $row['civicrm_case_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_case_subject_link'] = $url;
        $rows[$rowNum]['civicrm_case_subject_hover'] = ts("Manage Case");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_case_is_deleted', $row)) {
        $value = $row['civicrm_case_is_deleted'];
        $rows[$rowNum]['civicrm_case_is_deleted'] = $this->deleted_labels[$value];
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
