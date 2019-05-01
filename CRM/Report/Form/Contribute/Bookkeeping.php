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
 */
class CRM_Report_Form_Contribute_Bookkeeping extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Contribution',
    'Membership',
  ];

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
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'first_name' => [
            'title' => ts('First Name'),
          ],
          'middle_name' => [
            'title' => ts('Middle Name'),
          ],
          'last_name' => [
            'title' => ts('Last Name'),
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'gender_id' => [
            'title' => ts('Gender'),
          ],
          'birth_date' => [
            'title' => ts('Birth Date'),
          ],
          'age' => [
            'title' => ts('Age'),
            'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, CURDATE())',
          ],
          'contact_type' => [
            'title' => ts('Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => ts('Contact Subtype'),
          ],
        ],
        'grouping' => 'contact-fields',
        'order_bys' => [
          'sort_name' => [
            'title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ],
          'first_name' => [
            'name' => 'first_name',
            'title' => ts('First Name'),
          ],
          'gender_id' => [
            'name' => 'gender_id',
            'title' => ts('Gender'),
          ],
          'birth_date' => [
            'name' => 'birth_date',
            'title' => ts('Birth Date'),
          ],
          'contact_type' => [
            'title' => ts('Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => ts('Contact Subtype'),
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'operator' => 'like',
          ],
          'id' => [
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
          ],
          'gender_id' => [
            'title' => ts('Gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'),
          ],
          'birth_date' => [
            'title' => ts('Birth Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'contact_type' => [
            'title' => ts('Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => ts('Contact Subtype'),
          ],
        ],
      ],
      'civicrm_membership' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => [
          'id' => [
            'title' => ts('Membership #'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_financial_account' => [
        'dao' => 'CRM_Financial_DAO_FinancialAccount',
        'fields' => [
          'debit_accounting_code' => [
            'title' => ts('Financial Account Code - Debit'),
            'name' => 'accounting_code',
            'alias' => 'financial_account_civireport_debit',
            'default' => TRUE,
          ],
          'debit_contact_id' => [
            'title' => ts('Financial Account Owner - Debit'),
            'name' => 'organization_name',
            'alias' => 'debit_contact',
          ],
          'credit_accounting_code' => [
            'title' => ts('Financial Account Code - Credit'),
            'name' => 'accounting_code',
            'alias' => 'financial_account_civireport_credit',
            'default' => TRUE,
          ],
          'credit_contact_id' => [
            'title' => ts('Financial Account Owner - Credit'),
            'name' => 'organization_name',
            'alias' => 'credit_contact',
          ],
          'debit_name' => [
            'title' => ts('Financial Account Name - Debit'),
            'name' => 'name',
            'alias' => 'financial_account_civireport_debit',
            'default' => TRUE,
          ],
          'credit_name' => [
            'title' => ts('Financial Account Name - Credit'),
            'name' => 'name',
            'alias' => 'financial_account_civireport_credit',
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'debit_accounting_code' => [
            'title' => ts('Financial Account Code - Debit'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialAccount(NULL, NULL, 'accounting_code', 'accounting_code'),
            'name' => 'accounting_code',
            'alias' => 'financial_account_civireport_debit',
          ],
          'debit_contact_id' => [
            'title' => ts('Financial Account Owner - Debit'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'type' => CRM_Utils_Type::T_INT,
            'options' => ['' => '- Select Organization -'] + CRM_Financial_BAO_FinancialAccount::getOrganizationNames(FALSE),
            'name' => 'contact_id',
            'alias' => 'financial_account_civireport_debit',
          ],
          'credit_accounting_code' => [
            'title' => ts('Financial Account Code - Credit'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialAccount(NULL, NULL, 'accounting_code', 'accounting_code'),
            'name' => 'accounting_code',
            'alias' => 'financial_account_civireport_credit',
          ],
          'credit_contact_id' => [
            'title' => ts('Financial Account Owner - Credit'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'type' => CRM_Utils_Type::T_INT,
            'options' => ['' => '- Select Organization -'] + CRM_Financial_BAO_FinancialAccount::getOrganizationNames(FALSE),
            'name' => 'contact_id',
            'alias' => 'financial_account_civireport_credit',
          ],
          'debit_name' => [
            'title' => ts('Financial Account Name - Debit'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialAccount(),
            'name' => 'id',
            'alias' => 'financial_account_civireport_debit',
          ],
          'credit_name' => [
            'title' => ts('Financial Account Name - Credit'),
            'type' => CRM_Utils_Type::T_STRING,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialAccount(),
            'name' => 'id',
            'alias' => 'financial_account_civireport_credit',
          ],
        ],
      ],
      'civicrm_line_item' => [
        'dao' => 'CRM_Price_DAO_LineItem',
        'fields' => [
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes(),
          ],
        ],
        'order_bys' => [
          'financial_type_id' => ['title' => ts('Financial Type')],
        ],
      ],
      'civicrm_batch' => [
        'dao' => 'CRM_Batch_DAO_Batch',
        'fields' => [
          'title' => [
            'title' => ts('Batch Title'),
            'alias' => 'batch',
            'default' => FALSE,
          ],
          'name' => [
            'title' => ts('Batch Name'),
            'alias' => 'batch',
            'default' => TRUE,
          ],
        ],
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'receive_date' => [
            'default' => TRUE,
          ],
          'invoice_id' => [
            'title' => ts('Invoice Reference'),
            'default' => TRUE,
          ],
          'invoice_number' => [
            'title' => ts('Invoice Number'),
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'default' => TRUE,
          ],
          'contribution_source' => [
            'title' => ts('Source'),
            'name' => 'source',
          ],
          'id' => [
            'title' => ts('Contribution ID'),
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'contribution_id' => [
            'title' => ts('Contribution ID'),
            'name' => 'id',
            'operatorType' => CRM_Report_Form::OP_INT,
            'type' => CRM_Utils_Type::T_INT,
          ],
          'receive_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'contribution_source' => [
            'title' => ts('Source'),
            'name' => 'source',
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => [1],
          ],
        ],
        'order_bys' => [
          'contribution_id' => ['title' => ts('Contribution #')],
          'contribution_status_id' => ['title' => ts('Contribution Status')],
        ],
        'grouping' => 'contri-fields',
      ],
      'civicrm_financial_trxn' => [
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => [
          'check_number' => [
            'title' => ts('Cheque #'),
            'default' => TRUE,
          ],
          'payment_instrument_id' => [
            'title' => ts('Payment Method'),
            'default' => TRUE,
          ],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'trxn_date' => [
            'title' => ts('Transaction Date'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          ],
          'trxn_id' => [
            'title' => ts('Trans #'),
            'default' => TRUE,
          ],
          'card_type_id' => [
            'title' => ts('Credit Card Type'),
          ],
        ],
        'filters' => [
          'payment_instrument_id' => [
            'title' => ts('Payment Method'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
          ],
          'currency' => [
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'trxn_date' => [
            'title' => ts('Transaction Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          ],
          'status_id' => [
            'title' => ts('Financial Transaction Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => [1],
          ],
          'card_type_id' => [
            'title' => ts('Credit Card Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
        'order_bys' => [
          'payment_instrument_id' => ['title' => ts('Payment Method')],
        ],
      ],
      'civicrm_entity_financial_trxn' => [
        'dao' => 'CRM_Financial_DAO_EntityFinancialTrxn',
        'fields' => [
          'amount' => [
            'title' => ts('Amount'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
        'filters' => [
          'amount' => ['title' => ts('Amount')],
        ],
      ],
    ];

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = [];

    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            switch ($fieldName) {
              case 'credit_accounting_code':
              case 'credit_name':
                $select[] = " CASE
                            WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
                            THEN  {$this->_aliases['civicrm_financial_account']}_credit_1.{$field['name']}
                            ELSE  {$this->_aliases['civicrm_financial_account']}_credit_2.{$field['name']}
                            END AS civicrm_financial_account_{$fieldName} ";
                break;

              case 'amount':
                $select[] = " CASE
                            WHEN  {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id IS NOT NULL
                            THEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.amount
                            ELSE {$this->_aliases['civicrm_entity_financial_trxn']}.amount
                            END AS civicrm_entity_financial_trxn_amount ";
                break;

              case 'credit_contact_id':
                $select[] = " CASE
                            WHEN {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NOT NULL
                            THEN  credit_contact_1.{$field['name']}
                            ELSE  credit_contact_2.{$field['name']}
                            END AS civicrm_financial_account_{$fieldName} ";
                break;

              default:
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                break;
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = 'SELECT ' . implode(', ', $select) . ' ';
  }

  public function from() {
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
              LEFT JOIN civicrm_contact debit_contact ON {$this->_aliases['civicrm_financial_account']}_debit.contact_id = debit_contact.id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_credit_1
                    ON {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id = {$this->_aliases['civicrm_financial_account']}_credit_1.id
              LEFT JOIN civicrm_contact credit_contact_1 ON {$this->_aliases['civicrm_financial_account']}_credit_1.contact_id = credit_contact_1.id
              LEFT JOIN civicrm_entity_financial_trxn {$this->_aliases['civicrm_entity_financial_trxn']}_item
                    ON ({$this->_aliases['civicrm_financial_trxn']}.id = {$this->_aliases['civicrm_entity_financial_trxn']}_item.financial_trxn_id AND
                        {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_table = 'civicrm_financial_item')
              LEFT JOIN civicrm_financial_item fitem
                    ON fitem.id = {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id
              LEFT JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}_credit_2
                    ON fitem.financial_account_id = {$this->_aliases['civicrm_financial_account']}_credit_2.id
              LEFT JOIN civicrm_contact credit_contact_2 ON {$this->_aliases['civicrm_financial_account']}_credit_2.contact_id = credit_contact_2.id
              LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
                    ON  fitem.entity_id = {$this->_aliases['civicrm_line_item']}.id AND fitem.entity_table = 'civicrm_line_item'
              ";

    if ($this->isTableSelected('civicrm_batch')) {
      $this->_from .= "LEFT JOIN civicrm_entity_batch ent_batch
                    ON  {$this->_aliases['civicrm_financial_trxn']}.id = ent_batch.entity_id AND ent_batch.entity_table = 'civicrm_financial_trxn'
              LEFT JOIN civicrm_batch batch
                    ON  ent_batch.batch_id = batch.id";
    }
  }

  public function orderBy() {
    parent::orderBy();

    // please note this will just add the order-by columns to select query, and not display in column-headers.
    // This is a solution to not throw fatal errors when there is a column in order-by, not present in select/display columns.
    foreach ($this->_orderByFields as $orderBy) {
      if (!array_key_exists($orderBy['name'], $this->_params['fields']) &&
        empty($orderBy['section'])
      ) {
        $this->_select .= ", {$orderBy['dbAlias']} as {$orderBy['tplField']}";
      }
    }
  }

  public function where() {
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (in_array($fieldName, [
            'credit_accounting_code',
            'credit_name',
            'credit_contact_id',
          ])) {
            $field['dbAlias'] = "CASE
              WHEN financial_trxn_civireport.from_financial_account_id IS NOT NULL
              THEN  financial_account_civireport_credit_1.{$field['name']}
              ELSE  financial_account_civireport_credit_2.{$field['name']}
              END";
          }
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
    if (empty($clauses)) {
      $this->_where = 'WHERE ( 1 )';
    }
    else {
      $this->_where = 'WHERE ' . implode(' AND ', $clauses);
    }
  }

  public function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  public function groupBy() {
    $groupBy = [
      "{$this->_aliases['civicrm_entity_financial_trxn']}.id",
      "{$this->_aliases['civicrm_line_item']}.id",
    ];
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $financialSelect = "CASE WHEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.entity_id IS NOT NULL
            THEN {$this->_aliases['civicrm_entity_financial_trxn']}_item.amount
            ELSE {$this->_aliases['civicrm_entity_financial_trxn']}.amount
            END as amount";

    $this->_selectClauses = [
      "{$this->_aliases['civicrm_contribution']}.id",
      "{$this->_aliases['civicrm_entity_financial_trxn']}.id as trxnID",
      "{$this->_aliases['civicrm_contribution']}.currency",
      $financialSelect,
    ];
    $select = "SELECT " . implode(', ', $this->_selectClauses);

    $this->groupBy();

    $tempTableName = $this->createTemporaryTable('tempTable', "
                  {$select} {$this->_from} {$this->_where} {$this->_groupBy} ");

    $sql = "SELECT COUNT(trxnID) as count, SUM(amount) as amount, currency
            FROM {$tempTableName}
            GROUP BY currency";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $amount = $avg = [];
    while ($dao->fetch()) {
      $amount[] = CRM_Utils_Money::format($dao->amount, $dao->currency);
      $avg[] = CRM_Utils_Money::format(round(($dao->amount /
        $dao->count), 2), $dao->currency);
    }

    $statistics['counts']['amount'] = [
      'value' => implode(', ', $amount),
      'title' => ts('Total Amount'),
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $statistics['counts']['avg'] = [
      'value' => implode(', ', $avg),
      'title' => ts('Average'),
      'type' => CRM_Utils_Type::T_STRING,
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
    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $creditCardTypes = CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id');
    foreach ($rows as $rowNum => $row) {
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        !empty($rows[$rowNum]['civicrm_contact_sort_name']) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Summary for this Contact.');
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
      if ($value = CRM_Utils_Array::value('civicrm_entity_financial_trxn_amount', $row)) {
        $rows[$rowNum]['civicrm_entity_financial_trxn_amount'] = CRM_Utils_Money::format($rows[$rowNum]['civicrm_entity_financial_trxn_amount'], $rows[$rowNum]['civicrm_financial_trxn_currency']);
      }

      //handle gender
      if (array_key_exists('civicrm_contact_gender_id', $row)) {
        if ($value = $row['civicrm_contact_gender_id']) {
          $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
          $rows[$rowNum]['civicrm_contact_gender_id'] = $gender[$value];
        }
        $entryFound = TRUE;
      }

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = CRM_Utils_Array::value($row['civicrm_financial_trxn_card_type_id'], $creditCardTypes);
        $entryFound = TRUE;
      }

      // display birthday in the configured custom format
      if (array_key_exists('civicrm_contact_birth_date', $row)) {
        $birthDate = $row['civicrm_contact_birth_date'];
        if ($birthDate) {
          $rows[$rowNum]['civicrm_contact_birth_date'] = CRM_Utils_Date::customFormat($birthDate, '%Y%m%d');
        }
        $entryFound = TRUE;
      }

    }
  }

}
