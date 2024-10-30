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
class CRM_Report_Form_Contribute_PCP extends CRM_Report_Form {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Supporter'),
            'required' => TRUE,
            'default' => TRUE,
          ],
          'id' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'contact_type' => [
            'title' => ts('Supporter Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => ts('Supporter Contact Subtype'),
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => ts('Supporter Name'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ],
          'id' => [
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
          ],
        ],
        'grouping' => 'pcp-fields',
      ],
      'civicrm_contribution_page' => [
        'dao' => 'CRM_Contribute_DAO_ContributionPage',
        'alias' => 'cp',
        'fields' => [
          'page_title' => [
            'title' => ts('Page Title'),
            'name' => 'title',
            'dbAlias' => 'coalesce(cp_civireport.title, e_civireport.title)',
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'page_title' => [
            'title' => ts('Contribution Page Title'),
            'name' => 'title',
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
        'grouping' => 'pcp-fields',
      ],
      'civicrm_event' => [
        'alias' => 'e',
        'filters' => [
          'event_title' => [
            'title' => ts('Event Title'),
            'name' => 'title',
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
        'grouping' => 'pcp-fields',
      ],
      'civicrm_pcp' => [
        'dao' => 'CRM_PCP_DAO_PCP',
        'fields' => [
          'title' => [
            'title' => ts('Personal Campaign Title'),
            'default' => TRUE,
          ],
          'page_type' => [
            'title' => ts('Page Type'),
            'default' => FALSE,
          ],
          'goal_amount' => [
            'title' => ts('Goal Amount'),
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'title' => [
            'title' => ts('Personal Campaign Title'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
        'group_bys' => [
          'pcp_id' => [
            'name' => 'id',
            'required' => TRUE,
            'default' => TRUE,
            'title' => ts('Personal Campaign Page'),
          ],
        ],
        'grouping' => 'pcp-fields',
      ],
      'civicrm_contribution_soft' => [
        'dao' => 'CRM_Contribute_DAO_ContributionSoft',
        'fields' => [
          'amount_1' => [
            'title' => ts('Committed Amount'),
            'name' => 'amount',
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
            'statistics' => [
              'sum' => ts('Committed Amount'),
            ],
          ],
          'amount_2' => [
            'title' => ts('Amount Received'),
            'name' => 'amount',
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
            // nice trick with dbAlias
            'dbAlias' => 'SUM(IF( contribution_civireport.contribution_status_id > 1, 0, contribution_soft_civireport.amount))',
          ],
          'soft_id' => [
            'title' => ts('Number of Donors'),
            'name' => 'id',
            'default' => TRUE,
            'statistics' => [
              'count' => ts('Number of Donors'),
            ],
          ],
        ],
        'filters' => [
          'amount_2' => [
            'title' => ts('Amount Received'),
            'type' => CRM_Utils_Type::T_MONEY,
            'dbAlias' => 'SUM(IF( contribution_civireport.contribution_status_id > 1, 0, contribution_soft_civireport.amount))',
          ],
        ],
        'grouping' => 'pcp-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'contribution_id' => [
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'receive_date' => [
            'title' => ts('Most Recent Contribution'),
            'default' => TRUE,
            'statistics' => [
              'max' => ts('Most Recent Contribution'),
            ],
          ],
        ],
        'filters' => [
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => [1],
          ],
        ],
        'grouping' => 'pcp-fields',
      ],
      'civicrm_financial_trxn' => [
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => [
          'card_type_id' => [
            'title' => ts('Credit Card Type'),
            'dbAlias' => 'GROUP_CONCAT(financial_trxn_civireport.card_type_id SEPARATOR ",")',
          ],
        ],
        'filters' => [
          'card_type_id' => [
            'title' => ts('Credit Card Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
      ],
    ];

    parent::__construct();
    $this->optimisedForOnlyFullGroupBy = FALSE;
  }

  public function from() {
    $this->_from = "
FROM civicrm_pcp {$this->_aliases['civicrm_pcp']}

LEFT JOIN civicrm_contribution_soft {$this->_aliases['civicrm_contribution_soft']}
          ON {$this->_aliases['civicrm_pcp']}.id =
             {$this->_aliases['civicrm_contribution_soft']}.pcp_id

LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
          ON {$this->_aliases['civicrm_contribution_soft']}.contribution_id =
             {$this->_aliases['civicrm_contribution']}.id

LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
          ON {$this->_aliases['civicrm_pcp']}.contact_id =
             {$this->_aliases['civicrm_contact']}.id

LEFT JOIN civicrm_contribution_page {$this->_aliases['civicrm_contribution_page']}
          ON {$this->_aliases['civicrm_pcp']}.page_id =
             {$this->_aliases['civicrm_contribution_page']}.id
               AND {$this->_aliases['civicrm_pcp']}.page_type = 'contribute'

LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']}
          ON {$this->_aliases['civicrm_pcp']}.page_id =
             {$this->_aliases['civicrm_event']}.id
               AND {$this->_aliases['civicrm_pcp']}.page_type = 'event'";

    // for credit card type
    $this->addFinancialTrxnFromClause();
  }

  public function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name ";
  }

  public function where() {
    $whereClauses = $havingClauses = [];

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;
            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
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
            if ($tableName == 'civicrm_contribution_soft' &&
              $fieldName == 'amount_2'
            ) {
              $havingClauses[] = $clause;
            }
            else {
              $whereClauses[] = $clause;
            }
          }
        }
      }
    }
    if (empty($whereClauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $whereClauses);
    }
    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
    $this->_having = "";
    if (!empty($havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = "HAVING " . implode(' AND ', $havingClauses);
    }
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    // Calculate totals from the civicrm_contribution_soft table.
    $select = "SELECT SUM({$this->_aliases['civicrm_contribution_soft']}.amount) "
      . "as committed_total, COUNT({$this->_aliases['civicrm_contribution_soft']}.id) "
      . "as donors_total, SUM(IF( contribution_civireport.contribution_status_id > 1, 0, "
      . "contribution_soft_civireport.amount)) AS received_total ";
    $sql = "{$select} {$this->_from} {$this->_where}";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    $committed_total = $dao->committed_total;
    $received_total = $dao->received_total;
    $donors_total = $dao->donors_total;

    // Calculate goal total goal from the PCP table (we only want one result per
    // PCP page - the query above produces one result per contribution made).
    $sql = "SELECT SUM(goal_amount) as goal_total FROM civicrm_pcp WHERE "
      . "goal_amount IS NOT NULL AND id IN ("
      . "SELECT DISTINCT {$this->_aliases['civicrm_pcp']}.id {$this->_from} "
      . "{$this->_where}"
      . ")";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    $goal_total = $dao->goal_total;

    $statistics['counts']['goal_total'] = [
      'title' => ts('Goal Total'),
      'value' => $goal_total,
      'type' => CRM_Utils_Type::T_MONEY,
    ];
    $statistics['counts']['committed_total'] = [
      'title' => ts('Total Committed'),
      'value' => $committed_total,
      'type' => CRM_Utils_Type::T_MONEY,
    ];
    $statistics['counts']['received_total'] = [
      'title' => ts('Total Received'),
      'value' => $received_total,
      'type' => CRM_Utils_Type::T_MONEY,
    ];
    $statistics['counts']['donors_total'] = [
      'title' => ts('Total Donors'),
      'value' => $donors_total,
      'type' => CRM_Utils_Type::T_INT,
    ];
    return $statistics;
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
    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // not repeat contact sort names if it matches with the one
        // in previous row
        $repeatFound = FALSE;

        foreach ($row as $colName => $colVal) {
          if (!empty($checkList[$colName]) &&
            is_array($checkList[$colName]) &&
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

      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        $rows[$rowNum]['civicrm_contact_sort_name'] &&
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

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      if (!empty($row['civicrm_pcp_page_type'])) {
        $rows[$rowNum]['civicrm_pcp_page_type'] = ucfirst($rows[$rowNum]['civicrm_pcp_page_type']);
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
