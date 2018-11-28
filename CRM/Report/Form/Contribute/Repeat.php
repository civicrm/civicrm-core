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
class CRM_Report_Form_Contribute_Repeat extends CRM_Report_Form {
  protected $_amountClauseWithAND = NULL;

  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
  );

  public $_drilldownReport = array('contribute/detail' => 'Link to Detail Report');

  /**
   * Temp table for first time frame.
   *
   * @var int
   */
  protected $tempTableRepeat1 = NULL;

  /**
   * Temp table for second time frame.
   *
   * @var int
   */
  protected $tempTableRepeat2 = NULL;

  /**
   * The table the report is being grouped by.
   *
   * @var string
   */
  protected $groupByTable;

  /**
   * The field the report is being grouped by.
   *
   * @var string
   */
  protected $groupByFieldName;

  /**
   * The alias of the table the report is being grouped by.
   *
   * @var string
   */
  protected $groupByTableAlias;

  /**
   * The column in the contribution table that joins to the temp tables.
   *
   * @var
   */
  protected $contributionJoinTableColumn;

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
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'grouping' => 'contact-fields',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Contact Name'),
            'no_repeat' => TRUE,
            'default' => TRUE,
          ),
          'display_name' => array(
            'title' => ts('Display Name'),
            'no_repeat' => TRUE,
          ),
          'addressee_display' => array(
            'title' => ts('Addressee Name'),
            'no_repeat' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
            'no_repeat' => TRUE,
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
            'no_repeat' => TRUE,
          ),
        ),
        'filters' => array(
          'percentage_change' => array(
            'title' => ts('Percentage Change'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_INT,
            'name' => 'percentage_change',
            'dbAlias' => '( ( contribution_civireport2.total_amount_sum - contribution_civireport1.total_amount_sum ) * 100 / contribution_civireport1.total_amount_sum )',
          ),
        ),
        'group_bys' => array(
          'id' => array(
            'title' => ts('Contact'),
            'default' => TRUE,
          ),
        ),
        'order_bys' => array(
          'sort_name' => array(
            'title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ),
          'first_name' => array(
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
      'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'grouping' => 'contact-fields',
        'fields' => array(
          'street_address' => array('title' => ts('Street Address')),
          'supplemental_address_1' => array('title' => ts('Supplemental Address 1')),
          'city' => array('title' => ts('City')),
          'country_id' => array('title' => ts('Country')),
          'state_province_id' => array('title' => ts('State/Province')),
          'postal_code' => array('title' => ts('Postal Code')),
        ),
        'group_bys' => array(
          'country_id' => array('title' => ts('Country')),
          'state_province_id' => array(
            'title' => ts('State/Province'),
          ),
        ),
      ),
      'civicrm_financial_type' => array(
        'dao' => 'CRM_Financial_DAO_FinancialType',
        'fields' => array('financial_type' => array('title' => ts('Financial Type'))),
        'grouping' => 'contri-fields',
        'group_bys' => array(
          'financial_type' => array(
            'name' => 'id',
            'title' => ts('Financial Type'),
          ),
        ),
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'contribution_source' => NULL,
          'total_amount1' => array(
            'name' => 'total_amount',
            'alias' => 'contribution1',
            'title' => ts('Range One Stat'),
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
            'required' => TRUE,
            'clause' => 'contribution_civireport1.total_amount_count as contribution1_total_amount_count, contribution_civireport1.total_amount_sum as contribution1_total_amount_sum',
          ),
          'total_amount2' => array(
            'name' => 'total_amount',
            'alias' => 'contribution2',
            'title' => ts('Range Two Stat'),
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
            'required' => TRUE,
            'clause' => 'contribution_civireport2.total_amount_count as contribution2_total_amount_count, contribution_civireport2.total_amount_sum as contribution2_total_amount_sum',
          ),
        ),
        'grouping' => 'contri-fields',
        'filters' => array(
          'receive_date1' => array(
            'title' => ts('Initial Date Range'),
            'default' => 'previous.year',
            'operatorType' => CRM_Report_Form::OP_DATE,
            'name' => 'receive_date',
          ),
          'receive_date2' => array(
            'title' => ts('Second Date Range'),
            'default' => 'this.year',
            'operatorType' => CRM_Report_Form::OP_DATE,
            'name' => 'receive_date',
          ),
          'total_amount1' => array(
            'title' => ts('Range One Amount'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_INT,
            'name' => 'total_amount',
          ),
          'total_amount2' => array(
            'title' => ts('Range Two Amount'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_INT,
            'name' => 'total_amount',
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
        'group_bys' => array('contribution_source' => NULL),
      ),
    );

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;

    parent::__construct();
  }

  /**
   * Override parent select for reasons someone will someday make sense of & document.
   */
  public function select() {
    $select = array();
    $append = NULL;
    // since contact fields not related to financial type
    if (array_key_exists('financial_type', $this->_params['group_bys']) ||
      array_key_exists('contribution_source', $this->_params['group_bys'])
    ) {
      unset($this->_columns['civicrm_contact']['fields']['id']);
    }

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if (isset($field['clause'])) {
              $select[] = $field['clause'];

              // FIXME: dirty hack for setting columnHeaders
              $this->_columnHeaders["{$field['alias']}_{$field['name']}_sum"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$field['alias']}_{$field['name']}_sum"]['title'] = $field['title'];
              $this->_columnHeaders["{$field['alias']}_{$field['name']}_count"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$field['alias']}_{$field['name']}_count"]['title'] = $field['title'];
              continue;
            }

            // only include statistics columns if set
            $select[] = "{$field['dbAlias']} as {$field['alias']}_{$field['name']}";
            $this->_columnHeaders["{$field['alias']}_{$field['name']}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$field['alias']}_{$field['name']}"]['title'] = CRM_Utils_Array::value('title', $field);
            if (!empty($field['no_display'])) {
              $this->_columnHeaders["{$field['alias']}_{$field['name']}"]['no_display'] = TRUE;
            }
          }
        }
      }
    }
    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * Inspect the group by params to determine group by information.
   */
  public function setGroupByInformation() {
    if (!empty($this->_params['group_bys']) &&
      is_array($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (array_key_exists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (!empty($this->_params['group_bys'][$fieldName])) {
              $this->groupByTable = $tableName;
              $this->groupByTableAlias = $field['alias'];
              $this->groupByFieldName = $field['name'];
              if ($this->groupByTable == 'civicrm_contact') {
                $this->contributionJoinTableColumn = "contact_id";
              }
              elseif ($this->groupByTable == 'civicrm_contribution_type') {
                $this->contributionJoinTableColumn = "contribution_type_id";
              }
              elseif ($this->groupByTable == 'civicrm_contribution') {
                $this->contributionJoinTableColumn = $this->groupByFieldName;
              }
              elseif ($this->groupByTable == 'civicrm_address') {
                $this->contributionJoinTableColumn = "contact_id";
              }
              elseif ($this->groupByTable == 'civicrm_financial_type') {
                $this->contributionJoinTableColumn = 'financial_type_id';
              }
              return;
            }
          }
        }
      }

    }
  }

  public function from() {
    $this->buildTempTables();
    $fromCol = $this->groupByFieldName;

    $from = "$this->groupByTable $this->groupByTableAlias";

    if ($this->groupByTable == 'civicrm_contact') {
      $from .= "
LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id
LEFT JOIN civicrm_email   {$this->_aliases['civicrm_email']}
       ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND {$this->_aliases['civicrm_email']}.is_primary = 1
LEFT JOIN civicrm_phone   {$this->_aliases['civicrm_phone']}
       ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND {$this->_aliases['civicrm_phone']}.is_primary = 1";

    }
    elseif ($this->groupByTable == 'civicrm_address') {
      $from .= "
INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON {$this->_aliases['civicrm_address']}.contact_id = {$this->_aliases['civicrm_contact']}.id";
      $this->groupByTableAlias = $this->_aliases['civicrm_contact'];
      $fromCol = "id";
    }

    $this->_from = "
FROM $from
LEFT JOIN $this->tempTableRepeat1 {$this->_aliases['civicrm_contribution']}1
       ON {$this->groupByTableAlias}.$fromCol = {$this->_aliases['civicrm_contribution']}1
       .{$this->contributionJoinTableColumn}
LEFT JOIN $this->tempTableRepeat2 {$this->_aliases['civicrm_contribution']}2
       ON {$this->groupByTableAlias}.$fromCol = {$this->_aliases['civicrm_contribution']}2.{$this->contributionJoinTableColumn}";

    //Join temp table if report is filtered by group. This is specific to 'notin' operator and covered in unit test(ref dev/core#212)
    if (!empty($this->_params['gid_op']) && $this->_params['gid_op'] == 'notin') {
      $this->joinGroupTempTable('civicrm_contact', 'id', $this->_aliases['civicrm_contact']);
    }
  }

  /**
   * @param string $replaceAliasWith
   *
   * @return mixed|string
   */
  public function fromContribution($replaceAliasWith = 'contribution1') {
    $this->setFromBase('civicrm_contribution', 'contact_id', $replaceAliasWith);

    $temp = $this->_aliases['civicrm_contribution'];
    $this->_aliases['civicrm_contribution'] = $replaceAliasWith;
    $from = $this->_from;
    $this->_aliases['civicrm_contribution'] = $temp;
    $this->_where = '';
    return $from;
  }

  /**
   * @param string $replaceAliasWith
   *
   * @return mixed|string
   */
  public function whereContribution($replaceAliasWith = 'contribution1') {
    $clauses = array("is_test" => "{$this->_aliases['civicrm_contribution']}.is_test = 0");

    foreach ($this->_columns['civicrm_contribution']['filters'] as $fieldName => $field) {
      $clause = NULL;
      if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
        $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
        $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
        $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

        $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to, $field['type']);
      }
      else {
        $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
        if ($op) {
          $clause = $this->whereClause($field,
            $op,
            CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
            CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
            CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
          );
        }
      }
      if (!empty($clause)) {
        $clauses[$fieldName] = $clause;
      }
    }

    if (!$this->_amountClauseWithAND) {
      $amountClauseWithAND = array();
      if (!empty($clauses['total_amount1'])) {
        $amountClauseWithAND[] = str_replace("{$this->_aliases['civicrm_contribution']}.total_amount",
          "{$this->_aliases['civicrm_contribution']}1.total_amount_sum", $clauses['total_amount1']);
      }
      if (!empty($clauses['total_amount2'])) {
        $amountClauseWithAND[] = str_replace("{$this->_aliases['civicrm_contribution']}.total_amount",
          "{$this->_aliases['civicrm_contribution']}2.total_amount_sum", $clauses['total_amount2']);
      }
      $this->_amountClauseWithAND = !empty($amountClauseWithAND) ? implode(' AND ', $amountClauseWithAND) : NULL;
    }

    if ($replaceAliasWith == 'contribution1') {
      unset($clauses['receive_date2'], $clauses['total_amount2']);
    }
    else {
      unset($clauses['receive_date1'], $clauses['total_amount1']);
    }

    $whereClause = !empty($clauses) ? "WHERE " . implode(' AND ', $clauses) : '';

    if ($replaceAliasWith) {
      $whereClause = str_replace($this->_aliases['civicrm_contribution'], $replaceAliasWith, $whereClause);
    }

    return $whereClause;
  }

  public function where() {
    if (!$this->_amountClauseWithAND) {
      $this->_amountClauseWithAND
        = "!({$this->_aliases['civicrm_contribution']}1.total_amount_count IS NULL AND {$this->_aliases['civicrm_contribution']}2.total_amount_count IS NULL)";
    }
    $clauses = array("atleast_one_amount" => $this->_amountClauseWithAND);

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table) &&
        $tableName != 'civicrm_contribution'
      ) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
          if ($op) {
            $clause = $this->whereClause($field,
              $op,
              CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
              CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
              CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
            );
          }
          if (!empty($clause)) {
            $clauses[$fieldName] = $clause;
          }
        }
      }
    }

    $this->_where = !empty($clauses) ? "WHERE " . implode(' AND ', $clauses) : '';
  }

  /**
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {

    $errors = $checkDate = $errorCount = array();

    $rules = array(
      'id' => array(
        'sort_name',
        'display_name',
        'addressee_display',
        'contact_type',
        'contact_sub_type',
        'email',
        'phone',
        'state_province_id',
        'country_id',
        'city',
        'street_address',
        'supplemental_address_1',
        'postal_code',
      ),
      'country_id' => array('country_id'),
      'state_province_id' => array('country_id', 'state_province_id'),
      'contribution_source' => array('contribution_source'),
      'financial_type' => array('financial_type'),
    );

    $idMapping = array(
      'id' => ts('Contact'),
      'exposed_id' => ts('Contact'),
      'country_id' => ts('Country'),
      'state_province_id' => ts('State/Province'),
      'contribution_source' => ts('Contribution Source'),
      'financial_type' => ts('Financial Type'),
      'sort_name' => ts('Contact Name'),
      'email' => ts('Email'),
      'phone' => ts('Phone'),
    );

    if (empty($fields['group_bys'])) {
      $errors['fields'] = ts('Please select at least one Group by field.');
    }
    elseif ((array_key_exists('contribution_source', $fields['group_bys']) ||
        array_key_exists('contribution_type', $fields['group_bys'])
      ) &&
      (count($fields['group_bys']) > 1)
    ) {
      $errors['fields'] = ts('You can not use other Group by with Financial type or Contribution source.');
    }
    else {
      foreach ($fields['fields'] as $fld_id => $value) {
        if (!($fld_id == 'total_amount1') && !($fld_id == 'total_amount2')) {
          $found = FALSE;
          $invlidGroups = array();
          foreach ($fields['group_bys'] as $grp_id => $val) {
            $validFields = $rules[$grp_id];
            if (in_array($fld_id, $validFields)) {
              $found = TRUE;
            }
            else {
              $invlidGroups[] = $idMapping[$grp_id];
            }
          }
          if (!$found) {
            $erorrGrps = implode(',', $invlidGroups);
            $tempErrors[] = ts("Do not select field %1 with Group by %2.", array(
              1 => $idMapping[$fld_id],
              2 => $erorrGrps,
            ));
          }
        }
      }
      if (!empty($tempErrors)) {
        $errors['fields'] = implode("<br/>", $tempErrors);
      }
    }

    if (!empty($fields['gid_value']) && !empty($fields['group_bys'])) {
      if (!array_key_exists('id', $fields['group_bys'])) {
        $errors['gid_value'] = ts("Filter with Group only allow with group by Contact");
      }
    }

    if ($fields['receive_date1_relative'] == '0') {
      $checkDate['receive_date1']['receive_date1_from'] = $fields['receive_date1_from'];
      $checkDate['receive_date1']['receive_date1_to'] = $fields['receive_date1_to'];
    }

    if ($fields['receive_date2_relative'] == '0') {
      $checkDate['receive_date2']['receive_date2_from'] = $fields['receive_date2_from'];
      $checkDate['receive_date2']['receive_date2_to'] = $fields['receive_date2_to'];
    }

    foreach ($checkDate as $date_range => $range_data) {
      foreach ($range_data as $key => $value) {
        if (CRM_Utils_Date::isDate($value)) {
          $errorCount[$date_range][$key]['valid'] = 'true';
          $errorCount[$date_range][$key]['is_empty'] = 'false';
        }
        else {
          $errorCount[$date_range][$key]['valid'] = 'false';
          $errorCount[$date_range][$key]['is_empty'] = 'true';
          if (is_array($value)) {
            foreach ($value as $v) {
              if ($v) {
                $errorCount[$date_range][$key]['is_empty'] = 'false';
              }
            }
          }
          elseif (!isset($value)) {
            $errorCount[$date_range][$key]['is_empty'] = 'false';
          }
        }
      }
    }

    $errorText = ts("Select valid date range");
    foreach ($errorCount as $date_range => $error_data) {

      if (($error_data[$date_range . '_from']['valid'] == 'false') &&
        ($error_data[$date_range . '_to']['valid'] == 'false')
      ) {

        if (($error_data[$date_range . '_from']['is_empty'] == 'true') &&
          ($error_data[$date_range . '_to']['is_empty'] == 'true')
        ) {
          $errors[$date_range . '_relative'] = $errorText;
        }

        if ($error_data[$date_range . '_from']['is_empty'] == 'false') {
          $errors[$date_range . '_from'] = $errorText;
        }

        if ($error_data[$date_range . '_to']['is_empty'] == 'false') {
          $errors[$date_range . '_to'] = $errorText;
        }
      }
      elseif (($error_data[$date_range . '_from']['valid'] == 'true') &&
        ($error_data[$date_range . '_to']['valid'] == 'false')
      ) {
        if ($error_data[$date_range . '_to']['is_empty'] == 'false') {
          $errors[$date_range . '_to'] = $errorText;
        }
      }
      elseif (($error_data[$date_range . '_from']['valid'] == 'false') &&
        ($error_data[$date_range . '_to']['valid'] == 'true')
      ) {
        if ($error_data[$date_range . '_from']['is_empty'] == 'false') {
          $errors[$date_range . '_from'] = $errorText;
        }
      }
    }

    return $errors;
  }

  /**
   * @param $rows
   *
   * @return array
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $sql = "{$this->_select} {$this->_from} {$this->_where}";
    $dao = $this->executeReportQuery($sql);
    //store contributions in array 'contact_sums' for comparison
    $contact_sums = array();
    while ($dao->fetch()) {
      $contact_sums[$dao->contact_civireport_id] = array(
        'contribution1_total_amount_sum' => $dao->contribution1_total_amount_sum,
        'contribution2_total_amount_sum' => $dao->contribution2_total_amount_sum,
      );
    }

    $total_distinct_contacts = count($contact_sums);
    $maintained = 0;
    $upgraded = 0;
    $downgraded = 0;
    $new = 0;
    $lapsed = 0;

    foreach ($contact_sums as $uid => $row) {
      if ($row['contribution1_total_amount_sum'] &&
        $row['contribution2_total_amount_sum']
      ) {
        $change = ($row['contribution1_total_amount_sum'] -
          $row['contribution2_total_amount_sum']);
        if ($change == 0) {
          $maintained += 1;
        }
        elseif ($change > 0) {
          $upgraded += 1;
        }
        elseif ($change < 0) {
          $downgraded += 1;
        }
      }
      elseif ($row['contribution1_total_amount_sum']) {
        $new += 1;
      }
      elseif ($row['contribution2_total_amount_sum']) {
        $lapsed += 1;
      }
    }

    //calculate percentages from numbers
    if (!empty($total_distinct_contacts)) {
      $maintained = ($maintained / $total_distinct_contacts) * 100;
      $upgraded = ($upgraded / $total_distinct_contacts) * 100;
      $downgraded = ($downgraded / $total_distinct_contacts) * 100;
      $new = ($new / $total_distinct_contacts) * 100;
      $lapsed = ($lapsed / $total_distinct_contacts) * 100;
    }
    //display percentages for new, lapsed, upgraded, downgraded, and maintained contributors
    $statistics['counts']['count_new'] = array(
      'value' => $new,
      'title' => ts('% New Donors'),
    );
    $statistics['counts']['count_lapsed'] = array(
      'value' => $lapsed,
      'title' => ts('% Lapsed Donors'),
    );
    $statistics['counts']['count_upgraded'] = array(
      'value' => $upgraded,
      'title' => ts('% Upgraded Donors'),
    );
    $statistics['counts']['count_downgraded'] = array(
      'value' => $downgraded,
      'title' => ts('% Downgraded Donors'),
    );
    $statistics['counts']['count_maintained'] = array(
      'value' => $maintained,
      'title' => ts('% Maintained Donors'),
    );

    $select = "
SELECT COUNT({$this->_aliases['civicrm_contribution']}1.total_amount_count )       as count,
       SUM({$this->_aliases['civicrm_contribution']}1.total_amount_sum )           as amount,
       ROUND(AVG({$this->_aliases['civicrm_contribution']}1.total_amount_sum), 2)  as avg,
       COUNT({$this->_aliases['civicrm_contribution']}2.total_amount_count )       as count2,
       SUM({$this->_aliases['civicrm_contribution']}2.total_amount_sum )           as amount2,
       ROUND(AVG({$this->_aliases['civicrm_contribution']}2.total_amount_sum), 2)  as avg2,
       currency";
    $sql = "{$select} {$this->_from} {$this->_where}
GROUP BY    currency
";
    $dao = $this->executeReportQuery($sql);

    $amount = $average = $amount2 = $average2 = array();
    $count = $count2 = 0;
    while ($dao->fetch()) {
      if ($dao->amount) {
        $amount[]
          = CRM_Utils_Money::format($dao->amount, $dao->currency) . "(" .
          $dao->count . ")";
        $average[] = CRM_Utils_Money::format($dao->avg, $dao->currency);
      }

      $count += $dao->count;
      if ($dao->amount2) {
        $amount2[]
          = CRM_Utils_Money::format($dao->amount2, $dao->currency) . "(" .
          $dao->count . ")";
        $average2[] = CRM_Utils_Money::format($dao->avg2, $dao->currency);
      }
      $count2 += $dao->count2;
    }

    $statistics['counts']['range_one_title'] = array('title' => ts('Initial Date Range:'));
    $statistics['counts']['amount'] = array(
      'value' => implode(',  ', $amount),
      'title' => ts('Total Amount'),
      'type' => CRM_Utils_Type::T_STRING,
    );
    $statistics['counts']['count'] = array(
      'value' => $count,
      'title' => ts('Total Donations'),
    );
    $statistics['counts']['avg'] = array(
      'value' => implode(',  ', $average),
      'title' => ts('Average'),
      'type' => CRM_Utils_Type::T_STRING,
    );
    $statistics['counts']['range_two_title'] = array(
      'title' => ts('Second Date Range:'),
    );
    $statistics['counts']['amount2'] = array(
      'value' => implode(',  ', $amount2),
      'title' => ts('Total Amount'),
      'type' => CRM_Utils_Type::T_STRING,
    );
    $statistics['counts']['count2'] = array(
      'value' => $count2,
      'title' => ts('Total Donations'),
    );
    $statistics['counts']['avg2'] = array(
      'value' => implode(',  ', $average2),
      'title' => ts('Average'),
      'type' => CRM_Utils_Type::T_STRING,
    );

    return $statistics;
  }

  public function postProcess() {
    $this->beginPostProcess();

    $this->buildGroupTempTable();
    $this->select();
    $this->from();
    $this->where();
    $this->groupBy();
    $this->orderBy();
    $this->limit();

    $count = 0;
    $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_orderBy} {$this->_limit}";
    $dao = $this->executeReportQuery($sql);
    $rows = array();
    while ($dao->fetch()) {
      foreach ($this->_columnHeaders as $key => $value) {
        $rows[$count][$key] = $dao->$key;
      }
      $count++;
    }

    // FIXME: calculate % using query
    foreach ($rows as $uid => $row) {
      if ($row['contribution1_total_amount_sum'] &&
        $row['contribution2_total_amount_sum']
      ) {
        $rows[$uid]['change'] = number_format((($row['contribution2_total_amount_sum'] -
              $row['contribution1_total_amount_sum']
            ) * 100) /
          ($row['contribution1_total_amount_sum']), 2
        );
      }
      elseif ($row['contribution1_total_amount_sum']) {
        $rows[$uid]['change'] = ts('Skipped Donation');
      }
      elseif ($row['contribution2_total_amount_sum']) {
        $rows[$uid]['change'] = ts('New Donor');
      }
      if ($row['contribution1_total_amount_count']) {
        $rows[$uid]['contribution1_total_amount_sum']
          = $row['contribution1_total_amount_sum'] .
          " ({$row['contribution1_total_amount_count']})";
      }
      if ($row['contribution2_total_amount_count']) {
        $rows[$uid]['contribution2_total_amount_sum']
          = $row['contribution2_total_amount_sum'] .
          " ({$row['contribution2_total_amount_count']})";
      }
    }
    $this->_columnHeaders['change'] = array(
      'title' => ts('% Change'),
      'type' => CRM_Utils_Type::T_INT,
    );

    // hack to fix title
    list($from1, $to1) = $this->getFromTo(CRM_Utils_Array::value("receive_date1_relative", $this->_params),
      CRM_Utils_Array::value("receive_date1_from", $this->_params),
      CRM_Utils_Array::value("receive_date1_to", $this->_params)
    );
    $from1 = CRM_Utils_Date::customFormat($from1, NULL, array('d'));
    $to1 = CRM_Utils_Date::customFormat($to1, NULL, array('d'));

    list($from2, $to2) = $this->getFromTo(CRM_Utils_Array::value("receive_date2_relative", $this->_params),
      CRM_Utils_Array::value("receive_date2_from", $this->_params),
      CRM_Utils_Array::value("receive_date2_to", $this->_params)
    );
    $from2 = CRM_Utils_Date::customFormat($from2, NULL, array('d'));
    $to2 = CRM_Utils_Date::customFormat($to2, NULL, array('d'));

    $this->_columnHeaders['contribution1_total_amount_sum']['title'] = "$from1 -<br/> $to1";
    $this->_columnHeaders['contribution2_total_amount_sum']['title'] = "$from2 -<br/> $to2";
    unset($this->_columnHeaders['contribution1_total_amount_count'],
      $this->_columnHeaders['contribution2_total_amount_count']
    );

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
    list($from1, $to1) = $this->getFromTo(CRM_Utils_Array::value("receive_date1_relative", $this->_params),
      CRM_Utils_Array::value("receive_date1_from", $this->_params),
      CRM_Utils_Array::value("receive_date1_to", $this->_params)
    );
    list($from2, $to2) = $this->getFromTo(CRM_Utils_Array::value("receive_date2_relative", $this->_params),
      CRM_Utils_Array::value("receive_date2_from", $this->_params),
      CRM_Utils_Array::value("receive_date2_to", $this->_params)
    );

    $dateUrl = "";
    if ($from1) {
      $dateUrl .= "receive_date1_from={$from1}&";
    }
    if ($to1) {
      $dateUrl .= "receive_date1_to={$to1}&";
    }
    if ($from2) {
      $dateUrl .= "receive_date2_from={$from2}&";
    }
    if ($to2) {
      $dateUrl .= "receive_date2_to={$to2}&";
    }

    foreach ($rows as $rowNum => $row) {
      // handle country
      if (array_key_exists('address_civireport_country_id', $row)) {
        if ($value = $row['address_civireport_country_id']) {
          $rows[$rowNum]['address_civireport_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);

          $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
            "reset=1&force=1&" .
            "country_id_op=in&country_id_value={$value}&" .
            "$dateUrl",
            $this->_absoluteUrl, $this->_id, $this->_drilldownReport
          );

          $rows[$rowNum]['address_civireport_country_id_link'] = $url;
          $rows[$rowNum]['address_civireport_country_id_hover'] = ts("View contributions for this Country.");
        }
      }

      // handle state province
      if (array_key_exists('address_civireport_state_province_id', $row)) {
        if ($value = $row['address_civireport_state_province_id']) {
          $rows[$rowNum]['address_civireport_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);

          $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
            "reset=1&force=1&" .
            "state_province_id_op=in&state_province_id_value={$value}&" .
            "$dateUrl",
            $this->_absoluteUrl, $this->_id, $this->_drilldownReport
          );

          $rows[$rowNum]['address_civireport_state_province_id_link'] = $url;
          $rows[$rowNum]['address_civireport_state_province_id_hover'] = ts("View repeatDetails for this state.");
        }
      }

      // convert display name to links
      if (array_key_exists('contact_civireport_sort_name', $row) &&
        array_key_exists('contact_civireport_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['contact_civireport_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );

        $rows[$rowNum]['contact_civireport_sort_name_link'] = $url;
        $rows[$rowNum]['contact_civireport_sort_name_hover'] = ts("View Contribution details for this contact");
      }
    }
    // foreach ends
  }

  /**
   * Build the temp tables for comparison.
   */
  protected function buildTempTables() {
    $this->setGroupByInformation();
    $create = $subSelect1 = $subSelect2 = NULL;
    if ($this->tempTableRepeat1) {
      return;
    }

    if ($this->groupByTable == 'civicrm_financial_type') {
      $subSelect1 = 'contribution1.contact_id,';
      $subSelect2 = 'contribution2.contact_id,';
      $create = 'contact_id int unsigned,';
    }

    $subWhere = $this->whereContribution();
    $from = $this->fromContribution();
    $subContributionQuery1 = "
SELECT {$subSelect1} contribution1.{$this->contributionJoinTableColumn},
       sum( contribution1.total_amount ) AS total_amount_sum,
       count( * ) AS total_amount_count
{$from}
{$subWhere}
GROUP BY contribution1.{$this->contributionJoinTableColumn}";

    $subWhere = $this->whereContribution('contribution2');
    $from = $this->fromContribution('contribution2');
    $subContributionQuery2 = "
SELECT {$subSelect2} contribution2.{$this->contributionJoinTableColumn},
       sum( contribution2.total_amount ) AS total_amount_sum,
       count( * ) AS total_amount_count,
       currency
{$from}
{$subWhere}
GROUP BY contribution2.{$this->contributionJoinTableColumn}, currency";
    $this->tempTableRepeat1 = 'civicrm_temp_civireport_repeat1' . uniqid();
    $sql = "
CREATE TEMPORARY TABLE $this->tempTableRepeat1 (
{$create}
{$this->contributionJoinTableColumn} int unsigned,
total_amount_sum int,
total_amount_count int
) ENGINE=HEAP {$this->_databaseAttributes}";
    $this->executeReportQuery($sql);
    $this->executeReportQuery("INSERT INTO $this->tempTableRepeat1 {$subContributionQuery1}");

    $this->executeReportQuery("
      ALTER TABLE $this->tempTableRepeat1 ADD INDEX ({$this->contributionJoinTableColumn})
    ");

    $this->tempTableRepeat2 = 'civicrm_temp_civireport_repeat2' . uniqid();
    $sql = "
CREATE TEMPORARY TABLE  $this->tempTableRepeat2 (
{$create}
{$this->contributionJoinTableColumn} int unsigned,
total_amount_sum int,
total_amount_count int,
currency varchar(3)
) ENGINE=HEAP {$this->_databaseAttributes}";
    $this->executeReportQuery($sql);
    $sql = "INSERT INTO $this->tempTableRepeat2 {$subContributionQuery2}";
    $this->executeReportQuery($sql);

    $this->executeReportQuery("
      ALTER TABLE $this->tempTableRepeat2 ADD INDEX ({$this->contributionJoinTableColumn})
    ");

  }

}
