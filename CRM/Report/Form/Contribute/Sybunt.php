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
class CRM_Report_Form_Contribute_Sybunt extends CRM_Report_Form {

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Contribution',
  ];

  public $_drilldownReport = ['contribute/detail' => 'Link to Detail Report'];

  /**
   * This report has been optimised for group filtering.
   *
   * @var bool
   * @see https://issues.civicrm.org/jira/browse/CRM-19170
   */
  protected $groupFilterNotOptimised = FALSE;

  /**
   * @var string
   */
  protected $statusClause = '';

  /**
   * Class constructor.
   */
  public function __construct() {
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

    $this->_columns = array_merge($this->getColumns('Contact', [
      'order_bys_defaults' => ['sort_name' => 'ASC '],
      'fields_required' => ['sort_name'],
      'filters_defaults' => ['is_deleted' => 0],
      'no_field_disambiguation' => TRUE,
    ]), [
      'civicrm_line_item' => [
        'dao' => 'CRM_Price_DAO_LineItem',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'grouping' => 'contact-field',
        'fields' => [
          'email' => [
            'title' => ts('Email'),
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'on_hold' => [
            'title' => ts('On Hold'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => ['' => ts('Any')] + CRM_Core_PseudoConstant::emailOnHoldOptions(),
          ],
        ],
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'grouping' => 'contact-field',
        'fields' => [
          'phone' => [
            'title' => ts('Phone'),
            'default' => TRUE,
          ],
        ],
      ],
    ]);
    $this->_columns += $this->addAddressFields();
    $this->_columns += [
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'contact_id' => [
            'title' => ts('contactId'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'total_amount' => [
            'title' => ts('Total Amount'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'receive_date' => [
            'title' => ts('Year'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'filters' => [
          'yid' => [
            'name' => 'receive_date',
            'title' => ts('This Year'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $optionYear,
            'default' => date('Y'),
            'type' => CRM_Utils_Type::T_INT,
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => ['1'],
          ],
        ],
      ],
    ];
    $this->_columns += [
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
            'default' => NULL,
            'options' => CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
      ],
    ];

    // If we have a campaign, build out the relevant elements
    $this->addCampaignFields('civicrm_contribution');

    // Add charts support
    $this->_charts = [
      '' => ts('Tabular'),
      'barChart' => ts('Bar Chart'),
      'pieChart' => ts('Pie Chart'),
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
    $current_year = $this->_params['yid_value'];
    $previous_year = $current_year - 1;
    $previous_pyear = $current_year - 2;
    $previous_ppyear = $current_year - 3;
    $upTo_year = $current_year - 4;

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {

          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if ($fieldName == 'total_amount') {
              $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}";

              $this->_columnHeaders["civicrm_upto_{$upTo_year}"]['type'] = $field['type'];
              $this->_columnHeaders["civicrm_upto_{$upTo_year}"]['title'] = ts("Up To %1", [1 => $upTo_year]);

              $this->_columnHeaders["year_{$previous_ppyear}"]['type'] = $field['type'];
              $this->_columnHeaders["year_{$previous_ppyear}"]['title'] = $previous_ppyear;

              $this->_columnHeaders["year_{$previous_pyear}"]['type'] = $field['type'];
              $this->_columnHeaders["year_{$previous_pyear}"]['title'] = $previous_pyear;

              $this->_columnHeaders["year_{$previous_year}"]['type'] = $field['type'];
              $this->_columnHeaders["year_{$previous_year}"]['title'] = $previous_year;

              $this->_columnHeaders["civicrm_life_time_total"]['type'] = $field['type'];
              $this->_columnHeaders["civicrm_life_time_total"]['title'] = ts('LifeTime');
            }
            elseif ($fieldName == 'receive_date') {
              $select[] = self::fiscalYearOffset($field['dbAlias']) .
                " as {$tableName}_{$fieldName}";
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
            if (!empty($field['no_display'])) {
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = TRUE;
            }
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {
    $this->setFromBase('civicrm_contribution', 'contact_id');
    $this->_from .= "
              INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id
             {$this->_aclFrom}";

    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    // for credit card type
    $this->addFinancialTrxnFromClause();

    $this->joinAddressFromContact();
  }

  public function where() {
    $this->statusClause = "";
    $clauses = [
      $this->_aliases['civicrm_contribution'] . '.is_test = 0',
      $this->_aliases['civicrm_contribution'] . '.is_template = 0',
    ];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($fieldName == 'yid') {
            $clause = "contribution_civireport.contact_id NOT IN
(SELECT distinct cont.id FROM civicrm_contact cont, civicrm_contribution contri
 WHERE  cont.id = contri.contact_id AND " .
              self::fiscalYearOffset('contri.receive_date') .
              " = {$this->_params['yid_value']} AND contri.is_test = 0 AND contri.is_template = 0 )";
          }
          elseif (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE
          ) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                $this->_params["{$fieldName}_value"] ?? NULL,
                $this->_params["{$fieldName}_min"] ?? NULL,
                $this->_params["{$fieldName}_max"] ?? NULL
              );
              if (($fieldName == 'contribution_status_id' ||
                  $fieldName == 'financial_type_id') && !empty($clause)
              ) {
                $this->statusClause .= " AND " . $clause;
              }
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    $this->_where = "WHERE " . implode(' AND ', $clauses);

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  public function groupBy() {
    $this->assign('chartSupported', TRUE);
    $fiscalYearOffset = self::fiscalYearOffset("{$this->_aliases['civicrm_contribution']}.receive_date");
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contribution']}.contact_id, {$fiscalYearOffset}";
    $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect($this->_selectClauses, ["{$this->_aliases['civicrm_contribution']}.contact_id", $fiscalYearOffset]);
    $this->_groupBy .= " {$this->_rollup}";
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    if (!empty($rows)) {
      $select = "
                   SELECT
                        SUM({$this->_aliases['civicrm_contribution']}.total_amount ) as amount ";

      $sql = "{$select} {$this->_from} {$this->_where}";
      $dao = CRM_Core_DAO::executeQuery($sql);
      if ($dao->fetch()) {
        $statistics['counts']['amount'] = [
          'value' => $dao->amount,
          'title' => ts('Total LifeTime'),
          'type' => CRM_Utils_Type::T_MONEY,
        ];
      }
    }
    return $statistics;
  }

  public function postProcess() {
    // get ready with post process params
    $this->beginPostProcess();
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $this->buildQuery();

    $rows = $contactIds = [];
    if (empty($this->_params['charts'])) {
      $this->limit();
      $getContacts = "SELECT SQL_CALC_FOUND_ROWS {$this->_aliases['civicrm_contact']}.id as cid {$this->_from} {$this->_where} GROUP BY {$this->_aliases['civicrm_contact']}.id {$this->_limit}";

      $dao = CRM_Core_DAO::executeQuery($getContacts);

      while ($dao->fetch()) {
        $contactIds[] = $dao->cid;
      }
      $this->setPager();
    }

    if (!empty($contactIds) || !empty($this->_params['charts'])) {
      if (!empty($this->_params['charts'])) {
        $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy}";
      }
      else {
        $sql = "" .
          "{$this->_select} {$this->_from} WHERE {$this->_aliases['civicrm_contact']}.id IN (" .
          implode(',', $contactIds) .
          ") AND {$this->_aliases['civicrm_contribution']}.is_test = 0 AND {$this->_aliases['civicrm_contribution']}.is_template = 0 {$this->statusClause} {$this->_groupBy} ";
      }

      $current_year = $this->_params['yid_value'];
      $previous_year = $current_year - 1;
      $previous_pyear = $current_year - 2;
      $previous_ppyear = $current_year - 3;
      $upTo_year = $current_year - 4;

      $rows = $row = [];
      $dao = CRM_Core_DAO::executeQuery($sql);
      $contributionSum = 0;
      $yearcal = [];
      while ($dao->fetch()) {
        if (!$dao->civicrm_contribution_contact_id) {
          continue;
        }
        $row = [];
        foreach ($this->_columnHeaders as $key => $value) {
          if (property_exists($dao, $key)) {
            $rows[$dao->civicrm_contribution_contact_id][$key] = $dao->$key;
          }
        }
        if ($dao->civicrm_contribution_receive_date) {
          if ($dao->civicrm_contribution_receive_date > $upTo_year) {
            $contributionSum += $dao->civicrm_contribution_total_amount;
            $rows[$dao->civicrm_contribution_contact_id]['year_' . $dao->civicrm_contribution_receive_date] = $dao->civicrm_contribution_total_amount;
          }
        }
        else {
          $rows[$dao->civicrm_contribution_contact_id]['civicrm_life_time_total'] = $dao->civicrm_contribution_total_amount;
          if (($dao->civicrm_contribution_total_amount - $contributionSum) > 0
          ) {
            $rows[$dao->civicrm_contribution_contact_id]["civicrm_upto_{$upTo_year}"]
              = $dao->civicrm_contribution_total_amount - $contributionSum;
          }
          $contributionSum = 0;
        }
      }
    }
    // format result set.
    $this->formatDisplay($rows, FALSE);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  /**
   * @param array $rows
   */
  public function buildChart(&$rows) {
    $graphRows = [];
    $display = [];
    $current_year = $this->_params['yid_value'];
    $previous_year = $current_year - 1;
    $previous_two_year = $current_year - 2;
    $previous_three_year = $current_year - 3;
    $upto = $current_year - 4;

    $interval[$previous_year] = $previous_year;
    $interval[$previous_two_year] = $previous_two_year;
    $interval[$previous_three_year] = $previous_three_year;
    $interval["upto_{$upto}"] = "Up To {$upto}";

    foreach ($rows as $key => $row) {
      $display["upto_{$upto}"]
        = ($display["upto_{$upto}"] ?? 0) + ($row["civicrm_upto_{$upto}"] ?? 0);
      $display[$previous_year]
        = ($display[$previous_year] ?? 0) + ($row[$previous_year] ?? 0);
      $display[$previous_two_year]
        = ($display[$previous_two_year] ?? 0) + ($row[$previous_two_year] ?? 0);
      $display[$previous_three_year]
        = ($display[$previous_three_year] ?? 0) + ($row[$previous_three_year] ?? 0);
    }

    $graphRows['value'] = $display;
    $config = CRM_Core_Config::Singleton();
    $chartInfo = [
      'legend' => ts('Sybunt Report'),
      'xname' => ts('Year'),
      'yname' => ts('Amount (%1)', [1 => $config->defaultCurrency]),
    ];
    if ($this->_params['charts']) {
      // build the chart.
      CRM_Utils_Chart::reportChart($graphRows, $this->_params['charts'], $interval, $chartInfo);
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

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s)') ? TRUE : $entryFound;
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
      return [
        'calendar' => ts('Is Calendar Year'),
        'fiscal' => ts('Fiscal Year Starting'),
      ];
    }
    return parent::getOperationPair($type, $fieldName);
  }

}
