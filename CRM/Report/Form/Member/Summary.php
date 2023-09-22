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
class CRM_Report_Form_Member_Summary extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_interval = NULL;

  protected $_add2groupSupported = FALSE;

  protected $_customGroupExtends = ['Membership'];
  protected $_customGroupGroupBy = FALSE;
  public $_drilldownReport = ['member/detail' => 'Link to Detail Report'];

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
      'civicrm_membership' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'grouping' => 'member-fields',
        'fields' => [
          'membership_type_id' => [
            'title' => ts('Membership Type'),
            'required' => TRUE,
          ],
        ],
        'filters' => [
          'membership_join_date' => [
            'title' => ts('Member Since'),
            'type' => CRM_Utils_Type::T_DATE,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'membership_start_date' => [
            'name' => 'start_date',
            'title' => ts('Membership Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'membership_end_date' => [
            'name' => 'end_date',
            'title' => ts('Membership Expiration Date'),
            'type' => CRM_Utils_Type::T_DATE,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'owner_membership_id' => [
            'title' => ts('Primary Membership'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'membership_type_id' => [
            'title' => ts('Membership Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ],
          'status_id' => [
            'title' => ts('Membership Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ],
        ],
        'group_bys' => [
          'join_date' => [
            'title' => ts('Member Since'),
            'default' => TRUE,
            'frequency' => TRUE,
            'chart' => TRUE,
            'type' => 12,
          ],
          'membership_type_id' => [
            'title' => ts('Membership Type'),
            'default' => TRUE,
            'chart' => TRUE,
          ],
        ],
      ],
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'contact_id' => [
            'no_display' => TRUE,
          ],
          'contact_type' => [
            'title' => ts('Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => ts('Contact Subtype'),
          ],
        ],
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'total_amount' => [
            'title' => ts('Amount Statistics'),
            'default' => TRUE,
            'statistics' => [
              'sum' => ts('Total Payments Made'),
              'count' => ts('Contribution Count'),
              'avg' => ts('Average'),
            ],
          ],
        ],
        'filters' => [
          'currency' => [
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
          ],
        ],
        'grouping' => 'member-fields',
      ],
    ];
    $this->_tagFilter = TRUE;

    // If we have campaigns enabled, add those elements to both the fields, filters and group by
    $this->addCampaignFields('civicrm_membership', TRUE);

    // Add charts support
    $this->_charts = [
      '' => ts('Tabular'),
      'barChart' => ts('Bar Chart'),
      'pieChart' => ts('Pie Chart'),
    ];

    $this->_groupFilter = TRUE;
    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  public function select() {
    $select = [];
    $groupBys = FALSE;
    $this->_columnHeaders = [];
    $select[] = " COUNT( DISTINCT {$this->_aliases['civicrm_membership']}.id ) as civicrm_membership_member_count";
    $select['joinDate'] = " {$this->_aliases['civicrm_membership']}.join_date  as civicrm_membership_member_join_date";
    $this->_columnHeaders["civicrm_membership_member_join_date"] = [
      'title' => ts('Member Since'),
      'type' => CRM_Utils_Type::T_DATE,
    ];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (!empty($this->_params['group_bys'][$fieldName])) {

            switch ($this->_params['group_bys_freq'][$fieldName] ?? NULL) {
              case 'YEARWEEK':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";

                $select[] = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Week';
                break;

              case 'YEAR':
                $select[] = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Year';
                break;

              case 'MONTH':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                $select[] = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Month';
                break;

              case 'QUARTER':
                $select[] = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Quarter';
                break;
            }
            if (!empty($this->_params['group_bys_freq'][$fieldName])) {
              $this->_interval = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title'] = $field['title'] . ' Beginning';
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['group_by'] = $this->_params['group_bys_freq'][$fieldName];

              // just to make sure these values are transferred to rows.
              // since we need that for calculation purpose,
              // e.g making subtotals look nicer or graphs
              $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = ['no_display' => TRUE];
              $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = ['no_display' => TRUE];
            }
            $groupBys = TRUE;
          }
        }
      }
      // end of select

      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            // only include statistics columns if set
            if (!empty($field['statistics'])) {
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
              if (empty($this->_params['group_bys']['membership_type_id']) &&
                !empty($this->_params['group_bys']['join_date'])
              ) {
                $select[] = "GROUP_CONCAT(DISTINCT {$field['dbAlias']}  ORDER BY {$field['dbAlias']} ) as {$tableName}_{$fieldName}";
              }
              else {
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              }
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['operatorType'] = $field['operatorType'] ?? NULL;
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['operatorType'] = $field['operatorType'] ?? NULL;
            }
          }
        }
      }
      $this->_columnHeaders["civicrm_membership_member_count"] = [
        'title' => ts('Member Count'),
        'type' => CRM_Utils_Type::T_INT,
      ];
    }
    //If grouping is availabled then remove join date from field
    if ($groupBys) {
      unset($select['joinDate']);
      unset($this->_columnHeaders["civicrm_membership_member_join_date"]);
    }
    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {
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

  public function where() {
    $this->_whereClauses[] = "{$this->_aliases['civicrm_membership']}.is_test = 0 AND
                              {$this->_aliases['civicrm_contact']}.is_deleted = 0";
    parent::where();
  }

  public function groupBy() {
    $this->_groupBy = "";
    if (is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (!empty($this->_params['group_bys'][$fieldName])) {
              if (!empty($field['chart'])) {
                $this->assign('chartSupported', TRUE);
              }
              if (!empty($table['group_bys'][$fieldName]['frequency']) &&
                !empty($this->_params['group_bys_freq'][$fieldName])
              ) {

                $append = "YEAR({$field['dbAlias']})";
                if (in_array(strtolower($this->_params['group_bys_freq'][$fieldName]),
                  ['year']
                )) {
                  $append = '';
                }
                $this->_groupByArray[] = $append;
                $this->_groupByArray[] = "{$this->_params['group_bys_freq'][$fieldName]}({$field['dbAlias']})";
                $append = TRUE;
              }
              else {
                $this->_groupByArray[] = $field['dbAlias'];
              }
            }
          }
        }
      }

      $this->_rollup = ' WITH ROLLUP';
      $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect($this->_selectClauses, array_filter($this->_groupByArray));
      $this->_groupBy = 'GROUP BY ' . implode(', ', array_filter($this->_groupByArray)) .
        " {$this->_rollup} ";
    }
    else {
      $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, "{$this->_aliases['civicrm_membership']}.join_date");
    }
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
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

    $totalAmount = $average = [];
    $count = $memberCount = 0;
    while ($dao->fetch()) {
      $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency) . "(" . $dao->count . ")";
      $average[] = CRM_Utils_Money::format($dao->avg, $dao->currency);
      $count += $dao->count;
      $memberCount += $dao->memberCount;
    }
    $statistics['counts']['amount'] = [
      'title' => ts('Total Amount'),
      'value' => implode(',  ', $totalAmount),
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $statistics['counts']['count'] = [
      'title' => ts('Total Contributions'),
      'value' => $count,
    ];
    $statistics['counts']['memberCount'] = [
      'title' => ts('Total Members'),
      'value' => $memberCount,
    ];
    $statistics['counts']['avg'] = [
      'title' => ts('Average'),
      'value' => implode(',  ', $average),
      'type' => CRM_Utils_Type::T_STRING,
    ];

    if (!(int) $statistics['counts']['amount']['value']) {
      //if total amount is zero then hide Chart Options
      $this->assign('chartSupported', FALSE);
    }

    return $statistics;
  }

  public function postProcess() {
    parent::postProcess();
  }

  public function getOperationPair($type = "string", $fieldName = NULL) {
    //re-name IS NULL/IS NOT NULL for clarity
    if ($fieldName == 'owner_membership_id') {
      $result = [];
      $result['nll'] = ts('Primary members only');
      $result['nnll'] = ts('Non-primary members only');
      $options = parent::getOperationPair($type, $fieldName);
      foreach ($options as $key => $label) {
        if (!array_key_exists($key, $result)) {
          $result[$key] = $label;
        }
      }
    }
    else {
      $result = parent::getOperationPair($type, $fieldName);
    }
    return $result;
  }

  /**
   * @param array $rows
   */
  public function buildChart(&$rows) {
    $graphRows = [];
    $count = 0;
    $membershipTypeValues = CRM_Member_PseudoConstant::membershipType();
    $isMembershipType = $this->_params['group_bys']['membership_type_id'] ?? NULL;
    $isJoiningDate = $this->_params['group_bys']['join_date'] ?? NULL;
    if (!empty($this->_params['charts'])) {
      foreach ($rows as $key => $row) {
        if (!($row['civicrm_membership_join_date_subtotal'] &&
          $row['civicrm_membership_membership_type_id']
        )
        ) {
          continue;
        }
        if ($isMembershipType) {
          $join_date = $row['civicrm_membership_join_date_start'] ?? NULL;
          $displayInterval = $row['civicrm_membership_join_date_interval'] ?? NULL;
          if ($join_date) {
            list($year, $month) = explode('-', $join_date);
          }
          if (!empty($row['civicrm_membership_join_date_subtotal'])) {

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
            $membershipType = $displayRange . "-" .
              $membershipTypeValues[$row['civicrm_membership_membership_type_id']];
          }
          else {

            $membershipType = $membershipTypeValues[$row['civicrm_membership_membership_type_id']];
          }

          $interval[$membershipType] = $membershipType;
          $display[$membershipType] = $row['civicrm_contribution_total_amount_sum'];
        }
        else {
          $graphRows['receive_date'][] = $row['civicrm_membership_join_date_start'] ?? NULL;
          $graphRows[$this->_interval][] = $row['civicrm_membership_join_date_interval'] ?? NULL;
          $graphRows['value'][] = $row['civicrm_contribution_total_amount_sum'];
          $count++;
        }
      }

      // build chart.
      if ($isMembershipType) {
        $graphRows['value'] = $display;
        $chartInfo = [
          'legend' => ts('Membership Summary'),
          'xname' => ts('Member Since / Member Type'),
          'yname' => ts('Fees'),
        ];
        CRM_Utils_Chart::reportChart($graphRows, $this->_params['charts'], $interval, $chartInfo);
      }
      else {
        CRM_Utils_Chart::chart($graphRows, $this->_params['charts'], $this->_interval);
      }
    }
    $this->assign('chartType', $this->_params['charts']);
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
      if (!empty($this->_params['group_bys']['join_date']) &&
        !empty($row['civicrm_membership_join_date_start']) &&
        $row['civicrm_membership_join_date_start'] &&
        $row['civicrm_membership_join_date_subtotal']
      ) {

        $dateStart = CRM_Utils_Date::customFormat($row['civicrm_membership_join_date_start'], '%Y%m%d');
        $endDate = new DateTime($dateStart);
        $dateEnd = [];

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
        if (!empty($this->_params['group_bys']['membership_type_id']) &&
          $typeID = $row['civicrm_membership_membership_type_id']
        ) {
          $typeUrl = "&tid_op=in&tid_value={$typeID}";
        }
        $statusUrl = '';
        if (!empty($this->_params['status_id_value'])) {
          $statusUrl = "&sid_op=in&sid_value=" .
            implode(",", $this->_params['status_id_value']);
        }
        $url = CRM_Report_Utils_Report::getNextUrl('member/detail',
          "reset=1&force=1&membership_join_date_from={$dateStart}&membership_join_date_to={$dateEnd}{$typeUrl}{$statusUrl}",
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
        $rows[$rowNum]['civicrm_membership_membership_type_id'] = '<b>' . ts('Subtotal') . '</b>';
        $entryFound = TRUE;
      }

      // If using campaigns, convert campaign_id to campaign title
      if (array_key_exists('civicrm_membership_campaign_id', $row)) {
        if ($value = $row['civicrm_membership_campaign_id']) {
          $rows[$rowNum]['civicrm_membership_campaign_id'] = $this->campaigns[$value];
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
