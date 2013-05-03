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
class CRM_Report_Form_Pledge_Pbnp extends CRM_Report_Form {
  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );
  public $_drilldownReport = array('pledge/summary' => 'Link to Detail Report');

  protected $_customGroupExtends = array(
    'Pledge');

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name' =>
          array('title' => ts('Constituent Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_pledge' =>
      array(
        'dao' => 'CRM_Pledge_DAO_Pledge',
        'fields' =>
        array(
          'pledge_create_date' =>
          array('title' => ts('Pledge Made'),
            'required' => TRUE,
          ),
          'financial_type_id' =>
          array('title' => ts('Financial Type'),
            'requried' => TRUE,
          ),
          'amount' =>
          array('title' => ts('Amount'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
          ),
          'currency' =>
          array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'status_id' =>
          array('title' => ts('Status'),
          ),
        ),
        'filters' =>
        array(
          'pledge_create_date' =>
          array(
            'title' => ts('Pledge Made'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'currency' =>
          array('title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'financial_type_id' =>
          array( 'title'   => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options'      => CRM_Contribute_PseudoConstant::financialType(),
          ),
        ),
        'grouping' => 'pledge-fields',
      ),
      'civicrm_pledge_payment' =>
      array(
        'dao' => 'CRM_Pledge_DAO_PledgePayment',
        'fields' =>
        array(
          'scheduled_date' =>
          array('title' => ts('Next Payment Due'),
            'type' => CRM_Utils_Type::T_DATE,
            'required' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'scheduled_date' =>
          array(
            'title' => ts('Next Payment Due'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
        'grouping' => 'pledge-fields',
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
    $this->_currencyColumn = 'civicrm_pledge_currency';
    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', ts('Pledge But Not Paid Report'));
    parent::preProcess();
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
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
          }
        }
      }
    }
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = NULL;

    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $pendingStatus = array_search('Pending', $allStatus);
    foreach (array(
      'Pending', 'In Progress', 'Overdue') as $statusKey) {
      if ($key = CRM_Utils_Array::key($statusKey, $allStatus)) {
        $unpaidStatus[] = $key;
      }
    }

    $statusIds = implode(', ', $unpaidStatus);

    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
             INNER JOIN civicrm_pledge  {$this->_aliases['civicrm_pledge']}
                        ON ({$this->_aliases['civicrm_pledge']}.contact_id =
                            {$this->_aliases['civicrm_contact']}.id)  AND
                            {$this->_aliases['civicrm_pledge']}.status_id IN ( {$statusIds} )
             LEFT  JOIN civicrm_pledge_payment {$this->_aliases['civicrm_pledge_payment']}
                        ON ({$this->_aliases['civicrm_pledge']}.id =
                            {$this->_aliases['civicrm_pledge_payment']}.pledge_id AND  {$this->_aliases['civicrm_pledge_payment']}.status_id = {$pendingStatus} ) ";

    // include address field if address column is to be included
    if ($this->_addressField) {
      $this->_from .= "
             LEFT  JOIN civicrm_address {$this->_aliases['civicrm_address']}
                        ON ({$this->_aliases['civicrm_contact']}.id =
                            {$this->_aliases['civicrm_address']}.contact_id) AND
                            {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }

    // include email field if email column is to be included
    if ($this->_emailField) {
      $this->_from .= "
            LEFT  JOIN civicrm_email {$this->_aliases['civicrm_email']}
                       ON ({$this->_aliases['civicrm_contact']}.id =
                           {$this->_aliases['civicrm_email']}.contact_id) AND
                           {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }
  }

  function groupBy() {
    $this->_groupBy = "
         GROUP BY {$this->_aliases['civicrm_pledge']}.contact_id,
                  {$this->_aliases['civicrm_pledge']}.id,
                  {$this->_aliases['civicrm_pledge']}.currency";
  }

  function orderBy() {
    $this->_orderBy = "ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_pledge']}.contact_id, {$this->_aliases['civicrm_pledge']}.id";
  }

  function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::PostProcess();
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound   = FALSE;
    $checkList    = array();
    $display_flag = $prev_cid = $cid = 0;

    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (array_key_exists('civicrm_contact_id', $row)) {
          if ($cid = $row['civicrm_contact_id']) {
            if ($rowNum == 0) {
              $prev_cid = $cid;
            }
            else {
              if ($prev_cid == $cid) {
                $display_flag = 1;
                $prev_cid = $cid;
              }
              else {
                $display_flag = 0;
                $prev_cid = $cid;
              }
            }

            if ($display_flag) {
              foreach ($row as $colName => $colVal) {
                if (in_array($colName, $this->_noRepeats)) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }

      //handle the Financial Type Ids
      if (array_key_exists('civicrm_pledge_financial_type_id', $row)) {
        if ($value = $row['civicrm_pledge_financial_type_id']) {
          $rows[$rowNum]['civicrm_pledge_financial_type_id'] =
            CRM_Contribute_PseudoConstant::financialType($value, false);
        }
        $entryFound = TRUE;
      }

      //handle the Status Ids
      if (array_key_exists('civicrm_pledge_status_id', $row)) {
        if ($value = $row['civicrm_pledge_status_id']) {
          $rows[$rowNum]['civicrm_pledge_status_id'] = CRM_Contribute_PseudoConstant::contributionStatus($value);
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
        $url = CRM_Report_Utils_Report::getNextUrl('pledge/summary',
          'reset=1&force=1&id_op=eq&id_value=' .
          $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Pledge Details for this contact");
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

