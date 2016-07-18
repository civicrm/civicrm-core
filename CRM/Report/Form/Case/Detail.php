<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_Report_Form_Case_Detail extends CRM_Report_Form {

  protected $_relField = FALSE;

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_worldRegionField = FALSE;

  protected $_activityLast = FALSE;

  protected $_activityLastCompleted = FALSE;

  protected $_includeCaseDetailExtra = FALSE;

  protected $_caseDetailExtra = array();

  protected $_customGroupExtends = array('Case');

  /**
   */
  public function __construct() {
    $this->case_statuses = CRM_Core_OptionGroup::values('case_status');
    $this->case_types = CRM_Case_PseudoConstant::caseType();
    $rels = CRM_Core_PseudoConstant::relationshipType();
    foreach ($rels as $relid => $v) {
      $this->rel_types[$relid] = $v['label_b_a'];
    }

    $this->deleted_labels = array(
      '' => ts('- select -'),
      0 => ts('No'),
      1 => ts('Yes'),
    );

    $this->caseActivityTypes = array();
    foreach (CRM_Case_PseudoConstant::caseActivityType() as $typeDetail) {
      $this->caseActivityTypes[$typeDetail['id']] = $typeDetail['label'];
    }

    $this->_columns = array(
      'civicrm_case' => array(
        'dao' => 'CRM_Case_DAO_Case',
        'fields' => array(
          'id' => array(
            'title' => ts('Case ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'subject' => array(
            'title' => ts('Subject'),
            'required' => TRUE,
          ),
          'start_date' => array(
            'title' => ts('Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'end_date' => array(
            'title' => ts('End Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'status_id' => array('title' => ts('Case Status')),
          'case_type_id' => array('title' => ts('Case Type')),
          'is_deleted' => array(
            'title' => ts('Deleted?'),
            'default' => FALSE,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'filters' => array(
          'start_date' => array(
            'title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'end_date' => array(
            'title' => ts('End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'status_id' => array(
            'title' => ts('Case Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('status_id', 'search'),
          ),
          'case_type_id' => array(
            'title' => ts('Case Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Case_BAO_Case::buildOptions('case_type_id', 'search'),
          ),
          'is_deleted' => array(
            'title' => ts('Deleted?'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->deleted_labels,
            'default' => 0,
          ),
        ),
      ),
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'client_sort_name' => array(
            'name' => 'sort_name',
            'title' => ts('Client Name'),
            'required' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array('title' => ts('Client Name')),
        ),
      ),
      'civicrm_relationship' => array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => array(
          'case_role' => array(
            'name' => 'relationship_type_id',
            'title' => ts('Case Role(s)'),
          ),
        ),
        'filters' => array(
          'case_role' => array(
            'name' => 'relationship_type_id',
            'title' => ts('Case Role(s)'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->rel_types,
          ),
        ),
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => array(
            'title' => ts('Email'),
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'phone' => array(
            'title' => ts('Phone'),
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => array(
          'street_address' => NULL,
          'state_province_id' => array(
            'title' => ts('State/Province'),
          ),
          'country_id' => array('title' => ts('Country')),
        ),
        'grouping' => 'contact-fields',
        'filters' => array(
          'country_id' => array(
            'title' => ts('Country'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ),
          'state_province_id' => array(
            'title' => ts('State/Province'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
          ),
        ),
      ),
      'civicrm_worldregion' => array(
        'dao' => 'CRM_Core_DAO_Worldregion',
        'filters' => array(
          'worldregion_id' => array(
            'name' => 'id',
            'title' => ts('World Region'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::worldRegion(),
          ),
        ),
      ),
      'civicrm_country' => array(
        'dao' => 'CRM_Core_DAO_Country',
      ),
      'civicrm_activity_last' => array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'filters' => array(
          'last_activity_date_time' => array(
            'name' => 'activity_date_time',
            'title' => ts('Last Action Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
        'alias' => 'civireport_activity_last',
      ),
      'civicrm_activity_last_completed' => array(
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => array(
          'last_completed_activity_subject' => array(
            'name' => 'subject',
            'title' => ts('Subject of the last completed activity in the case'),
          ),
          'last_completed_activity_type' => array(
            'name' => 'activity_type_id',
            'title' => ts('Activity type of the last completed activity'),
          ),
        ),
      ),
    );

    $this->_options = array(
      'my_cases' => array(
        'title' => ts('My Cases'),
        'type' => 'checkbox',
      ),
    );
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
    $elements = array();
    $elements[] = &$this->createElement('select', 'case_activity_all_dates', NULL,
      array(
        '' => ts('- select -'),
      ) + $this->caseActivityTypes
    );
    $this->addGroup($elements, 'case_detail_extra');

    $this->_caseDetailExtra = array(
      'case_activity_all_dates' => array(
        'title' => ts('List of all dates of activities of Type'),
        'name' => 'activity_date_time',
      ),
    );

    $this->assign('caseDetailExtra', $this->_caseDetailExtra);
  }

  public function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            elseif ($tableName == 'civicrm_relationship') {
              $this->_relField = TRUE;
            }
            if ($fieldName == 'sort_name') {
              $select[] = "GROUP_CONCAT({$field['dbAlias']}  ORDER BY {$field['dbAlias']} )
                                         as {$tableName}_{$fieldName}";
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

            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
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

    if ($this->_addressField) {
      $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                    ON {$conact}.id = {$this->_aliases['civicrm_address']}.contact_id AND
                       {$this->_aliases['civicrm_address']}.is_primary = 1 ";
    }
    if ($this->_emailField) {
      $this->_from .= "
             LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                   ON {$conact}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                       {$this->_aliases['civicrm_email']}.is_primary = 1 ";
    }
    if ($this->_phoneField) {
      $this->_from .= "
             LEFT JOIN  civicrm_phone {$this->_aliases['civicrm_phone']}
                       ON ( {$conact}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                          {$this->_aliases['civicrm_phone']}.is_primary = 1) ";
    }
    if ($this->_worldRegionField) {
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
  }

  public function where() {
    $clauses = array();
    $this->_having = '';
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type']);
          }
          else {

            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
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
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = "select COUNT( DISTINCT( {$this->_aliases['civicrm_address']}.country_id))";
    $sql = "{$select} {$this->_from} {$this->_where}";
    $countryCount = CRM_Core_DAO::singleValueQuery($sql);

    $statistics['counts']['case'] = array(
      'title' => ts('Total Number of Cases '),
      'value' => isset($statistics['counts']['rowsFound']) ? $statistics['counts']['rowsFound']['value'] : count($rows),
    );
    $statistics['counts']['country'] = array(
      'title' => ts('Total Number of Countries '),
      'value' => $countryCount,
    );

    return $statistics;
  }

  public function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_case']}.start_date DESC ";
  }

  public function caseDetailSpecialColumnProcess() {
    if (!$this->_includeCaseDetailExtra) {
      return;
    }

    $from = $select = array();
    $case = $this->_aliases['civicrm_case'];

    if ($activityType = CRM_Utils_Array::value('case_activity_all_dates', $this->_params['case_detail_extra'])) {
      $select[] = "GROUP_CONCAT(DISTINCT(civireport_activity_all_{$activityType}.{$this->_caseDetailExtra['case_activity_all_dates']['name']}) ORDER BY civireport_activity_all_{$activityType}.{$this->_caseDetailExtra['case_activity_all_dates']['name']}) as case_activity_all_dates";

      $from[] = " LEFT JOIN civicrm_case_activity civireport_case_activity_all_{$activityType} ON ( civireport_case_activity_all_{$activityType}.case_id = {$case}.id)
                        LEFT JOIN civicrm_activity civireport_activity_all_{$activityType} ON ( civireport_activity_all_{$activityType}.id = civireport_case_activity_all_{$activityType}.activity_id AND civireport_activity_all_{$activityType}.activity_type_id = {$activityType})";

      $this->_columnHeaders['case_activity_all_dates'] = array(
        'title' => $this->_caseDetailExtra['case_activity_all_dates']['title'] . ": {$this->caseActivityTypes[$activityType]}",
        'type' => CRM_Utils_Array::value('type', $this->_caseDetailExtra['case_activity_all_dates']),
      );
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

    $rows = $graphRows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);

    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  public function checkEnabledFields() {
    if (isset($this->_params['worldregion_id_value']) &&
      !empty($this->_params['worldregion_id_value'])
    ) {
      $this->_addressField = TRUE;
      $this->_worldRegionField = TRUE;
    }

    if (isset($this->_params['case_role_value'])
      && !empty($this->_params['case_role_value'])
    ) {
      $this->_relField = TRUE;
    }

    if (!empty($this->_params['activity_date_time_relative']) ||
      !empty($this->_params['activity_date_time_from']) ||
      CRM_Utils_Array::value('activity_date_time_to', $this->_params)
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
          $rows[$rowNum]['civicrm_case_status_id'] = $this->case_statuses[$value];

          $entryFound = TRUE;
        }
      }
      if (array_key_exists('civicrm_case_case_type_id', $row)) {
        if ($value = str_replace(CRM_Core_DAO::VALUE_SEPARATOR, '', $row['civicrm_case_case_type_id'])) {
          $rows[$rowNum]['civicrm_case_case_type_id'] = $this->case_types[$value];

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
            $caseRoles[$num] = $this->rel_types[$caseRole];
          }
          $rows[$rowNum]['civicrm_relationship_case_role'] = implode('; ', $caseRoles);
        }
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
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
