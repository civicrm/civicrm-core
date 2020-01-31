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
class CRM_Report_Form_Contribute_Summary extends CRM_Report_Form {

  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );
  protected $_customGroupExtends = array('Contribution', 'Contact', 'Individual');
  protected $_customGroupGroupBy = TRUE;

  public $_drilldownReport = array('contribute/detail' => 'Link to Detail Report');

  /**
   * To what frequency group-by a date column
   *
   * @var array
   */
  protected $_groupByDateFreq = array(
    'MONTH' => 'Month',
    'YEARWEEK' => 'Week',
    'DATE' => 'Day',
    'QUARTER' => 'Quarter',
    'YEAR' => 'Year',
    'FISCALYEAR' => 'Fiscal Year',
  );

  /**
   * This report has been optimised for group filtering.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = FALSE;

  /**
   * Indicate that report is not fully FGB compliant.
   *
   * @var bool
   */
  public $optimisedForOnlyFullGroupBy;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array_merge(
          $this->getBasicContactFields(),
          array(
            'sort_name' => array(
              'title' => ts('Contact Name'),
              'no_repeat' => TRUE,
            ),
          )
        ),
        'filters' => $this->getBasicContactFilters(array('deceased' => NULL)),
        'grouping' => 'contact-fields',
        'group_bys' => array(
          'id' => array('title' => ts('Contact ID')),
          'sort_name' => array(
            'title' => ts('Contact Name'),
          ),
        ),
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => array(
            'title' => ts('Email'),
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_line_item' => array(
        'dao' => 'CRM_Price_DAO_LineItem',
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'phone' => array(
            'title' => ts('Phone'),
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_financial_type' => array(
        'dao' => 'CRM_Financial_DAO_FinancialType',
        'fields' => array('financial_type' => NULL),
        'grouping' => 'contri-fields',
        'group_bys' => array(
          'financial_type' => array('title' => ts('Financial Type')),
        ),
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
          //'bao'           => 'CRM_Contribute_BAO_Contribution',
        'fields' => array(
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
          ),
          'contribution_source' => array('title' => ts('Source')),
          'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'contribution_page_id' => array(
            'title' => ts('Contribution Page'),
          ),
          'total_amount' => array(
            'title' => ts('Contribution Amount Stats'),
            'default' => TRUE,
            'statistics' => array(
              'count' => ts('Contributions'),
              'sum' => ts('Contribution Aggregate'),
              'avg' => ts('Contribution Avg'),
            ),
          ),
          'non_deductible_amount' => array(
            'title' => ts('Non-deductible Amount'),
          ),
        ),
        'grouping' => 'contri-fields',
        'filters' => array(
          'receive_date' => array('operatorType' => CRM_Report_Form::OP_DATE),
          'thankyou_date' => array('operatorType' => CRM_Report_Form::OP_DATE),
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => array(1),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'contribution_page_id' => array(
            'title' => ts('Contribution Page'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionPage(),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'currency' => array(
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes(),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'contribution_page_id' => array(
            'title' => ts('Contribution Page'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionPage(),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'total_amount' => array(
            'title' => ts('Contribution Amount'),
          ),
          'non_deductible_amount' => array(
            'title' => ts('Non-deductible Amount'),
          ),
          'total_sum' => array(
            'title' => ts('Contribution Aggregate'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_total_amount_sum',
            'having' => TRUE,
          ),
          'total_count' => array(
            'title' => ts('Contribution Count'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_total_amount_count',
            'having' => TRUE,
          ),
          'total_avg' => array(
            'title' => ts('Contribution Avg'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_total_amount_avg',
            'having' => TRUE,
          ),
        ),
        'group_bys' => array(
          'receive_date' => array(
            'frequency' => TRUE,
            'default' => TRUE,
            'chart' => TRUE,
          ),
          'contribution_source' => NULL,
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => array(1),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'contribution_page_id' => array(
            'title' => ts('Contribution Page'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionPage(),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
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
      'civicrm_batch' => array(
        'dao' => 'CRM_Batch_DAO_EntityBatch',
        'grouping' => 'contri-fields',
        'fields' => array(
          'batch_id' => array(
            'name' => 'batch_id',
            'title' => ts('Batch Title'),
            'dbAlias' => 'GROUP_CONCAT(DISTINCT batch_civireport.batch_id
                                    ORDER BY batch_civireport.batch_id SEPARATOR ",")',
          ),
        ),
        'filters' => array(
          'batch_id' => array(
            'title' => ts('Batch Title'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Batch_BAO_Batch::getBatches(),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'group_bys' => array(
          'batch_id' => array('title' => ts('Batch Title')),
        ),
      ),
      'civicrm_contribution_soft' => array(
        'dao' => 'CRM_Contribute_DAO_ContributionSoft',
        'fields' => array(
          'soft_amount' => array(
            'title' => ts('Soft Credit Amount Stats'),
            'name' => 'amount',
            'statistics' => array(
              'count' => ts('Soft Credits'),
              'sum' => ts('Soft Credit Aggregate'),
              'avg' => ts('Soft Credit Avg'),
            ),
          ),
        ),
        'grouping' => 'contri-fields',
        'filters' => array(
          'amount' => array(
            'title' => ts('Soft Credit Amount'),
          ),
          'soft_credit_type_id' => array(
            'title' => ts('Soft Credit Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('soft_credit_type'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'soft_sum' => array(
            'title' => ts('Soft Credit Aggregate'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_soft_soft_amount_sum',
            'having' => TRUE,
          ),
          'soft_count' => array(
            'title' => ts('Soft Credits Count'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_soft_soft_amount_count',
            'having' => TRUE,
          ),
          'soft_avg' => array(
            'title' => ts('Soft Credit Avg'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_soft_soft_amount_avg',
            'having' => TRUE,
          ),
        ),
      ),
    ) + $this->addAddressFields();

    $this->addCampaignFields('civicrm_contribution', TRUE);

    $this->_tagFilter = TRUE;
    $this->_groupFilter = TRUE;
    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  /**
   * Set select clause.
   */
  public function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (!empty($this->_params['group_bys'][$fieldName])) {
            switch (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
              case 'YEARWEEK':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";
                $select[] = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = ts('Week Beginning');
                break;

              case 'YEAR':
                $select[] = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = ts('Year Beginning');
                break;

              case 'FISCALYEAR':
                $config = CRM_Core_Config::singleton();
                $fy = $config->fiscalYearStart;
                $fiscal = self::fiscalYearOffset($field['dbAlias']);

                $select[] = "DATE_ADD(MAKEDATE({$fiscal}, 1), INTERVAL ({$fy{'M'}})-1 MONTH) AS {$tableName}_{$fieldName}_start";
                $select[] = "{$fiscal} AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "{$fiscal} AS {$tableName}_{$fieldName}_interval";
                $field['title'] = ts('Fiscal Year Beginning');
                break;

              case 'MONTH':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                $select[] = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = ts('Month Beginning');
                break;

              case 'DATE':
                $select[] = "DATE({$field['dbAlias']}) as {$tableName}_{$fieldName}_start";
                $field['title'] = ts('Date');
                break;

              case 'QUARTER':
                $select[] = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Quarter';
                break;
            }
            if (!empty($this->_params['group_bys_freq'][$fieldName])) {
              $this->_interval = $this->_params['group_bys_freq'][$fieldName];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['group_by'] = $this->_params['group_bys_freq'][$fieldName];

              // just to make sure these values are transferred to rows.
              // since we need that for calculation purpose,
              // e.g making subtotals look nicer or graphs
              $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = array('no_display' => TRUE);
              $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = array('no_display' => TRUE);
            }
          }
        }
      }

      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            // only include statistics columns if set
            if (!empty($field['statistics'])) {
              foreach ($field['statistics'] as $stat => $label) {
                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                switch (strtolower($stat)) {
                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'count':
                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    break;

                  case 'avg':
                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            }
          }
        }
      }
    }

    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * Set form rules.
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Report_Form_Contribute_Summary $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    // Check for searching combination of display columns and
    // grouping criteria
    $ignoreFields = array('total_amount', 'sort_name');
    $errors = $self->customDataFormRule($fields, $ignoreFields);

    if (empty($fields['fields']['total_amount'])) {
      foreach (array(
        'total_count_value',
        'total_sum_value',
        'total_avg_value',
      ) as $val) {
        if (!empty($fields[$val])) {
          $errors[$val] = ts("Please select the Amount Statistics");
        }
      }
    }

    return $errors;
  }

  /**
   * Set from clause.
   *
   * @param string $entity
   *
   * @todo fix function signature to match parent. Remove hacky passing of $entity
   * to acheive unclear results.
   */
  public function from($entity = NULL) {
    $softCreditJoinType = "LEFT";
    if (!empty($this->_params['fields']['soft_amount']) &&
      empty($this->_params['fields']['total_amount'])
    ) {
      // if its only soft credit stats, use inner join
      $softCreditJoinType = "INNER";
    }

    $softCreditJoin = "{$softCreditJoinType} JOIN civicrm_contribution_soft {$this->_aliases['civicrm_contribution_soft']}
                       ON {$this->_aliases['civicrm_contribution_soft']}.contribution_id = {$this->_aliases['civicrm_contribution']}.id";
    if ($entity == 'contribution' || empty($this->_params['fields']['soft_amount'])) {
      $softCreditJoin .= " AND {$this->_aliases['civicrm_contribution_soft']}.id = (SELECT MIN(id) FROM civicrm_contribution_soft cs WHERE cs.contribution_id = {$this->_aliases['civicrm_contribution']}.id) ";
    }

    $this->setFromBase('civicrm_contact');

    $this->_from .= "
             INNER JOIN civicrm_contribution   {$this->_aliases['civicrm_contribution']}
                     ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND
                        {$this->_aliases['civicrm_contribution']}.is_test = 0
             {$softCreditJoin}
             LEFT  JOIN civicrm_financial_type  {$this->_aliases['civicrm_financial_type']}
                     ON {$this->_aliases['civicrm_contribution']}.financial_type_id ={$this->_aliases['civicrm_financial_type']}.id
             ";

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    //for contribution batches
    if ($this->isTableSelected('civicrm_batch')) {
      $this->_from .= "
        LEFT JOIN civicrm_entity_financial_trxn eft
          ON eft.entity_id = {$this->_aliases['civicrm_contribution']}.id AND
            eft.entity_table = 'civicrm_contribution'
        LEFT JOIN civicrm_entity_batch {$this->_aliases['civicrm_batch']}
          ON ({$this->_aliases['civicrm_batch']}.entity_id = eft.financial_trxn_id
          AND {$this->_aliases['civicrm_batch']}.entity_table = 'civicrm_financial_trxn')";
    }

    $this->addFinancialTrxnFromClause();
  }

  /**
   * Set group by clause.
   */
  public function groupBy() {
    $this->_groupBy = "";
    $groupByColumns = array();
    $append = FALSE;
    if (!empty($this->_params['group_bys']) &&
      is_array($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (!empty($this->_params['group_bys'][$fieldName])) {
              if (!empty($field['chart'])) {
                $this->assign('chartSupported', TRUE);
              }

              if (!empty($table['group_bys'][$fieldName]['frequency']) &&
                !empty($this->_params['group_bys_freq'][$fieldName])
              ) {

                $append = "YEAR({$field['dbAlias']});;";
                if (in_array(strtolower($this->_params['group_bys_freq'][$fieldName]),
                  array('year')
                )) {
                  $append = '';
                }
                if ($this->_params['group_bys_freq'][$fieldName] == 'FISCALYEAR') {
                  $groupByColumns[] = self::fiscalYearOffset($field['dbAlias']);
                }
                else {
                  $groupByColumns[] = "$append {$this->_params['group_bys_freq'][$fieldName]}({$field['dbAlias']})";
                }
                $append = TRUE;
              }
              else {
                $groupByColumns[] = $field['dbAlias'];
              }
            }
          }
        }
      }

      if (!empty($this->_statFields) &&
        (($append && count($groupByColumns) <= 1) || (!$append)) &&
        !$this->_having
      ) {
        $this->_rollup = " WITH ROLLUP";
      }
      $groupBy = array();
      foreach ($groupByColumns as $key => $val) {
        if (strpos($val, ';;') !== FALSE) {
          $groupBy = array_merge($groupBy, explode(';;', $val));
        }
        else {
          $groupBy[] = $groupByColumns[$key];
        }
      }
      $this->_groupBy = "GROUP BY " . implode(', ', $groupBy);
    }
    else {
      $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id";
    }
    $this->_groupBy .= $this->_rollup;
  }

  /**
   * Store having clauses as an array.
   */
  public function storeWhereHavingClauseArray() {
    parent::storeWhereHavingClauseArray();
    if (empty($this->_params['fields']['soft_amount']) &&
      !empty($this->_havingClauses)
    ) {
      foreach ($this->_havingClauses as $key => $havingClause) {
        if (stristr($havingClause, 'soft_soft')) {
          unset($this->_havingClauses[$key]);
        }
      }
    }
  }

  /**
   * Set statistics.
   *
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $softCredit = CRM_Utils_Array::value('soft_amount', $this->_params['fields']);
    $onlySoftCredit = $softCredit && !CRM_Utils_Array::value('total_amount', $this->_params['fields']);
    $group = "\nGROUP BY {$this->_aliases['civicrm_contribution']}.currency";

    $this->from('contribution');
    if ($softCredit) {
      $this->from();
    }
    $this->customDataFrom();

    // Ensure that Extensions that modify the from statement in the sql also modify it in the statistics.
    CRM_Utils_Hook::alterReportVar('sql', $this, $this);

    $contriQuery = "
COUNT({$this->_aliases['civicrm_contribution']}.total_amount )        as civicrm_contribution_total_amount_count,
SUM({$this->_aliases['civicrm_contribution']}.total_amount )          as civicrm_contribution_total_amount_sum,
ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as civicrm_contribution_total_amount_avg,
{$this->_aliases['civicrm_contribution']}.currency                    as currency
{$this->_from} {$this->_where}";

    if ($softCredit) {
      $select = "
COUNT({$this->_aliases['civicrm_contribution_soft']}.amount )        as civicrm_contribution_soft_soft_amount_count,
SUM({$this->_aliases['civicrm_contribution_soft']}.amount )          as civicrm_contribution_soft_soft_amount_sum,
ROUND(AVG({$this->_aliases['civicrm_contribution_soft']}.amount), 2) as civicrm_contribution_soft_soft_amount_avg";
      $contriQuery = "{$select}, {$contriQuery}";
      $softSQL = "SELECT {$select}, {$this->_aliases['civicrm_contribution']}.currency as currency
      {$this->_from} {$this->_where} {$group} {$this->_having}";
    }

    $contriSQL = "SELECT {$contriQuery} {$group} {$this->_having}";
    $contriDAO = CRM_Core_DAO::executeQuery($contriSQL);
    $this->addToDeveloperTab($contriSQL);
    $totalAmount = $average = $mode = $median = $softTotalAmount = $softAverage = array();
    $count = $softCount = 0;
    while ($contriDAO->fetch()) {
      $totalAmount[]
        = CRM_Utils_Money::format($contriDAO->civicrm_contribution_total_amount_sum, $contriDAO->currency) .
        " (" . $contriDAO->civicrm_contribution_total_amount_count . ")";
      $average[] = CRM_Utils_Money::format($contriDAO->civicrm_contribution_total_amount_avg, $contriDAO->currency);
      $count += $contriDAO->civicrm_contribution_total_amount_count;
    }

    $groupBy = "\n{$group}, {$this->_aliases['civicrm_contribution']}.total_amount";
    $orderBy = "\nORDER BY civicrm_contribution_total_amount_count DESC";
    $modeSQL = "SELECT MAX(civicrm_contribution_total_amount_count) as civicrm_contribution_total_amount_count,
      SUBSTRING_INDEX(GROUP_CONCAT(amount ORDER BY mode.civicrm_contribution_total_amount_count DESC SEPARATOR ';'), ';', 1) as amount,
      currency
      FROM (SELECT {$this->_aliases['civicrm_contribution']}.total_amount as amount,
    {$contriQuery} {$groupBy} {$orderBy}) as mode GROUP BY currency";

    $mode = $this->calculateMode($modeSQL);
    $median = $this->calculateMedian();

    if ($softCredit) {
      $softDAO = CRM_Core_DAO::executeQuery($softSQL);
      $this->addToDeveloperTab($softSQL);
      while ($softDAO->fetch()) {
        $softTotalAmount[]
          = CRM_Utils_Money::format($softDAO->civicrm_contribution_soft_soft_amount_sum, $softDAO->currency) .
          " (" . $softDAO->civicrm_contribution_soft_soft_amount_count . ")";
        $softAverage[] = CRM_Utils_Money::format($softDAO->civicrm_contribution_soft_soft_amount_avg, $softDAO->currency);
        $softCount += $softDAO->civicrm_contribution_soft_soft_amount_count;
      }
    }

    if (!$onlySoftCredit) {
      $statistics['counts']['amount'] = array(
        'title' => ts('Total Amount'),
        'value' => implode(',  ', $totalAmount),
        'type' => CRM_Utils_Type::T_STRING,
      );
      $statistics['counts']['count'] = array(
        'title' => ts('Total Contributions'),
        'value' => $count,
      );
      $statistics['counts']['avg'] = array(
        'title' => ts('Average'),
        'value' => implode(',  ', $average),
        'type' => CRM_Utils_Type::T_STRING,
      );
      $statistics['counts']['mode'] = array(
        'title' => ts('Mode'),
        'value' => implode(',  ', $mode),
        'type' => CRM_Utils_Type::T_STRING,
      );
      $statistics['counts']['median'] = array(
        'title' => ts('Median'),
        'value' => implode(',  ', $median),
        'type' => CRM_Utils_Type::T_STRING,
      );
    }
    if ($softCredit) {
      $statistics['counts']['soft_amount'] = array(
        'title' => ts('Total Soft Credit Amount'),
        'value' => implode(',  ', $softTotalAmount),
        'type' => CRM_Utils_Type::T_STRING,
      );
      $statistics['counts']['soft_count'] = array(
        'title' => ts('Total Soft Credits'),
        'value' => $softCount,
      );
      $statistics['counts']['soft_avg'] = array(
        'title' => ts('Average Soft Credit'),
        'value' => implode(',  ', $softAverage),
        'type' => CRM_Utils_Type::T_STRING,
      );
    }
    return $statistics;
  }

  /**
   * Post process function.
   */
  public function postProcess() {
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  /**
   * Build chart.
   *
   * @param array $rows
   */
  public function buildChart(&$rows) {
    $graphRows = array();

    if (!empty($this->_params['charts'])) {
      if (!empty($this->_params['group_bys']['receive_date'])) {

        $contrib = !empty($this->_params['fields']['total_amount']) ? TRUE : FALSE;
        $softContrib = !empty($this->_params['fields']['soft_amount']) ? TRUE : FALSE;

        foreach ($rows as $key => $row) {
          if ($row['civicrm_contribution_receive_date_subtotal']) {
            $graphRows['receive_date'][] = $row['civicrm_contribution_receive_date_start'];
            $graphRows[$this->_interval][] = $row['civicrm_contribution_receive_date_interval'];
            if ($softContrib && $contrib) {
              // both contri & soft contri stats are present
              $graphRows['multiValue'][0][] = $row['civicrm_contribution_total_amount_sum'];
              $graphRows['multiValue'][1][] = $row['civicrm_contribution_soft_soft_amount_sum'];
            }
            elseif ($softContrib) {
              // only soft contributions
              $graphRows['multiValue'][0][] = $row['civicrm_contribution_soft_soft_amount_sum'];
            }
            else {
              // only contributions
              $graphRows['multiValue'][0][] = $row['civicrm_contribution_total_amount_sum'];
            }
          }
        }

        if ($softContrib && $contrib) {
          $graphRows['barKeys'][0] = ts('Contributions');
          $graphRows['barKeys'][1] = ts('Soft Credits');
          $graphRows['legend'] = ts('Contributions and Soft Credits');
        }
        elseif ($softContrib) {
          $graphRows['legend'] = ts('Soft Credits');
        }

        // build the chart.
        $config = CRM_Core_Config::Singleton();
        $graphRows['xname'] = $this->_interval;
        $graphRows['yname'] = ts('Amount (%1)', array(1 => $config->defaultCurrency));
        CRM_Utils_Chart::chart($graphRows, $this->_params['charts'], $this->_interval);
        $this->assign('chartType', $this->_params['charts']);
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
    $entryFound = FALSE;
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    $contributionPages = CRM_Contribute_PseudoConstant::contributionPage();
    //CRM-16338 if both soft-credit and contribution are enabled then process the contribution's
    //total amount's average, count and sum separately and add it to the respective result list
    $softCredit = (!empty($this->_params['fields']['soft_amount']) && !empty($this->_params['fields']['total_amount'])) ? TRUE : FALSE;
    if ($softCredit) {
      $this->from('contribution');
      $this->customDataFrom();
      $contriSQL = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";
      CRM_Core_DAO::disableFullGroupByMode();
      $contriDAO = CRM_Core_DAO::executeQuery($contriSQL);
      CRM_Core_DAO::reenableFullGroupByMode();
      $this->addToDeveloperTab($contriSQL);
      $contriFields = array(
        'civicrm_contribution_total_amount_sum',
        'civicrm_contribution_total_amount_avg',
        'civicrm_contribution_total_amount_count',
      );
      $count = 0;
      while ($contriDAO->fetch()) {
        foreach ($contriFields as $column) {
          $rows[$count][$column] = $contriDAO->$column;
        }
        $count++;
      }
    }
    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      if (!empty($this->_params['group_bys']['receive_date']) &&
        !empty($row['civicrm_contribution_receive_date_start']) &&
        CRM_Utils_Array::value('civicrm_contribution_receive_date_start', $row) &&
        !empty($row['civicrm_contribution_receive_date_subtotal'])
      ) {

        $dateStart = CRM_Utils_Date::customFormat($row['civicrm_contribution_receive_date_start'], '%Y%m%d');
        $endDate = new DateTime($dateStart);
        $dateEnd = array();

        list($dateEnd['Y'], $dateEnd['M'], $dateEnd['d']) = explode(':', $endDate->format('Y:m:d'));

        switch (strtolower($this->_params['group_bys_freq']['receive_date'])) {
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

          case 'fiscalyear':
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
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          "reset=1&force=1&receive_date_from={$dateStart}&receive_date_to={$dateEnd}",
          $this->_absoluteUrl,
          $this->_id,
          $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contribution_receive_date_start_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_receive_date_start_hover'] = ts('List all contribution(s) for this date unit.');
        $entryFound = TRUE;
      }

      // make subtotals look nicer
      if (array_key_exists('civicrm_contribution_receive_date_subtotal', $row) &&
        !$row['civicrm_contribution_receive_date_subtotal']
      ) {
        $this->fixSubTotalDisplay($rows[$rowNum], $this->_statFields);
        $entryFound = TRUE;
      }

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("Lists detailed contribution(s) for this record.");
        $entryFound = TRUE;
      }

      // convert contribution status id to status name
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_page_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_page_id'] = $contributionPages[$value];
        $entryFound = TRUE;
      }

      // If using campaigns, convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->campaigns[$value];
        }
        $entryFound = TRUE;
      }

      // convert batch id to batch title
      if (!empty($row['civicrm_batch_batch_id']) && !in_array('Subtotal', $rows[$rowNum])) {
        $rows[$rowNum]['civicrm_batch_batch_id'] = $this->getLabels($row['civicrm_batch_batch_id'], 'CRM_Batch_BAO_EntityBatch', 'batch_id');
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s) for this ') ? TRUE : $entryFound;
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * Calculate mode.
   *
   * Note this is a slow query. Alternative is extended reports.
   *
   * @param string $sql
   * @return array|null
   */
  protected function calculateMode($sql) {
    $mode = [];
    $modeDAO = CRM_Core_DAO::executeQuery($sql);
    while ($modeDAO->fetch()) {
      if ($modeDAO->civicrm_contribution_total_amount_count > 1) {
        $mode[] = CRM_Utils_Money::format($modeDAO->amount, $modeDAO->currency);
      }
      else {
        $mode[] = 'N/A';
      }
    }
    return $mode;
  }

  /**
   * Calculate mode.
   *
   * Note this is a slow query. Alternative is extended reports.
   *
   * @return array|null
   */
  protected function calculateMedian() {
    $sql = "{$this->_from} {$this->_where}";
    $currencies = CRM_Core_OptionGroup::values('currencies_enabled');
    $median = [];
    foreach ($currencies as $currency => $val) {
      $midValue = 0;
      $where = "AND {$this->_aliases['civicrm_contribution']}.currency = '{$currency}'";
      $rowCount = CRM_Core_DAO::singleValueQuery("SELECT count(*) as count {$sql} {$where}");

      $even = FALSE;
      $offset = 1;
      $medianRow = floor($rowCount / 2);
      if ($rowCount % 2 == 0 && !empty($medianRow)) {
        $even = TRUE;
        $offset++;
        $medianRow--;
      }

      $medianValue = "SELECT {$this->_aliases['civicrm_contribution']}.total_amount as median
             {$sql} {$where}
             ORDER BY median LIMIT {$medianRow},{$offset}";
      $medianValDAO = CRM_Core_DAO::executeQuery($medianValue);
      while ($medianValDAO->fetch()) {
        if ($even) {
          $midValue = $midValue + $medianValDAO->median;
        }
        else {
          $median[] = CRM_Utils_Money::format($medianValDAO->median, $currency);
        }
      }
      if ($even) {
        $midValue = $midValue / 2;
        $median[] = CRM_Utils_Money::format($midValue, $currency);
      }
    }
    return $median;
  }

}
