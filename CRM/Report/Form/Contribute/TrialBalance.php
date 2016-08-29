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
class CRM_Report_Form_Contribute_TrialBalance extends CRM_Report_Form {

  /**
   */
  public function __construct() {
    $params['labelColumn'] = 'name';
    $financialAccountType = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_FinancialAccount', 'financial_account_type_id', $params);
    $financialAccountType = array(
      array_search('Liability', $financialAccountType),
      array_search('Revenue', $financialAccountType),
    );
    $this->_columns = array(
      'civicrm_financial_account' => array(
        'dao' => 'CRM_Financial_DAO_FinancialAccount',
        'fields' => array(
          'name' => array(
            'title' => ts('Account'),
            'required' => TRUE,
          ),
          'accounting_code' => array(
            'title' => ts('Accounting Code'),
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'contact_id' => array(
            'title' => ts('Organization Name'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_BAO_FinancialAccount::getOrganizationNames(),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
      'civicrm_financial_trxn' => array(
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => array(
          'debit' => array(
            'title' => ts('Debit'),
            'required' => TRUE,
            'dbAlias' => 'IF (financial_account_type_id NOT IN (' . implode(',', $financialAccountType) . '), total_amount_1, NULL) + IF (current_period_opening_balance <> 0, current_period_opening_balance, opening_balance)',
          ),
          'credit' => array(
            'title' => ts('Credit'),
            'required' => TRUE,
            'dbAlias' => 'IF (financial_account_type_id IN (' . implode(',', $financialAccountType) . '), total_amount_1, NULL) + IF (current_period_opening_balance <> 0, current_period_opening_balance, opening_balance)',
          ),
        ),
      ),
    );

    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }


  public function from() {
    $closingDate = 'now()';
    $priorDate = CRM_Contribute_BAO_Contribution::checkContributeSettings('prior_financial_period');
    if (empty($priorDate)) {
      $where = " <= $closingDate ";
    }
    else {
      $priorDate = date('Y-m-d', strtotime($priorDate));
      $where = " BETWEEN '$priorDate' AND $closingDate ";
    }
    $this->_from = "
      FROM (
        SELECT SUM(total_amount) AS total_amount_1, to_financial_account_id AS financial_account_id 
          FROM civicrm_financial_trxn
          WHERE trxn_date {$where}
          GROUP BY to_financial_account_id
        UNION
        SELECT -sum(total_amount) AS total_amount_2, from_financial_account_id 
          FROM civicrm_financial_trxn
          WHERE from_financial_account_id IS NOT NULL AND trxn_date {$where}
          GROUP BY from_financial_account_id
        UNION
        SELECT -sum(amount) total_amount_3, financial_account_id 
          FROM civicrm_financial_item
          WHERE transaction_date {$where}
          GROUP BY financial_account_id
      ) AS {$this->_aliases['civicrm_financial_trxn']}
      INNER JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']} ON {$this->_aliases['civicrm_financial_trxn']}.financial_account_id = {$this->_aliases['civicrm_financial_account']}.id
";
  }

  public function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_financial_account']}.name ";
  }

  public function postProcess() {
    // get ready with post process params
    $this->beginPostProcess();

    // build query
    $sql = $this->buildQuery(FALSE);

    // build array of result based on column headers. This method also allows
    // modifying column headers before using it to build result set i.e $rows.
    $rows = array();
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  public function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_financial_account']}.id ";
  }

  /**
   * @param $rows
   *
   */
  public function statistics(&$rows) {

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
    if (empty($rows)) {
      return NULL;
    }
    $creditAmount = $debitAmount = 0;
    foreach ($rows as &$row) {
      $creditAmount += $row['civicrm_financial_trxn_credit'];
      $debitAmount += $row['civicrm_financial_trxn_debit'];
      $row['civicrm_financial_trxn_credit'] = CRM_Utils_Money::format($row['civicrm_financial_trxn_credit']);
      $row['civicrm_financial_trxn_debit'] = CRM_Utils_Money::format($row['civicrm_financial_trxn_debit']);
    }
    $rows[] = array(
      'civicrm_financial_account_accounting_code' => ts('<b>Total Amount</b>'),
      'civicrm_financial_trxn_debit' => '<b>' . CRM_Utils_Money::format($debitAmount) . '</b>',
      'civicrm_financial_trxn_credit' => '<b>' . CRM_Utils_Money::format($creditAmount) . '</b>',
    );
  }

}
