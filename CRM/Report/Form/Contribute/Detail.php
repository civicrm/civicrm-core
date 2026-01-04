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
class CRM_Report_Form_Contribute_Detail extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_softFrom = NULL;

  protected $noDisplayContributionOrSoftColumn = FALSE;

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Contribution',
  ];

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
   * @var bool
   * @see https://issues.civicrm.org/jira/browse/CRM-19170
   */
  protected $groupFilterNotOptimised = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $this->_columns = array_merge(
      $this->getColumns('Contact', [
        'order_bys_defaults' => ['sort_name' => 'ASC '],
        'fields_defaults' => ['sort_name'],
        'fields_excluded' => ['id'],
        'fields_required' => ['id'],
        'filters_defaults' => ['is_deleted' => 0],
        'no_field_disambiguation' => TRUE,
      ]),
      [
        'civicrm_email' => [
          'dao' => 'CRM_Core_DAO_Email',
          'fields' => [
            'email' => [
              'title' => ts('Donor Email'),
              'default' => TRUE,
            ],
          ],
          'grouping' => 'contact-fields',
        ],
        'civicrm_line_item' => [
          'dao' => 'CRM_Price_DAO_LineItem',
        ],
        'civicrm_phone' => [
          'dao' => 'CRM_Core_DAO_Phone',
          'fields' => [
            'phone' => [
              'title' => ts('Donor Phone'),
              'default' => TRUE,
              'no_repeat' => TRUE,
            ],
          ],
          'grouping' => 'contact-fields',
        ],
        'civicrm_contribution' => [
          'dao' => 'CRM_Contribute_DAO_Contribution',
          'fields' => [
            'contribution_id' => [
              'name' => 'id',
              'no_display' => TRUE,
              'required' => TRUE,
            ],
            'list_contri_id' => [
              'name' => 'id',
              'title' => ts('Contribution ID'),
            ],
            'financial_type_id' => [
              'title' => ts('Financial Type'),
              'default' => TRUE,
            ],
            'contribution_status_id' => [
              'title' => ts('Contribution Status'),
            ],
            'contribution_page_id' => [
              'title' => ts('Contribution Page'),
            ],
            'source' => [
              'title' => ts('Contribution Source'),
            ],
            'payment_instrument_id' => [
              'title' => ts('Payment Type'),
            ],
            'check_number' => [
              'title' => ts('Check Number'),
            ],
            'currency' => [
              'required' => TRUE,
              'no_display' => TRUE,
            ],
            'trxn_id' => NULL,
            'receive_date' => ['default' => TRUE],
            'receipt_date' => NULL,
            'thankyou_date' => NULL,
            'total_amount' => [
              'title' => ts('Amount'),
              'required' => TRUE,
            ],
            'non_deductible_amount' => [
              'title' => ts('Non-deductible Amount'),
            ],
            'fee_amount' => NULL,
            'net_amount' => NULL,
            'contribution_or_soft' => [
              'title' => ts('Contribution OR Soft Credit?'),
              'dbAlias' => "'Contribution'",
            ],
            'soft_credits' => [
              'title' => ts('Soft Credits'),
              'dbAlias' => "NULL",
            ],
            'soft_credit_for' => [
              'title' => ts('Soft Credit For'),
              'dbAlias' => "NULL",
            ],
            'cancel_date' => [
              'title' => ts('Cancelled / Refunded Date'),
              'name' => 'contribution_cancel_date',
            ],
            'cancel_reason' => [
              'title' => ts('Cancellation / Refund Reason'),
            ],
          ],
          'filters' => [
            'contribution_or_soft' => [
              'title' => ts('Contribution OR Soft Credit?'),
              'clause' => "(1)",
              'operatorType' => CRM_Report_Form::OP_SELECT,
              'type' => CRM_Utils_Type::T_STRING,
              'options' => [
                'contributions_only' => ts('Contributions Only'),
                'soft_credits_only' => ts('Soft Credits Only'),
                'both' => ts('Both'),
              ],
              'default' => 'contributions_only',
            ],
            'receive_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
            'receipt_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
            'thankyou_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
            'contribution_source' => [
              'title' => ts('Contribution Source'),
              'name' => 'source',
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'currency' => [
              'title' => ts('Currency'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
              'default' => NULL,
              'type' => CRM_Utils_Type::T_STRING,
            ],
            'non_deductible_amount' => [
              'title' => ts('Non-deductible Amount'),
            ],
            'financial_type_id' => [
              'title' => ts('Financial Type'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
              'type' => CRM_Utils_Type::T_INT,
            ],
            'contribution_page_id' => [
              'title' => ts('Contribution Page'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Contribute_PseudoConstant::contributionPage(),
              'type' => CRM_Utils_Type::T_INT,
            ],
            'payment_instrument_id' => [
              'title' => ts('Payment Type'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
              'type' => CRM_Utils_Type::T_INT,
            ],
            'contribution_status_id' => [
              'title' => ts('Contribution Status'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
              'default' => [1],
              'type' => CRM_Utils_Type::T_INT,
            ],
            'total_amount' => ['title' => ts('Contribution Amount')],
            'cancel_date' => [
              'title' => ts('Cancelled / Refunded Date'),
              'operatorType' => CRM_Report_Form::OP_DATE,
              'name' => 'contribution_cancel_date',
            ],
            'cancel_reason' => [
              'title' => ts('Cancellation / Refund Reason'),
            ],
          ],
          'order_bys' => [
            'financial_type_id' => ['title' => ts('Financial Type')],
            'contribution_status_id' => ['title' => ts('Contribution Status')],
            'payment_instrument_id' => ['title' => ts('Payment Method')],
            'receive_date' => ['title' => ts('Contribution Date')],
            'receipt_date' => ['title' => ts('Receipt Date')],
            'thankyou_date' => ['title' => ts('Thank-you Date')],
          ],
          'group_bys' => [
            'contribution_id' => [
              'name' => 'id',
              'required' => TRUE,
              'default' => TRUE,
              'title' => ts('Contribution'),
            ],
          ],
          'grouping' => 'contri-fields',
        ],
        'civicrm_pledge_payment' => [
          'dao' => 'CRM_Pledge_DAO_PledgePayment',
          'filters' => [
            'contribution_id' => [
              'title' => ts('Contribution is a pledge payment'),
              'type' => CRM_Utils_Type::T_BOOLEAN,
            ],
          ],
        ],
        'civicrm_contribution_soft' => [
          'dao' => 'CRM_Contribute_DAO_ContributionSoft',
          'fields' => [
            'soft_credit_type_id' => ['title' => ts('Soft Credit Type')],
            'soft_credit_amount' => ['title' => ts('Soft Credit amount'), 'name' => 'amount', 'type' => CRM_Utils_Type::T_MONEY],
          ],
          'filters' => [
            'soft_credit_type_id' => [
              'title' => ts('Soft Credit Type'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Core_OptionGroup::values('soft_credit_type'),
              'default' => NULL,
              'type' => CRM_Utils_Type::T_STRING,
            ],
          ],
          'group_bys' => [
            'soft_credit_id' => [
              'name' => 'id',
              'title' => ts('Soft Credit'),
            ],
          ],
        ],
        'civicrm_financial_trxn' => [
          'dao' => 'CRM_Financial_DAO_FinancialTrxn',
          'fields' => [
            'card_type_id' => [
              'title' => ts('Credit Card Type'),
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
        'civicrm_batch' => [
          'dao' => 'CRM_Batch_DAO_EntityBatch',
          'grouping' => 'contri-fields',
          'fields' => [
            'batch_id' => [
              'name' => 'batch_id',
              'title' => ts('Batch Name'),
            ],
          ],
          'filters' => [
            'bid' => [
              'title' => ts('Batch Name'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => CRM_Batch_BAO_Batch::getBatches(),
              'type' => CRM_Utils_Type::T_INT,
              'dbAlias' => 'batch_civireport.batch_id',
            ],
          ],
        ],
        'civicrm_contribution_ordinality' => [
          'dao' => 'CRM_Contribute_DAO_Contribution',
          'alias' => 'cordinality',
          'filters' => [
            'ordinality' => [
              'title' => ts('Contribution Ordinality'),
              'operatorType' => CRM_Report_Form::OP_MULTISELECT,
              'options' => [
                0 => ts('First by Contributor'),
                1 => ts('Second or Later by Contributor'),
              ],
              'type' => CRM_Utils_Type::T_INT,
            ],
          ],
        ],
        'civicrm_note' => [
          'dao' => 'CRM_Core_DAO_Note',
          'fields' => [
            'contribution_note' => [
              'name' => 'note',
              'title' => ts('Contribution Note'),
            ],
          ],
          'filters' => [
            'note' => [
              'name' => 'note',
              'title' => ts('Contribution Note'),
              'operator' => 'like',
              'type' => CRM_Utils_Type::T_STRING,
            ],
          ],
        ],
        'civicrm_pledge_payment' => [
          'dao' => 'CRM_Pledge_DAO_PledgePayment',
          'fields' => [
            'pledge_id' => [
              'title' => ts('Pledge ID'),
            ],
          ],
          'filters' => [
            'pledge_id' => [
              'title' => ts('Pledge ID'),
              'type' => CRM_Utils_Type::T_INT,
            ],
          ],
        ],
      ],
      $this->getColumns('Address')
    );
    // The tests test for this variation of the sort_name field. Don't argue with the tests :-).
    $this->_columns['civicrm_contact']['fields']['sort_name']['title'] = ts('Donor Name');
    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    // If we have campaigns enabled, add those elements to both the fields, filters and sorting
    $this->addCampaignFields('civicrm_contribution', FALSE, TRUE);

    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  /**
   * Validate incompatible report settings.
   *
   * @return bool
   *   true if no error found
   */
  public function validate() {
    // If you're displaying Contributions Only, you can't group by soft credit.
    $contributionOrSoftVal = $this->getElementValue('contribution_or_soft_value');
    if ($contributionOrSoftVal[0] == 'contributions_only') {
      $groupBySoft = $this->getElementValue('group_bys');
      if (!empty($groupBySoft['soft_credit_id'])) {
        $this->setElementError('group_bys', ts('You cannot group by soft credit when displaying contributions only.  Please uncheck "Soft Credit" in the Grouping tab.'));
      }
    }

    return parent::validate();
  }

  /**
   * Set the FROM clause for the report.
   */
  public function from() {
    $this->setFromBase('civicrm_contact');
    $this->_from .= "
      INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
        ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id
        AND {$this->_aliases['civicrm_contribution']}.is_test = 0
        AND {$this->_aliases['civicrm_contribution']}.is_template = 0";

    $this->joinContributionToSoftCredit();
    $this->appendAdditionalFromJoins();
  }

  /**
   * @param array $rows
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $totalAmount = $average = $fees = $net = [];
    $count = 0;
    $select = "
        SELECT COUNT(civicrm_contribution_total_amount ) as count,
               SUM( civicrm_contribution_total_amount ) as amount,
               ROUND(AVG(civicrm_contribution_total_amount), 2) as avg,
               stats.currency as currency,
               SUM( stats.fee_amount ) as fees,
               SUM( stats.net_amount ) as net
        ";

    $group = "\nGROUP BY civicrm_contribution_currency";
    $from = " FROM {$this->temporaryTables['civireport_contribution_detail_temp3']['name']} "
    . "JOIN civicrm_contribution stats ON {$this->temporaryTables['civireport_contribution_detail_temp3']['name']}.civicrm_contribution_contribution_id = stats.id ";
    $sql = "{$select} {$from} {$group} ";
    CRM_Core_DAO::disableFullGroupByMode();
    $dao = CRM_Core_DAO::executeQuery($sql);
    CRM_Core_DAO::reenableFullGroupByMode();
    $this->addToDeveloperTab($sql);

    while ($dao->fetch()) {
      $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency) . " (" . $dao->count . ")";
      $fees[] = CRM_Utils_Money::format($dao->fees, $dao->currency);
      $net[] = CRM_Utils_Money::format($dao->net, $dao->currency);
      $average[] = CRM_Utils_Money::format($dao->avg, $dao->currency);
      $count += $dao->count;
    }
    $statistics['counts']['amount'] = [
      'title' => ts('Total Amount (Contributions)'),
      'value' => implode(',  ', $totalAmount),
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $statistics['counts']['count'] = [
      'title' => ts('Total Contributions'),
      'value' => $count,
    ];
    $statistics['counts']['fees'] = [
      'title' => ts('Fees'),
      'value' => implode(',  ', $fees),
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $statistics['counts']['net'] = [
      'title' => ts('Net'),
      'value' => implode(',  ', $net),
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $statistics['counts']['avg'] = [
      'title' => ts('Average'),
      'value' => implode(',  ', $average),
      'type' => CRM_Utils_Type::T_STRING,
    ];

    // Stats for soft credits
    if ($this->_softFrom &&
      ($this->_params['contribution_or_soft_value'] ?? NULL) !=
      'contributions_only'
    ) {
      $totalAmount = $average = [];
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
      $statistics['counts']['softamount'] = [
        'title' => ts('Total Amount (Soft Credits)'),
        'value' => implode(',  ', $totalAmount),
        'type' => CRM_Utils_Type::T_STRING,
      ];
      $statistics['counts']['softcount'] = [
        'title' => ts('Total Soft Credits'),
        'value' => $count,
      ];
      $statistics['counts']['softavg'] = [
        'title' => ts('Average (Soft Credits)'),
        'value' => implode(',  ', $average),
        'type' => CRM_Utils_Type::T_STRING,
      ];
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
  public function buildQuery($applyLimit = FALSE) {
    if ($this->isTempTableBuilt) {
      $this->limit();
      return "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->temporaryTables['civireport_contribution_detail_temp3']['name']} $this->_orderBy $this->_limit";
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

    if (($this->_params['contribution_or_soft_value'] ?? NULL) == 'contributions_only') {
      $this->isContributionBaseMode = TRUE;
    }
    if ($this->isContributionBaseMode &&
      (!empty($this->_params['fields']['soft_credit_type_id'])
      || !empty($this->_params['soft_credit_type_id_value']))
    ) {
      unset($this->_params['fields']['soft_credit_type_id']);
      if (!empty($this->_params['soft_credit_type_id_value'])) {
        $this->_params['soft_credit_type_id_value'] = [];
        CRM_Core_Session::setStatus(ts('Is it not possible to filter on soft contribution type when not including soft credits.'));
      }
    }
    // 1. use main contribution query to build temp table 1
    $sql = $this->buildQuery();
    $this->createTemporaryTable('civireport_contribution_detail_temp1', $sql);

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

    // we inner join with temp1 to restrict soft contributions to those in temp1 table.
    // no group by here as we want to display as many soft credit rows as actually exist.
    CRM_Utils_Hook::alterReportVar('sql', $this, $this);
    $sql = "{$select} {$this->_from} {$this->_where} $this->_groupBy";
    $this->createTemporaryTable('civireport_contribution_detail_temp2', $sql);

    if (($this->_params['contribution_or_soft_value'] ?? NULL) ==
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
      $this->createTemporaryTable('civireport_contribution_detail_temp3',
        "(SELECT * FROM {$this->temporaryTables['civireport_contribution_detail_temp1']['name']})"
      );
    }
    elseif (($this->_params['contribution_or_soft_value'] ?? NULL) ==
      'soft_credits_only'
    ) {
      $this->createTemporaryTable('civireport_contribution_detail_temp3',
        "(SELECT * FROM {$this->temporaryTables['civireport_contribution_detail_temp2']['name']})"
      );
    }
    else {
      $this->createTemporaryTable('civireport_contribution_detail_temp3', "
(SELECT * FROM {$this->temporaryTables['civireport_contribution_detail_temp1']['name']})
UNION ALL
(SELECT * FROM {$this->temporaryTables['civireport_contribution_detail_temp2']['name']})");
    }
    $this->isTempTableBuilt = TRUE;
  }

  /**
   * Store group bys into array - so we can check elsewhere what is grouped.
   *
   * If we are generating a table of soft credits we need to group by them.
   */
  protected function storeGroupByArray() {
    if ($this->queryMode === 'SoftCredit') {
      $this->_groupByArray = [$this->_aliases['civicrm_contribution_soft'] . '.id'];
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
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    // We pass in TRUE as 2nd param so that even disabled contribution page titles are returned and replaced in the report
    $contributionPages = CRM_Contribute_PseudoConstant::contributionPage(NULL, TRUE);
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

      if (($rows[$rowNum]['civicrm_contribution_contribution_or_soft'] ?? NULL) ==
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

      $value = $row['civicrm_contribution_financial_type_id'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_financial_type_id'] = $contributionTypes[$value];
        $entryFound = TRUE;
      }
      $value = $row['civicrm_contribution_contribution_status_id'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_contribution_status_id'] = $contributionStatus[$value];
        $entryFound = TRUE;
      }
      $value = $row['civicrm_contribution_contribution_page_id'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_contribution_page_id'] = $contributionPages[$value];
        $entryFound = TRUE;
      }
      $value = $row['civicrm_contribution_payment_instrument_id'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }
      if (!empty($row['civicrm_batch_batch_id'])) {
        $rows[$rowNum]['civicrm_batch_batch_id'] = $batches[$row['civicrm_batch_batch_id']] ?? NULL;
        $entryFound = TRUE;
      }
      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
        $entryFound = TRUE;
      }

      // Contribution amount links to viewing contribution
      $value = $row['civicrm_contribution_total_amount'] ?? NULL;
      if ($value) {
        $rows[$rowNum]['civicrm_contribution_total_amount'] = CRM_Utils_Money::format($value, $row['civicrm_contribution_currency']);
        if (CRM_Core_Permission::check('access CiviContribute')) {
          $url = CRM_Utils_System::url(
            "civicrm/contact/view/contribution",
            [
              'reset' => 1,
              'id' => $row['civicrm_contribution_contribution_id'],
              'cid' => $row['civicrm_contact_id'],
              'action' => 'view',
              'context' => 'contribution',
              'selectedChild' => 'contribute',
            ],
            $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_contribution_total_amount_link'] = $url;
          $rows[$rowNum]['civicrm_contribution_total_amount_hover'] = ts("View Details of this Contribution.");
        }
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->campaigns[$value];
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
FROM   {$this->temporaryTables['civireport_contribution_detail_temp2']['name']}
WHERE  civicrm_contribution_contribution_id={$row['civicrm_contribution_contribution_id']}";
        $dao = CRM_Core_DAO::executeQuery($query);
        $string = '';
        $separator = ($this->_outputMode !== 'csv') ? "<br/>" : ' ';
        while ($dao->fetch()) {
          $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' .
            $dao->civicrm_contact_id);
          $string .= ($string ? $separator : '') .
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
FROM   {$this->temporaryTables['civireport_contribution_detail_temp1']['name']}
WHERE  civicrm_contribution_contribution_id={$row['civicrm_contribution_contribution_id']}";
        $dao = CRM_Core_DAO::executeQuery($query);
        $string = '';
        while ($dao->fetch()) {
          $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' .
            $dao->civicrm_contact_id);
          $string .=
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

      // Contribution amount links to viewing contribution
      $value = $row['civicrm_pledge_payment_pledge_id'] ?? NULL;
      if ($value) {
        if (CRM_Core_Permission::check('access CiviContribute')) {
          $url = CRM_Utils_System::url(
            "civicrm/contact/view/pledge",
            [
              'reset' => 1,
              'id' => $row['civicrm_pledge_payment_pledge_id'],
              'cid' => $row['civicrm_contact_id'],
              'action' => 'view',
              'context' => 'pledge',
              'selectedChild' => 'pledge',
            ],
            $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_pledge_payment_pledge_id_link'] = $url;
          $rows[$rowNum]['civicrm_pledge_payment_pledge_id_hover'] = ts("View Details of this Pledge.");
        }
        $entryFound = TRUE;
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

      $ifnulls = [];
      foreach (array_merge($sectionAliases, $this->_selectAliases) as $alias) {
        $ifnulls[] = "ifnull($alias, '') as $alias";
      }
      $select = CRM_Contact_BAO_Query::appendAnyValueToSelect($ifnulls, $sectionAliases);

      /* Group (un-limited) report by all aliases and get counts. This might
       * be done more efficiently when the contents of $sql are known, ie. by
       * overriding this method in the report class.
       */

      $addtotals = '';

      if (array_search("civicrm_contribution_total_amount", $this->_selectAliases) !==
        FALSE
      ) {
        $addtotals = ", sum(civicrm_contribution_total_amount) as sumcontribs";
        $showsumcontribs = TRUE;
      }

      $query = $select .
        "$addtotals, count(*) as ct from {$this->temporaryTables['civireport_contribution_detail_temp3']['name']} group by " .
        implode(", ", $sectionAliases);
      // initialize array of total counts
      $sumcontribs = $totals = [];
      $dao = CRM_Core_DAO::executeQuery($query);
      $this->addToDeveloperTab($query);
      while ($dao->fetch()) {

        // let $this->_alterDisplay translate any integer ids to human-readable values.
        $rows[0] = $dao->toArray();
        $this->alterDisplay($rows);
        $row = $rows[0];

        // add totals for all permutations of section values
        $values = [];
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
        $totalandsum = [];
        // ts exception to avoid having ts("%1 %2: %3")
        $title = '%1 contributions / soft-credits: %2';

        if (($this->_params['contribution_or_soft_value'] ?? NULL) == 'contributions_only') {
          $title = '%1 contributions: %2';
        }
        elseif (($this->_params['contribution_or_soft_value'] ?? NULL) == 'soft_credits_only') {
          $title = '%1 soft-credits: %2';
        }
        foreach ($totals as $key => $total) {
          $totalandsum[$key] = _ts($title, [
            1 => $total,
            2 => CRM_Utils_Money::format($sumcontribs[$key]),
          ]);
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
      FROM  {$this->temporaryTables['civireport_contribution_detail_temp1']['name']} temp1_civireport
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

    //for pledge payment
    if ($this->isTableSelected('civicrm_pledge_payment')) {
      $this->_from .= "
        LEFT JOIN civicrm_pledge_payment {$this->_aliases['civicrm_pledge_payment']} ON {$this->_aliases['civicrm_contribution']}.id = {$this->_aliases['civicrm_pledge_payment']}.contribution_id
      ";
    }

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

    if ($this->isTableSelected('civicrm_pledge_payment')) {
      $this->_from .= "
        LEFT JOIN civicrm_pledge_payment {$this->_aliases['civicrm_pledge_payment']} ON {$this->_aliases['civicrm_pledge_payment']}.contribution_id = {$this->_aliases['civicrm_contribution']}.id
      ";
    }
  }

  /**
   * Add join to the soft credit table.
   */
  protected function joinContributionToSoftCredit() {
    if (($this->_params['contribution_or_soft_value'] ?? NULL) == 'contributions_only'
      && !$this->isTableSelected('civicrm_contribution_soft')) {
      return;
    }
    $joinType = ' LEFT ';
    if (($this->_params['contribution_or_soft_value'] ?? NULL) == 'soft_credits_only') {
      $joinType = ' INNER ';
    }
    $this->_from .= "
      $joinType JOIN civicrm_contribution_soft {$this->_aliases['civicrm_contribution_soft']}
      ON {$this->_aliases['civicrm_contribution_soft']}.contribution_id = {$this->_aliases['civicrm_contribution']}.id
   ";
  }

  /**
   * End post processing.
   *
   * @param array|null $rows
   */
  public function endPostProcess(&$rows = NULL) {
    $this->groupConcatTested = FALSE;
    $this->orderBy();
    $this->groupConcatTested = TRUE;
    $this->optimisedForOnlyFullGroupBy = FALSE;
    parent::endPostProcess($rows);
    $this->optimisedForOnlyFullGroupBy = TRUE;
  }

}
