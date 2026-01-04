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
class CRM_Report_Form_Contribute_SoftCredit extends CRM_Report_Form {

  protected $_emailField = FALSE;
  protected $_emailFieldCredit = FALSE;
  protected $_phoneField = FALSE;
  protected $_phoneFieldCredit = FALSE;

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Contribution',
  ];

  public $_drilldownReport = ['contribute/detail' => 'Link to Detail Report'];

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
   */
  public function __construct() {
    $this->optimisedForOnlyFullGroupBy = FALSE;

    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'display_name_creditor' => [
            'title' => ts('Soft Credit Name'),
            'name' => 'sort_name',
            'alias' => 'contact_civireport',
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'id_creditor' => [
            'title' => ts('Soft Credit Id'),
            'name' => 'id',
            'alias' => 'contact_civireport',
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'display_name_constituent' => [
            'title' => ts('Contributor Name'),
            'name' => 'sort_name',
            'alias' => 'constituentname',
            'required' => TRUE,
          ],
          'id_constituent' => [
            'title' => ts('Const Id'),
            'name' => 'id',
            'alias' => 'constituentname',
            'no_display' => TRUE,
            'required' => TRUE,
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
          'age_at_event' => [
            'name' => 'age_at_event',
            'title' => ts('Age at Event'),
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
            'name' => 'sort_name',
            'title' => ts('Soft Credit Name'),
          ],
          'id_creditor' => [
            'name' => 'id',
            'title' => ts('Soft Credit Contact ID'),
          ],
          'gender_id' => [
            'title' => ts('Gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contact_DAO_Contact::buildOptions('gender_id'),
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
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email_creditor' => [
            'title' => ts('Soft Credit Email'),
            'name' => 'email',
            'alias' => 'emailcredit',
            'default' => TRUE,
            'no_repeat' => TRUE,
          ],
          'email_constituent' => [
            'title' => ts('Contributor\'s Email'),
            'name' => 'email',
            'alias' => 'emailconst',
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone_creditor' => [
            'title' => ts('Soft Credit Phone'),
            'name' => 'phone',
            'alias' => 'pcredit',
            'default' => TRUE,
          ],
          'phone_constituent' => [
            'title' => ts('Contributor\'s Phone'),
            'name' => 'phone',
            'alias' => 'pconst',
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_financial_type' => [
        'dao' => 'CRM_Financial_DAO_FinancialType',
        'fields' => ['financial_type' => NULL],
        'filters' => [
          'id' => [
            'name' => 'id',
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search'),
          ],
        ],
        'grouping' => 'softcredit-fields',
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'contribution_source' => NULL,
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
        ],
        'grouping' => 'softcredit-fields',
        'filters' => [
          'receive_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'receipt_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'currency' => [
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => [1],
          ],
        ],
      ],
      'civicrm_contribution_soft' => [
        'dao' => 'CRM_Contribute_DAO_ContributionSoft',
        'fields' => [
          'contribution_id' => [
            'title' => ts('Contribution ID'),
            'no_display' => TRUE,
            'default' => TRUE,
          ],
          'amount' => [
            'title' => ts('Amount Statistics'),
            'default' => TRUE,
            'statistics' => [
              'sum' => ts('Aggregate Amount'),
              'count' => ts('Contributions'),
              'avg' => ts('Average'),
            ],
          ],
          'id' => [
            'default' => TRUE,
            'no_display' => TRUE,
          ],
          'soft_credit_type_id' => ['title' => ts('Soft Credit Type')],
        ],
        'filters' => [
          'soft_credit_type_id' => [
            'title' => ts('Soft Credit Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('soft_credit_type'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'amount' => [
            'title' => ts('Soft Credit Amount'),
          ],
        ],
        'grouping' => 'softcredit-fields',
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

    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  public function select(): void {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            // include email column if set
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
              $this->_emailFieldCredit = TRUE;
            }
            elseif ($tableName == 'civicrm_email_creditor') {
              $this->_emailFieldCredit = TRUE;
            }

            // include phone columns if set
            if ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
              $this->_phoneFieldCredit = TRUE;
            }
            elseif ($tableName == 'civicrm_phone_creditor') {
              $this->_phoneFieldCredit = TRUE;
            }

            // only include statistics columns if set
            if (!empty($field['statistics'])) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'count':
                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'avg':
                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = 'SELECT ' . implode(', ', $select) . ' ';
  }

  public function from(): void {
    $alias_constituent = 'constituentname';
    $alias_creditor = 'contact_civireport';
    $this->_from = "
        FROM  civicrm_contribution {$this->_aliases['civicrm_contribution']}
              INNER JOIN civicrm_contribution_soft {$this->_aliases['civicrm_contribution_soft']}
                         ON {$this->_aliases['civicrm_contribution_soft']}.contribution_id =
                            {$this->_aliases['civicrm_contribution']}.id
              INNER JOIN civicrm_contact {$alias_constituent}
                         ON {$this->_aliases['civicrm_contribution']}.contact_id =
                            {$alias_constituent}.id
              LEFT  JOIN civicrm_financial_type  {$this->_aliases['civicrm_financial_type']}
                         ON {$this->_aliases['civicrm_contribution']}.financial_type_id =
                            {$this->_aliases['civicrm_financial_type']}.id
              LEFT  JOIN civicrm_contact {$alias_creditor}
                         ON {$this->_aliases['civicrm_contribution_soft']}.contact_id =
                            {$alias_creditor}.id
              {$this->_aclFrom} ";

    // include Constituent email field if email column is to be included
    if ($this->_emailField) {
      $alias = 'emailconst';
      $this->_from .= "
            LEFT JOIN civicrm_email {$alias}
                      ON {$alias_constituent}.id =
                         {$alias}.contact_id   AND
                         {$alias}.is_primary = 1\n";
    }

    // include  Creditors email field if email column is to be included
    if ($this->_emailFieldCredit) {
      $alias = 'emailcredit';
      $this->_from .= "
            LEFT JOIN civicrm_email {$alias}
                      ON {$alias_creditor}.id =
                         {$alias}.contact_id  AND
                         {$alias}.is_primary = 1\n";
    }

    // include  Constituents phone field if email column is to be included
    if ($this->_phoneField) {
      $alias = 'pconst';
      $this->_from .= "
            LEFT JOIN civicrm_phone {$alias}
                      ON {$alias_constituent}.id =
                         {$alias}.contact_id  AND
                         {$alias}.is_primary = 1\n";
    }

    // include  Creditors phone field if email column is to be included
    if ($this->_phoneFieldCredit) {
      $alias = 'pcredit';
      $this->_from .= "
            LEFT JOIN civicrm_phone pcredit
                      ON {$alias_creditor}.id =
                         {$alias}.contact_id  AND
                         {$alias}.is_primary = 1\n";
    }
    // for credit card type
    $this->addFinancialTrxnFromClause();
  }

  public function groupBy() {
    $this->_rollup = 'WITH ROLLUP';
    $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect($this->_selectClauses, ["{$this->_aliases['civicrm_contribution_soft']}.contact_id", "constituentname.id"]);
    $this->_groupBy = "
GROUP BY {$this->_aliases['civicrm_contribution_soft']}.contact_id, constituentname.id {$this->_rollup}";
  }

  public function where() {
    parent::where();
    $this->_where .= " AND {$this->_aliases['civicrm_contribution']}.is_test = 0 AND {$this->_aliases['civicrm_contribution']}.is_template = 0 ";
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = "
        SELECT COUNT({$this->_aliases['civicrm_contribution_soft']}.amount ) as count,
               SUM({$this->_aliases['civicrm_contribution_soft']}.amount ) as amount,
               ROUND(AVG({$this->_aliases['civicrm_contribution_soft']}.amount), 2) as avg,
               {$this->_aliases['civicrm_contribution']}.currency as currency
        ";

    $sql = "{$select} {$this->_from} {$this->_where}
GROUP BY   {$this->_aliases['civicrm_contribution']}.currency
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $count = 0;
    $totalAmount = $average = [];
    while ($dao->fetch()) {
      $totalAmount[] = CRM_Utils_Money::format($dao->amount, $dao->currency) . '(' .
        $dao->count . ')';
      $average[] = CRM_Utils_Money::format($dao->avg, $dao->currency);
      $count += $dao->count;
    }
    $statistics['counts']['amount'] = [
      'title' => ts('Total Amount'),
      'value' => implode(',  ', $totalAmount),
      'type' => CRM_Utils_Type::T_STRING,
    ];
    $statistics['counts']['count'] = [
      'title' => ts('Total Contributions'),
      'value' => $count,
    ];
    $statistics['counts']['avg'] = [
      'title' => ts('Average'),
      'value' => implode(',  ', $average),
      'type' => CRM_Utils_Type::T_STRING,
    ];

    return $statistics;
  }

  public function postProcess() {
    $this->beginPostProcess();

    $this->buildACLClause(['constituentname', 'contact_civireport']);
    $sql = $this->buildQuery();

    $rows = $graphRows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
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
    $dispname_flag = $phone_flag = $email_flag = 0;
    $prev_email = $prev_dispname = $prev_phone = NULL;

    foreach ($rows as $rowNum => $row) {
      // Link constituent (contributor) to contribution detail
      if (array_key_exists('civicrm_contact_display_name_constituent', $row) &&
        array_key_exists('civicrm_contact_id_constituent', $row)
      ) {

        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' .
          $row['civicrm_contact_id_constituent'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_display_name_constituent_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_constituent_hover'] = ts('List all direct contribution(s) from this contact.');
        $entryFound = TRUE;
      }

      // convert soft credit contact name to link
      if (array_key_exists('civicrm_contact_display_name_creditor', $row) &&
        !empty($rows[$rowNum]['civicrm_contact_display_name_creditor']) &&
        array_key_exists('civicrm_contact_id_creditor', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_contact_id_creditor'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_display_name_creditor_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_creditor_hover'] = ts("View contact summary");
      }

      // make subtotals look nicer
      if (array_key_exists('civicrm_contact_id_constituent', $row) &&
        !$row['civicrm_contact_id_constituent']
      ) {
        $this->fixSubTotalDisplay($rows[$rowNum], $this->_statFields);
        $entryFound = TRUE;
      }

      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_contribution_campaign_id', $row)) {
        if ($value = $row['civicrm_contribution_campaign_id']) {
          $rows[$rowNum]['civicrm_contribution_campaign_id'] = $this->campaigns[$value];
          $entryFound = TRUE;
        }
      }

      //convert soft_credit_type_id into label
      if (array_key_exists('civicrm_contribution_soft_soft_credit_type_id', $rows[$rowNum])) {
        $rows[$rowNum]['civicrm_contribution_soft_soft_credit_type_id'] = CRM_Core_PseudoConstant::getLabel(
          'CRM_Contribute_BAO_ContributionSoft',
          'soft_credit_type_id',
          $row['civicrm_contribution_soft_soft_credit_type_id']
        );
      }

      if (!empty($row['civicrm_financial_trxn_card_type_id']) && !in_array('Subtotal', $rows[$rowNum])) {
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

    $this->removeDuplicates($rows);
  }

}
