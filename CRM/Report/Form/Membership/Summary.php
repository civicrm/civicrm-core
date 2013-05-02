<?php
// $Id$

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
class CRM_Report_Form_Membership_Summary extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );
  
  function __construct() {
    // UI for selecting columns to appear in the report list
    // array conatining the columns, group_bys and filters build and provided to Form
    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name' =>
          array('title' => ts('Member Name'),
            'no_repeat' => TRUE,
            'required' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'group_bys' =>
        array(
          'id' =>
          array('title' => ts('Contact ID')),
          'display_name' =>
          array('title' => ts('Contact Name'),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership_type' =>
      array(
        'dao' => 'CRM_Member_DAO_MembershipType',
        'grouping' => 'member-fields',
        'filters' =>
        array(
          'gid' =>
          array(
            'name' => 'id',
            'title' => ts('Membership Types'),
            'type' => CRM_Utils_Type::T_INT + CRM_Utils_Type::T_ENUM,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ),
        ),
      ),
      'civicrm_membership' =>
      array(
        'dao' => 'CRM_Member_DAO_Membership',
        'grouping' => 'member-fields',
        'fields' =>
        array(
          'membership_type_id' =>
          array(
            'title' => 'Membership Type',
            'required' => TRUE,
          ),
          'join_date' => NULL,
          'start_date' => array('title' => ts('Current Cycle Start Date'),
          ),
          'end_date' => array('title' => ts('Current Cycle End Date'),
          ),
        ),
        'group_bys' =>
        array(
          'membership_type_id' =>
          array('title' => ts('Membership Type')),
        ),
        'filters' =>
        array(
          'join_date' =>
          array('type' => CRM_Utils_Type::T_DATE),
        ),
      ),
      'civicrm_address' =>
      array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' =>
          array('title' => ts('State/Province'),
          ),
          'country_id' =>
          array('title' => ts('Country'),
            'default' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array('email' => NULL),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'filters' =>
        array(
          'total_amount' =>
          array('title' => ts('Contribution Amount'),
          ),
        ),
      ),
    );
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Membership Summary Report'));
    parent::preProcess();
  }

  function setDefaultValues() {
    return parent::setDefaultValues();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            // to include optional columns address and email, only if checked
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    //check for searching combination of dispaly columns and
    //grouping criteria

    return $errors;
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "
FROM       civicrm_contact    {$this->_aliases['civicrm_contact']}
INNER JOIN civicrm_membership {$this->_aliases['civicrm_membership']} 
       ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_membership']}.contact_id
LEFT  JOIN civicrm_membership_type  {$this->_aliases['civicrm_membership_type']} 
       ON {$this->_aliases['civicrm_membership']}.membership_type_id = {$this->_aliases['civicrm_membership_type']}.id
LEFT  JOIN civicrm_contribution  {$this->_aliases['civicrm_contribution']} 
       ON {$this->_aliases['civicrm_membership']}.contact_id = {$this->_aliases['civicrm_contribution']}.contact_id
";
    //  include address field if address column is to be included
    if ($this->_addressField) {
      $this->_from .= "LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }

    // include email field if email column is to be included
    if ($this->_emailField) {
      $this->_from .= "LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($field['type'] & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to);
            }
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

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }
  }

  function statistics(&$rows) {
    $statistics = array();
    $statistics[] = array('title' => ts('Row(s) Listed'),
      'value' => count($rows),
    );
    return $statistics;
  }

  function groupBy() {
    $this->_groupBy = "";
    if (is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {
              $this->_groupBy[] = $field['dbAlias'];
            }
          }
        }
      }

      if (!empty($this->_statFields) &&
        (($append && count($this->_groupBy) <= 1) || (!$append))
      ) {
        $this->_rollup = " WITH ROLLUP";
      }
      $this->_groupBy = "GROUP BY " . implode(', ', $this->_groupBy) . " {$this->_rollup} ";
    }
    else {
      $this->_groupBy = "GROUP BY contact.id";
    }
  }

  function postProcess() {
    $this->_params = $this->controller->exportValues($this->_name);
    if (empty($this->_params) &&
      $this->_force
    ) {
      $this->_params = $this->_formValues;
    }
    $this->_formValues = $this->_params;

    $this->processReportMode();

    $this->select();

    $this->from();

    $this->where();

    $this->groupBy();

    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy}";

    $dao   = CRM_Core_DAO::executeQuery($sql);
    $rows  = $graphRows = array();
    $count = 0;
    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        $row[$key] = $dao->$key;
      }

      if (CRM_Utils_Array::value('charts', $this->_params) &&
        $row['civicrm_contribution_receive_date_subtotal']
      ) {
        $graphRows['receive_date'][] = $row['civicrm_contribution_receive_date_start'];
        $graphRows[$this->_interval][] = $row['civicrm_contribution_receive_date_interval'];
        $graphRows['value'][] = $row['civicrm_contribution_total_amount_sum'];
        $count++;
      }

      $rows[] = $row;
    }
    $this->formatDisplay($rows);

    $this->assign_by_ref('columnHeaders', $this->_columnHeaders);
    $this->assign_by_ref('rows', $rows);
    $this->assign('statistics', $this->statistics($rows));

    if (CRM_Utils_Array::value('charts', $this->_params)) {
      foreach (array(
        'receive_date', $this->_interval, 'value') as $ignore) {
        unset($graphRows[$ignore][$count - 1]);
      }

      // build chart.
      CRM_Utils_OpenFlashChart::chart($graphRows, $this->_params['charts'], $this->_interval);
    }
    parent::endPostProcess();
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $checkList = array();

    foreach ($rows as $rowNum => $row) {

      if (!empty($this->_noRepeats)) {
        // not repeat contact display names if it matches with the one
        // in previous row

        $repeatFound = FALSE;
        foreach ($row as $colName => $colVal) {
          if (is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
            $repeatFound = TRUE;
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      //handle the Membership Type Ids
      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // handle state province
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // handle country
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/report/member/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name'] = "<a href='$url'>" . $row["civicrm_contact_sort_name"] . '</a>';
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }
}

