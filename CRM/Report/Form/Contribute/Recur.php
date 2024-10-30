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
class CRM_Report_Form_Contribute_Recur extends CRM_Report_Form {

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
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'order_bys' => [
          'sort_name' => [
            'title' => ts("Last name, First name"),
          ],
        ],
        'fields' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'title' => ts('Email'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => [
            'title' => ts('Phone'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'total_amount' => [
            'title' => ts('Amount Contributed to Date'),
            'statistics' => [
              'sum' => ts("Total Amount Contributed"),
            ],
          ],
          'source' => [
            'title' => ts('Contribution Source'),
          ],
        ],
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
      'civicrm_contribution_recur' => [
        'dao' => 'CRM_Contribute_DAO_ContributionRecur',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'currency' => [
            'title' => ts("Currency"),
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
          ],
          'frequency_interval' => [
            'title' => ts('Frequency interval'),
            'default' => TRUE,
          ],
          'frequency_unit' => [
            'title' => ts('Frequency unit'),
            'default' => TRUE,
          ],
          'amount' => [
            'title' => ts('Installment Amount'),
            'default' => TRUE,
          ],
          'installments' => [
            'title' => ts('Installments'),
            'default' => TRUE,
          ],
          'start_date' => [
            'title' => ts('Start Date'),
          ],
          'create_date' => [
            'title' => ts('Create Date'),
          ],
          'modified_date' => [
            'title' => ts('Modified Date'),
          ],
          'cancel_date' => [
            'title' => ts('Cancel Date'),
          ],
          'end_date' => [
            'title' => ts('End Date'),
          ],
          'next_sched_contribution_date' => [
            'title' => ts('Next Scheduled Contribution Date'),
          ],
          'failure_count' => [
            'title' => ts('Failure Count'),
          ],
          'failure_retry_date' => [
            'title' => ts('Failure Retry Date'),
          ],
          'payment_processor_id' => [
            'title' => ts('Payment Processor'),
          ],
        ],
        'filters' => [
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => [5],
            'type' => CRM_Utils_Type::T_INT,
          ],
          'currency' => [
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'frequency_unit' => [
            'title' => ts('Frequency Unit'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('recur_frequency_units'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'frequency_interval' => [
            'title' => ts('Frequency Interval'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'amount' => [
            'title' => ts('Installment Amount'),
            'type' => CRM_Utils_Type::T_MONEY,
          ],
          'installments' => [
            'title' => ts('Installments'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'start_date' => [
            'title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          ],
          'next_sched_contribution_date' => [
            'title' => ts('Next Scheduled Contribution Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          ],
          'end_date' => [
            'title' => ts('End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          ],
          'modified_date' => [
            'title' => ts('Last Contribution Processed'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          ],
          'calculated_end_date' => [
            'title' => ts('Calculated end date (either end date or date all installments will be made)'),
            'description' => "does this work?",
            'operatorType' => CRM_Report_Form::OP_DATE,
            'pseudofield' => TRUE,
          ],
          'payment_processor_id' => [
            'title' => ts('Payment Processor'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_ContributionRecur::buildOptions('payment_processor_id', 'get'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
      ],
    ];
    $this->_currencyColumn = 'civicrm_contribution_recur_currency';
    $this->_groupFilter = TRUE;
    parent::__construct();
  }

  /**
   * Get template file name.
   *
   * @return string
   */
  public function getTemplateName() {
    return 'CRM/Report/Form.tpl';
  }

  /**
   * Generate FROM SQL clause.
   */
  public function from() {
    $this->_from = "
      FROM civicrm_contact  {$this->_aliases['civicrm_contact']}
        INNER JOIN civicrm_contribution_recur   {$this->_aliases['civicrm_contribution_recur']}
          ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution_recur']}.contact_id";
    $this->_from .= "
      LEFT JOIN civicrm_contribution  {$this->_aliases['civicrm_contribution']}
        ON {$this->_aliases['civicrm_contribution_recur']}.id = {$this->_aliases['civicrm_contribution']}.contribution_recur_id";

    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    // for credit card type
    $this->addFinancialTrxnFromClause();
  }

  public function groupBy() {
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, "{$this->_aliases['civicrm_contribution_recur']}.id");
  }

  public function where() {
    parent::where();
    // Handle calculated end date. This can come from one of two sources:
    // Either there is a specified end date for the end_date field
    // Or, we calculate the end date based on the start date +
    // installments * intervals using the mysql date_add function, along
    // with the interval unit (e.g. DATE_ADD(start_date, INTERVAL 12 * 1 MONTH)
    $date_suffixes = ['relative', 'from', 'to'];
    foreach ($date_suffixes as $suffix) {
      $isBreak = FALSE;
      // Check to see if the user wants to search by calculated date.
      if (!empty($this->_params['calculated_end_date_' . $suffix])) {
        // The calculated date field is in use - spring into action
        // Gather values
        $relative = $this->_params["calculated_end_date_relative"] ?? NULL;
        $from = $this->_params["calculated_end_date_from"] ?? NULL;
        $to = $this->_params["calculated_end_date_to"] ?? NULL;
        $end_date_db_alias = $this->_columns['civicrm_contribution_recur']['filters']['end_date']['dbAlias'];
        $end_date_type = $this->_columns['civicrm_contribution_recur']['filters']['end_date']['type'];
        $start_date_type = $this->_columns['civicrm_contribution_recur']['filters']['start_date']['type'];
        $frequency_unit_db_alias = $this->_columns['civicrm_contribution_recur']['filters']['frequency_unit']['dbAlias'];
        $frequency_interval_db_alias = $this->_columns['civicrm_contribution_recur']['filters']['frequency_interval']['dbAlias'];
        $installments_db_alias = $this->_columns['civicrm_contribution_recur']['filters']['installments']['dbAlias'];
        $start_date_db_alias = $this->_columns['civicrm_contribution_recur']['filters']['start_date']['dbAlias'];

        // The end date clause is simple to construct
        $end_date_clause = $this->dateClause($end_date_db_alias, $relative, $from, $to, $end_date_type, NULL, NULL);

        // NOTE: For the calculation based on installment, there doesn't
        // seem to be a way to include the interval unit (e.g. month,
        // date, etc) as a field name - so we have to build a complex
        // OR statement instead.

        $this->_where .= 'AND (' .
          $this->dateClause("DATE_ADD($start_date_db_alias, INTERVAL $installments_db_alias * COALESCE($frequency_interval_db_alias,1) month)",
            $relative, $from, $to, $start_date_type, NULL, NULL);
        $this->_where .= " AND $frequency_unit_db_alias = 'month' ) OR \n";

        $this->_where .= '(' .
          $this->dateClause("DATE_ADD($start_date_db_alias, INTERVAL $installments_db_alias * COALESCE($frequency_interval_db_alias,1) day)",
            $relative, $from, $to, $start_date_type, NULL, NULL);
        $this->_where .= " AND $frequency_unit_db_alias = 'day' ) OR \n";

        $this->_where .= '(' .
          $this->dateClause("DATE_ADD($start_date_db_alias, INTERVAL $installments_db_alias * COALESCE($frequency_interval_db_alias, 1) week)",
            $relative, $from, $to, $start_date_type, NULL, NULL);
        $this->_where .= " AND $frequency_unit_db_alias = 'week' ) OR \n";

        $this->_where .= '(' .
          $this->dateClause("DATE_ADD($start_date_db_alias, INTERVAL $installments_db_alias * COALESCE($frequency_interval_db_alias, 1) year)",
            $relative, $from, $to, $start_date_type, NULL, NULL);
        $this->_where .= " AND $frequency_unit_db_alias = 'year' )
   AND (($end_date_db_alias IS NOT NULL AND $end_date_clause)
    OR ($installments_db_alias IS NOT NULL))
";
        $isBreak = TRUE;
      }
      if (!empty($this->_params['modified_date_' . $suffix])) {
        $this->_where .= " AND {$this->_aliases['civicrm_contribution_recur']}.contribution_status_id = 1";
        $isBreak = TRUE;
      }
      if (!empty($isBreak)) {
        break;
      }
    }
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
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    foreach ($rows as $rowNum => $row) {
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        CRM_Utils_Array::value('civicrm_contact_sort_name', $rows[$rowNum]) &&
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
      $value = $row['civicrm_contribution_recur_contribution_status_id'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_recur_contribution_status_id'] = $contributionStatus[$value];
      }

      $value = $row['civicrm_contribution_recur_amount'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_recur_amount'] = CRM_Utils_Money::format($rows[$rowNum]['civicrm_contribution_recur_amount'], $rows[$rowNum]['civicrm_contribution_recur_currency']);
      }

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
      }

      if (!empty($row['civicrm_contribution_recur_payment_processor_id'])) {
        $rows[$rowNum]['civicrm_contribution_recur_payment_processor_id'] = $this->getLabels($row['civicrm_contribution_recur_payment_processor_id'], 'CRM_Contribute_BAO_ContributionRecur', 'payment_processor_id');
      }
    }
  }

}
