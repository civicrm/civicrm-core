<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 * $Id$
 *
 */
class CRM_Report_Form_Contribute_PCP extends CRM_Report_Form {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Supporter'),
            'required' => TRUE,
            'default' => TRUE,
          ),
          'id' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'contact_type' => array(
            'title' => ts('Supporter Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Supporter Contact Subtype'),
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => ts('Supporter Name'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ),
          'id' => array(
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
          ),
        ),
        'grouping' => 'pcp-fields',
      ),
      'civicrm_contribution_page' => array(
        'dao' => 'CRM_Contribute_DAO_ContributionPage',
        'alias' => 'cp',
        'fields' => array(
          'page_title' => array(
            'title' => ts('Page Title'),
            'name' => 'title',
            'dbAlias' => 'coalesce(cp_civireport.title, e_civireport.title)',
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'page_title' => array(
            'title' => ts('Contribution Page Title'),
            'name' => 'title',
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
        'grouping' => 'pcp-fields',
      ),
      'civicrm_event' => array(
        'alias' => 'e',
        'filters' => array(
          'event_title' => array(
            'title' => ts('Event Title'),
            'name' => 'title',
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
        'grouping' => 'pcp-fields',
      ),
      'civicrm_pcp' => array(
        'dao' => 'CRM_PCP_DAO_PCP',
        'fields' => array(
          'title' => array(
            'title' => ts('Personal Campaign Title'),
            'default' => TRUE,
          ),
          'page_type' => array(
            'title' => ts('Page Type'),
            'default' => FALSE,
          ),
          'goal_amount' => array(
            'title' => ts('Goal Amount'),
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'title' => array(
            'title' => ts('Personal Campaign Title'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
        'grouping' => 'pcp-fields',
      ),
      'civicrm_contribution_soft' => array(
        'dao' => 'CRM_Contribute_DAO_ContributionSoft',
        'fields' => array(
          'amount_1' => array(
            'title' => ts('Committed Amount'),
            'name' => 'amount',
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
            'statistics' => array(
              'sum' => ts('Committed Amount'),
            ),
          ),
          'amount_2' => array(
            'title' => ts('Amount Received'),
            'name' => 'amount',
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
            // nice trick with dbAlias
            'dbAlias' => 'SUM(IF( contribution_civireport.contribution_status_id > 1, 0, contribution_soft_civireport.amount))',
          ),
          'soft_id' => array(
            'title' => ts('Number of Donors'),
            'name' => 'id',
            'default' => TRUE,
            'statistics' => array(
              'count' => ts('Number of Donors'),
            ),
          ),
        ),
        'filters' => array(
          'amount_2' => array(
            'title' => ts('Amount Received'),
            'type' => CRM_Utils_Type::T_MONEY,
            'dbAlias' => 'SUM(IF( contribution_civireport.contribution_status_id > 1, 0, contribution_soft_civireport.amount))',
          ),
        ),
        'grouping' => 'pcp-fields',
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'contribution_id' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'receive_date' => array(
            'title' => ts('Most Recent Contribution'),
            'default' => TRUE,
            'statistics' => array(
              'max' => ts('Most Recent Contribution'),
            ),
          ),
        ),
        'grouping' => 'pcp-fields',
      ),
      'civicrm_financial_trxn' => array(
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => array(
          'card_type_id' => array(
            'title' => ts('Credit Card Type'),
            'dbAlias' => 'GROUP_CONCAT(financial_trxn_civireport.card_type_id SEPARATOR ",")',
          ),
        ),
        'filters' => array(
          'card_type_id' => array(
            'title' => ts('Credit Card Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
    );

    parent::__construct();
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

  public function groupBy() {
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, "{$this->_aliases['civicrm_pcp']}.id");
  }

  public function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name ";
  }

  public function where() {
    $whereClauses = $havingClauses = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);
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
   * @param $rows
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

    $statistics['counts']['goal_total'] = array(
      'title' => ts('Goal Total'),
      'value' => $goal_total,
      'type' => CRM_Utils_Type::T_MONEY,
    );
    $statistics['counts']['committed_total'] = array(
      'title' => ts('Total Committed'),
      'value' => $committed_total,
      'type' => CRM_Utils_Type::T_MONEY,
    );
    $statistics['counts']['received_total'] = array(
      'title' => ts('Total Received'),
      'value' => $received_total,
      'type' => CRM_Utils_Type::T_MONEY,
    );
    $statistics['counts']['donors_total'] = array(
      'title' => ts('Total Donors'),
      'value' => $donors_total,
      'type' => CRM_Utils_Type::T_INT,
    );
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
    $checkList = array();
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
