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
class CRM_Report_Form_Contribute_History extends CRM_Report_Form {
  // Primary Contacts count limitCONSTROW_COUNT_LIMIT = 10;

  protected $_addressField = FALSE;
  protected $_emailField = FALSE;
  protected $_phoneField = FALSE;
  protected $_relationshipColumns = array();

  protected $_customGroupExtends = array('Contribution');

  protected $_referenceYear = array(
    'this_year' => '',
    'other_year' => '',
  );
  protected $_yearStatisticsFrom = '';

  protected $_yearStatisticsTo = '';
  function __construct() {
    $yearsInPast = 4;
    $date        = CRM_Core_SelectValues::date('custom', NULL, $yearsInPast, 0);
    $count       = $date['maxYear'];
    $optionYear  = array('' => ts('-- select --'));

    $this->_yearStatisticsFrom = $date['minYear'];
    $this->_yearStatisticsTo = $date['maxYear'];

    while ($date['minYear'] <= $count) {
      $optionYear[$date['minYear']] = $date['minYear'];
      $date['minYear']++;
    }

    $relationTypeOp = array();
    $relationshipTypes = CRM_Core_PseudoConstant::relationshipType();
    foreach ($relationshipTypes as $rid => $rtype) {
      if ($rtype['label_a_b'] != $rtype['label_b_a']) {
        $relationTypeOp[$rid] = "{$rtype['label_a_b']}/{$rtype['label_b_a']}";
      }
      else {
        $relationTypeOp[$rid] = $rtype['label_a_b'];
      }
    }

    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'default' => TRUE,
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
        'filters' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name')),
          'id' =>
          array('title' => ts('Contact ID'),
            'no_display' => TRUE,
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
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array(
          'phone' =>
          array('title' => ts('Phone'),
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
    ) + $this->addAddressFields(FALSE, FALSE, FALSE, array(
      )) + array('civicrm_relationship' =>
      array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'fields' =>
        array(
          'relationship_type_id' =>
          array('title' => ts('Relationship Type'),
            'default' => TRUE,
          ),
          'contact_id_a' =>
          array('no_display' => TRUE),
          'contact_id_b' =>
          array('no_display' => TRUE),
        ),
        'filters' =>
        array(
          'relationship_type_id' =>
          array('title' => ts('Relationship Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $relationTypeOp,
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      ),
    ) + array(
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'total_amount' =>
          array('title' => ts('Amount Statistics'),
            'default' => TRUE,
            'required' => TRUE,
            'no_display' => TRUE,
            'statistics' =>
            array('sum' => ts('Aggregate Amount')),
          ),
          'receive_date' =>
          array(
            'required' => TRUE,
            'default' => TRUE,
            'no_display' => TRUE,
          ),
        ),
        'grouping' => 'contri-fields',
        'filters' =>
        array(
          'this_year' =>
          array(
            'title' => ts('This Year'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $optionYear,
            'default' => '',
          ),
          'other_year' =>
          array(
            'title' => ts('Other Years'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $optionYear,
            'default' => '',
          ),
          'receive_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'contribution_status_id' =>
          array('title' => ts('Donation Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => array(1),
          ),
          'financial_type_id' => array( 
            'title' => ts('Financial Type'), 
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ),
          'total_amount' =>
          array('title' => ts('Donation Amount'),
          ),
          'total_sum' =>
          array('title' => ts('Aggregate Amount'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_total_amount_sum',
            'having' => TRUE,
          ),
        ),
      ),
    ) + array(
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

    $this->_columns['civicrm_contribution']['fields']['civicrm_upto_' . $this->_yearStatisticsFrom] = array('title' => ts('Up To %1 Donation', array(1 => $this->_yearStatisticsFrom)),
      'default' => TRUE,
      'type' => CRM_Utils_Type::T_MONEY,
      'is_statistics' => TRUE,
    );

    $yearConter = $this->_yearStatisticsFrom;
    $yearConter++;
    while ($yearConter <= $this->_yearStatisticsTo) {
      $this->_columns['civicrm_contribution']['fields'][$yearConter] = array('title' => ts('%1 Donation', array(1 => $yearConter)),
        'default' => TRUE,
        'type' => CRM_Utils_Type::T_MONEY,
        'is_statistics' => TRUE,
      );
      $yearConter++;
    }

    $this->_columns['civicrm_contribution']['fields']['aggregate_amount'] = array('title' => ts('Aggregate Amount'),
      'type' => CRM_Utils_Type::T_MONEY,
      'is_statistics' => TRUE,
    );

    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {

          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            if ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            if ($tableName == 'civicrm_relationship') {
              $this->_relationshipColumns["{$tableName}_{$fieldName}"] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              continue;
            }

            if (CRM_Utils_Array::value('is_statistics', $field)) {
              $this->_columnHeaders[$fieldName]['type'] = $field['type'];
              $this->_columnHeaders[$fieldName]['title'] = $field['title'];
              continue;
            }
            elseif ($fieldName == 'receive_date') { 
                if ((CRM_Utils_Array::value('this_year_op', $this->_params) == 'fiscal' && 
                     CRM_Utils_Array::value('this_year_value', $this->_params)) ||
                    (CRM_Utils_Array::value('other_year_op', $this->_params == 'fiscal') && 
                      CRM_Utils_Array::value('other_year_value', $this->_params)
                     )){
                    $select[] = self::fiscalYearOffset($field['dbAlias']) . " as {$tableName}_{$fieldName}";
                }else{
                    $select[] = " YEAR(".$field['dbAlias'].")" . " as {$tableName}_{$fieldName}";
                }
            }
            elseif ($fieldName == 'total_amount') {
              $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}";
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
            if (CRM_Utils_Array::value('no_display', $field)) {
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = TRUE;
            }
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = "
        FROM civicrm_contact  {$this->_aliases['civicrm_contact']}
             INNER JOIN civicrm_contribution   {$this->_aliases['civicrm_contribution']} 
                     ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND
                        {$this->_aliases['civicrm_contribution']}.is_test = 0 ";

    if ($this->_emailField) {
      $this->_from .= " LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} 
                     ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND 
                        {$this->_aliases['civicrm_email']}.is_primary = 1) ";
    }

    if ($this->_phoneField) {
      $this->_from .= " LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']} 
                     ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND 
                        {$this->_aliases['civicrm_phone']}.is_primary = 1) ";
    }

    $relContacAlias = 'contact_relationship';
    $this->_relationshipFrom = " INNER JOIN civicrm_relationship {$this->_aliases['civicrm_relationship']} 
                     ON (({$this->_aliases['civicrm_relationship']}.contact_id_a = {$relContacAlias}.id OR {$this->_aliases['civicrm_relationship']}.contact_id_b = {$relContacAlias}.id ) AND {$this->_aliases['civicrm_relationship']}.is_active = 1) ";

    if ($this->_addressField) {
      $this->_from .= "
                  LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                         ON {$this->_aliases['civicrm_contact']}.id = 
                            {$this->_aliases['civicrm_address']}.contact_id AND 
                            {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
  }

  function where() {
    $whereClauses = $havingClauses = $relationshipWhere = array();
    $this->_relationshipWhere = '';
    $this->_statusClause = '';

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($fieldName == 'this_year' || $fieldName == 'other_year') {
            continue;
          }elseif (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
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
            if ($tableName == 'civicrm_relationship') {
              $relationshipWhere[] = $clause;
              continue;
            }

            if ($fieldName == 'contribution_status_id') {
              $this->_statusClause = " AND " . $clause;
            }

            if (CRM_Utils_Array::value('having', $field)) {
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
      $this->_relationshipWhere = ' AND ' . implode(' AND ', $relationshipWhere);
    }
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contribution']}.contact_id, YEAR({$this->_aliases['civicrm_contribution']}.receive_date)";
  }

  //Override to set limit is 10
  function limit($rowCount = self::ROW_COUNT_LIMIT) {
    parent::limit($rowCount);
  }

  //Override to set pager with limit is 10
  function setPager($rowCount = self::ROW_COUNT_LIMIT) {
    parent::setPager($rowCount);
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $count = 0;
    foreach ($rows as $rownum => $row) {
      if (is_numeric($rownum)) {
        $count++;
      }
    }
    $statistics['counts']['rowCount'] = array('title' => ts('Primary Contact(s) Listed'),
      'value' => $count,
    );

    if ($this->_rowsFound && ($this->_rowsFound > $count)) {
      $statistics['counts']['rowsFound'] = array('title' => ts('Total Primary Contact(s)'),
        'value' => $this->_rowsFound,
      );
    }

    return $statistics;
  }

  static function formRule($fields, $files, $self) {
    $errors = array();
    if (CRM_Utils_Array::value('this_year_value', $fields) &&
      CRM_Utils_Array::value('other_year_value', $fields) &&
      ($fields['this_year_value'] == $fields['other_year_value'])
    ) {
      $errors['other_year_value'] = ts("Value for filters 'This Year' and 'Other Years' can not be same.");
    }
    return $errors;
  }

  function postProcess() {
    // get ready with post process params
    $this->beginPostProcess();
    $this->fixReportParams();

    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $this->select();
    $this->where();
    $this->from();
    $this->groupBy();

    $rows = array();

    // build array of result based on column headers. This method also allows
    // modifying column headers before using it to build result set i.e $rows.
    $this->buildRows($rows);

    // format result set.
    $this->formatDisplay($rows, FALSE);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  function fixReportParams() {
    if (CRM_Utils_Array::value('this_year_value', $this->_params)) {
      $this->_referenceYear['this_year'] = $this->_params['this_year_value'];
    }
    if (CRM_Utils_Array::value('other_year_value', $this->_params)) {
      $this->_referenceYear['other_year'] = $this->_params['other_year_value'];
    }
  }

  function buildRows(&$rows) {
    $contactIds = array();

    $addWhere = '';

    if (CRM_Utils_Array::value('other_year', $this->_referenceYear)) {
        (CRM_Utils_Array::value('other_year_op', $this->_params) == 'calendar') ? $other_receive_date = 'YEAR (contri.receive_date)' : $other_receive_date = self::fiscalYearOffset('contri.receive_date');
        $addWhere .= " AND {$this->_aliases['civicrm_contact']}.id NOT IN ( SELECT DISTINCT cont.id FROM civicrm_contact cont, civicrm_contribution contri WHERE  cont.id = contri.contact_id AND {$other_receive_date} = {$this->_referenceYear['other_year']} AND contri.is_test = 0 ) ";
    }
    if (CRM_Utils_Array::value('this_year', $this->_referenceYear)) {
        (CRM_Utils_Array::value('this_year_op', $this->_params) == 'calendar') ? $receive_date = 'YEAR (contri.receive_date)' : $receive_date = self::fiscalYearOffset('contri.receive_date');
        $addWhere .= " AND {$this->_aliases['civicrm_contact']}.id IN ( SELECT DISTINCT cont.id FROM civicrm_contact cont, civicrm_contribution contri WHERE cont.id = contri.contact_id AND {$receive_date} = {$this->_referenceYear['this_year']} AND contri.is_test = 0 ) ";
    }  
    $this->limit();
    $getContacts = "SELECT SQL_CALC_FOUND_ROWS {$this->_aliases['civicrm_contact']}.id as cid, SUM({$this->_aliases['civicrm_contribution']}.total_amount) as civicrm_contribution_total_amount_sum {$this->_from} {$this->_where} {$addWhere} GROUP BY {$this->_aliases['civicrm_contact']}.id {$this->_having} {$this->_limit}";
    
    $dao = CRM_Core_DAO::executeQuery($getContacts);

    while ($dao->fetch()) {
      $contactIds[] = $dao->cid;
    }
    $dao->free();
    $this->setPager();

    $relationshipRows = array();
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

    $summaryYears   = array();
    $summaryYears[] = "civicrm_upto_{$this->_yearStatisticsFrom}";
    $yearConter     = $this->_yearStatisticsFrom;
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
      $total = array();
      $total['civicrm_contact_sort_name'] = ts('Total');
      foreach ($summaryYears as $year) {
        $total[$year] = CRM_Utils_Array::value($year, $primaryRow, 0);
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
          $total[$year] += CRM_Utils_Array::value($year, $relatedRow, 0);
        }

        foreach (array_keys($this->_relationshipColumns) as $col) {
          if (CRM_Utils_Array::value($col, $relRow)) {
            $relatedRow[$col] = $relRow[$col];
          }
        }
        $rows["{$cid}_{$relcid}"] = $relatedRow;
      }
      if ($relatedContact) {
        $rows["{$cid}_total"] = $total;
        $rows["{$cid}_bank"] = array('civicrm_contact_sort_name' => '&nbsp;');
      }
    }
  }

  function buildContributionRows($contactIds) {
    $rows = array();
    if (empty($contactIds)) {
      return $rows;
    }

    $sqlContribution = "{$this->_select} {$this->_from} WHERE {$this->_aliases['civicrm_contact']}.id IN (" . implode(',', $contactIds) . ") AND {$this->_aliases['civicrm_contribution']}.is_test = 0 {$this->_statusClause} {$this->_groupBy} ";

    $dao             = CRM_Core_DAO::executeQuery($sqlContribution);
    $contributionSum = 0;
    $yearcal         = array();
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
        if ($dao->civicrm_contribution_receive_date > $this->_yearStatisticsFrom) {
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
    $dao->free();
    return $rows;
  }

  function buildRelationshipRows($contactIds) {
    $relationshipRows = $relatedContactIds = array();
    if (empty($contactIds)) {
      return array($relationshipRows, $relatedContactIds);
    }

    $relContactAlias = 'contact_relationship';
    $addRelSelect = '';
    if (!empty($this->_relationshipColumns)) {
      $addRelSelect = ', ' . implode(', ', $this->_relationshipColumns);
    }
    $sqlRelationship = "SELECT {$this->_aliases['civicrm_relationship']}.relationship_type_id as relationship_type_id, {$this->_aliases['civicrm_relationship']}.contact_id_a as contact_id_a, {$this->_aliases['civicrm_relationship']}.contact_id_b as contact_id_b {$addRelSelect} FROM civicrm_contact {$relContactAlias} {$this->_relationshipFrom} WHERE {$relContactAlias}.id IN (" . implode(',', $contactIds) . ") AND {$this->_aliases['civicrm_relationship']}.is_active = 1 {$this->_relationshipWhere} GROUP BY {$this->_aliases['civicrm_relationship']}.contact_id_a, {$this->_aliases['civicrm_relationship']}.contact_id_b";
    $relationshipTypes = CRM_Core_PseudoConstant::relationshipType();

    $dao = CRM_Core_DAO::executeQuery($sqlRelationship);
    while ($dao->fetch()) {
      $row = array();
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
    $dao->free();
    return array($relationshipRows, $relatedContactIds);
  }
  
  // Override "This Year" $op options
  static function getOperationPair($type = "string", $fieldName = NULL) {
      if ($fieldName == 'this_year' || $fieldName == 'other_year') {
          return array('calendar' => ts('Is Calendar Year'), 'fiscal' => ts('Fiscal Year Starting'));
      }
      return parent::getOperationPair($type, $fieldName);
  }
  
  

  function alterDisplay(&$rows) {
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
            $value = CRM_Utils_Money::format($value, ' ');
          }
          $row[$key] = '<strong>' . $value . '</strong>';
        }
        $rows[$rowNum] = $row;
      }

      // Convert Display name into link
      if (CRM_Utils_Array::value('civicrm_contact_sort_name', $row) &&
        CRM_Utils_Array::value('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contribution Details for this Contact.");
      }
    }
  }
 }

