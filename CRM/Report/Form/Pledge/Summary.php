<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
class CRM_Report_Form_Pledge_Summary extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_totalPaid = FALSE;
  protected $_customGroupExtends = array('Pledge', 'Individual');
  protected $_customGroupGroupBy = TRUE;
  protected $_addressField = FALSE;
  protected $_emailField = FALSE;

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'no_repeat' => TRUE,
          ),
          'postal_greeting_display' =>
          array('title' => ts('Postal Greeting')),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
        'group_bys' =>
        array(
          'id' =>
          array('title' => ts('Contact ID')),
          'sort_name' =>
          array('title' => ts('Contact Name'),
          ),
        ),
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
          'email' =>
          array(
            'no_repeat' => TRUE,
            'title' => ts('email'),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_pledge' =>
      array(
        'dao' => 'CRM_Pledge_DAO_Pledge',
        'fields' =>
        array(
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => FALSE,
          ),
          'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'amount' =>
          array('title' => ts('Pledge Amount'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
            'statistics' =>
            array('sum' => ts('Aggregate Amount Pledged'),
              'count' => ts('Pledges'),
              'avg' => ts('Average'),
            ),
          ),
          'frequency_unit' =>
          array('title' => ts('Frequency Unit'),
          ),
          'installments' =>
          array('title' => ts('Installments'),
          ),
          'pledge_create_date' =>
          array('title' => ts('Pledge Made Date'),
          ),
          'start_date' =>
          array('title' => ts('Pledge Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'end_date' =>
          array('title' => ts('Pledge End Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'status_id' =>
          array('title' => ts('Pledge Status'),
          ),
        ),
        'filters' =>
        array(
          'pledge_create_date' =>
          array(
            'title' => 'Pledge Made Date',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'pledge_amount' =>
          array('title' => ts('Pledged Amount'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ),
          'currency' =>
          array('title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'sid' =>
          array(
            'name' => 'status_id',
            'title' => ts('Pledge Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('contribution_status'),
          ),
        ),
        'group_bys' =>
        array(
          'pledge_create_date' =>
          array(
            'frequency' => TRUE,
            'default' => TRUE,
            'chart' => TRUE,
          ),
          'frequency_unit' =>
          array('title' => ts('Frequency Unit'),
          ),
          'status_id' =>
          array('title' => ts('Pledge Status'),
          ),
        ),
      ),
      'civicrm_pledge_payment' =>
      array(
        'dao' => 'CRM_Pledge_DAO_PledgePayment',
        'fields' =>
        array(
          'total_paid' =>
            array(
              'title' => ts('Total Amount Paid'),
              'type' => CRM_Utils_Type::T_STRING,
              'dbAlias' => 'sum(pledge_payment_civireport.actual_amount)',
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
            'title' => ts(' Group'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'group' => TRUE,
            'options' => CRM_Core_PseudoConstant::group(),
          ),
        ),
      ),
    ) + $this->addAddressFields();

    $this->_tagFilter = TRUE;
    $this->_currencyColumn = 'civicrm_pledge_currency';
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    parent::select();
  }

  function from() {
    $this->_from = "
            FROM civicrm_pledge {$this->_aliases['civicrm_pledge']}
                 LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                      ON ({$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_pledge']}.contact_id )
                 {$this->_aclFrom} ";

    // include address field if address column is to be included
    if ($this->_addressField) {
      $this->_from .= "
                 LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                           ON ({$this->_aliases['civicrm_contact']}.id =
                               {$this->_aliases['civicrm_address']}.contact_id) AND
                               {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }

    // include email field if email column is to be included
    if ($this->_emailField) {
      $this->_from .= "
                 LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                           ON ({$this->_aliases['civicrm_contact']}.id =
                               {$this->_aliases['civicrm_email']}.contact_id) AND
                               {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }

    if(CRM_Utils_Array::value('total_paid', $this->_params['fields'])){
      $this->_from .= "
        LEFT JOIN civicrm_pledge_payment {$this->_aliases['civicrm_pledge_payment']} ON
          {$this->_aliases['civicrm_pledge']}.id = {$this->_aliases['civicrm_pledge_payment']}.pledge_id
          AND {$this->_aliases['civicrm_pledge_payment']}.status_id = 1
      ";
    }
  }

  function groupBy() {
    $this->_groupBy = "";
    $append = FALSE;

    if (is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {
              if (CRM_Utils_Array::value('chart', $field)) {
                $this->assign('chartSupported', TRUE);
              }

              if (CRM_Utils_Array::value('frequency', $table['group_bys'][$fieldName]) &&
                CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])
              ) {

                $append = "YEAR({$field['dbAlias']}),";
                if (in_array(strtolower($this->_params['group_bys_freq'][$fieldName]),
                    array('year')
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

      if (!empty($this->_statFields) &&
        (($append && count($this->_groupBy) <= 1) || (!$append)) && !$this->_having
      ) {
        $this->_rollup = " WITH ROLLUP";
      }
      $this->_groupBy = "GROUP BY " . implode(', ', $this->_groupBy) . " {$this->_rollup} ";
    }
    else {
      $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id";
    }
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    if (!$this->_having) {
      $select = "
            SELECT COUNT({$this->_aliases['civicrm_pledge']}.amount )       as count,
                   SUM({$this->_aliases['civicrm_pledge']}.amount )         as amount,
                   ROUND(AVG({$this->_aliases['civicrm_pledge']}.amount), 2) as avg
            ";

      $sql = "{$select} {$this->_from} {$this->_where}";

      $dao = CRM_Core_DAO::executeQuery($sql);

      if ($dao->fetch()) {
        $statistics['count']['amount'] = array(
          'value' => $dao->amount,
          'title' => 'Total Pledged',
          'type' => CRM_Utils_Type::T_MONEY,
        );
        $statistics['count']['count '] = array(
          'value' => $dao->count,
          'title' => 'Total No Pledges',
        );
        $statistics['count']['avg   '] = array(
          'value' => $dao->avg,
          'title' => 'Average',
          'type' => CRM_Utils_Type::T_MONEY,
        );
      }
    }
    return $statistics;
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value",
                  $this->_params
                ),
                CRM_Utils_Array::value("{$fieldName}_min",
                  $this->_params
                ),
                CRM_Utils_Array::value("{$fieldName}_max",
                  $this->_params
                )
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
      $this->_where = "WHERE ({$this->_aliases['civicrm_pledge']}.is_test=0 ) ";
    }
    else {
      $this->_where = "WHERE  ({$this->_aliases['civicrm_pledge']}.is_test=0 )  AND
                                      " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function postProcess() {
    parent::postProcess();
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound   = FALSE;
    $checkList    = array();
    $display_flag = $prev_cid = $cid = 0;
    crm_Core_error::Debug('$rows', $rows);
    foreach ($rows as $rowNum => $row) {

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      //handle status id
      if (array_key_exists('civicrm_pledge_status_id', $row)) {
        if ($value = $row['civicrm_pledge_status_id']) {
          $rows[$rowNum]['civicrm_pledge_status_id'] = CRM_Contribute_PseudoConstant::contributionStatus($value);
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'pledge/summary', 'List all pledge(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }
}

