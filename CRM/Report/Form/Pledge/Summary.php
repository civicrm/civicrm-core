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
 * $Id$
 *
 */
class CRM_Report_Form_Pledge_Summary extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_totalPaid = FALSE;
  protected $_customGroupExtends = ['Pledge', 'Individual'];
  protected $_customGroupGroupBy = TRUE;

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * CRM-19170
   *
   * @var bool
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
            'no_repeat' => TRUE,
          ],
          'postal_greeting_display' => ['title' => ts('Postal Greeting')],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
        'group_bys' => [
          'id' => ['title' => ts('Contact ID')],
          'sort_name' => [
            'title' => ts('Contact Name'),
          ],
        ],
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'no_repeat' => TRUE,
            'title' => ts('email'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_pledge' => [
        'dao' => 'CRM_Pledge_DAO_Pledge',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => FALSE,
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
          ],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'amount' => [
            'title' => ts('Pledge Amount'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
            'statistics' => [
              'sum' => ts('Aggregate Amount Pledged'),
              'count' => ts('Pledges'),
              'avg' => ts('Average'),
            ],
          ],
          'frequency_unit' => [
            'title' => ts('Frequency Unit'),
          ],
          'installments' => [
            'title' => ts('Installments'),
          ],
          'pledge_create_date' => [
            'title' => ts('Pledge Made Date'),
          ],
          'start_date' => [
            'title' => ts('Pledge Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'end_date' => [
            'title' => ts('Pledge End Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'status_id' => [
            'title' => ts('Pledge Status'),
          ],
        ],
        'filters' => [
          'pledge_create_date' => [
            'title' => ts('Pledge Made Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'pledge_amount' => [
            'title' => ts('Pledged Amount'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'currency' => [
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'sid' => [
            'name' => 'status_id',
            'title' => ts('Pledge Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('pledge_status'),
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ],
        ],
        'group_bys' => [
          'pledge_create_date' => [
            'frequency' => TRUE,
            'default' => TRUE,
            'chart' => TRUE,
          ],
          'frequency_unit' => [
            'title' => ts('Frequency Unit'),
          ],
          'status_id' => [
            'title' => ts('Pledge Status'),
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
          ],
        ],
      ],
      'civicrm_pledge_payment' => [
        'dao' => 'CRM_Pledge_DAO_PledgePayment',
        'fields' => [
          'total_paid' => [
            'title' => ts('Total Amount Paid'),
            'type' => CRM_Utils_Type::T_STRING,
            'dbAlias' => 'sum(pledge_payment_civireport.actual_amount)',
          ],
        ],
      ],
    ] + $this->addAddressFields();

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->_currencyColumn = 'civicrm_pledge_currency';
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    parent::select();
  }

  public function from() {
    $this->_from = "
            FROM civicrm_pledge {$this->_aliases['civicrm_pledge']}
                 LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                      ON ({$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_pledge']}.contact_id )
                 {$this->_aclFrom} ";

    $this->joinAddressFromContact();
    $this->joinEmailFromContact();

    if (!empty($this->_params['fields']['total_paid'])) {
      $this->_from .= "
        LEFT JOIN civicrm_pledge_payment {$this->_aliases['civicrm_pledge_payment']} ON
          {$this->_aliases['civicrm_pledge']}.id = {$this->_aliases['civicrm_pledge_payment']}.pledge_id
          AND {$this->_aliases['civicrm_pledge_payment']}.status_id = 1
      ";
    }
  }

  public function groupBy() {
    $this->_groupBy = "";
    $append = FALSE;

    if (is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (!empty($this->_params['group_bys'][$fieldName])) {
              if (!empty($field['chart'])) {
                $this->assign('chartSupported', TRUE);
              }

              if (!empty($table['group_bys'][$fieldName]['frequency']) &&
                !empty($this->_params['group_bys_freq'][$fieldName])
              ) {

                $append = "YEAR({$field['dbAlias']}),";
                if (in_array(strtolower($this->_params['group_bys_freq'][$fieldName]),
                  ['year']
                )) {
                  $append = '';
                }
                $this->_groupByArray[] = "$append {$this->_params['group_bys_freq'][$fieldName]}({$field['dbAlias']})";
                $append = TRUE;
              }
              else {
                $this->_groupByArray[] = $field['dbAlias'];
              }
            }
          }
        }
      }

      if (!empty($this->_statFields) &&
        (($append && count($this->_groupByArray) <= 1) || (!$append)) &&
        !$this->_having
      ) {
        $this->_rollup = " WITH ROLLUP";
      }
      $groupBy = $this->_groupByArray;
      $this->_groupBy = "GROUP BY " . implode(', ', $this->_groupByArray);
    }
    else {
      $groupBy = "{$this->_aliases['civicrm_contact']}.id";
      $this->_groupBy = "GROUP BY {$groupBy}";
    }
    $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect($this->_selectClauses, $groupBy);
    $this->_groupBy .= " {$this->_rollup}";
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
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
        $statistics['count']['amount'] = [
          'value' => $dao->amount,
          'title' => ts('Total Pledged'),
          'type' => CRM_Utils_Type::T_MONEY,
        ];
        $statistics['count']['count '] = [
          'value' => $dao->count,
          'title' => ts('Total No Pledges'),
        ];
        $statistics['count']['avg   '] = [
          'value' => $dao->avg,
          'title' => ts('Average'),
          'type' => CRM_Utils_Type::T_MONEY,
        ];
      }
    }
    return $statistics;
  }

  public function where() {
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

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

  public function postProcess() {
    parent::postProcess();
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
    $checkList = [];
    $display_flag = $prev_cid = $cid = 0;
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

      if (array_key_exists('civicrm_pledge_financial_type_id', $row)) {
        if ($value = $row['civicrm_pledge_financial_type_id']) {
          $rows[$rowNum]['civicrm_pledge_financial_type_id'] = CRM_Contribute_PseudoConstant::financialType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      //handle status id
      if (array_key_exists('civicrm_pledge_status_id', $row)) {
        if ($value = $row['civicrm_pledge_status_id']) {
          $rows[$rowNum]['civicrm_pledge_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Pledge_BAO_Pledge', 'status_id', $value);
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
