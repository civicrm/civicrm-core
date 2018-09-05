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
 */
class CRM_Report_Form_Contribute_Detail extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_softFrom = NULL;

  protected $noDisplayContributionOrSoftColumn = FALSE;

  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
    'Contribution',
  );

  protected $groupConcatTested = TRUE;

  protected $isTempTableBuilt = FALSE;

  /**
   * Query mode.
   *
   * This can be 'Main' or 'SoftCredit' to denote which query we are building.
   *
   * @var string
   */
  protected $queryMode = 'Main';

  /**
   * Is this report being run on contributions as the base entity.
   *
   * The report structure is generally designed around a base entity but
   * depending on input it can be run in a sort of hybrid way that causes a lot
   * of complexity.
   *
   * If it is in isContributionsOnlyMode we can simplify.
   *
   * (arguably there should be 2 separate report templates, not one doing double duty.)
   *
   * @var bool
   */
  protected $isContributionBaseMode = FALSE;

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
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    // Check if CiviCampaign is a) enabled and b) has active campaigns
    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
      $this->activeCampaigns = $getCampaigns['campaigns'];
      asort($this->activeCampaigns);
    }

    $this->_columns = array_merge($this->getColumns('Contact', array(
      'order_bys_defaults' => array('sort_name' => 'ASC '),
      'fields_defaults' => array('sort_name'),
      'fields_excluded' => array('id'),
      'fields_required' => array('id'),
      'filters_defaults' => array('is_deleted' => 0),
      'no_field_disambiguation' => TRUE,
    )), array(
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => array(
            'title' => ts('Donor Email'),
            'default' => TRUE,
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
            'title' => ts('Donor Phone'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'contribution_id' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'list_contri_id' => array(
            'name' => 'id',
            'title' => ts('Contribution ID'),
          ),
          'financial_type_id' => array(
            'title' => ts('Financial Type'),
            'default' => TRUE,
          ),
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
          ),
          'contribution_page_id' => array(
            'title' => ts('Contribution Page'),
          ),
          'source' => array(
            'title' => ts('Source'),
          ),
          'payment_instrument_id' => array(
            'title' => ts('Payment Type'),
          ),
          'check_number' => array(
            'title' => ts('Check Number'),
          ),
          'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'trxn_id' => NULL,
          'receive_date' => array('default' => TRUE),
          'receipt_date' => NULL,
          'total_amount' => array(
            'title' => ts('Amount'),
            'required' => TRUE,
            'statistics' => array('sum' => ts('Amount')),
          ),
          'non_deductible_amount' => array(
            'title' => ts('Non-deductible Amount'),
          ),
          'fee_amount' => NULL,
          'net_amount' => NULL,
          'contribution_or_soft' => array(
            'title' => ts('Contribution OR Soft Credit?'),
            'dbAlias' => "'Contribution'",
          ),
          'soft_credits' => array(
            'title' => ts('Soft Credits'),
            'dbAlias' => "NULL",
          ),
          'soft_credit_for' => array(
            'title' => ts('Soft Credit For'),
            'dbAlias' => "NULL",
          ),
        ),
        'filters' => array(
          'contribution_or_soft' => array(
            'title' => ts('Contribution OR Soft Credit?'),
            'clause' => "(1)",
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'type' => CRM_Utils_Type::T_STRING,
            'options' => array(
              'contributions_only' => ts('Contributions Only'),
              'soft_credits_only' => ts('Soft Credits Only'),
              'both' => ts('Both'),
            ),
          ),
          'receive_date' => array('operatorType' => CRM_Report_Form::OP_DATE),
          'contribution_source' => array(
            'title' => ts('Source'),
            'name' => 'source',
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'currency' => array(
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'non_deductible_amount' => array(
            'title' => ts('Non-deductible Amount'),
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
          'payment_instrument_id' => array(
            'title' => ts('Payment Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
            'type' => CRM_Utils_Type::T_INT,
          ),
          'total_amount' => array('title' => ts('Contribution Amount')),
        ),
        'order_bys' => array(
          'financial_type_id' => array('title' => ts('Financial Type')),
          'contribution_status_id' => array('title' => ts('Contribution Status')),
          'payment_instrument_id' => array('title' => ts('Payment Method')),
          'receive_date' => array('title' => ts('Date Received')),
        ),
        'group_bys' => array(
          'contribution_id' => array(
            'name' => 'id',
            'required' => TRUE,
            'title' => ts('Contribution'),
          ),
        ),
        'grouping' => 'contri-fields',
      ),
      'civicrm_contribution_soft' => array(
        'dao' => 'CRM_Contribute_DAO_ContributionSoft',
        'fields' => array(
          'soft_credit_type_id' => array('title' => ts('Soft Credit Type')),
        ),
        'filters' => array(
          'soft_credit_type_id' => array(
            'title' => ts('Soft Credit Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('soft_credit_type'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
      'civicrm_financial_trxn' => array(
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => array(
          'card_type_id' => array(
            'title' => ts('Credit Card Type'),
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
            'title' => ts('Batch Name'),
          ),
        ),
        'filters' => array(
          'bid' => array(
            'title' => ts('Batch Name'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Batch_BAO_Batch::getBatches(),
            'type' => CRM_Utils_Type::T_INT,
            'dbAlias' => 'batch_civireport.batch_id',
          ),
        ),
      ),
      'civicrm_contribution_ordinality' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'alias' => 'cordinality',
        'filters' => array(
          'ordinality' => array(
            'title' => ts('Contribution Ordinality'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => array(
              0 => 'First by Contributor',
              1 => 'Second or Later by Contributor',
            ),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
      ),
      'civicrm_note' => array(
        'dao' => 'CRM_Core_DAO_Note',
        'fields' => array(
          'contribution_note' => array(
            'name' => 'note',
            'title' => ts('Contribution Note'),
          ),
        ),
        'filters' => array(
          'note' => array(
            'name' => 'note',
            'title' => ts('Contribution Note'),
            'operator' => 'like',
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
    )) + $this->addAddressFields(FALSE);
    // The tests test for this variation of the sort_name field. Don't argue with the tests :-).
    $this->_columns['civicrm_contact']['fields']['sort_name']['title'] = ts('Donor Name');
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;

    // If we have active campaigns add those elements to both the fields and filters
    if ($campaignEnabled && !empty($this->activeCampaigns)) {
      $this->_columns['civicrm_contribution']['fields']['campaign_id'] = array(
        'title' => ts('Campaign'),
        'default' => 'false',
      );
      $this->_columns['civicrm_contribution']['filters']['campaign_id'] = array(
        'title' => ts('Campaign'),
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => $this->activeCampaigns,
        'type' => CRM_Utils_Type::T_INT,
      );
      $this->_columns['civicrm_contribution']['order_bys']['campaign_id'] = array('title' => ts('Campaign'));
    }

    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  /**
   * Set the FROM clause for the report.
   */
  public function from() {
    $this->setFromBase('civicrm_contact');
    $this->_from .= "
      INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
        ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id
        AND {$this->_aliases['civicrm_contribution']}.is_test = 0";

    if (CRM_Utils_Array::value('contribution_or_soft_value', $this->_params) ==
      'both'
    ) {
      $this->_from .= "\n LEFT JOIN civicrm_contribution_soft contribution_soft_civireport
                         ON contribution_soft_civireport.contribution_id = {$this->_aliases['civicrm_contribution']}.id";
    }
    elseif (CRM_Utils_Array::value('contribution_or_soft_value', $this->_params) ==
      'soft_credits_only'
    ) {
      $this->_from .= "\n INNER JOIN civicrm_contribution_soft contribution_soft_civireport
                         ON contribution_soft_civireport.contribution_id = {$this->_aliases['civicrm_contribution']}.id";
    }
    $this->appendAdditionalFromJoins();
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $totalAmount = $average = $fees = $net = array();
    $count = 0;
    $select = "
        SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount ) as count,
               SUM( {$this->_aliases['civicrm_contribution']}.total_amount ) as amount,
               ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as avg,
               {$this->_aliases['civicrm_contribution']}.currency as currency,
               SUM( {$this->_aliases['civicrm_contribution']}.fee_amount ) as fees,
               SUM( {$this->_aliases['civicrm_contribution']}.net_amount ) as net
        ";

    $group = "\nGROUP BY {$this->_aliases['civicrm_contribution']}.currency";
    $sql = "{$select} {$this->_from} {$this->_where} {$group}";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->addToDeveloperTab($sql);

    while ($dao->fetch()) {
      $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency) . " (" . $dao->count . ")";
      $fees[] = CRM_Utils_Money::format($dao->fees, $dao->currency);
      $net[] = CRM_Utils_Money::format($dao->net, $dao->currency);
      $average[] = CRM_Utils_Money::format($dao->avg, $dao->currency);
      $count += $dao->count;
    }
    $statistics['counts']['amount'] = array(
      'title' => ts('Total Amount (Contributions)'),
      'value' => implode(',  ', $totalAmount),
      'type' => CRM_Utils_Type::T_STRING,
    );
    $statistics['counts']['count'] = array(
      'title' => ts('Total Contributions'),
      'value' => $count,
    );
    $statistics['counts']['fees'] = array(
      'title' => ts('Fees'),
      'value' => implode(',  ', $fees),
      'type' => CRM_Utils_Type::T_STRING,
    );
    $statistics['counts']['net'] = array(
      'title' => ts('Net'),
      'value' => implode(',  ', $net),
      'type' => CRM_Utils_Type::T_STRING,
    );
    $statistics['counts']['avg'] = array(
      'title' => ts('Average'),
      'value' => implode(',  ', $average),
      'type' => CRM_Utils_Type::T_STRING,
    );

    // Stats for soft credits
    if ($this->_softFrom &&
      CRM_Utils_Array::value('contribution_or_soft_value', $this->_params) !=
      'contributions_only'
    ) {
      $totalAmount = $average = array();
      $count = 0;
      $select = "
SELECT COUNT(contribution_soft_civireport.amount ) as count,
       SUM(contribution_soft_civireport.amount ) as amount,
       ROUND(AVG(contribution_soft_civireport.amount), 2) as avg,
       {$this->_aliases['civicrm_contribution']}.currency as currency";
      $sql = "
{$select}
{$this->_softFrom}
GROUP BY {$this->_aliases['civicrm_contribution']}.currency";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $this->addToDeveloperTab($sql);
      while ($dao->fetch()) {
        $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency) . " (" .
          $dao->count . ")";
        $average[] = CRM_Utils_Money::format($dao->avg, $dao->currency);
        $count += $dao->count;
      }
      $statistics['counts']['softamount'] = array(
        'title' => ts('Total Amount (Soft Credits)'),
        'value' => implode(',  ', $totalAmount),
        'type' => CRM_Utils_Type::T_STRING,
      );
      $statistics['counts']['softcount'] = array(
        'title' => ts('Total Soft Credits'),
        'value' => $count,
      );
      $statistics['counts']['softavg'] = array(
        'title' => ts('Average (Soft Credits)'),
        'value' => implode(',  ', $average),
        'type' => CRM_Utils_Type::T_STRING,
      );
    }

    return $statistics;
  }

  /**
   * Build the report query.
   *
   * @param bool $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    if ($this->isTempTableBuilt) {
      return "SELECT * FROM civireport_contribution_detail_temp3 $this->_orderBy";
    }
    return parent::buildQuery($applyLimit);
  }

  /**
   * Shared function for preliminary processing.
   *
   * This is called by the api / unit tests and the form layer and is
   * the right place to do 'initial analysis of input'.
   */
  public function beginPostProcessCommon() {
    // CRM-18312 - display soft_credits and soft_credits_for column
    // when 'Contribution or Soft Credit?' column is not selected
    if (empty($this->_params['fields']['contribution_or_soft'])) {
      $this->_params['fields']['contribution_or_soft'] = 1;
      $this->noDisplayContributionOrSoftColumn = TRUE;
    }

    if (CRM_Utils_Array::value('contribution_or_soft_value', $this->_params) == 'contributions_only') {
      $this->isContributionBaseMode = TRUE;
    }
    if ($this->isContributionBaseMode &&
      (!empty($this->_params['fields']['soft_credit_type_id'])
      || !empty($this->_params['soft_credit_type_id_value']))
    ) {
      unset($this->_params['fields']['soft_credit_type_id']);
      if (!empty($this->_params['soft_credit_type_id_value'])) {
        $this->_params['soft_credit_type_id_value'] = array();
        CRM_Core_Session::setStatus(ts('Is it not possible to filter on soft contribution type when not including soft credits.'));
      }
    }
    // 1. use main contribution query to build temp table 1
    $sql = $this->buildQuery();
    $tempQuery = "CREATE TEMPORARY TABLE civireport_contribution_detail_temp1 {$this->_databaseAttributes} AS {$sql}";
    $this->temporaryTables['civireport_contribution_detail_temp1'] = ['name' => 'civireport_contribution_detail_temp1', 'temporary' => TRUE];
    $this->executeReportQuery($tempQuery);
    $this->setPager();

    // 2. customize main contribution query for soft credit, and build temp table 2 with soft credit contributions only
    $this->queryMode = 'SoftCredit';
    // Rebuild select with no groupby. Do not let column headers change.
    $headers = $this->_columnHeaders;
    $this->select();
    $this->_columnHeaders = $headers;
    $this->softCreditFrom();
    // also include custom group from if included
    // since this might be included in select
    $this->customDataFrom();

    $select = str_ireplace('contribution_civireport.total_amount', 'contribution_soft_civireport.amount', $this->_select);
    $select = str_ireplace("'Contribution' as", "'Soft Credit' as", $select);
    // We really don't want to join soft credit in if not required.
    if (!empty($this->_groupBy) && !$this->noDisplayContributionOrSoftColumn) {
      $this->_groupBy .= ', contribution_soft_civireport.amount';
    }
    // we inner join with temp1 to restrict soft contributions to those in temp1 table.
    // no group by here as we want to display as many soft credit rows as actually exist.
    $sql = "{$select} {$this->_from} {$this->_where}";
    $tempQuery = "CREATE TEMPORARY TABLE civireport_contribution_detail_temp2 {$this->_databaseAttributes} AS {$sql}";
    $this->executeReportQuery($tempQuery);
    $this->temporaryTables['civireport_contribution_detail_temp2'] = ['name' => 'civireport_contribution_detail_temp2', 'temporary' => TRUE];

    if (CRM_Utils_Array::value('contribution_or_soft_value', $this->_params) ==
      'soft_credits_only'
    ) {
      // revise pager : prev, next based on soft-credits only
      $this->setPager();
    }

    // copy _from for later use of stats calculation for soft credits, and reset $this->_from to main query
    $this->_softFrom = $this->_from;

    // simple reset of ->_from
    $this->from();

    // also include custom group from if included
    // since this might be included in select
    $this->customDataFrom();

    // 3. Decide where to populate temp3 table from
    if ($this->isContributionBaseMode
    ) {
      $this->executeReportQuery(
        "CREATE TEMPORARY TABLE civireport_contribution_detail_temp3 {$this->_databaseAttributes} AS (SELECT * FROM civireport_contribution_detail_temp1)"
      );
    }
    elseif (CRM_Utils_Array::value('contribution_or_soft_value', $this->_params) ==
      'soft_credits_only'
    ) {
      $this->executeReportQuery(
        "CREATE TEMPORARY TABLE civireport_contribution_detail_temp3 {$this->_databaseAttributes} AS (SELECT * FROM civireport_contribution_detail_temp2)"
      );
    }
    else {
      $this->executeReportQuery("CREATE TEMPORARY TABLE civireport_contribution_detail_temp3 {$this->_databaseAttributes}
(SELECT * FROM civireport_contribution_detail_temp1)
UNION ALL
(SELECT * FROM civireport_contribution_detail_temp2)");
    }
    $this->temporaryTables['civireport_contribution_detail_temp3'] = ['name' => 'civireport_contribution_detail_temp3', 'temporary' => TRUE];
    $this->isTempTableBuilt = TRUE;
  }

  /**
   * Store group bys into array - so we can check elsewhere what is grouped.
   *
   * If we are generating a table of soft credits we do not want to be using
   * group by.
   */
  protected function storeGroupByArray() {
    if ($this->queryMode === 'SoftCredit') {
      $this->_groupByArray = [];
    }
    else {
      parent::storeGroupByArray();
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
    $display_flag = $prev_cid = $cid = 0;
    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $contributionPages = CRM_Contribute_PseudoConstant::contributionPage();
    $batches = CRM_Batch_BAO_Batch::getBatches();
    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (array_key_exists('civicrm_contact_id', $row)) {
          if ($cid = $row['civicrm_contact_id']) {
            if ($rowNum == 0) {
              $prev_cid = $cid;
            }
            else {
              if ($prev_cid == $cid) {
                $display_flag = 1;
                $prev_cid = $cid;
              }
              else {
                $display_flag = 0;
                $prev_cid = $cid;
              }
            }

            if ($display_flag) {
              foreach ($row as $colName => $colVal) {
                // Hide repeats in no-repeat columns, but not if the field's a section header
                if (in_array($colName, $this->_noRepeats) &&
                  !array_key_exists($colName, $this->_sections)
                ) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }

      if (CRM_Utils_Array::value('civicrm_contribution_contribution_or_soft', $rows[$rowNum]) ==
        'Contribution'
      ) {
        unset($rows[$rowNum]['civicrm_contribution_soft_soft_credit_type_id']);
      }

      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contribution/detail', ts('View Contribution Details')) ? TRUE : $entryFound;
      // convert donor sort name to link
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        !empty($rows[$rowNum]['civicrm_contact_sort_name']) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
      }

      if ($value = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_contribution_page_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_contribution_page_id'] = $contributionPages[$value];
        $entryFound = TRUE;
      }
      if ($value = CRM_Utils_Array::value('civicrm_contribution_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }
      if (!empty($row['civicrm_batch_batch_id'])) {
        $rows[$rowNum]['civicrm_batch_batch_id'] = CRM_Utils_Array::value($row['civicrm_batch_batch_id'], $batches);
        $entryFound = TRUE;
      }
      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      // Contribution amount links to viewing contribution
      if (($value = CRM_Utils_Array::value('civicrm_contribution_total_amount', $row)) &&
        CRM_Core_Permission::check('access CiviContribute')
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view/contribution",
          "reset=1&id=" . $row['civicrm_contribution_contribution_id'] .
          "&cid=" . $row['civicrm_contact_id'] .
          "&action=view&context=contribution&selectedChild=contribute",
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contribution_total_amount_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_total_amount_hover'] = ts("View Details of this Contribution.");
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->activeCampaigns[$value];
          $entryFound = TRUE;
        }
      }

      // soft credits
      if (array_key_exists('civicrm_contribution_soft_credits', $row) &&
        'Contribution' ==
        CRM_Utils_Array::value('civicrm_contribution_contribution_or_soft', $rows[$rowNum]) &&
        array_key_exists('civicrm_contribution_contribution_id', $row)
      ) {
        $query = "
SELECT civicrm_contact_id, civicrm_contact_sort_name, civicrm_contribution_total_amount, civicrm_contribution_currency
FROM   civireport_contribution_detail_temp2
WHERE  civicrm_contribution_contribution_id={$row['civicrm_contribution_contribution_id']}";
        $this->addToDeveloperTab($query);
        $dao = CRM_Core_DAO::executeQuery($query);
        $string = '';
        $separator = ($this->_outputMode !== 'csv') ? "<br/>" : ' ';
        while ($dao->fetch()) {
          $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' .
            $dao->civicrm_contact_id);
          $string = $string . ($string ? $separator : '') .
            "<a href='{$url}'>{$dao->civicrm_contact_sort_name}</a> " .
            CRM_Utils_Money::format($dao->civicrm_contribution_total_amount, $dao->civicrm_contribution_currency);
        }
        $rows[$rowNum]['civicrm_contribution_soft_credits'] = $string;
      }

      if (array_key_exists('civicrm_contribution_soft_credit_for', $row) &&
        'Soft Credit' ==
        CRM_Utils_Array::value('civicrm_contribution_contribution_or_soft', $rows[$rowNum]) &&
        array_key_exists('civicrm_contribution_contribution_id', $row)
      ) {
        $query = "
SELECT civicrm_contact_id, civicrm_contact_sort_name
FROM   civireport_contribution_detail_temp1
WHERE  civicrm_contribution_contribution_id={$row['civicrm_contribution_contribution_id']}";
        $this->addToDeveloperTab($query);
        $dao = CRM_Core_DAO::executeQuery($query);
        $string = '';
        while ($dao->fetch()) {
          $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' .
            $dao->civicrm_contact_id);
          $string = $string .
            "\n<a href='{$url}'>{$dao->civicrm_contact_sort_name}</a>";
        }
        $rows[$rowNum]['civicrm_contribution_soft_credit_for'] = $string;
      }

      // CRM-18312 - hide 'contribution_or_soft' column if unchecked.
      if (!empty($this->noDisplayContributionOrSoftColumn)) {
        unset($rows[$rowNum]['civicrm_contribution_contribution_or_soft']);
        unset($this->_columnHeaders['civicrm_contribution_contribution_or_soft']);
      }

      //convert soft_credit_type_id into label
      if (array_key_exists('civicrm_contribution_soft_soft_credit_type_id', $rows[$rowNum])) {
        $rows[$rowNum]['civicrm_contribution_soft_soft_credit_type_id'] = CRM_Core_PseudoConstant::getLabel(
          'CRM_Contribute_BAO_ContributionSoft',
          'soft_credit_type_id',
          $row['civicrm_contribution_soft_soft_credit_type_id']
        );
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
      $lastKey = $rowNum;
    }
  }

  public function sectionTotals() {

    // Reports using order_bys with sections must populate $this->_selectAliases in select() method.
    if (empty($this->_selectAliases)) {
      return;
    }

    if (!empty($this->_sections)) {
      // build the query with no LIMIT clause
      $select = str_ireplace('SELECT SQL_CALC_FOUND_ROWS ', 'SELECT ', $this->_select);
      $sql = "{$select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy}";

      // pull section aliases out of $this->_sections
      $sectionAliases = array_keys($this->_sections);

      // hack alert - but it's tested so go forth & make pretty, or whack the new mole that popped up with gay abandon.
      if (in_array('civicrm_contribution_total_amount', $this->_selectAliases)) {
        $keyToHack = array_search('civicrm_contribution_total_amount', $this->_selectAliases);
        $this->_selectAliases[$keyToHack] = 'civicrm_contribution_total_amount_sum';
      }

      $ifnulls = array();
      foreach (array_merge($sectionAliases, $this->_selectAliases) as $alias) {
        $ifnulls[] = "ifnull($alias, '') as $alias";
      }
      $this->_select = "SELECT " . implode(", ", $ifnulls);
      $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect($ifnulls, $sectionAliases);

      /* Group (un-limited) report by all aliases and get counts. This might
       * be done more efficiently when the contents of $sql are known, ie. by
       * overriding this method in the report class.
       */

      $addtotals = '';

      if (array_search("civicrm_contribution_total_amount_sum", $this->_selectAliases) !==
        FALSE
      ) {
        $addtotals = ", sum(civicrm_contribution_total_amount_sum) as sumcontribs";
        $showsumcontribs = TRUE;
      }

      $query = $this->_select .
        "$addtotals, count(*) as ct from civireport_contribution_detail_temp3 group by " .
        implode(", ", $sectionAliases);
      // initialize array of total counts
      $sumcontribs = $totals = array();
      $dao = CRM_Core_DAO::executeQuery($query);
      $this->addToDeveloperTab($query);
      while ($dao->fetch()) {

        // let $this->_alterDisplay translate any integer ids to human-readable values.
        $rows[0] = $dao->toArray();
        $this->alterDisplay($rows);
        $row = $rows[0];

        // add totals for all permutations of section values
        $values = array();
        $i = 1;
        $aliasCount = count($sectionAliases);
        foreach ($sectionAliases as $alias) {
          $values[] = $row[$alias];
          $key = implode(CRM_Core_DAO::VALUE_SEPARATOR, $values);
          if ($i == $aliasCount) {
            // the last alias is the lowest-level section header; use count as-is
            $totals[$key] = $dao->ct;
            if ($showsumcontribs) {
              $sumcontribs[$key] = $dao->sumcontribs;
            }
          }
          else {
            // other aliases are higher level; roll count into their total
            $totals[$key] = (array_key_exists($key, $totals)) ? $totals[$key] + $dao->ct : $dao->ct;
            if ($showsumcontribs) {
              $sumcontribs[$key] = array_key_exists($key, $sumcontribs) ? $sumcontribs[$key] + $dao->sumcontribs : $dao->sumcontribs;
            }
          }
        }
      }
      if ($showsumcontribs) {
        $totalandsum = array();
        // ts exception to avoid having ts("%1 %2: %3")
        $title = '%1 contributions / soft-credits: %2';

        if (CRM_Utils_Array::value('contribution_or_soft_value', $this->_params) ==
          'contributions_only'
        ) {
          $title = '%1 contributions: %2';
        }
        elseif (CRM_Utils_Array::value('contribution_or_soft_value', $this->_params) ==
          'soft_credits_only'
        ) {
          $title = '%1 soft-credits: %2';
        }
        foreach ($totals as $key => $total) {
          $totalandsum[$key] = ts($title, array(
            1 => $total,
            2 => CRM_Utils_Money::format($sumcontribs[$key]),
          ));
        }
        $this->assign('sectionTotals', $totalandsum);
      }
      else {
        $this->assign('sectionTotals', $totals);
      }
    }
  }

  /**
   * Generate the from clause as it relates to the soft credits.
   */
  public function softCreditFrom() {

    $this->_from = "
      FROM  civireport_contribution_detail_temp1 temp1_civireport
      INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
        ON temp1_civireport.civicrm_contribution_contribution_id = {$this->_aliases['civicrm_contribution']}.id
      INNER JOIN civicrm_contribution_soft contribution_soft_civireport
        ON contribution_soft_civireport.contribution_id = {$this->_aliases['civicrm_contribution']}.id
      INNER JOIN civicrm_contact      {$this->_aliases['civicrm_contact']}
        ON {$this->_aliases['civicrm_contact']}.id = contribution_soft_civireport.contact_id
      {$this->_aclFrom}
    ";

    //Join temp table if report is filtered by group. This is specific to 'notin' operator and covered in unit test(ref dev/core#212)
    if (!empty($this->_params['gid_op']) && $this->_params['gid_op'] == 'notin') {
      $this->joinGroupTempTable('civicrm_contact', 'id', $this->_aliases['civicrm_contact']);
    }
    $this->appendAdditionalFromJoins();
  }

  /**
   * Append the joins that are required regardless of context.
   */
  public function appendAdditionalFromJoins() {
    if (!empty($this->_params['ordinality_value'])) {
      $this->_from .= "
              INNER JOIN (SELECT c.id, IF(COUNT(oc.id) = 0, 0, 1) AS ordinality FROM civicrm_contribution c LEFT JOIN civicrm_contribution oc ON c.contact_id = oc.contact_id AND oc.receive_date < c.receive_date GROUP BY c.id) {$this->_aliases['civicrm_contribution_ordinality']}
                      ON {$this->_aliases['civicrm_contribution_ordinality']}.id = {$this->_aliases['civicrm_contribution']}.id";
    }
    $this->joinPhoneFromContact();
    $this->joinAddressFromContact();
    $this->joinEmailFromContact();

    // include contribution note
    if (!empty($this->_params['fields']['contribution_note']) ||
      !empty($this->_params['note_value'])
    ) {
      $this->_from .= "
            LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
                      ON ( {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_contribution' AND
                           {$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_note']}.entity_id )";
    }
    //for contribution batches
    if (!empty($this->_params['fields']['batch_id']) ||
      !empty($this->_params['bid_value'])
    ) {
      $this->_from .= "
        LEFT JOIN civicrm_entity_financial_trxn eft
          ON eft.entity_id = {$this->_aliases['civicrm_contribution']}.id AND
            eft.entity_table = 'civicrm_contribution'
        LEFT JOIN civicrm_entity_batch {$this->_aliases['civicrm_batch']}
          ON ({$this->_aliases['civicrm_batch']}.entity_id = eft.financial_trxn_id
          AND {$this->_aliases['civicrm_batch']}.entity_table = 'civicrm_financial_trxn')";
    }
    // for credit card type
    $this->addFinancialTrxnFromClause();
  }

}
