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
class CRM_Report_Form_Contribute_Lybunt extends CRM_Report_Form {

  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );

  /**
   * This is the report that links will lead to.
   *
   * It is a bit problematic to use contribute/detail for anything other than a single contact
   * as the filtering from this report does not carry over to that report.
   *
   * @var array
   */
  public $_drilldownReport = array('contribute/detail' => 'Link to Detail Report');

  protected $lifeTime_from = NULL;
  protected $lifeTime_where = NULL;
  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
    'Household',
    'Organization',
  );

  /**
   * Table containing list of contact IDs.
   *
   * @var string
   */
  protected $contactTempTable = '';

  /**
   * This report has been optimised for group filtering.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->optimisedForOnlyFullGroupBy = FALSE;
    $this->_rollup = 'WITH ROLLUP';
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $yearsInPast = 10;
    $yearsInFuture = 1;
    $date = CRM_Core_SelectValues::date('custom', NULL, $yearsInPast, $yearsInFuture);
    $count = $date['maxYear'];
    while ($date['minYear'] <= $count) {
      $optionYear[$date['minYear']] = $date['minYear'];
      $date['minYear']++;
    }

    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'grouping' => 'contact-field',
        'fields' => $this->getBasicContactFields(),
        'order_bys' => array(
          'sort_name' => array(
            'title' => ts('Last Name, First Name'),
            'default' => '0',
            'default_order' => 'ASC',
          ),
          'first_name' => array(
            'name' => 'first_name',
            'title' => ts('First Name'),
          ),
          'gender_id' => array(
            'name' => 'gender_id',
            'title' => ts('Gender'),
          ),
          'birth_date' => array(
            'name' => 'birth_date',
            'title' => ts('Birth Date'),
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => ts('Donor Name'),
            'operator' => 'like',
          ),
          'id' => array(
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
          ),
          'gender_id' => array(
            'title' => ts('Gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'),
          ),
          'birth_date' => array(
            'title' => ts('Birth Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
          ),
          'is_deceased' => array(),
          'do_not_phone' => array(),
          'do_not_email' => array(),
          'do_not_sms' => array(),
          'do_not_mail' => array(),
          'is_opt_out' => array(),
        ),
      ),
      'civicrm_line_item' => array(
        'dao' => 'CRM_Price_DAO_LineItem',
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'grouping' => 'contact-field',
        'fields' => array(
          'email' => array(
            'title' => ts('Email'),
            'default' => TRUE,
          ),
          'on_hold' => array(
            'title' => ts('Email on hold'),
          ),
        ),
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'grouping' => 'contact-field',
        'fields' => array(
          'phone' => array(
            'title' => ts('Phone'),
            'default' => TRUE,
          ),
        ),
      ),
    );
    $this->_columns += $this->addAddressFields(FALSE);
    $this->_columns += array(
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'contact_id' => array(
            'title' => ts('contactId'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'receive_date' => array(
            'title' => ts('Year'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'last_year_total_amount' => array(
            'title' => ts('Last Year Total'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
            'required' => TRUE,
          ),
          'civicrm_life_time_total' => array(
            'title' => ts('Lifetime Total'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
            'statistics' => array('sum' => ts('Lifetime total')),
          ),
        ),
        'filters' => array(
          'yid' => array(
            'name' => 'receive_date',
            'title' => ts('This Year'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $optionYear,
            'default' => date('Y'),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes(),
          ),
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array('1'),
          ),
        ),
        'order_bys' => array(
          'last_year_total_amount' => array(
            'title' => ts('Total amount last year'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'DESC',
          ),
        ),
      ),
    );
    $this->_columns += array(
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

    // If we have a campaign, build out the relevant elements
    $this->addCampaignFields('civicrm_contribution');

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  /**
   * Build select clause for a single field.
   *
   * @param string $tableName
   * @param string $tableKey
   * @param string $fieldName
   * @param string $field
   *
   * @return string
   */
  public function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    if ($fieldName == 'last_year_total_amount') {
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = $field;
      $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $this->getLastYearColumnTitle();
      $this->_statFields[$this->getLastYearColumnTitle()] = "{$tableName}_{$fieldName}";
      return "SUM(IF(" . $this->whereClauseLastYear('contribution_civireport.receive_date') .  ", contribution_civireport.total_amount, 0)) as {$tableName}_{$fieldName}";
    }
    if ($fieldName == 'civicrm_life_time_total') {
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = $field;
      $this->_statFields[$field['title']] = "{$tableName}_{$fieldName}";
      return "SUM({$this->_aliases[$tableName]}.total_amount) as {$tableName}_{$fieldName}";
    }
    if ($fieldName == 'receive_date') {
      return self::fiscalYearOffset($field['dbAlias']) .
        " as {$tableName}_{$fieldName} ";
    }
    return FALSE;
  }

  /**
   * Get the title for the last year column.
   */
  public function getLastYearColumnTitle() {
    if ($this->getYearFilterType() == 'calendar') {
      return ts('Total for ') . ($this->getCurrentYear() - 1);
    }
    return ts('Total for Fiscal Year ') . ($this->getCurrentYear() - 1) . '-' . ($this->getCurrentYear());
  }

  /**
   * Construct from clause.
   *
   * On the first run we are creating a table of contacts to include in the report.
   *
   * Once contactTempTable is populated we should avoid using any further filters that affect
   * the contacts that should be visible.
   */
  public function from() {
    if (!empty($this->contactTempTable)) {
      $this->_from = "
        FROM  civicrm_contribution {$this->_aliases['civicrm_contribution']}
        INNER JOIN $this->contactTempTable restricted_contacts
          ON restricted_contacts.cid = {$this->_aliases['civicrm_contribution']}.contact_id
          AND {$this->_aliases['civicrm_contribution']}.is_test = 0
        INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
          ON restricted_contacts.cid = {$this->_aliases['civicrm_contact']}.id";

      $this->joinAddressFromContact();
      $this->joinPhoneFromContact();
      $this->joinEmailFromContact();
    }
    else {
      $this->setFromBase('civicrm_contact');

      $this->_from .= " INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} ";
      if (!$this->groupTempTable) {
        // The received_date index is better than the contribution_status_id index (fairly substantially).
        // But if we have already pre-filtered down to a group of contacts then we want that to be the
        // primary filter and the index hint will block that.
        $this->_from .= "USE index (received_date)";
      }
      $this->_from .= " ON {$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_contact']}.id
         AND {$this->_aliases['civicrm_contribution']}.is_test = 0
         AND " . $this->whereClauseLastYear("{$this->_aliases['civicrm_contribution']}.receive_date") . "
       {$this->_aclFrom} ";
      $this->selectivelyAddLocationTablesJoinsToFilterQuery();
    }

    // for credit card type
    $this->addFinancialTrxnFromClause();
  }

  /**
   * Generate where clause.
   *
   * We are overriding this primarily for 'before-after' handling of the receive_date placeholder field.
   *
   * We call this twice. The first time we are generating a temp table and we want to do an IS NULL on the
   * join that draws in contributions from this year. The second time we are filtering elsewhere (contacts via
   * the temp table & contributions via selective addition of contributions in the select function).
   *
   * If lifetime total is NOT selected we can add a further filter here to possibly improve performance
   * but the benefit if unproven as yet.
   * $clause = $this->whereClauseLastYear("{$this->_aliases['civicrm_contribution']}.receive_date");
   *
   * @param array $field Field specifications
   * @param string $op Query operator (not an exact match to sql)
   * @param mixed $value
   * @param float $min
   * @param float $max
   *
   * @return null|string
   */
  public function whereClause(&$field, $op, $value, $min, $max) {
    if ($field['name'] == 'receive_date') {
      $clause = 1;
      if (empty($this->contactTempTable)) {
        $clause = "{$this->_aliases['civicrm_contact']}.id NOT IN (
          SELECT cont_exclude.contact_id
          FROM civicrm_contribution cont_exclude
          WHERE " . $this->whereClauseThisYear('cont_exclude.receive_date')
        . ")";
      }
    }
    // Group filtering is already done so skip.
    elseif (!empty($field['group']) && $this->contactTempTable) {
      return 1;
    }
    else {
      $clause = parent::whereClause($field, $op, $value, $min, $max);
    }
    return $clause;
  }

  /**
   * Generate where clause for last calendar year or fiscal year.
   *
   * @todo must be possible to re-use relative dates stuff.
   *
   * @param string $fieldName
   *
   * @return string
   */
  public function whereClauseLastYear($fieldName) {
    return "$fieldName BETWEEN '" . $this->getFirstDateOfPriorRange() . "' AND '" . $this->getLastDateOfPriorRange() . "'";
  }

  /**
   * Generate where clause for last calendar year or fiscal year.
   *
   * @todo must be possible to re-use relative dates stuff.
   *
   * @param string $fieldName
   *
   * @param int $current_year
   * @return null|string
   */
  public function whereClauseThisYear($fieldName, $current_year = NULL) {
    return "$fieldName BETWEEN '" . $this->getFirstDateOfCurrentRange() . "' AND '" . $this->getLastDateOfCurrentRange() . "'";
  }


  /**
   * Get the year value for the current year.
   *
   * @return string
   */
  public function getCurrentYear() {
    return $this->_params['yid_value'];
  }

  /**
   * Get the date time of the first date in the 'this year' range.
   *
   * @return string
   */
  public function getFirstDateOfCurrentRange() {
    $current_year = $this->getCurrentYear();
    if ($this->getYearFilterType() == 'calendar') {
      return "{$current_year }-01-01";
    }
    else {
      $fiscalYear = CRM_Core_Config::singleton()->fiscalYearStart;
      return "{$current_year}-{$fiscalYear['M']}-{$fiscalYear['d']}";
    }
  }

  /**
   * Get the year value for the current year.
   *
   * @return string
   */
  public function getYearFilterType() {
    return CRM_Utils_Array::value('yid_op', $this->_params, 'calendar');
  }

  /**
   * Get the date time of the last date in the 'this year' range.
   *
   * @return string
   */
  public function getLastDateOfCurrentRange() {
    return date('YmdHis', strtotime('+ 1 year - 1 second', strtotime($this->getFirstDateOfCurrentRange())));
  }

  /**
   * Get the date time of the first date in the 'last year' range.
   *
   * @return string
   */
  public function getFirstDateOfPriorRange() {
    return date('YmdHis', strtotime('- 1 year', strtotime($this->getFirstDateOfCurrentRange())));
  }

  /**
   * Get the date time of the last date in the 'last year' range.
   *
   * @return string
   */
  public function getLastDateOfPriorRange() {
    return date('YmdHis', strtotime('+ 1 year - 1 second', strtotime($this->getFirstDateOfPriorRange())));
  }


  public function groupBy() {
    $this->_groupBy = "GROUP BY  {$this->_aliases['civicrm_contribution']}.contact_id ";
    $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect($this->_selectClauses, "{$this->_aliases['civicrm_contribution']}.contact_id");
    $this->assign('chartSupported', TRUE);
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {

    $statistics = parent::statistics($rows);
    // The parent class does something odd where it adds an extra row to the count for the grand total.
    // Perhaps that works on some other report? But here it just seems odd.
    $this->countStat($statistics, count($rows));
    if (!empty($rows)) {
      if (!empty($this->rollupRow) && !empty($this->rollupRow['civicrm_contribution_last_year_total_amount'])) {
        $statistics['counts']['civicrm_contribution_last_year_total_amount'] = array(
          'value' => $this->rollupRow['civicrm_contribution_last_year_total_amount'],
          'title' => $this->getLastYearColumnTitle(),
          'type' => CRM_Utils_Type::T_MONEY,
        );

      }
      if (!empty($this->rollupRow) && !empty($this->rollupRow['civicrm_contribution_civicrm_life_time_total'])) {
        $statistics['counts']['civicrm_contribution_civicrm_life_time_total'] = array(
          'value' => $this->rollupRow['civicrm_contribution_civicrm_life_time_total'],
          'title' => ts('Total LifeTime'),
          'type' => CRM_Utils_Type::T_MONEY,
        );
      }
      else {
        $select = "SELECT SUM({$this->_aliases['civicrm_contribution']}.total_amount) as amount,
          SUM(IF( " . $this->whereClauseLastYear('contribution_civireport.receive_date') .  ", contribution_civireport.total_amount, 0)) as last_year
         ";
        $sql = "{$select} {$this->_from} {$this->_where}";
        $dao = CRM_Core_DAO::executeQuery($sql);
        if ($dao->fetch()) {
          $statistics['counts']['amount'] = array(
            'value' => $dao->amount,
            'title' => ts('Total LifeTime'),
            'type' => CRM_Utils_Type::T_MONEY,
          );
          $statistics['counts']['last_year'] = array(
            'value' => $dao->last_year,
            'title' => $this->getLastYearColumnTitle(),
            'type' => CRM_Utils_Type::T_MONEY,
          );
        }
      }
    }

    return $statistics;
  }

  /**
   * This function is called by both the api (tests) and the UI.
   */
  public function beginPostProcessCommon() {
    $this->buildQuery();
    // @todo this acl has no test coverage and is very hard to test manually so could be fragile.
    $this->resetFormSqlAndWhereHavingClauses();

    $this->contactTempTable = $this->createTemporaryTable('rptlybunt', "
      SELECT SQL_CALC_FOUND_ROWS {$this->_aliases['civicrm_contact']}.id as cid {$this->_from}
      {$this->_where}
      GROUP BY {$this->_aliases['civicrm_contact']}.id"
    );
    $this->limit();
    if (empty($this->_params['charts'])) {
      $this->setPager();
    }

    // Reset where clauses to be regenerated in postProcess.
    $this->_whereClauses = array();
  }

  /**
   * Build the report query.
   *
   * The issue we are hitting is that if we want to do group by & then ORDER BY we have to
   * wrap the query in an outer query with the order by - otherwise the group by takes precedent.
   * This is an issue when we want to group by contact but order by the maximum aggregate donation.
   *
   * @param bool $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    $this->buildGroupTempTable();
    $this->buildPermissionClause();
    // Calling where & select before FROM allows us to build temp tables to use in from.
    $this->where();
    $this->select();
    $this->from();
    $this->customDataFrom(empty($this->contactTempTable));

    $this->groupBy();
    $this->orderBy();
    $limitFilter = '';

    // order_by columns not selected for display need to be included in SELECT
    // This differs from parent in that we are getting those not in order by rather than not in
    // sections, as we need to adapt to our contact group by.
    $unselectedSectionColumns = array_diff_key($this->_orderByFields, $this->getSelectColumns());
    foreach ($unselectedSectionColumns as $alias => $section) {
      $this->_select .= ", {$section['dbAlias']} as {$alias}";
    }

    if ($applyLimit && empty($this->_params['charts'])) {
      $this->limit();
    }

    $sql = "{$this->_select} {$this->_from} {$this->_where} {$limitFilter} {$this->_groupBy} {$this->_having} {$this->_rollup}";

    if (!empty($this->_orderByArray)) {
      $this->_orderBy = str_replace('contact_civireport.', 'civicrm_contact_', "ORDER BY ISNULL(civicrm_contribution_contact_id), " . implode(', ', $this->_orderByArray));
      $this->_orderBy = str_replace('contribution_civireport.', 'civicrm_contribution_', $this->_orderBy);
      foreach ($this->_orderByFields as $field) {
        $this->_orderBy = str_replace($field['dbAlias'], $field['tplField'], $this->_orderBy);
      }
      $sql = str_replace('SQL_CALC_FOUND_ROWS', '', $sql);
      $sql = "SELECT SQL_CALC_FOUND_ROWS  * FROM ( $sql ) as inner_query {$this->_orderBy} $this->_limit";
    }

    CRM_Utils_Hook::alterReportVar('sql', $this, $this);
    $this->addToDeveloperTab($sql);

    return $sql;
  }

  /**
   * Reset the form sql and where / having clause arrays.
   *
   * We do an early iteration of the report queries to generate the temp table.
   *
   * However, that iteration populates the sql for the developer tab,
   * the whereClauses & the havingClauses and they are populated again in the normal
   * report flow. This is harmless but confusing - ie. the where clause winds up repeating
   * the same filters and the dev tab shows the query twice, so we rest them.
   */
  protected function resetFormSqlAndWhereHavingClauses() {
    $this->sql = '';
    $this->_havingClauses = array();
    $this->_whereClauses = array();
    $this->sqlArray = array();
  }

  /**
   * @param $rows
   */
  public function buildChart(&$rows) {

    $graphRows = array();
    $count = 0;
    $display = array();

    $current_year = $this->_params['yid_value'];
    $previous_year = $current_year - 1;
    $interval[$previous_year] = $previous_year;
    $interval['life_time'] = 'Life Time';

    foreach ($rows as $key => $row) {
      $display['life_time'] = CRM_Utils_Array::value('life_time', $display) +
        $row['civicrm_life_time_total'];
      $display[$previous_year] = CRM_Utils_Array::value($previous_year, $display) + $row[$previous_year];
    }

    $config = CRM_Core_Config::Singleton();
    $graphRows['value'] = $display;
    $chartInfo = array(
      'legend' => ts('Lybunt Report'),
      'xname' => ts('Year'),
      'yname' => ts('Amount (%1)', array(1 => $config->defaultCurrency)),
    );
    if ($this->_params['charts']) {
      // build chart.
      CRM_Utils_OpenFlashChart::reportChart($graphRows, $this->_params['charts'], $interval, $chartInfo);
      $this->assign('chartType', $this->_params['charts']);
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

    foreach ($rows as $rowNum => $row) {
      //Convert Display name into link
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contribution_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' .
          $row['civicrm_contribution_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contribution Details for this Contact.");
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->campaigns[$value];
          $entryFound = TRUE;
        }
      }
      // Display 'Yes' if the email is on hold (leave blank for no so it stands out better).
      if (array_key_exists('civicrm_email_on_hold', $row)) {
        $rows[$rowNum]['civicrm_email_on_hold'] = $row['civicrm_email_on_hold'] ? ts('Yes') : '';
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, 'List all contribution(s)') ? TRUE : $entryFound;
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, NULL, 'List all contribution(s)') ? TRUE : $entryFound;

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * Override "This Year" $op options
   * @param string $type
   * @param null $fieldName
   *
   * @return array
   */
  public function getOperationPair($type = "string", $fieldName = NULL) {
    if ($fieldName == 'yid') {
      return array(
        'calendar' => ts('Is Calendar Year'),
        'fiscal' => ts('Fiscal Year Starting'),
      );
    }
    return parent::getOperationPair($type, $fieldName);
  }

}
