<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
class CRM_Report_Form_Contribute_TopDonor extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
    'Contribution',
  );

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = TRUE;

  public $_drilldownReport = array('contribute/detail' => 'Link to Detail Report');

  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );

  /**
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $this->_columns = array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'display_name' => array(
            'title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'first_name' => array(
            'title' => ts('First Name'),
          ),
          'middle_name' => array(
            'title' => ts('Middle Name'),
          ),
          'last_name' => array(
            'title' => ts('Last Name'),
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'gender_id' => array(
            'title' => ts('Gender'),
          ),
          'birth_date' => array(
            'title' => ts('Birth Date'),
          ),
          'age' => array(
            'title' => ts('Age'),
            'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, CURDATE())',
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
          ),
        ),
        'filters' => array(
          'gender_id' => array(
            'title' => ts('Gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'),
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
          ),
        ),
        'filters' => array(
          'gender_id' => array(
            'title' => ts('Gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'),
          ),
        ),
      ),
      'civicrm_line_item' => array(
        'dao' => 'CRM_Price_DAO_LineItem',
      ),
    );
    $this->_columns += $this->getAddressColumns();
    $this->_columns += array(
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'total_amount' => array(
            'title' => ts('Amount Statistics'),
            'required' => TRUE,
            'statistics' => array(
              'sum' => ts('Aggregate Amount'),
              'count' => ts('Donations'),
              'avg' => ts('Average'),
            ),
          ),
          'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
        ),
        'filters' => array(
          'sort_name' => array(
            'title' => ts('Participant Name'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ),
          'id' => array(
            'title' => ts('Contact ID'),
            'type' => CRM_Utils_Type::T_INT,
            'no_display' => TRUE,
          ),
          'birth_date' => array(
            'title' => ts('Birth Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'contact_type' => array(
            'title' => ts('Contact Type'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'contact_sub_type' => array(
            'title' => ts('Contact Subtype'),
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'receive_date' => array(
            'default' => 'this.year',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'currency' => array(
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'total_range' => array(
            'title' => ts('Show no. of Top Donors'),
            'type' => CRM_Utils_Type::T_INT,
            'default_op' => 'eq',
          ),
          'financial_type_id' => array(
            'name' => 'financial_type_id',
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes(),
          ),
          'contribution_status_id' => array(
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
          ),
        ),
      ),
      'civicrm_email' => array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => array(
          'email' => array(
            'title' => ts('Email'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'email-fields',
      ),
      'civicrm_phone' => array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => array(
          'phone' => array(
            'title' => ts('Phone'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'phone-fields',
      ),
    );

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = array();
    $this->_columnHeaders = array();
    //Headers for Rank column
    $this->_columnHeaders["civicrm_donor_rank"]['title'] = ts('Rank');
    $this->_columnHeaders["civicrm_donor_rank"]['type'] = 1;
    //$select[] ="(@rank:=@rank+1)  as civicrm_donor_rank ";

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
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
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
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
              // $field['type'] is not always set. Use string type as default if not set.
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = isset($field['type']) ? $field['type'] : 2;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }
        }
      }
    }
    $this->_selectClauses = $select;

    $this->_select = " SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = array();

    $op = CRM_Utils_Array::value('total_range_op', $fields);
    $val = CRM_Utils_Array::value('total_range_value', $fields);

    if (!in_array($op, array(
      'eq',
      'lte',
    ))
    ) {
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
                ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0
             LEFT  JOIN civicrm_email  {$this->_aliases['civicrm_email']}
                         ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id
                         AND {$this->_aliases['civicrm_email']}.is_primary = 1
             LEFT  JOIN civicrm_phone  {$this->_aliases['civicrm_phone']}
                         ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                            {$this->_aliases['civicrm_phone']}.is_primary = 1";
    $this->addAddressFromClause();
  }

  public function where() {
    $clauses = array();
    $this->_tempClause = $this->_outerCluase = $this->_groupLimit = '';
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
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
            if ($fieldName == 'total_range') {
              $value = CRM_Utils_Array::value("total_range_value", $this->_params);
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

  public function groupBy() {
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, array("{$this->_aliases['civicrm_contact']}.id", "{$this->_aliases['civicrm_contribution']}.currency"));
  }

  public function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);

    $this->buildQuery();

    //set the variable value rank, rows = 0
    $setVariable = " SET @rows:=0, @rank=0 ";
    CRM_Core_DAO::singleValueQuery($setVariable);

    $sql = "SELECT * FROM ( {$this->_select} {$this->_from}  {$this->_where} {$this->_groupBy}
                     ORDER BY civicrm_contribution_total_amount_sum DESC
                 ) as abc {$this->_outerCluase} $this->_limit
               ";

    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        if (property_exists($dao, $key)) {
          $row[$key] = $dao->$key;
        }
      }
      $rows[] = $row;
    }
    $this->formatDisplay($rows);

    $this->doTemplateAssignment($rows);

    $this->endPostProcess($rows);
  }

  /**
   * @param int $groupID
   */
  public function add2group($groupID) {
    if (is_numeric($groupID)) {

      $sql = "
{$this->_select} {$this->_from}  {$this->_where} {$this->_groupBy}
ORDER BY civicrm_contribution_total_amount_sum DESC
) as abc {$this->_groupLimit}";
      $dao = CRM_Core_DAO::executeQuery($sql);

      $contact_ids = array();
      // Add resulting contacts to group
      while ($dao->fetch()) {
        $contact_ids[$dao->civicrm_contact_id] = $dao->civicrm_contact_id;
      }

      CRM_Contact_BAO_GroupContact::addContactsToGroup($contact_ids, $groupID);
      CRM_Core_Session::setStatus(ts("Listed contact(s) have been added to the selected group."), ts('Contacts Added'), 'success');
    }
  }

  /**
   * @param int $rowCount
   */
  public function limit($rowCount = CRM_Report_Form::ROW_COUNT_LIMIT) {
    // lets do the pager if in html mode
    $this->_limit = NULL;

    // CRM-14115, over-ride row count if rowCount is specified in URL
    if ($this->_dashBoardRowCount) {
      $rowCount = $this->_dashBoardRowCount;
    }
    if ($this->_outputMode == 'html' || $this->_outputMode == 'group') {
      // Replace only first occurrence of SELECT.
      $this->_select = preg_replace('/SELECT/', 'SELECT SQL_CALC_FOUND_ROWS ', $this->_select, 1);
      $pageId = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);

      if (!$pageId && !empty($_POST) && isset($_POST['crmPID_B'])) {
        if (!isset($_POST['PagerBottomButton'])) {
          unset($_POST['crmPID_B']);
        }
        else {
          $pageId = max((int) @$_POST['crmPID_B'], 1);
        }
      }

      $pageId = $pageId ? $pageId : 1;
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

        //handle gender
        if (array_key_exists('civicrm_contact_gender_id', $row)) {
          if ($value = $row['civicrm_contact_gender_id']) {
            $gender = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
            $rows[$rowNum]['civicrm_contact_gender_id'] = $gender[$value];
          }
          $entryFound = TRUE;
        }

        // display birthday in the configured custom format
        if (array_key_exists('civicrm_contact_birth_date', $row)) {
          $birthDate = $row['civicrm_contact_birth_date'];
          if ($birthDate) {
            $rows[$rowNum]['civicrm_contact_birth_date'] = CRM_Utils_Date::customFormat($birthDate, '%Y%m%d');
          }
          $entryFound = TRUE;
        }

        // skip looking further in rows, if first row itself doesn't
        // have the column we need
        if (!$entryFound) {
          break;
        }
      }
    }
  }

}
