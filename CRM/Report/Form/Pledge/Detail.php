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


/*
 *   !!!!!!!!!!!!!!!!!!!!
 *     NB: this is named detail but behaves like a summary report.
 *   It is also accessed through the Pledge Summary link in the UI
 *   This should presumably be changed.
 *   ~ Doten
 *   !!!!!!!!!!!!!!!!!!!!
 *
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Report_Form_Pledge_Detail extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_totalPaid = FALSE;
  protected $_pledgeStatuses = [];
  protected $_customGroupExtends = [
    'Pledge',
    'Individual',
  ];

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
    $this->_pledgeStatuses = CRM_Core_OptionGroup::values('pledge_status',
      FALSE, FALSE, FALSE, NULL, 'name'
    );

    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'filters' => [
          'sort_name' => ['title' => ts('Contact Name')],
          'id' => ['no_display' => TRUE],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => ['no_repeat' => TRUE],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_pledge' => [
        'dao' => 'CRM_Pledge_DAO_Pledge',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'contact_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
          ],
          'amount' => [
            'title' => ts('Pledge Amount'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
          ],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'frequency_unit' => [
            'title' => ts('Frequency Unit'),
          ],
          'installments' => [
            'title' => ts('Installments'),
          ],
          'pledge_create_date' => [
            'title' => ts('Pledge Made Date'),
            'type' => CRM_Utils_Type::T_DATE,
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
            'default' => TRUE,
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
      ],
      'civicrm_pledge_payment' => [
        'dao' => 'CRM_Pledge_DAO_PledgePayment',
        'fields' => [
          'total_paid' => [
            'title' => ts('Total Amount Paid'),
            'type' => CRM_Utils_Type::T_MONEY,
          ],
          'balance_due' => [
            'title' => ts('Balance Due'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
          ],
        ],
      ],
    ];

    $this->_columns += $this->getAddressColumns(['group_by' => FALSE]) + $this->getPhoneColumns();

    // If we have a campaign, build out the relevant elements
    $this->addCampaignFields('civicrm_pledge', TRUE);

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

  /**
   * If we are retrieving total paid we need to define the inclusion of pledge_payment.
   *
   * @param string $tableName
   * @param string $tableKey
   * @param string $fieldName
   * @param array $field
   *
   * @return bool|string
   */
  public function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    if ($fieldName == 'total_paid') {
      // add pledge_payment join
      $this->_totalPaid = TRUE;
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = [
        'title' => $field['title'],
        'type' => $field['type'],
      ];
      return "COALESCE(sum({$this->_aliases[$tableName]}.actual_amount), 0) as {$tableName}_{$fieldName}";
    }
    if ($fieldName == 'balance_due') {
      $cancelledStatus = array_search('Cancelled', $this->_pledgeStatuses);
      $completedStatus = array_search('Completed', $this->_pledgeStatuses);
      // add pledge_payment join
      $this->_totalPaid = TRUE;
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = $field['title'];
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = [
        'title' => $field['title'],
        'type' => $field['type'],
      ];
      return "IF({$this->_aliases['civicrm_pledge']}.status_id IN({$cancelledStatus}, $completedStatus), 0, COALESCE({$this->_aliases['civicrm_pledge']}.amount, 0) - COALESCE(sum({$this->_aliases[$tableName]}.actual_amount),0)) as {$tableName}_{$fieldName}";
    }
    return FALSE;
  }

  public function groupBy() {
    parent::groupBy();
    if (empty($this->_groupBy) && $this->_totalPaid) {
      $groupBy = ["{$this->_aliases['civicrm_pledge']}.id", "{$this->_aliases['civicrm_pledge']}.currency"];
      $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
    }
  }

  public function from() {
    $this->_from = "
            FROM civicrm_pledge {$this->_aliases['civicrm_pledge']}
                 LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                      ON ({$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_pledge']}.contact_id )
                 {$this->_aclFrom} ";

    if ($this->_totalPaid) {
      $this->_from .= "
        LEFT JOIN civicrm_pledge_payment {$this->_aliases['civicrm_pledge_payment']} ON
          {$this->_aliases['civicrm_pledge']}.id = {$this->_aliases['civicrm_pledge_payment']}.pledge_id
          AND {$this->_aliases['civicrm_pledge_payment']}.status_id = 1
      ";
    }

    $this->joinPhoneFromContact();
    $this->joinAddressFromContact();
    $this->joinEmailFromContact();
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    //regenerate the from field without extra left join on pledge payments
    $totalPaid = $this->_totalPaid;
    $this->_totalPaid = FALSE;
    $this->from();
    $this->customDataFrom();
    if (!$this->_having) {
      $totalAmount = $average = [];
      $count = 0;
      $select = "
        SELECT COUNT({$this->_aliases['civicrm_pledge']}.amount )       as count,
          SUM({$this->_aliases['civicrm_pledge']}.amount )         as amount,
          ROUND(AVG({$this->_aliases['civicrm_pledge']}.amount), 2) as avg,
          {$this->_aliases['civicrm_pledge']}.currency as currency
        ";

      $group = "GROUP BY {$this->_aliases['civicrm_pledge']}.currency";
      $sql = "{$select} {$this->_from} {$this->_where} {$group}";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $count = $index = $totalCount = 0;
      // this will run once per currency
      while ($dao->fetch()) {
        $totalAmount = CRM_Utils_Money::format($dao->amount, $dao->currency);
        $average = CRM_Utils_Money::format($dao->avg, $dao->currency);
        $count = $dao->count;
        $totalCount .= $count;
        $statistics['counts']['amount' . $index] = [
          'title' => ts('Total Pledged') . ' (' . $dao->currency . ')',
          'value' => $totalAmount,
          'type' => CRM_Utils_Type::T_STRING,
        ];
        $statistics['counts']['avg' . $index] = [
          'title' => ts('Average') . ' (' . $dao->currency . ')',
          'value' => $average,
          'type' => CRM_Utils_Type::T_STRING,
        ];
        $statistics['counts']['count' . $index] = [
          'title' => ts('Total No Pledges') . ' (' . $dao->currency . ')',
          'value' => $count,
          'type' => CRM_Utils_Type::T_INT,
        ];
        $index++;
      }
      if ($totalCount > $count) {
        $statistics['counts']['count' . $index] = [
          'title' => ts('Total No Pledges'),
          'value' => $totalCount,
          'type' => CRM_Utils_Type::T_INT,
        ];
      }
    }
    // reset from clause
    if ($totalPaid) {
      $this->_totalPaid = TRUE;
      $this->from();
    }
    return $statistics;
  }

  public function orderBy() {
    $this->_orderBy = "ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id";
  }

  public function where() {
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                $this->_params["{$fieldName}_value"] ?? NULL,
                $this->_params["{$fieldName}_min"] ?? NULL,
                $this->_params["{$fieldName}_max"] ?? NULL
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

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = $this->buildQuery();
    $rows = $payment = [];

    $dao = CRM_Core_DAO::executeQuery($sql);

    // Set pager for the Main Query only which displays basic information
    $this->setPager();
    $this->assign('columnHeaders', $this->_columnHeaders);

    while ($dao->fetch()) {
      $pledgeID = $dao->civicrm_pledge_id;
      foreach ($this->_columnHeaders as $columnHeadersKey => $columnHeadersValue) {
        $row = [];
        if (property_exists($dao, $columnHeadersKey)) {
          $display[$pledgeID][$columnHeadersKey] = $dao->$columnHeadersKey;
        }
      }
      $pledgeIDArray[] = $pledgeID;
    }

    // Add Special headers
    $this->_columnHeaders['scheduled_date'] = [
      'type' => CRM_Utils_Type::T_DATE,
      'title' => ts('Next Payment Due'),
    ];
    $this->_columnHeaders['scheduled_amount'] = [
      'type' => CRM_Utils_Type::T_MONEY,
      'title' => ts('Next Payment Amount'),
    ];
    $this->_columnHeaders['status_id'] = NULL;

    /*
     * this is purely about ordering the total paid & balance due fields off to the end
     * of the table in case custom or address fields cause them to fall in the middle
     * (arguably the pledge amount should be moved to after these fields too)
     *
     */
    $tableHeaders = [
      'civicrm_pledge_payment_total_paid',
      'civicrm_pledge_payment_balance_due',
    ];

    foreach ($tableHeaders as $header) {
      //per above, unset & reset them so they move to the end
      if (isset($this->_columnHeaders[$header])) {
        $headervalue = $this->_columnHeaders[$header];
        unset($this->_columnHeaders[$header]);
        $this->_columnHeaders[$header] = $headervalue;
      }
    }

    // To Display Payment Details of pledged amount
    // for pledge payments In Progress
    if (!empty($display)) {
      $statusId = array_keys(CRM_Core_PseudoConstant::accountOptionValues("contribution_status", NULL, " AND v.name IN  ('Pending', 'Overdue')"));
      $statusId = implode(',', $statusId);
      $select = "payment.pledge_id, payment.scheduled_amount, pledge.contact_id";
      $sqlPayment = "
                 SELECT min(payment.scheduled_date) as scheduled_date,
                        {$select}

                  FROM civicrm_pledge_payment payment
                       LEFT JOIN civicrm_pledge pledge
                                 ON pledge.id = payment.pledge_id

                  WHERE payment.status_id IN ({$statusId})

                  GROUP BY {$select}";

      $daoPayment = CRM_Core_DAO::executeQuery($sqlPayment);

      while ($daoPayment->fetch()) {
        foreach ($pledgeIDArray as $key => $val) {
          if ($val == $daoPayment->pledge_id) {

            $display[$daoPayment->pledge_id]['scheduled_date'] = $daoPayment->scheduled_date;

            $display[$daoPayment->pledge_id]['scheduled_amount'] = $daoPayment->scheduled_amount;
          }
        }
      }
    }

    // Displaying entire data on the form
    if (!empty($display)) {
      foreach ($display as $key => $value) {
        $row = [];
        foreach ($this->_columnHeaders as $columnKey => $columnValue) {
          if (array_key_exists($columnKey, $value)) {
            $row[$columnKey] = !empty($value[$columnKey]) ? $value[$columnKey] : '';
          }
        }
        $rows[] = $row;
      }
    }

    unset($this->_columnHeaders['status_id']);
    unset($this->_columnHeaders['civicrm_pledge_id']);
    unset($this->_columnHeaders['civicrm_pledge_contact_id']);

    $this->formatDisplay($rows, FALSE);
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
    $checkList = [];
    $display_flag = $prev_cid = $cid = 0;

    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (array_key_exists('civicrm_pledge_contact_id', $row)) {
          if ($cid = $row['civicrm_pledge_contact_id']) {
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

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_pledge_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_pledge_contact_id'],
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

      // If using campaigns, convert campaign_id to campaign title
      if (array_key_exists('civicrm_pledge_campaign_id', $row)) {
        if ($value = $row['civicrm_pledge_campaign_id']) {
          $rows[$rowNum]['civicrm_pledge_campaign_id'] = $this->campaigns[$value];
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'pledge/detail', 'List all pledge(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

}
