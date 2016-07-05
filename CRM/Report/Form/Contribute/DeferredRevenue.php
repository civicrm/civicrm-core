<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_Report_Form_Contribute_DeferredRevenue extends CRM_Report_Form {

  /**
   * Holds Deferred Financial Account
   */
  protected $_deferredFinancialAccount = array();

  /**
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $this->_deferredFinancialAccount = CRM_Financial_BAO_FinancialAccount::getAllDeferredFinancialAccount();
    $this->_columns = array(
      'civicrm_financial_account' => array(
        'dao' => 'CRM_Financial_DAO_FinancialAccount',
        'alias' => 'financial_account_deferred',
        'filters' => array(
          'id' => array(
            'title' => ts('Deferred Financial Account'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->_deferredFinancialAccount,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
    );
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    // TODO: add column
    $this->_select = ' SELECT 
financial_account_deferred_civireport.name deferred_account,
financial_account_deferred_civireport.id deferred_account_id,
financial_account_deferred_civireport.accounting_code deferred_account_code,
financial_account_revenue.name revenue_account,
financial_account_revenue.id revenue_account_id,
financial_account_revenue.accounting_code revenue_account_code,
financial_item.status_id,
financial_item.id item_id,
financial_trxn_contribution_1.status_id,
financial_trxn_contribution_1.trxn_date transaction_date,
financial_trxn_contribution_1.total_amount,
contribution.id contribution_id,
contribution.contact_id,
contact.display_name,
contribution.source,
GROUP_CONCAT(financial_trxn.total_amount) trxn_amount,
GROUP_CONCAT(financial_trxn.trxn_date) trxn_date,
financial_item.description,
IFNULL(membership.start_date, event.start_date) start_date,
IFNULL(membership.end_date, event.end_date) end_date
';
  }

  public function from() {
    $deferredRelationship = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Deferred Revenue Account is' "));
    $revenueRelationship = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    $this->_from = " FROM civicrm_financial_item financial_item
INNER JOIN civicrm_entity_financial_account entity_financial_account_deferred
  ON financial_item.financial_account_id = entity_financial_account_deferred.financial_account_id AND entity_financial_account_deferred.entity_table = 'civicrm_financial_type'
    AND entity_financial_account_deferred.account_relationship = {$deferredRelationship}
INNER JOIN civicrm_financial_account financial_account_deferred_civireport
  ON entity_financial_account_deferred.financial_account_id = financial_account_deferred_civireport.id
INNER JOIN civicrm_entity_financial_account entity_financial_account_revenue
  ON entity_financial_account_deferred.entity_id = entity_financial_account_revenue.entity_id
    AND entity_financial_account_deferred.entity_table= entity_financial_account_revenue.entity_table
INNER JOIN civicrm_financial_account financial_account_revenue
  ON entity_financial_account_revenue.financial_account_id = financial_account_revenue.id
    AND {$revenueRelationship} = entity_financial_account_revenue.account_relationship
INNER JOIN civicrm_entity_financial_trxn entity_financial_trxn_item
  ON entity_financial_trxn_item.entity_id = financial_item.id AND entity_financial_trxn_item.entity_table = 'civicrm_financial_item'
INNER JOIN civicrm_financial_trxn financial_trxn
  ON financial_trxn.from_financial_account_id = financial_account_deferred_civireport.id AND financial_trxn.id =  entity_financial_trxn_item.financial_trxn_id 
INNER JOIN civicrm_entity_financial_trxn financial_trxn_contribution
  ON financial_trxn_contribution.financial_trxn_id = financial_trxn.id AND financial_trxn_contribution.entity_table = 'civicrm_contribution'
INNER JOIN civicrm_entity_financial_trxn entity_financial_trxn_contribution ON entity_financial_trxn_contribution.entity_id = financial_item.id and entity_financial_trxn_contribution.entity_table = 'civicrm_financial_item'  
INNER JOIN civicrm_financial_trxn financial_trxn_contribution_1 ON financial_trxn_contribution_1.id = entity_financial_trxn_contribution.financial_trxn_id AND (financial_trxn_contribution_1.from_financial_account_id NOT IN (" . implode(',', array_keys($this->_deferredFinancialAccount)) . ") OR financial_trxn_contribution_1.from_financial_account_id IS NULL)
INNER JOIN civicrm_contribution contribution 
  ON contribution.id = financial_trxn_contribution.entity_id
INNER JOIN civicrm_contact contact 
  ON contact.id = contribution.contact_id
INNER JOIN civicrm_line_item line_item 
  ON line_item.contribution_id = contribution.id
LEFT JOIN  civicrm_membership membership 
  ON CASE
    WHEN line_item.entity_table = 'civicrm_membership'
    THEN line_item.entity_id = membership.id
    ELSE membership.id = 0
  END
LEFT JOIN civicrm_participant participant
  ON CASE
    WHEN line_item.entity_table = 'civicrm_participant'
    THEN line_item.entity_id = participant.id
    ELSE participant.id = 0
  END
LEFT JOIN civicrm_event event ON participant.event_id = event.id
";
  }

  public function orderBy() {
    parent::orderBy();
  }

  public function where() {
    $clauses = array();
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
            $clauses[] = $clause;
          }
        }
      }
    }
    if (!empty($clauses)) {
      $this->_where = 'WHERE ' . implode(' AND ', $clauses);
    }
  }

  public function postProcess() {
    $this->_noFields = TRUE;
    parent::postProcess();
  }

  public function groupBy() {
    $this->_groupBy = "GROUP BY financial_account_deferred_civireport.id, financial_account_revenue.id, financial_item.id";
  }

  /**
   * Build output rows.
   *
   * @param string $sql
   * @param array $rows
   */
  public function buildRows($sql, &$rows) {
    $dao = CRM_Core_DAO::executeQuery($sql);
    if (!is_array($rows)) {
      $rows = array();
    }
    $statuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $dateColumn = array();
    $columns = array(
      'Transaction' => 1,
      'Date of Transaction' => 1,
      'Amount' => 1,
      'Contribution ID' => 1,
      'Item' => 1,
      'Contact ID' => 1,
      'Contact Name' => 1,
      'Source' => 1,
      'Start Date' => 1,
      'End Date' => 1,
    );
    $dateFormat = Civi::settings()->get('dateformatFinancialBatch');
    while ($dao->fetch()) {
      $arraykey = $dao->deferred_account_id . '_' . $dao->revenue_account_id;
      if (empty($rows[$arraykey])) {
        $rows[$arraykey]['label'] = "Deferred Revenue Account: {$dao->deferred_account} ({$dao->deferred_account_code}), Revenue Account: {$dao->revenue_account} {$dao->revenue_account_code}";
      }
      $rows[$arraykey]['rows'][$dao->item_id] = array(
        'Transaction' => $statuses[$dao->status_id],
        'Date of Transaction' => CRM_Utils_Date::customFormat($dao->transaction_date, $dateFormat),
        'Amount' => CRM_Utils_Money::format($dao->total_amount),
        'Contribution ID' => $dao->contribution_id,
        'Item' => $dao->description,
        'Contact ID' => $dao->contact_id,
        'Contact Name' => $dao->display_name,
        'Source' => $dao->source,
        'Start Date' => CRM_Utils_Date::customFormat($dao->start_date, $dateFormat),
        'End Date' => CRM_Utils_Date::customFormat($dao->end_date, $dateFormat),
      );
      $trxnDate = explode(',', $dao->trxn_date);
      $trxnAmount = explode(',', $dao->trxn_amount);
      foreach ($trxnDate as $key => $date) {
        $keyDate = date('M, Y', strtotime($date));
        $rows[$arraykey]['rows'][$dao->item_id][$keyDate] = CRM_Utils_Money::format($trxnAmount[$key]);
        $dateColumn[date('Ymd', strtotime($date))] = 1;
      }
    }
    ksort($dateColumn);
    foreach ($dateColumn as $key => $ignore) {
      $columns[date('M, Y', strtotime($key))] = 1;
    }
    $this->_columnHeaders = $columns;
  }
  /**
   * @param $rows
   *
   */
  public function statistics(&$rows) {}

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {}

}
