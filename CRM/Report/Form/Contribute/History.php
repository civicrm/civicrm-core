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
class CRM_Report_Form_Contribute_History extends CRM_Report_Form {
  /**
   * @var array
   */
  protected $_relationshipColumns = [];

  protected $_relationshipFrom = '';

  protected $_relationshipWhere = '';

  protected $_contributionClauses = [];

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Contribution',
  ];

  protected $_referenceYear = [
    'this_year' => '',
    'other_year' => '',
  ];
  protected $_yearStatisticsFrom = '';

  protected $_yearStatisticsTo = '';

  /**
   * Class constructor.
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $yearsInPast = 4;
    $date = CRM_Core_SelectValues::date('custom', NULL, $yearsInPast, 0);
    $count = $date['maxYear'];
    $optionYear = ['' => ts('- select -')];

    $this->_yearStatisticsFrom = $date['minYear'];
    $this->_yearStatisticsTo = $date['maxYear'];

    while ($date['minYear'] <= $count) {
      $optionYear[$date['minYear']] = $date['minYear'];
      $date['minYear']++;
    }

    $relationTypeOp = [];
    $relationshipTypes = CRM_Core_PseudoConstant::relationshipType();
    foreach ($relationshipTypes as $rid => $rtype) {
      if ($rtype['label_a_b'] != $rtype['label_b_a']) {
        $relationTypeOp[$rid] = "{$rtype['label_a_b']}/{$rtype['label_b_a']}";
      }
      else {
        $relationTypeOp[$rid] = $rtype['label_a_b'];
      }
    }

    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Contact Name'),
            'default' => TRUE,
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
            'default' => TRUE,
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
          'sort_name' => ['title' => ts('Contact Name')],
          'id' => [
            'title' => ts('Contact ID'),
            'no_display' => TRUE,
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
          'email' => [
            'title' => ts('Email'),
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
    ] + $this->addAddressFields(FALSE, FALSE, FALSE, []) + [
      'civicrm_relationship' => [
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' => [
          'relationship_type_id' => [
            'title' => ts('Relationship Type'),
            'default' => TRUE,
          ],
          'contact_id_a' => ['no_display' => TRUE],
          'contact_id_b' => ['no_display' => TRUE],
        ],
        'filters' => [
          'relationship_type_id' => [
            'title' => ts('Relationship Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $relationTypeOp,
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
      ],
    ] + [
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'total_amount' => [
            'title' => ts('Amount Statistics'),
            'default' => TRUE,
            'required' => TRUE,
            'no_display' => TRUE,
            'statistics' => ['sum' => ts('Aggregate Amount')],
          ],
          'receive_date' => [
            'required' => TRUE,
            'default' => TRUE,
            'no_display' => TRUE,
          ],
        ],
        'grouping' => 'contri-fields',
        'filters' => [
          'this_year' => [
            'title' => ts('This Year'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $optionYear,
            'default' => '',
          ],
          'other_year' => [
            'title' => ts('Other Years'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $optionYear,
            'default' => '',
          ],
          'receive_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'receipt_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => [1],
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ],
          'total_amount' => [
            'title' => ts('Contribution Amount'),
          ],
          'total_sum' => [
            'title' => ts('Aggregate Amount'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_total_amount_sum',
            'having' => TRUE,
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
            'options' => CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
      ],
    ];

    $this->_columns['civicrm_contribution']['fields']['civicrm_upto_' .
    $this->_yearStatisticsFrom] = [
      'title' => ts('Up To %1 Donation', [1 => $this->_yearStatisticsFrom]),
      'default' => TRUE,
      'type' => CRM_Utils_Type::T_MONEY,
      'is_statistics' => TRUE,
    ];

    $yearConter = $this->_yearStatisticsFrom;
    $yearConter++;
    while ($yearConter <= $this->_yearStatisticsTo) {
      $this->_columns['civicrm_contribution']['fields'][$yearConter] = [
        'title' => ts('%1 Donation', [1 => $yearConter]),
        'default' => TRUE,
        'type' => CRM_Utils_Type::T_MONEY,
        'is_statistics' => TRUE,
      ];
      $yearConter++;
    }

    $this->_columns['civicrm_contribution']['fields']['aggregate_amount'] = [
      'title' => ts('Aggregate Amount'),
      'type' => CRM_Utils_Type::T_MONEY,
      'is_statistics' => TRUE,
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
    // @todo remove this & use parent (with maybe some override in this or better yet selectWhere fn)
    $this->_columnHeaders = [];

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {

          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if ($tableName == 'civicrm_relationship') {
              $this->_relationshipColumns["{$tableName}_{$fieldName}"] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              continue;
            }

            if (!empty($field['is_statistics'])) {
              $this->_columnHeaders[$fieldName]['type'] = $field['type'];
              $this->_columnHeaders[$fieldName]['title'] = $field['title'];
              continue;
            }
            elseif ($fieldName == 'receive_date') {
              if ((($this->_params['this_year_op'] ?? NULL) ==
                  'fiscal' && !empty($this->_params['this_year_value'])) ||
                (CRM_Utils_Array::value('other_year_op', $this->_params ==
                    'fiscal') && !empty($this->_params['other_year_value']))
              ) {
                $select[] = self::fiscalYearOffset($field['dbAlias']) .
                  " as {$tableName}_{$fieldName}";
              }
              else {
                $select[] = " YEAR(" . $field['dbAlias'] . ")" .
                  " as {$tableName}_{$fieldName}";
              }
            }
            elseif ($fieldName == 'total_amount') {
              $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}";
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
    $this->_from = "
        FROM civicrm_contact  {$this->_aliases['civicrm_contact']}
             INNER JOIN civicrm_contribution   {$this->_aliases['civicrm_contribution']}
                     ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND
                        {$this->_aliases['civicrm_contribution']}.is_test = 0 AND
                        {$this->_aliases['civicrm_contribution']}.is_template = 0";

    $relContacAlias = 'contact_relationship';
    $this->_relationshipFrom = " INNER JOIN civicrm_relationship {$this->_aliases['civicrm_relationship']}
                     ON (({$this->_aliases['civicrm_relationship']}.contact_id_a = {$relContacAlias}.id OR {$this->_aliases['civicrm_relationship']}.contact_id_b = {$relContacAlias}.id ) AND {$this->_aliases['civicrm_relationship']}.is_active = 1) ";

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    // for credit card type
    $this->addFinancialTrxnFromClause();
  }

  public function where() {
    $whereClauses = $havingClauses = $relationshipWhere = [];

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($fieldName == 'this_year' || $fieldName == 'other_year') {
            continue;
          }
          elseif (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE
          ) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
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
            if ($tableName == 'civicrm_relationship') {
              $relationshipWhere[] = $clause;
              continue;
            }

            // Make contribution filters work.
            // Note total_sum is already accounted for in the main buildRows
            // and this_year and other_year skip the loop above.
            if ($tableName == 'civicrm_contribution' && $fieldName != 'total_sum') {
              $this->_contributionClauses[$fieldName] = $clause;
            }

            if (!empty($field['having'])) {
              $havingClauses[] = $clause;
            }
            else {
              $whereClauses[] = $clause;
            }
          }
        }
      }
    }

    if (empty($whereClauses)) {
      $this->_where = "WHERE ( 1 ) ";
      $this->_having = "";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $whereClauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }

    if (!empty($havingClauses)) {
      // use this clause to construct group by clause.
      $this->_having = "HAVING " . implode(' AND ', $havingClauses);
    }

    if (!empty($relationshipWhere)) {
      $this->_relationshipWhere = ' AND ' .
        implode(' AND ', $relationshipWhere);
    }
  }

  public function groupBy() {
    $groupBy = [
      "{$this->_aliases['civicrm_contribution']}.contact_id",
      "YEAR({$this->_aliases['civicrm_contribution']}.receive_date)",
    ];
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
  }

  /**
   * Override to set limit to 10.
   *
   * @param int|null $rowCount
   *
   * @return array
   */
  public function limit($rowCount = NULL) {
    $rowCount ??= $this->getRowCount();
    return parent::limit($rowCount);
  }

  /**
   * Override to set pager with limit is 10.
   *
   * @param int|null $rowCount
   */
  public function setPager($rowCount = NULL) {
    $rowCount ??= $this->getRowCount();
    parent::setPager($rowCount);
  }

  /**
   * @param array $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $count = 0;
    foreach ($rows as $rownum => $row) {
      if (is_numeric($rownum)) {
        $count++;
      }
    }
    $statistics['counts']['rowCount'] = [
      'title' => ts('Primary Contact(s) Listed'),
      'value' => $count,
    ];

    if ($this->_rowsFound && ($this->_rowsFound > $count)) {
      $statistics['counts']['rowsFound'] = [
        'title' => ts('Total Primary Contact(s)'),
        'value' => $this->_rowsFound,
      ];
    }

    return $statistics;
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
    if (!empty($fields['this_year_value']) &&
      !empty($fields['other_year_value']) &&
      ($fields['this_year_value'] == $fields['other_year_value'])
    ) {
      $errors['other_year_value'] = ts("Value for filters 'This Year' and 'Other Years' can not be same.");
    }
    return $errors;
  }

  public function postProcess() {
    // get ready with post process params
    $this->beginPostProcess();
    $this->fixReportParams();

    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $this->select();
    $this->where();
    $this->from();
    $this->customDataFrom();
    $this->groupBy();

    $sql = NULL;
    $rows = [];

    // build array of result based on column headers. This method also allows
    // modifying column headers before using it to build result set i.e $rows.
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows, FALSE);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  public function fixReportParams() {
    if (!empty($this->_params['this_year_value'])) {
      $this->_referenceYear['this_year'] = $this->_params['this_year_value'];
    }
    if (!empty($this->_params['other_year_value'])) {
      $this->_referenceYear['other_year'] = $this->_params['other_year_value'];
    }
  }

  /**
   * @param $sql
   * @param $rows
   */
  public function buildRows($sql, &$rows) {
    $contactIds = [];

    $addWhere = '';

    if (!empty($this->_referenceYear['other_year'])) {
      (($this->_params['other_year_op'] ?? NULL) ==
        'calendar') ? $other_receive_date = 'YEAR (contri.receive_date)' : $other_receive_date = self::fiscalYearOffset('contri.receive_date');
      $addWhere .= " AND {$this->_aliases['civicrm_contact']}.id NOT IN ( SELECT DISTINCT cont.id FROM civicrm_contact cont, civicrm_contribution contri WHERE  cont.id = contri.contact_id AND {$other_receive_date} = {$this->_referenceYear['other_year']} AND contri.is_test = 0 AND contri.is_template = 0 ) ";
    }
    if (!empty($this->_referenceYear['this_year'])) {
      (($this->_params['this_year_op'] ?? NULL) ==
        'calendar') ? $receive_date = 'YEAR (contri.receive_date)' : $receive_date = self::fiscalYearOffset('contri.receive_date');
      $addWhere .= " AND {$this->_aliases['civicrm_contact']}.id IN ( SELECT DISTINCT cont.id FROM civicrm_contact cont, civicrm_contribution contri WHERE cont.id = contri.contact_id AND {$receive_date} = {$this->_referenceYear['this_year']} AND contri.is_test = 0 AND contri.is_template = 0 ) ";
    }
    $this->limit();
    $getContacts = "SELECT {$this->_aliases['civicrm_contact']}.id as cid, SUM({$this->_aliases['civicrm_contribution']}.total_amount) as civicrm_contribution_total_amount_sum {$this->_from} {$this->_where} {$addWhere} GROUP BY {$this->_aliases['civicrm_contact']}.id {$this->_having}";

    // Run it without limit/offset first to get the right number of rows for
    // the pager.
    CRM_Core_DAO::executeQuery($getContacts);
    $this->setPager();

    $getContacts .= ' ' . $this->_limit;
    $dao = CRM_Core_DAO::executeQuery($getContacts);

    while ($dao->fetch()) {
      $contactIds[] = $dao->cid;
    }

    $relationshipRows = [];
    if (empty($contactIds)) {
      return;
    }

    $primaryContributions = $this->buildContributionRows($contactIds);

    list($relationshipRows, $relatedContactIds) = $this->buildRelationshipRows($contactIds);

    if (empty($relatedContactIds)) {
      $rows = $primaryContributions;
      return;
    }

    $relatedContributions = $this->buildContributionRows($relatedContactIds);

    $summaryYears = [];
    $summaryYears[] = "civicrm_upto_{$this->_yearStatisticsFrom}";
    $yearConter = $this->_yearStatisticsFrom;
    $yearConter++;
    while ($yearConter <= $this->_yearStatisticsTo) {
      $summaryYears[] = $yearConter;
      $yearConter++;
    }
    $summaryYears[] = 'aggregate_amount';

    foreach ($primaryContributions as $cid => $primaryRow) {
      $row = $primaryRow;
      if (!isset($relationshipRows[$cid])) {
        $rows[$cid] = $row;
        continue;
      }
      $total = [];
      $total['civicrm_contact_sort_name'] = ts('Total');
      foreach ($summaryYears as $year) {
        $total[$year] = $primaryRow[$year] ?? 0;
      }

      $relatedContact = FALSE;
      $rows[$cid] = $row;
      foreach ($relationshipRows[$cid] as $relcid => $relRow) {
        if (!isset($relatedContributions[$relcid])) {
          continue;
        }
        $relatedContact = TRUE;
        $relatedRow = $relatedContributions[$relcid];
        foreach ($summaryYears as $year) {
          $total[$year] += $relatedRow[$year] ?? 0;
        }

        foreach (array_keys($this->_relationshipColumns) as $col) {
          if (!empty($relRow[$col])) {
            $relatedRow[$col] = $relRow[$col];
          }
        }
        $rows["{$cid}_{$relcid}"] = $relatedRow;
      }
      if ($relatedContact) {
        $rows["{$cid}_total"] = $total;
        $rows["{$cid}_bank"] = ['civicrm_contact_sort_name' => '&nbsp;'];
      }
    }
  }

  /**
   * @param $contactIds
   *
   * @return array
   */
  public function buildContributionRows($contactIds) {
    $rows = [];
    if (empty($contactIds)) {
      return $rows;
    }

    $contributionClauses = '';
    if (!empty($this->_contributionClauses)) {
      $contributionClauses = ' AND ' . implode(' AND ', $this->_contributionClauses);
    }

    $sqlContribution = "{$this->_select} {$this->_from} WHERE {$this->_aliases['civicrm_contact']}.id IN (" .
      implode(',', $contactIds) .
      ") AND {$this->_aliases['civicrm_contribution']}.is_test = 0 AND {$this->_aliases['civicrm_contribution']}.is_template = 0 {$contributionClauses} {$this->_groupBy} ";

    $dao = CRM_Core_DAO::executeQuery($sqlContribution);
    $contributionSum = 0;
    $yearcal = [];
    while ($dao->fetch()) {
      if (!$dao->civicrm_contact_id) {
        continue;
      }

      foreach ($this->_columnHeaders as $key => $value) {
        if (property_exists($dao, $key)) {
          $rows[$dao->civicrm_contact_id][$key] = $dao->$key;
        }
      }
      if ($dao->civicrm_contribution_receive_date) {
        if ($dao->civicrm_contribution_receive_date >
          $this->_yearStatisticsFrom
        ) {
          $rows[$dao->civicrm_contact_id][$dao->civicrm_contribution_receive_date] = $dao->civicrm_contribution_total_amount;
        }
        else {
          if (!isset($rows[$dao->civicrm_contact_id]["civicrm_upto_{$this->_yearStatisticsFrom}"])) {
            $rows[$dao->civicrm_contact_id]["civicrm_upto_{$this->_yearStatisticsFrom}"] = 0;
          }

          $rows[$dao->civicrm_contact_id]["civicrm_upto_{$this->_yearStatisticsFrom}"] += $dao->civicrm_contribution_total_amount;
        }
      }

      if (!isset($rows[$dao->civicrm_contact_id]['aggregate_amount'])) {
        $rows[$dao->civicrm_contact_id]['aggregate_amount'] = 0;
      }
      $rows[$dao->civicrm_contact_id]['aggregate_amount'] += $dao->civicrm_contribution_total_amount;
    }
    return $rows;
  }

  /**
   * @param $contactIds
   *
   * @return array
   */
  public function buildRelationshipRows($contactIds) {
    $relationshipRows = $relatedContactIds = [];
    if (empty($contactIds)) {
      return [$relationshipRows, $relatedContactIds];
    }

    $relContactAlias = 'contact_relationship';
    $addRelSelect = '';
    if (!empty($this->_relationshipColumns)) {
      $addRelSelect = ', ' . implode(', ', $this->_relationshipColumns);
    }
    $sqlRelationship = "SELECT {$this->_aliases['civicrm_relationship']}.relationship_type_id as relationship_type_id, {$this->_aliases['civicrm_relationship']}.contact_id_a as contact_id_a, {$this->_aliases['civicrm_relationship']}.contact_id_b as contact_id_b {$addRelSelect} FROM civicrm_contact {$relContactAlias} {$this->_relationshipFrom} WHERE {$relContactAlias}.id IN (" .
      implode(',', $contactIds) .
      ") AND {$this->_aliases['civicrm_relationship']}.is_active = 1 {$this->_relationshipWhere} GROUP BY {$this->_aliases['civicrm_relationship']}.contact_id_a, {$this->_aliases['civicrm_relationship']}.contact_id_b, {$this->_aliases['civicrm_relationship']}.relationship_type_id";
    $relationshipTypes = CRM_Core_PseudoConstant::relationshipType();

    $dao = CRM_Core_DAO::executeQuery($sqlRelationship);
    while ($dao->fetch()) {
      $row = [];
      foreach (array_keys($this->_relationshipColumns) as $rel_column) {
        $row[$rel_column] = $dao->$rel_column;
      }
      if (in_array($dao->contact_id_a, $contactIds)) {
        $row['civicrm_relationship_relationship_type_id'] = $relationshipTypes[$dao->relationship_type_id]['label_a_b'];
        $row['civicrm_relationship_contact_id'] = $dao->contact_id_b;
        $relationshipRows[$dao->contact_id_a][$dao->contact_id_b] = $row;
        $relatedContactIds[$dao->contact_id_b] = $dao->contact_id_b;
      }
      if (in_array($dao->contact_id_b, $contactIds)) {
        $row['civicrm_relationship_contact_id'] = $dao->contact_id_a;
        $row['civicrm_relationship_relationship_type_id'] = $relationshipTypes[$dao->relationship_type_id]['label_b_a'];
        $relationshipRows[$dao->contact_id_b][$dao->contact_id_a] = $row;
        $relatedContactIds[$dao->contact_id_a] = $dao->contact_id_a;
      }
    }
    return [$relationshipRows, $relatedContactIds];
  }

  /**
   * Override "This Year" $op options
   * @param string $type
   * @param null $fieldName
   *
   * @return array
   */
  public function getOperationPair($type = "string", $fieldName = NULL) {
    if ($fieldName == 'this_year' || $fieldName == 'other_year') {
      return [
        'calendar' => ts('Is Calendar Year'),
        'fiscal' => ts('Fiscal Year Starting'),
      ];
    }
    return parent::getOperationPair($type, $fieldName);
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
    if (empty($rows)) {
      return;
    }

    $last_primary = NULL;
    foreach ($rows as $rowNum => $row) {
      // Highlight primary contact and amount row
      if (is_numeric($rowNum) ||
        ($last_primary && ($rowNum == "{$last_primary}_total"))
      ) {
        if (is_numeric($rowNum)) {
          $last_primary = $rowNum;
        }
        foreach ($row as $key => $value) {
          if ($key == 'civicrm_contact_id') {
            continue;
          }
          if (empty($value)) {
            $row[$key] = '';
            continue;
          }

          if ($last_primary && ($rowNum == "{$last_primary}_total")) {
            // Passing non-numeric is deprecated, but this isn't a perfect fix
            // since it will still format things like postal code 90210 as
            // "90,210.00", but that predates the deprecation. See dev/core#2819
            if (is_numeric($value)) {
              $value = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($value);
            }
          }
          // TODO: It later tries to format this as money which then gives a warning. One option is to instead set something like $row[$key]['classes'] and then use that in the template, but I don't think the stock template supports something like that.
          $row[$key] = '<strong>' . $value . '</strong>';
        }
        $rows[$rowNum] = $row;
      }

      // The main rows don't have this set so gives a smarty warning.
      if (!isset($row['civicrm_relationship_relationship_type_id'])) {
        $rows[$rowNum]['civicrm_relationship_relationship_type_id'] = '';
      }

      // Convert Display name into link
      if (!empty($row['civicrm_contact_sort_name']) &&
        !empty($row['civicrm_contact_id'])
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contribution Details for this Contact.");
      }

      if (!empty($row['civicrm_financial_trxn_card_type_id'])) {
        $rows[$rowNum]['civicrm_financial_trxn_card_type_id'] = $this->getLabels($row['civicrm_financial_trxn_card_type_id'], 'CRM_Financial_DAO_FinancialTrxn', 'card_type_id');
      }

      $this->alterDisplayContactFields($row, $rows, $rowNum, NULL, NULL);
      $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL);
    }
  }

}
