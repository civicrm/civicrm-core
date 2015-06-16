<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Report_Form_Contribute_Summary extends CRM_Report_Form {
  protected $_addressField = FALSE;

  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );
  protected $_customGroupExtends = array('Contribution');
  protected $_customGroupGroupBy = TRUE;

  public $_drilldownReport = array('contribute/detail' => 'Link to Detail Report');

  /**
   */
  public function __construct() {

    // Check if CiviCampaign is a) enabled and b) has active campaigns
    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
      $this->activeCampaigns = $getCampaigns['campaigns'];
      asort($this->activeCampaigns);
    }

    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'no_repeat' => TRUE,
          ),
          'postal_greeting_display' => array('title' => ts('Postal Greeting')),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
          ),
        ),
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
          'total_amount' => array(
            'title' => ts('Contribution Amount Stats'),
            'default' => TRUE,
            'statistics' => array(
              'count' => ts('Contributions'),
              'sum' => ts('Contribution Aggregate'),
              'avg' => ts('Contribution Avg'),
            ),
          ),
        ),
        'grouping' => 'contri-fields',
        'filters' => array(
          'receive_date' => array('operatorType' => CRM_Report_Form::OP_DATE),
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'currency' => array(
            'title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
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
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
            'type' => CRM_Utils_Type::T_INT,
          ),
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
            'title' => 'Soft Credit Type',
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

    // If we have a campaign, build out the relevant elements
    if ($campaignEnabled && !empty($this->activeCampaigns)) {
      $this->_columns['civicrm_contribution']['fields']['campaign_id'] = array(
        'title' => 'Campaign',
        'default' => 'false',
      );
      $this->_columns['civicrm_contribution']['filters']['campaign_id'] = array(
        'title' => ts('Campaign'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => $this->activeCampaigns,
      );
      $this->_columns['civicrm_contribution']['group_bys']['campaign_id'] = array('title' => ts('Campaign'));
    }

    $this->_tagFilter = TRUE;
    $this->_groupFilter = TRUE;
    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  /**
   * @param bool $freeze
   *
   * @return array
   */
  public function setDefaultValues($freeze = TRUE) {
    return parent::setDefaultValues($freeze);
  }

  public function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
          if (!empty($this->_params['group_bys'][$fieldName])) {
            switch (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
              case 'YEARWEEK':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";
                $select[] = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Week';
                break;

              case 'YEAR':
                $select[] = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Year';
                break;

              case 'MONTH':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                $select[] = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Month';
                break;

              case 'QUARTER':
                $select[] = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Quarter';
                break;
            }
            if (!empty($this->_params['group_bys_freq'][$fieldName])) {
              $this->_interval = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title'] = $field['title'] . ' Beginning';
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
          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
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

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    //check for searching combination of dispaly columns and
    //grouping criteria
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

    $this->_from = "
        FROM civicrm_contact  {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
             INNER JOIN civicrm_contribution   {$this->_aliases['civicrm_contribution']}
                     ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND
                        {$this->_aliases['civicrm_contribution']}.is_test = 0
             {$softCreditJoin}
             LEFT  JOIN civicrm_financial_type  {$this->_aliases['civicrm_financial_type']}
                     ON {$this->_aliases['civicrm_contribution']}.financial_type_id ={$this->_aliases['civicrm_financial_type']}.id
             LEFT  JOIN civicrm_email {$this->_aliases['civicrm_email']}
                     ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                        {$this->_aliases['civicrm_email']}.is_primary = 1)

             LEFT  JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                     ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                        {$this->_aliases['civicrm_phone']}.is_primary = 1)";

    if ($this->_addressField) {
      $this->_from .= "
                  LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                         ON {$this->_aliases['civicrm_contact']}.id =
                            {$this->_aliases['civicrm_address']}.contact_id AND
                            {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
  }

  public function groupBy() {
    $this->_groupBy = "";
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

                $append = "YEAR({$field['dbAlias']}),";
                if (in_array(strtolower($this->_params['group_bys_freq'][$fieldName]),
                  array('year')
                )) {
                  $append = '';
                }
                $this->_groupBy[] = "$append {$this->_params['group_bys_freq'][$fieldName]}({$field['dbAlias']})";
                $append = TRUE;
              }
              else {
                $this->_groupBy[] = $field['dbAlias'];
              }
            }
          }
        }
      }

      if (!empty($this->_statFields) &&
        (($append && count($this->_groupBy) <= 1) || (!$append)) &&
        !$this->_having
      ) {
        $this->_rollup = " WITH ROLLUP";
      }
      $this->_groupBy = "GROUP BY " . implode(', ', $this->_groupBy) .
        " {$this->_rollup} ";
    }
    else {
      $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id";
    }
  }

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
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $softCredit = CRM_Utils_Array::value('soft_amount', $this->_params['fields']);
    $onlySoftCredit = $softCredit &&
      !CRM_Utils_Array::value('total_amount', $this->_params['fields']);
    $totalAmount = $average = $softTotalAmount = $softAverage = array();
    $group = "\nGROUP BY {$this->_aliases['civicrm_contribution']}.currency";

    $this->from('contribution');
    $contriSQL = "SELECT
COUNT({$this->_aliases['civicrm_contribution']}.total_amount )        as civicrm_contribution_total_amount_count,
SUM({$this->_aliases['civicrm_contribution']}.total_amount )          as civicrm_contribution_total_amount_sum,
ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as civicrm_contribution_total_amount_avg,
{$this->_aliases['civicrm_contribution']}.currency                    as currency
{$this->_from} {$this->_where} {$group} {$this->_having}";

    if ($softCredit) {
      $this->from();
      $softSQL = "SELECT
COUNT({$this->_aliases['civicrm_contribution_soft']}.amount )        as civicrm_contribution_soft_soft_amount_count,
SUM({$this->_aliases['civicrm_contribution_soft']}.amount )          as civicrm_contribution_soft_soft_amount_sum,
ROUND(AVG({$this->_aliases['civicrm_contribution_soft']}.amount), 2) as civicrm_contribution_soft_soft_amount_avg,
{$this->_aliases['civicrm_contribution']}.currency                    as currency
{$this->_from} {$this->_where} {$group} {$this->_having}";
    }

    $contriDAO = CRM_Core_DAO::executeQuery($contriSQL);

    $totalAmount = $average = $softTotalAmount = $softAverage = array();
    $count = $softCount = 0;
    while ($contriDAO->fetch()) {
      $totalAmount[]
        = CRM_Utils_Money::format($contriDAO->civicrm_contribution_total_amount_sum, $contriDAO->currency) .
        " (" . $contriDAO->civicrm_contribution_total_amount_count . ")";
      $average[] = CRM_Utils_Money::format($contriDAO->civicrm_contribution_total_amount_avg, $contriDAO->currency);
      $count += $contriDAO->civicrm_contribution_total_amount_count;
    }

    if ($softCredit) {
      $softDAO = CRM_Core_DAO::executeQuery($softSQL);
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

  public function postProcess() {
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  public function buildRows($sql, &$rows) {
    $dao = CRM_Core_DAO::executeQuery($sql);
    if (!is_array($rows)) {
      $rows = array();
    }

    // use this method to modify $this->_columnHeaders
    $this->modifyColumnHeaders();

    $unselectedSectionColumns = $this->unselectedSectionColumns();

    //CRM-16338 if both soft-credit and contribution are enabled then process the contribution's
    //total amount's average, count and sum separately and add it to the respective result list
    $softCredit = (!empty($this->_params['fields']['soft_amount']) && !empty($this->_params['fields']['total_amount'])) ? TRUE : FALSE;
    if ($softCredit) {
      $this->from('contribution');
      $contriSQL = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy} {$this->_limit}";
      $contriDAO = CRM_Core_DAO::executeQuery($contriSQL);
      $contriFields = array(
        'civicrm_contribution_total_amount_sum',
        'civicrm_contribution_total_amount_avg',
        'civicrm_contribution_total_amount_count');
      $contriRows = array();
      while ($contriDAO->fetch()) {
        $contriRow = array();
        foreach ($contriFields as $column) {
          $contriRow[$column] = $contriDAO->$column;
        }
        $contriRows[] = $contriRow;
      }
    }

    $count = 0;
    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        if ($softCredit && array_key_exists($key, $contriRows[$count])) {
          $row[$key] = $contriRows[$count][$key];
        }
        elseif (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }

      // section headers not selected for display need to be added to row
      foreach ($unselectedSectionColumns as $key => $values) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }

      $count++;
      $rows[] = $row;
    }

  }

  /**
   * @param $rows
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
        $graphRows['yname'] = "Amount ({$config->defaultCurrency})";
        CRM_Utils_OpenFlashChart::chart($graphRows, $this->_params['charts'], $this->_interval);
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
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();

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

      // If using campaigns, convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->activeCampaigns[$value];
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

}
