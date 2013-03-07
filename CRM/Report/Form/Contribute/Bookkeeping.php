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
class CRM_Report_Form_Contribute_Bookkeeping extends CRM_Report_Form {
  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array(
    'Membership'); 
  
  function __construct() {
    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'operator' => 'like',
          ),
          'id' =>
          array('title' => ts('Contact ID'),
            'no_display' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_membership' =>
      array(
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' =>
        array(
          'id' =>
          array('title' => ts('Membership #'),
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
      ),
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'receive_date' => array(
            'default' => TRUE
          ),
          'invoice_id' => array(
            'title' => ts('Invoice ID'),
            'default' => TRUE,
          ),
          'contribution_status_id' => array('title' => ts('Contribution Status'),
            'default' => TRUE,
          ),
          'id' => array('title' => ts('Contribution #'),
            'default' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'receive_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'contribution_status_id' =>
          array('title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
          ),
        ),
        'grouping' => 'contri-fields',
      ),     
      'civicrm_line_item' => array(
        'dao' => 'CRM_Price_DAO_LineItem',
        'fields' => array(
          'financial_type_id' => array('title' => ts('Financial Type'),
            'default' => TRUE,
          ),
        ),
        'filters' => array(
          'financial_type_id' => array( 
            'title' => ts('Financial Type'), 
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ),
        ),
      ),  
      'civicrm_financial_trxn' => array(
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => array(
          'check_number' => array(
            'title' => ts('Cheque #'),
            'default' => TRUE,
          ),
          'payment_instrument_id' => array('title' => ts('Payment Instrument'),
            'default' => TRUE,
          ),
          'trxn_date' => array(
            'title' => ts('Transaction Date'),
            'default' => TRUE
          ),
          'trxn_id' => array(
            'title' => ts('Trans #'),
            'default' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'payment_instrument_id' => array(
            'title' => ts('Payment Instrument'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
          ),
          'trxn_date' => array( 
            'title' => ts('Transaction Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
      ), 
      'civicrm_entity_financial_trxn' => array(
        'dao' => 'CRM_Financial_DAO_EntityFinancialTrxn',
        'fields' => array(
          'amount' => array(
            'title' => ts('Amount'),
            'default' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'amount' =>
          array('title' => ts('Amount')),
        ),
      ),
      'civicrm_financial_account' => array(
        'dao' => 'CRM_Financial_DAO_FinancialAccount',
        'fields' => array(
          'debit_accounting_code' => array(
            'title' => ts('Financial Account Code - Debit'),
            'name'  => 'accounting_code',
            'alias' => 'financial_account_civireport_debit',
            'default' => TRUE,
          ),
          'credit_accounting_code' => array(
            'title' => ts('Financial Account Code - Credit'),
            'name'  => 'accounting_code',
            'alias' => 'financial_account_civireport_credit',
            'default' => TRUE,
          ),
        )
      ),   
    );
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();

    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($fieldName != 'credit_accounting_code' && $fieldName != 'amount') {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
    $this->_select .= ", CASE 
            WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
              THEN  {$this->_aliases['civicrm_financial_account']}_credit_1.accounting_code
              ELSE  {$this->_aliases['civicrm_financial_account']}_credit_2.accounting_code
            END AS civicrm_financial_account_credit_accounting_code, CASE 
            WHEN  {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id IS NOT NULL
              THEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.amount
              ELSE {$this->_aliases['civicrm_entity_financial_trxn']}.amount
            END AS civicrm_entity_financial_trxn_amount ";
  }

  function from() {
    $this->_from = NULL;

    $this->_from = "FROM  civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
              INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND 
                         {$this->_aliases['civicrm_contribution']}.is_test = 0
              LEFT JOIN civicrm_membership_payment payment
                        ON ( {$this->_aliases['civicrm_contribution']}.id = payment.contribution_id )
              LEFT JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
                    ON payment.membership_id = {$this->_aliases['civicrm_membership']}.id 
              LEFT JOIN civicrm_entity_financial_trxn {$this->_aliases['civicrm_entity_financial_trxn']}
                    ON ({$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}.entity_id AND 
                        {$this->_aliases['civicrm_entity_financial_trxn']}.entity_table = 'civicrm_contribution')
              LEFT JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn']}
                    ON {$this->_aliases['civicrm_financial_trxn']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}.financial_trxn_id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_debit
                    ON {$this->_aliases['civicrm_financial_trxn']}.to_financial_account_id = {$this->_aliases['civicrm_financial_account']}_debit.id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_credit_1
                    ON {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id = {$this->_aliases['civicrm_financial_account']}_credit_1.id
              LEFT JOIN civicrm_entity_financial_trxn {$this->_aliases['civicrm_entity_financial_trxn']}_item
                    ON ({$this->_aliases['civicrm_financial_trxn']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}_item.financial_trxn_id AND 
                        {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_table = 'civicrm_financial_item')
              LEFT JOIN civicrm_financial_item fitem
                    ON fitem.id = {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_credit_2
                    ON fitem.financial_account_id = {$this->_aliases['civicrm_financial_account']}_credit_2.id
              LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
                    ON  fitem.entity_id = {$this->_aliases['civicrm_line_item']}.id AND fitem.entity_table = 'civicrm_line_item' ";
  }

  function orderBy() {
          $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contribution']}.id, {$this->_aliases['civicrm_entity_financial_trxn']}.id ";
  }

  function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = " SELECT COUNT({$this->_aliases['civicrm_financial_trxn']}.id ) as count,
                SUM(CASE 
                  WHEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id IS NOT NULL
                  THEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.amount
                  ELSE {$this->_aliases['civicrm_entity_financial_trxn']}.amount
                END) as amount ";

    $sql = "{$select} {$this->_from} {$this->_where}";
    $dao = CRM_Core_DAO::executeQuery($sql);

    if ($dao->fetch()) {
      $statistics['counts']['amount'] = array(
        'value' => $dao->amount,
        'title' => 'Total Amount',
        'type' => CRM_Utils_Type::T_MONEY,
      );
      $statistics['counts']['avg'] = array(
        'value' => round(($dao->amount / $dao->count), 2),
        'title' => 'Average',
        'type' => CRM_Utils_Type::T_MONEY,
      );
    }

    return $statistics;
  }

  function alterDisplay(&$rows) {
    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    foreach ($rows as $rowNum => $row) {
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        CRM_Utils_Array::value('civicrm_contact_sort_name', $rows[$rowNum]) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
      }

      // handle contribution status id
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
      }

      // handle payment instrument id
      if ($value = CRM_Utils_Array::value('civicrm_financial_trxn_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_financial_trxn_payment_instrument_id'] = $paymentInstruments[$value];
      }
      
      // handle financial type id
      if ($value = CRM_Utils_Array::value('civicrm_line_item_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_line_item_financial_type_id'] = $contributionTypes[$value];
      }
    }
  }
}

