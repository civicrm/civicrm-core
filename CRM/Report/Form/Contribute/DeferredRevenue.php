<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
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
    $this->_exposeContactID = FALSE;
    $this->_deferredFinancialAccount = CRM_Financial_BAO_FinancialAccount::getAllDeferredFinancialAccount();
    $this->_columns = array(
      'civicrm_financial_account' => array(
        'dao' => 'CRM_Financial_DAO_FinancialAccount',
        'fields' => array(
          'name' => array(
            'title' => ts('Deferred Account'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'id' => array(
            'title' => ts('Deferred Account ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'accounting_code' => array(
            'title' => ts('Deferred Accounting Code'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
        ),
        'filters' => array(
          'id' => array(
            'title' => ts('Deferred Financial Account'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->_deferredFinancialAccount,
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
      'civicrm_financial_account_1' => array(
        'dao' => 'CRM_Financial_DAO_FinancialAccount',
        'fields' => array(
          'name' => array(
            'title' => ts('Revenue Account'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'id' => array(
            'title' => ts('Revenue Account ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'accounting_code' => array(
            'title' => ts('Revenue Accounting code'),
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
      ),
      'civicrm_financial_item' => array(
        'dao' => 'CRM_Financial_DAO_FinancialItem',
        'fields' => array(
          'status_id' => array(
            'title' => ts('Status'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'id' => array(
            'title' => ts('Financial Item ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'description' => array(
            'title' => ts('Description'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
        ),
      ),
      'civicrm_financial_trxn_1' => array(
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => array(
          'total_amount' => array(
            'title' => ts('Deferred Transaction Amount'),
            'required' => TRUE,
            'no_display' => TRUE,
            'dbAlias' => 'GROUP_CONCAT(financial_trxn_1_civireport.total_amount)',
          ),
          'trxn_date' => array(
            'title' => ts('Deferred Transaction Date'),
            'required' => TRUE,
            'no_display' => TRUE,
            'dbAlias' => 'GROUP_CONCAT(financial_trxn_1_civireport.trxn_date)',
          ),
        ),
      ),
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'display_name' => array(
            'title' => ts('Display_name'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
        ),
      ),
      'civicrm_membership' => array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => array(
          'start_date' => array(
            'title' => ts('Start Date'),
            'required' => TRUE,
            'no_display' => TRUE,
            'dbAlias' => 'IFNULL(membership_civireport.start_date, event_civireport.start_date)',
          ),
          'end_date' => array(
            'title' => ts('End Date'),
            'required' => TRUE,
            'no_display' => TRUE,
            'dbdbAlias' => 'IFNULL(membership_civireport.end_date, event_civireport.end_date)',
          ),
        ),
      ),
      'civicrm_event' => array(
        'dao' => 'CRM_Event_DAO_Event',
      ),
      'civicrm_participant' => array(
        'dao' => 'CRM_Event_DAO_Participant',
      ),
      'civicrm_batch' => array(
        'dao' => 'CRM_Batch_DAO_EntityBatch',
        'grouping' => 'contri-fields',
        'filters' => array(
          'batch_id' => array(
            'title' => ts('Batch Title'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Batch_BAO_Batch::getBatches(),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'id' => array(
            'title' => ts('Contribution ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'contact_id' => array(
            'title' => ts('Contact ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'source' => array(
            'title' => ts('Source'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
        ),
        'filters' => array(
          'receive_date' => array(
            'title' => ts('Receive Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'cancel_date' => array(
            'title' => ts('Cancel Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'revenue_recognition_date' => array(
            'title' => ts('Revenue Recognition Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
      ),
      'civicrm_financial_trxn' => array(
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => array(
          'status_id' => array(
            'title' => ts('Transaction Status'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'trxn_date' => array(
            'title' => ts('Transaction Date'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'total_amount' => array(
            'title' => ts('Transaction Amount'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
        ),
        'filters' => array(
          'trxn_date' => array(
            'title' => ts('Transaction Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
      ),
    );
    parent::__construct();
  }

  /**
   * Pre process function.
   *
   * Called prior to build form.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Build from clause.
   */
  public function from() {
    $deferredRelationship = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Deferred Revenue Account is' "));
    $revenueRelationship = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    $this->_from = " FROM civicrm_financial_item {$this->_aliases['civicrm_financial_item']}
INNER JOIN civicrm_entity_financial_account entity_financial_account_deferred
  ON {$this->_aliases['civicrm_financial_item']}.financial_account_id = entity_financial_account_deferred.financial_account_id AND entity_financial_account_deferred.entity_table = 'civicrm_financial_type'
    AND entity_financial_account_deferred.account_relationship = {$deferredRelationship}
INNER JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}
  ON entity_financial_account_deferred.financial_account_id = {$this->_aliases['civicrm_financial_account']}.id
INNER JOIN civicrm_entity_financial_account entity_financial_account_revenue
  ON entity_financial_account_deferred.entity_id = entity_financial_account_revenue.entity_id
    AND entity_financial_account_deferred.entity_table= entity_financial_account_revenue.entity_table
INNER JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account_1']}
  ON entity_financial_account_revenue.financial_account_id = {$this->_aliases['civicrm_financial_account_1']}.id
    AND {$revenueRelationship} = entity_financial_account_revenue.account_relationship
INNER JOIN civicrm_entity_financial_trxn entity_financial_trxn_item
  ON entity_financial_trxn_item.entity_id = {$this->_aliases['civicrm_financial_item']}.id AND entity_financial_trxn_item.entity_table = 'civicrm_financial_item'
INNER JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn_1']}
  ON {$this->_aliases['civicrm_financial_trxn_1']}.from_financial_account_id = {$this->_aliases['civicrm_financial_account']}.id AND {$this->_aliases['civicrm_financial_trxn_1']}.id =  entity_financial_trxn_item.financial_trxn_id 
INNER JOIN civicrm_entity_financial_trxn financial_trxn_contribution
  ON financial_trxn_contribution.financial_trxn_id = {$this->_aliases['civicrm_financial_trxn_1']}.id AND financial_trxn_contribution.entity_table = 'civicrm_contribution'
INNER JOIN civicrm_entity_financial_trxn entity_financial_trxn_contribution ON entity_financial_trxn_contribution.entity_id = {$this->_aliases['civicrm_financial_item']}.id and entity_financial_trxn_contribution.entity_table = 'civicrm_financial_item'  
INNER JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn']} ON {$this->_aliases['civicrm_financial_trxn']}.id = entity_financial_trxn_contribution.financial_trxn_id AND ({$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id NOT IN (" . implode(',', array_keys($this->_deferredFinancialAccount)) . ") OR {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NULL)
INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
  ON {$this->_aliases['civicrm_contribution']}.id = financial_trxn_contribution.entity_id
INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
  ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id
INNER JOIN civicrm_line_item line_item 
  ON line_item.contribution_id = {$this->_aliases['civicrm_contribution']}.id
LEFT JOIN  civicrm_membership {$this->_aliases['civicrm_membership']}
  ON CASE
    WHEN line_item.entity_table = 'civicrm_membership'
    THEN line_item.entity_id = {$this->_aliases['civicrm_membership']}.id
    ELSE {$this->_aliases['civicrm_membership']}.id = 0
  END
LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
  ON CASE
    WHEN line_item.entity_table = 'civicrm_participant'
    THEN line_item.entity_id = {$this->_aliases['civicrm_participant']}.id
    ELSE {$this->_aliases['civicrm_participant']}.id = 0
  END
LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']} ON {$this->_aliases['civicrm_participant']}.event_id = {$this->_aliases['civicrm_event']}.id
";

    if (!empty($this->_params['batch_id_value'])) {
      $this->_from .= "
        LEFT JOIN civicrm_entity_batch {$this->_aliases['civicrm_batch']}
          ON {$this->_aliases['civicrm_batch']}.entity_id = {$this->_aliases['civicrm_financial_trxn_1']}.id AND
            {$this->_aliases['civicrm_batch']}.entity_table = 'civicrm_financial_trxn'\n";
    }
  }

  /**
   * Post process function.
   */
  public function postProcess() {
    $this->_noFields = TRUE;
    parent::postProcess();
  }

  /**
   * Set limit.
   *
   * @param int $rowCount
   */
  public function limit($rowCount = self::ROW_COUNT_LIMIT) {
    $this->_limit = NULL;
  }

  /**
   * Build where clause.
   */
  public function where() {
    parent::where();
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t', strtotime(date('ymd') . '+11 month'));
    $this->_where .= " AND {$this->_aliases['civicrm_financial_trxn_1']}.trxn_date BETWEEN '{$startDate}' AND '{$endDate}'";
  }

  /**
   * Build group by clause.
   */
  public function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_financial_account']}.id,  {$this->_aliases['civicrm_financial_account_1']}.id, {$this->_aliases['civicrm_financial_item']}.id";
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
    for ($i = 0; $i < 12; $i++) {
      //$columns[date('M, Y', strtotime("+1 month", date('Y-m-d')))] = 1;
      $columns[date('M, Y', strtotime(date('Y-m-d') . "+{$i} month"))] = 1;
    }
    while ($dao->fetch()) {
      $arraykey = $dao->civicrm_financial_account_id . '_' . $dao->civicrm_financial_account_1_id;
      if (empty($rows[$arraykey])) {
        $rows[$arraykey]['label'] = "Deferred Revenue Account: {$dao->civicrm_financial_account_name} ({$dao->civicrm_financial_account_accounting_code}), Revenue Account: {$dao->civicrm_financial_account_1_name} {$dao->civicrm_financial_account_1_accounting_code}";
      }
      $rows[$arraykey]['rows'][$dao->civicrm_financial_item_id] = array(
        'Transaction' => $statuses[$dao->civicrm_financial_trxn_status_id],
        'Date of Transaction' => CRM_Utils_Date::customFormat($dao->civicrm_financial_trxn_trxn_date, $dateFormat),
        'Amount' => CRM_Utils_Money::format($dao->civicrm_financial_trxn_total_amount),
        'Contribution ID' => $dao->civicrm_contribution_id,
        'Item' => $dao->civicrm_financial_item_description,
        'Contact ID' => $dao->civicrm_contribution_contact_id,
        'Contact Name' => $dao->civicrm_contact_display_name,
        'Source' => $dao->civicrm_contribution_source,
        'Start Date' => CRM_Utils_Date::customFormat($dao->civicrm_membership_start_date, $dateFormat),
        'End Date' => CRM_Utils_Date::customFormat($dao->civicrm_membership_end_date, $dateFormat),
      );
      $trxnDate = explode(',', $dao->civicrm_financial_trxn_1_trxn_date);
      $trxnAmount = explode(',', $dao->civicrm_financial_trxn_1_total_amount);
      foreach ($trxnDate as $key => $date) {
        $keyDate = date('M, Y', strtotime($date));
        if (!array_key_exists($keyDate, $columns)) {
          continue;
        }
        $rows[$arraykey]['rows'][$dao->civicrm_financial_item_id][$keyDate] = CRM_Utils_Money::format($trxnAmount[$key]);
      }
    }
    $this->_columnHeaders = $columns;
  }

}
