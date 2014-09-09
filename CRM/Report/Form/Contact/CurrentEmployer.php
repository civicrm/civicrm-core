<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Report_Form_Contact_CurrentEmployer extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupExtends = array(
    'Contact', 'Individual');

  public $_drilldownReport = array('contact/detail' => 'Link to Detail Report');

  /**
   *
   */
  /**
   *
   */
  function __construct() {

    $this->_columns = array(
      'civicrm_employer' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'organization_name' =>
          array('title' => ts('Employer Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'organization_name' =>
          array('title' => ts('Employer Name'),
            'operatorType' => CRM_Report_Form::OP_STRING,
          ),
        ),
      ),
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name' =>
          array('title' => ts('Employee Name'),
            'required' => TRUE,
          ),
      'first_name' => array('title' => ts('First Name'),
          ),
      'last_name' => array('title' => ts('Last Name'),
          ),
          'job_title' =>
          array('title' => ts('Job Title'),
            'default' => TRUE,
          ),
          'gender_id' =>
          array('title' => ts('Gender'),
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_type' =>
          array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' =>
          array(
            'title' => ts('Contact SubType'),
          ),
        ),
        'filters' =>
        array(
          'sort_name' =>
          array('title' => ts('Employee Name')),
          'id' =>
          array('no_display' => TRUE),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_relationship' =>
      array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' =>
        array(
          'start_date' =>
          array('title' => ts('Employee Since'),
            'default' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'start_date' =>
          array('title' => ts('Employee Since'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
      ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'grouping' => 'contact-fields',
        'fields' =>
        array(
          'phone' =>
          array('title' => ts('Phone'),
            'default' => TRUE,
          ),
        ),
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'grouping' => 'contact-fields',
        'fields' =>
        array(
          'email' =>
          array('title' => ts('Email'),
            'default' => TRUE,
          ),
        ),
      ),
      'civicrm_address' =>
      array(
        'dao' => 'CRM_Core_DAO_Address',
        'grouping' => 'contact-fields',
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
          ),
        ),
        'filters' =>
        array(
          'country_id' =>
          array('title' => ts('Country'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(NULL, FALSE),
          ),
          'state_province_id' =>
          array('title' => ts('State/Province'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
          ),
        ),
      ),
      'civicrm_group' =>
      array(
        'dao' => 'CRM_Contact_DAO_Group',
        'alias' => 'cgroup',
        'filters' =>
        array(
          'gid' =>
          array(
            'name' => 'group_id',
            'title' => ts('Group'),
            'group' => TRUE,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::staticGroup(),
          ),
        ),
      ),
    );

    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {

    $select = $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) || !empty($this->_params['fields'][$fieldName])) {

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = "
FROM civicrm_contact {$this->_aliases['civicrm_contact']}

     LEFT JOIN civicrm_contact {$this->_aliases['civicrm_employer']}
          ON {$this->_aliases['civicrm_employer']}.id={$this->_aliases['civicrm_contact']}.employer_id

     {$this->_aclFrom}
     LEFT JOIN civicrm_relationship {$this->_aliases['civicrm_relationship']}
          ON ( {$this->_aliases['civicrm_relationship']}.contact_id_a={$this->_aliases['civicrm_contact']}.id
              AND {$this->_aliases['civicrm_relationship']}.contact_id_b={$this->_aliases['civicrm_contact']}.employer_id
              AND {$this->_aliases['civicrm_relationship']}.relationship_type_id=4)
     LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
          ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id
             AND {$this->_aliases['civicrm_address']}.is_primary = 1 )

     LEFT JOIN  civicrm_phone {$this->_aliases['civicrm_phone']}
          ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id
             AND {$this->_aliases['civicrm_phone']}.is_primary = 1)
     LEFT JOIN  civicrm_email {$this->_aliases['civicrm_email']}
          ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id
             AND {$this->_aliases['civicrm_email']}.is_primary = 1) ";
  }

  function where() {

    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Report_Form::OP_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

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
            $clauses[$fieldName] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE {$this->_aliases['civicrm_contact']}.employer_id!='null' ";
    }
    else {
      $this->_where = "WHERE ({$this->_aliases['civicrm_contact']}.employer_id!='null') AND " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_employer']}.id,{$this->_aliases['civicrm_contact']}.id";
  }

  function orderBy() {
    $this->_orderBy = "ORDER BY {$this->_aliases['civicrm_employer']}.organization_name, {$this->_aliases['civicrm_contact']}.display_name";
  }

  function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause(array($this->_aliases['civicrm_contact'], $this->_aliases['civicrm_employer']));
    parent::postProcess();
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {
    // custom code to alter rows
    $checkList = array();
    $entryFound = FALSE;

    foreach ($rows as $rowNum => $row) {

      // convert employer name to links
      if (array_key_exists('civicrm_employer_organization_name', $row) &&
        array_key_exists('civicrm_employer_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_employer_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_employer_organization_name_link'] = $url;
        $entryFound = TRUE;
      }

      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact display names if it matches with the one
        // in previous row

        foreach ($row as $colName => $colVal) {
          if (!empty($checkList[$colName]) && is_array($checkList[$colName]) &&
            in_array($colVal, $checkList[$colName])
          ) {
            $rows[$rowNum][$colName] = "";
          }
          if (in_array($colName, $this->_noRepeats)) {
            $checkList[$colName][] = $colVal;
          }
        }
      }

      //handle gender
      if (array_key_exists('civicrm_contact_gender_id', $row)) {
        if ($value = $row['civicrm_contact_gender_id']) {
          $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
          $rows[$rowNum]['civicrm_contact_gender_id'] = $gender[$value];
        }
        $entryFound = TRUE;
      }

      // convert employee name to links
      if (array_key_exists('civicrm_contact_display_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $entryFound = TRUE;
      }

      // handle country
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

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }
}
