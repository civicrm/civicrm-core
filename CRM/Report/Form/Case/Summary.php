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
class CRM_Report_Form_Case_Summary extends CRM_Report_Form {

  protected $_relField = FALSE;
  protected $_exposeContactID = FALSE;
  protected $_customGroupExtends = ['Case'];

  /**
   * Class constructor.
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct() {
    $relationshipTypes = [];
    foreach (CRM_Core_PseudoConstant::relationshipType() as $relationshipTypeID => $values) {
      $relationshipTypes[$relationshipTypeID] = $values['label_b_a'];
    }
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
          'case_type_id' => [
            'title' => ts('Case Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('case_type_id', 'search'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'status_id' => [
            'title' => ts('Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('status_id', 'search'),
          ],
          'is_deleted' => [
            'title' => ts('Deleted?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
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
            'options' => $relationshipTypes,
          ],
          'is_active' => [
            'title' => ts('Active Relationship?'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
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

  public function select(): void {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            if ($tableName === 'civicrm_relationship_type') {
              $this->_relField = TRUE;
            }

            if ($fieldName === 'duration') {
              $select[] = "IF({$table['fields']['end_date']['dbAlias']} Is Null, '', DATEDIFF({$table['fields']['end_date']['dbAlias']}, {$table['fields']['start_date']['dbAlias']})) as {$tableName}_{$fieldName}";
            }
            elseif ($tableName === 'civicrm_relationship_type') {
              $select[] = '  IF(contact_civireport.id = relationship_civireport.contact_id_a, relationship_type_civireport.label_b_a, relationship_type_civireport.label_a_b) as civicrm_relationship_type_label_b_a';
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

  /**
   * @param array $fields
   *
   * @return array
   */
  public static function formRule(array $fields): array {
    $errors = [];
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

  public function from(): void {

    $cc = $this->_aliases['civicrm_case'];
    $c = $this->_aliases['civicrm_contact'];
    $c2 = $this->_aliases['civicrm_c2'];
    $cr = $this->_aliases['civicrm_relationship'];
    $crt = $this->_aliases['civicrm_relationship_type'];
    $ccc = $this->_aliases['civicrm_case_contact'];

    foreach ($this->_columns['civicrm_relationship']['filters'] as $fieldName => $field) {
      if (!empty($this->_params[$fieldName . '_op'])
        && array_key_exists("{$fieldName}_value", $this->_params)
        && !CRM_Utils_System::isNull($this->_params["{$fieldName}_value"])
      ) {
        $this->_relField = TRUE;
        break;
      }
    }

    if ($this->_relField) {
      $this->_from = "
            FROM civicrm_contact $c
inner join civicrm_relationship $cr on {$c}.id = {$cr}.contact_id_b OR {$c}.id = {$cr}.contact_id_a
inner join civicrm_case $cc on {$cc}.id = {$cr}.case_id
inner join civicrm_relationship_type $crt on {$crt}.id={$cr}.relationship_type_id
inner join civicrm_case_contact $ccc on {$ccc}.case_id = {$cc}.id
inner join civicrm_contact $c2 on {$c2}.id={$ccc}.contact_id
";
    }
    else {
      $this->_from = "
            FROM civicrm_case $cc
inner join civicrm_case_contact $ccc on {$ccc}.case_id = {$cc}.id
inner join civicrm_contact $c2 on {$c2}.id={$ccc}.contact_id
";
    }
  }

  public function storeWhereHavingClauseArray(): void {
    if (!empty($this->_params['fields']['label_b_a']) && (int) $this->_params['fields']['label_b_a'] === 1) {
      $this->_whereClauses[] = '(contact_civireport.sort_name != c2_civireport.sort_name)';
    }
    parent::storeWhereHavingClauseArray();
  }

  public function groupBy(): void {
    $this->_groupBy = '';
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
  public function alterDisplay(&$rows): void {
    $entryFound = FALSE;
    $caseTypes = CRM_Case_PseudoConstant::caseType();
    $caseStatuses = CRM_Core_OptionGroup::values('case_status');
    foreach ($rows as $rowNum => $row) {
      if (array_key_exists('civicrm_case_status_id', $row) && $value = $row['civicrm_case_status_id']) {
        $rows[$rowNum]['civicrm_case_status_id'] = $caseStatuses[$value];
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_case_case_type_id', $row) &&
        !empty($rows[$rowNum]['civicrm_case_case_type_id'])
      ) {
        $value = $row['civicrm_case_case_type_id'];
        $caseTypeIDs = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
        $value = [];
        foreach ($caseTypeIDs as $caseTypeID) {
          if ($caseTypeID) {
            $value[$caseTypeID] = $caseTypes[$caseTypeID];
          }
        }
        $rows[$rowNum]['civicrm_case_case_type_id'] = implode(', ', $value);
        $entryFound = TRUE;
      }

      // convert Case ID and Subject to links to Manage Case
      if (array_key_exists('civicrm_case_id', $row) &&
        !empty($rows[$rowNum]['civicrm_c2_id'])
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view/case',
          'reset=1&action=view&cid=' . $row['civicrm_c2_id'] . '&id=' .
          $row['civicrm_case_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_case_id_link'] = $url;
        $rows[$rowNum]['civicrm_case_id_hover'] = ts('Manage Case');
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_case_subject', $row) &&
        !empty($rows[$rowNum]['civicrm_c2_id'])
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view/case',
          'reset=1&action=view&cid=' . $row['civicrm_c2_id'] . '&id=' .
          $row['civicrm_case_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_case_subject_link'] = $url;
        $rows[$rowNum]['civicrm_case_subject_hover'] = ts('Manage Case');
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
