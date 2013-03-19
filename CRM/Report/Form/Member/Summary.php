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
class CRM_Report_Form_Member_Summary extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_interval = NULL;
  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );
  protected $_add2groupSupported = FALSE;

  protected $_customGroupExtends = array('Membership');
  protected $_customGroupGroupBy = FALSE; 
  public $_drilldownReport = array('member/detail' => 'Link to Detail Report');

  function __construct() {
    // UI for selecting columns to appear in the report list
    // array conatining the columns, group_bys and filters build and provided to Form

    $this->_columns = array(
      'civicrm_membership' =>
      array(
        'dao' => 'CRM_Member_DAO_MembershipType',
        'grouping' => 'member-fields',
        'fields' =>
        array(
          'membership_type_id' =>
          array(
            'title' => 'Membership Type',
            'required' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'join_date' =>
          array('title' => ts('Member Since'),
            'type' => CRM_Utils_Type::T_DATE,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'membership_start_date' =>
          array(
            'name' => 'start_date',
            'title' => ts('Membership Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'membership_end_date' =>
          array(
            'name' => 'end_date',
            'title' => ts('Membership End Date'),
            'type' => CRM_Utils_Type::T_DATE,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'membership_type_id' =>
          array('title' => ts('Membership Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ),
          'status_id' =>
          array('title' => ts('Membership Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ),
        ),
        'group_bys' =>
        array(
          'join_date' =>
          array('title' => ts('Member Since'),
            'default' => TRUE,
            'frequency' => TRUE,
            'chart' => TRUE,
            'type' => 12,
          ),
          'membership_type_id' =>
          array(
            'title' => 'Membership Type',
            'default' => TRUE,
            'chart' => TRUE,
          ),
        ),
      ),
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'contact_id' =>
          array(
            'no_display' => TRUE,
          ),
        ),
      ),
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'currency' =>
          array('required' => TRUE,
            'no_display' => TRUE,
          ),
          'total_amount' =>
          array('title' => ts('Amount Statistics'),
            'required' => TRUE,
            'statistics' =>
            array('sum' => ts('Total Payments Made'),
              'count' => ts('Contribution Count'),
              'avg' => ts('Average'),
            ),
          ),
        ),
        'filters' =>
        array(
          'currency' =>
          array('title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'contribution_status_id' =>
          array('title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
          ),
        ),
        'grouping' => 'member-fields',
      ),
    );
    $this->_tagFilter = TRUE;
    $this->_groupFilter = TRUE;
    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  function select() {
    $select = array();
    $groupBys = FALSE;
    $this->_columnHeaders = array();
    $select[] = " COUNT( DISTINCT {$this->_aliases['civicrm_membership']}.id ) as civicrm_membership_member_count";
    $select['joinDate'] = " {$this->_aliases['civicrm_membership']}.join_date  as civicrm_membership_member_join_date";
    $this->_columnHeaders["civicrm_membership_member_join_date"] = array('title' => ts('Member Since'),
      'type' => CRM_Utils_Type::T_DATE,
    );
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {

            switch (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
              case 'YEARWEEK':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";

                $select[]       = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[]       = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Week';
                break;

              case 'YEAR':
                $select[]       = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                $select[]       = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[]       = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Year';
                break;

              case 'MONTH':
                $select[]       = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                $select[]       = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[]       = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Month';
                break;

              case 'QUARTER':
                $select[]       = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                $select[]       = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[]       = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Quarter';
                break;
            }
            if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
              $this->_interval = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title'] = $field['title'] . ' Beginning';
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['group_by'] = $this->_params['group_bys_freq'][$fieldName];

              // just to make sure these values are transfered to rows.
              // since we need that for calculation purpose,
              // e.g making subtotals look nicer or graphs
              $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = array('no_display' => TRUE);
              $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = array('no_display' => TRUE);
            }
            $groupBys = TRUE;
          }
        }
      }
      // end of select

      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {

            // only include statistics columns if set
            if (CRM_Utils_Array::value('statistics', $field)) {
              $this->_statFields[] = 'civicrm_membership_member_count';
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'sum':
                    $select[] = "IFNULL(SUM({$field['dbAlias']}), 0) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'count':
                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'avg':
                    $select[] = "IFNULL(ROUND(AVG({$field['dbAlias']}),2), 0) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            }
            elseif ($fieldName == 'membership_type_id') {
              if (!CRM_Utils_Array::value('membership_type_id', $this->_params['group_bys']) &&
                CRM_Utils_Array::value('join_date', $this->_params['group_bys'])
              ) {
                $select[] = "GROUP_CONCAT(DISTINCT {$field['dbAlias']}  ORDER BY {$field['dbAlias']} ) as {$tableName}_{$fieldName}";
              }
              else {
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              }
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['operatorType'] = CRM_Utils_Array::value('operatorType', $field);
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['operatorType'] = CRM_Utils_Array::value('operatorType', $field);
            }
          }
        }
      }
      $this->_columnHeaders["civicrm_membership_member_count"] = array('title' => ts('Member Count'),
        'type' => CRM_Utils_Type::T_INT,
      );
    }
    //If grouping is availabled then remove join date from field
    if ($groupBys) {
      unset($select['joinDate']);
      unset($this->_columnHeaders["civicrm_membership_member_join_date"]);
    }
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = "
        FROM  civicrm_membership {$this->_aliases['civicrm_membership']}
               
              LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON ( {$this->_aliases['civicrm_membership']}.contact_id = {$this->_aliases['civicrm_contact']}.id )  
               
              LEFT JOIN civicrm_membership_status 
                        ON ({$this->_aliases['civicrm_membership']}.status_id = civicrm_membership_status.id  )
              LEFT JOIN civicrm_membership_payment payment
                        ON ( {$this->_aliases['civicrm_membership']}.id = payment.membership_id )
              LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} 
                         ON payment.contribution_id = {$this->_aliases['civicrm_contribution']}.id";
  }
  // end of from

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if ($field['operatorType'] & CRM_Utils_Type::T_DATE) {
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

    if (!empty($clauses)) {
      $this->_where = "WHERE {$this->_aliases['civicrm_membership']}.is_test = 0 AND " . implode(' AND ', $clauses);
    }
    else {
      $this->_where = "WHERE {$this->_aliases['civicrm_membership']}.is_test = 0";
    }
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

      $this->_rollup = ' WITH ROLLUP';
      $this->_groupBy = 'GROUP BY ' . implode(', ', $this->_groupBy) . " {$this->_rollup} ";
    }
    else {
      $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_membership']}.join_date";
    }
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $select = "
        SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount ) as count,
               IFNULL(SUM({$this->_aliases['civicrm_contribution']}.total_amount ), 0) as amount,
               IFNULL(ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2),0) as avg,
               COUNT( DISTINCT {$this->_aliases['civicrm_membership']}.id ) as memberCount,
               {$this->_aliases['civicrm_contribution']}.currency as currency
        ";

    $sql = "{$select} {$this->_from} {$this->_where}
GROUP BY    {$this->_aliases['civicrm_contribution']}.currency 
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    
    $totalAmount = $average = array();
    $count = $memberCount = 0;
    while ($dao->fetch()) {
      $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency)."(".$dao->count.")";
      $average[] =   CRM_Utils_Money::format($dao->avg, $dao->currency);
      $count += $dao->count;
      $memberCount += $dao->memberCount;
    }
    $statistics['counts']['amount'] = array(
      'title' => ts('Total Amount'),
      'value' => implode(',  ', $totalAmount),
      'type' => CRM_Utils_Type::T_STRING,
    );
    $statistics['counts']['count'] = array(
      'title' => ts('Total Donations'),
      'value' => $count,
    );
    $statistics['counts']['memberCount'] = array(
      'title' => ts('Total Members'),
      'value' => $memberCount,
    );
    $statistics['counts']['avg'] = array(
      'title' => ts('Average'),
      'value' => implode(',  ', $average),
      'type' => CRM_Utils_Type::T_STRING,
    );

    if (!(int)$statistics['counts']['amount']['value']) {
      //if total amount is zero then hide Chart Options
      $this->assign('chartSupported', FALSE);
    }

    return $statistics;
  }

  function postProcess() {
    parent::postProcess();
  }

  function buildChart(&$rows) {
    $graphRows = array();
    $count = 0;
    $membershipTypeValues = CRM_Member_PseudoConstant::membershipType();
    $isMembershipType = CRM_Utils_Array::value('membership_type_id', $this->_params['group_bys']);
    $isJoiningDate = CRM_Utils_Array::value('join_date', $this->_params['group_bys']);
    if (CRM_Utils_Array::value('charts', $this->_params)) {
      foreach ($rows as $key => $row) {
        if (!($row['civicrm_membership_join_date_subtotal'] &&
            $row['civicrm_membership_membership_type_id']
          )) {
          continue;
        }
        if ($isMembershipType) {
          $join_date = CRM_Utils_Array::value('civicrm_membership_join_date_start', $row);
          $displayInterval = CRM_Utils_Array::value('civicrm_membership_join_date_interval', $row);
          if ($join_date) {
            list($year, $month) = explode('-', $join_date);
          }
          if (CRM_Utils_Array::value('civicrm_membership_join_date_subtotal', $row)) {

            switch ($this->_interval) {
              case 'Month':
                $displayRange = $displayInterval . ' ' . $year;
                break;

              case 'Quarter':
                $displayRange = 'Quarter ' . $displayInterval . ' of ' . $year;
                break;

              case 'Week':
                $displayRange = 'Week ' . $displayInterval . ' of ' . $year;
                break;

              case 'Year':
                $displayRange = $year;
                break;
            }
            $membershipType = $displayRange . "-" . $membershipTypeValues[$row['civicrm_membership_membership_type_id']];
          }
          else {

            $membershipType = $membershipTypeValues[$row['civicrm_membership_membership_type_id']];
          }

          $interval[$membershipType] = $membershipType;
          $display[$membershipType] = $row['civicrm_contribution_total_amount_sum'];
        }
        else {
          $graphRows['receive_date'][] = CRM_Utils_Array::value('civicrm_membership_join_date_start', $row);
          $graphRows[$this->_interval][] = CRM_Utils_Array::value('civicrm_membership_join_date_interval', $row);
          $graphRows['value'][] = $row['civicrm_contribution_total_amount_sum'];
          $count++;
        }
      }

      // build chart.
      if ($isMembershipType) {
        $graphRows['value'] = $display;
        $chartInfo = array(
          'legend' => 'Membership Summary',
          'xname' => 'Member Since / Member Type',
          'yname' => 'Fees',
        );
        CRM_Utils_OpenFlashChart::reportChart($graphRows, $this->_params['charts'], $interval, $chartInfo);
      }
      else {
        CRM_Utils_OpenFlashChart::chart($graphRows, $this->_params['charts'], $this->_interval);
      }
    }
    $this->assign('chartType', $this->_params['charts']);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      if (CRM_Utils_Array::value('join_date', $this->_params['group_bys']) &&
        CRM_Utils_Array::value('civicrm_membership_join_date_start', $row) &&
        $row['civicrm_membership_join_date_start'] &&
        $row['civicrm_membership_join_date_subtotal']
      ) {

        $dateStart = CRM_Utils_Date::customFormat($row['civicrm_membership_join_date_start'], '%Y%m%d');
        $endDate   = new DateTime($dateStart);
        $dateEnd   = array();

        list($dateEnd['Y'], $dateEnd['M'], $dateEnd['d']) = explode(':', $endDate->format('Y:m:d'));

        switch (strtolower($this->_params['group_bys_freq']['join_date'])) {
          case 'month':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'] + 1,
                $dateEnd['d'] - 1, $dateEnd['Y']
              ));
            break;

          case 'year':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'],
                $dateEnd['d'] - 1, $dateEnd['Y'] + 1
              ));
            break;

          case 'yearweek':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'],
                $dateEnd['d'] + 6, $dateEnd['Y']
              ));
            break;

          case 'quarter':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'] + 3,
                $dateEnd['d'] - 1, $dateEnd['Y']
              ));
            break;
        }
        $typeUrl = '';
        if (CRM_Utils_Array::value('membership_type_id', $this->_params['group_bys']) &&
          $typeID = $row['civicrm_membership_membership_type_id']
        ) {
          $typeUrl = "&tid_op=in&tid_value={$typeID}";
        }
        $statusUrl = '';
        if (!empty($this->_params['status_id_value'])) {
          $statusUrl = "&sid_op=in&sid_value=" . implode(",", $this->_params['status_id_value']);
        }
        $url = CRM_Report_Utils_Report::getNextUrl('member/detail',
          "reset=1&force=1&join_date_from={$dateStart}&join_date_to={$dateEnd}{$typeUrl}{$statusUrl}",
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $row['civicrm_membership_join_date_start'] = CRM_Utils_Date::format($row['civicrm_membership_join_date_start']);
        $rows[$rowNum]['civicrm_membership_join_date_start_link'] = $url;
        $rows[$rowNum]['civicrm_membership_join_date_start_hover'] = ts("Lists Summary of Memberships for this date unit.");

        $entryFound = TRUE;
      }

      // handle Membership Types
      if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
        if ($value = $row['civicrm_membership_membership_type_id']) {
          $value = explode(',', $value);
          foreach ($value as $key => $id) {
            $value[$key] = CRM_Member_PseudoConstant::membershipType($id, FALSE);
          }
          $rows[$rowNum]['civicrm_membership_membership_type_id'] = implode(' , ', $value);
        }
        $entryFound = TRUE;
      }

      // make subtotals look nicer
      if (array_key_exists('civicrm_membership_join_date_subtotal', $row) &&
        !$row['civicrm_membership_join_date_subtotal']
      ) {
        $this->fixSubTotalDisplay($rows[$rowNum], $this->_statFields);
        $entryFound = TRUE;
      }
      elseif (array_key_exists('civicrm_membership_join_date_subtotal', $row) &&
        $row['civicrm_membership_join_date_subtotal'] &&
        !$row['civicrm_membership_membership_type_id']
      ) {
        $this->fixSubTotalDisplay($rows[$rowNum], $this->_statFields, FALSE);
        $rows[$rowNum]['civicrm_membership_membership_type_id'] = '<b>SubTotal</b>';
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

