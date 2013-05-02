<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Report_Form_Contribute_TopDonor extends CRM_Report_Form {

  protected $_summary = NULL;

  public $_drilldownReport = array('contribute/detail' => 'Link to Detail Report');

  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );
  
  function __construct() {
    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'display_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
		  'first_name' => array('title' => ts('First Name'),
          ),
		  'last_name' => array('title' => ts('Last Name'),
          ),
        ),
      ),
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'total_amount' =>
          array('title' => ts('Amount Statistics'),
            'required' => TRUE,
            'statistics' =>
            array('sum' => ts('Aggregate Amount'),
              'count' => ts('Donations'),
              'avg' => ts('Average'),
            ),
          ),
          'currency' => 
          array('required' => TRUE,
             'no_display' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'receive_date' =>
          array(
            'default' => 'this.year',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'currency' =>
          array('title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
            ),
          'total_range' =>
          array('title' => ts('Show no. of Top Donors'),
            'type' => CRM_Utils_Type::T_INT,
            'default_op' => 'eq',
          ),
          'financial_type_id' =>
          array('name' => 'financial_type_id',
            'title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType() ,
          ),
          'contribution_status_id' =>
          array('title' => ts('Donation Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
          ),
        ),
      ),
	'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
          'email' =>
          array('title' => ts('Email'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'email-fields',
      ),
	  
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array(
          'phone' =>
          array('title' => ts('Phone'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'phone-fields',
      ),
      'civicrm_group' =>
      array(
        'dao' => 'CRM_Contact_DAO_GroupContact',
        'alias' => 'cgroup',
        'filters' =>
        array(
          'gid' =>
          array(
            'name' => 'group_id',
            'title' => ts('Group'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'group' => TRUE,
            'options' => CRM_Core_PseudoConstant::group(),
          ),
        ),
      ),
    );

    $this->_tagFilter = TRUE;
    $this->_currencyColumn = 'civicrm_contribution_currency';
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();
    //Headers for Rank column
    $this->_columnHeaders["civicrm_donor_rank"]['title'] = ts('Rank');
    $this->_columnHeaders["civicrm_donor_rank"]['type'] = 1;
    //$select[] ="(@rank:=@rank+1)  as civicrm_donor_rank ";

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            // only include statistics columns if set
            if (CRM_Utils_Array::value('statistics', $field)) {
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
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }
        }
      }
    }
    $this->_select = " SELECT * FROM ( SELECT " . implode(', ', $select) . " ";
  }

  static function formRule($fields, $files, $self) {
    $errors = array();

    $op = CRM_Utils_Array::value('total_range_op', $fields);
    $val = CRM_Utils_Array::value('total_range_value', $fields);

    if (!in_array($op, array(
      'eq', 'lte'))) {
      $errors['total_range_op'] = ts("Please select 'Is equal to' OR 'Is Less than or equal to' operator");
    }

    if ($val && !CRM_Utils_Rule::positiveInteger($val)) {
      $errors['total_range_value'] = ts("Please enter positive number");
    }
    return $errors;
  }

  function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
	       	 INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} 
		             ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0
             LEFT  JOIN civicrm_email  {$this->_aliases['civicrm_email']} 
                         ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id  
                         AND {$this->_aliases['civicrm_email']}.is_primary = 1
             LEFT  JOIN civicrm_phone  {$this->_aliases['civicrm_phone']} 
                         ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                            {$this->_aliases['civicrm_phone']}.is_primary = 1 
	";
  }

  function where() {
    $clauses = array();
    $this->_tempClause = $this->_outerCluase = '';
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

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

  function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_contribution']}.currency";
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);

    $this->select();

    $this->from();

    $this->where();

    $this->groupBy();

    $this->limit();


    //set the variable value rank, rows = 0
    $setVariable = " SET @rows:=0, @rank=0 ";
    CRM_Core_DAO::singleValueQuery($setVariable);

    $sql = " {$this->_select} {$this->_from}  {$this->_where} {$this->_groupBy} 
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

  function add2group($groupID) {
    if (is_numeric($groupID)) {

      $sql = " 
{$this->_select} {$this->_from}  {$this->_where} {$this->_groupBy} 
ORDER BY civicrm_contribution_total_amount_sum DESC
) as abc {$this->_outerCluase}";
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

  function limit($rowCount = CRM_Report_Form::ROW_COUNT_LIMIT) {
    // lets do the pager if in html mode
    $this->_limit = NULL;
    if ($this->_outputMode == 'html' || $this->_outputMode == 'group') {
      //replace only first occurence of SELECT
      $this->_select = preg_replace('/SELECT/', 'SELECT SQL_CALC_FOUND_ROWS ', $this->_select, 1);
      $pageId = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);

      if (!$pageId && !empty($_POST) && isset($_POST['crmPID_B'])) {
        if (!isset($_POST['PagerBottomButton'])) {
          unset($_POST['crmPID_B']);
        }
        else {
          $pageId = max((int)@$_POST['crmPID_B'], 1);
        }
      }

      $pageId = $pageId ? $pageId : 1;
      $this->set(CRM_Utils_Pager::PAGE_ID, $pageId);
      $offset = ($pageId - 1) * $rowCount;

      $this->_limit = " LIMIT $offset, " . $rowCount;
    }
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound = FALSE;
    $rank = 1;
    if (!empty($rows)) {
      foreach ($rows as $rowNum => $row) {

        $rows[$rowNum]['civicrm_donor_rank'] = $rank++;
        // convert display name to links
        if (array_key_exists('civicrm_contact_display_name', $row) &&
          array_key_exists('civicrm_contact_id', $row) &&
            CRM_Utils_Array::value('civicrm_contribution_currency', $row)
        ) {
          $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
            'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'] . "&currency_value=" . $row['civicrm_contribution_currency'],
            $this->_absoluteUrl, $this->_id, $this->_drilldownReport
          );
          $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
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

