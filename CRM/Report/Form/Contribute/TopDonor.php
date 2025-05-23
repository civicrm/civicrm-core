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
class CRM_Report_Form_Contribute_TopDonor extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Contribution',
  ];

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

  public $_drilldownReport = ['contribute/detail' => 'Link to Detail Report'];

  /**
   * @var string
   */
  protected $_outerCluase = '';

  /**
   * @var string
   */
  protected $_groupLimit = '';

  /**
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'display_name' => [
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
        'filters' => $this->getBasicContactFilters(),
        'group_bys' => ['contact_contact_id' => ['name' => 'id', 'required' => 1, 'no_display' => 1]],
      ],
      'civicrm_line_item' => [
        'dao' => 'CRM_Price_DAO_LineItem',
      ],
    ];
    $this->_columns += $this->getAddressColumns(['group_by' => FALSE]);
    $this->_columns += [
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'total_amount' => [
            'title' => ts('Amount Statistics'),
            'required' => TRUE,
            'statistics' => [
              'sum' => ts('Aggregate Amount'),
              'count' => ts('Donations'),
              'avg' => ts('Average'),
            ],
          ],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
        ],
        'filters' => [
          'receive_date' => [
            'default' => 'this.year',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'receipt_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'currency' => [
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'total_range' => [
            'title' => ts('Show no. of Top Donors'),
            'type' => CRM_Utils_Type::T_INT,
            'default_op' => 'eq',
          ],
          'financial_type_id' => [
            'name' => 'financial_type_id',
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => [1],
          ],
        ],
        'group_bys' => ['contribution_currency' => ['name' => 'currency', 'required' => 1, 'no_display' => 1]],
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
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'title' => ts('Email'),
            'default' => TRUE,
            'no_repeat' => TRUE,
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
        'grouping' => 'email-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => [
            'title' => ts('Phone'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'phone-fields',
      ],
    ];

    // Add charts support
    $this->_charts = [
      '' => ts('Tabular'),
      'barChart' => ts('Bar Chart'),
      'pieChart' => ts('Pie Chart'),
    ];

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  /**
   * @param $fields
   * @param $files
   * @param self $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];

    $op = $fields['total_range_op'] ?? NULL;
    $val = $fields['total_range_value'] ?? NULL;

    if (!in_array($op, ['eq', 'lte'])) {
      $errors['total_range_op'] = ts("Please select 'Is equal to' OR 'Is Less than or equal to' operator");
    }

    if ($val && !CRM_Utils_Rule::positiveInteger($val)) {
      $errors['total_range_value'] = ts("Please enter positive number");
    }
    return $errors;
  }

  public function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
            INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0 AND {$this->_aliases['civicrm_contribution']}.is_template = 0
       ";

    // for credit card type
    $this->addFinancialTrxnFromClause();

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();
  }

  public function where() {
    $clauses = [];
    $this->_outerCluase = $this->_groupLimit = '';
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
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
            }
          }

          if (!empty($clause)) {
            if ($fieldName == 'total_range') {
              $value = $this->_params["total_range_value"] ?? NULL;
              $this->_outerCluase = " WHERE (( @rows := @rows + 1) <= {$value}) ";
              $this->_groupLimit = " LIMIT {$value}";
            }
            else {
              $clauses[] = $clause;
            }
          }
        }
      }
    }
    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  /**
   * Build output rows.
   *
   * @param string $sql
   * @param array $rows
   */
  public function buildRows($sql, &$rows) {
    $setVariable = " SET @rows:=0, @rank=0 ";
    CRM_Core_DAO::singleValueQuery($setVariable);
    $sql = "
      SELECT * FROM ( {$this->_select} {$this->_from}  {$this->_where} {$this->_groupBy}
        ORDER BY civicrm_contribution_total_amount_sum DESC
      ) as abc {$this->_outerCluase} $this->_limit
    ";
    parent::buildRows($sql, $rows);
  }

  /**
   * @param int $groupID
   */
  public function add2group($groupID) {
    if (is_numeric($groupID)) {
      $this->_limit = $this->_groupLimit;
      $rows = [];
      $this->_columnHeaders['civicrm_contact_id'] = 1;
      $this->buildRows('', $rows);

      $contact_ids = [];
      // Add resulting contacts to group
      foreach ($rows as $row) {
        $contact_ids[$row['civicrm_contact_id']] = $row['civicrm_contact_id'];
      }

      CRM_Contact_BAO_GroupContact::addContactsToGroup($contact_ids, $groupID);
      CRM_Core_Session::setStatus(ts("Listed contact(s) have been added to the selected group."), ts('Contacts Added'), 'success');
    }
  }

  /**
   * @param int $rowCount
   */
  public function limit($rowCount = NULL) {
    $rowCount ??= $this->getRowCount();
    // lets do the pager if in html mode
    $this->_limit = NULL;

    // CRM-14115, over-ride row count if rowCount is specified in URL
    if ($this->_dashBoardRowCount) {
      $rowCount = $this->_dashBoardRowCount;
    }
    if ($this->_outputMode == 'html') {
      // Replace only first occurrence of SELECT.
      $this->_select = preg_replace('/SELECT/', 'SELECT SQL_CALC_FOUND_ROWS ', $this->_select, 1);
      $pageId = CRM_Utils_Request::retrieve('crmPID', 'Integer');

      if (!$pageId && !empty($_POST) && isset($_POST['crmPID_B'])) {
        if (!isset($_POST['PagerBottomButton'])) {
          unset($_POST['crmPID_B']);
        }
        else {
          $pageId = max((int) @$_POST['crmPID_B'], 1);
        }
      }

      $pageId = $pageId ?: 1;
      $this->set(CRM_Utils_Pager::PAGE_ID, $pageId);
      $offset = ($pageId - 1) * $rowCount;

      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');

      $this->_limit = " LIMIT $offset, " . $rowCount;
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
    $rank = 1;
    if (!empty($rows)) {
      foreach ($rows as $rowNum => $row) {

        $rows[$rowNum]['civicrm_donor_rank'] = $rank++;
        // convert display name to links
        if (array_key_exists('civicrm_contact_display_name', $row) &&
          array_key_exists('civicrm_contact_id', $row) &&
          !empty($row['civicrm_contribution_currency'])
        ) {
          $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
            'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'] .
            "&currency_value=" . $row['civicrm_contribution_currency'],
            $this->_absoluteUrl, $this->_id, $this->_drilldownReport
          );
          $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
          $entryFound = TRUE;
        }
        $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s)') ? TRUE : $entryFound;

        if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
          $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
          $entryFound = TRUE;
        }

        $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;

        // skip looking further in rows, if first row itself doesn't
        // have the column we need
        if (!$entryFound) {
          break;
        }
      }
    }
  }

}
