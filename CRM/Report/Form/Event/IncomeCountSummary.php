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
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
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
class CRM_Report_Form_Event_IncomeCountSummary extends CRM_Report_Form_Event {

  protected $_summary = NULL;

  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );

  protected $_add2groupSupported = FALSE;

  protected $_customGroupExtends = array(
    'Event'); 

  public $_drilldownReport = array('event/participantlist' => 'Link to Detail Report');

  function __construct() {

    $this->_columns = array(
      'civicrm_event' =>
      array(
        'dao' => 'CRM_Event_DAO_Event',
        'fields' =>
        array(
          'title' => array('title' => ts('Event'),
            'required' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'event_type_id' => array('title' => ts('Event Type'),
          ),
          'fee_label' => array('title' => ts('Fee Label')),
          'event_start_date' => array('title' => ts('Event Start Date'),
          ),
          'event_end_date' => array('title' => ts('Event End Date'),
          ),
          'max_participants' => array('title' => ts('Capacity'),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'filters' =>
        array(
          'id' => array('title' => ts('Event Title'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->getEventFilterOptions(),
          ),
          'event_type_id' => array(
            'name' => 'event_type_id',
            'title' => ts('Event Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ),
          'event_start_date' => array('title' => ts('Event Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'event_end_date' => array('title' => ts('Event End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
      ),
      'civicrm_line_item' =>
      array(
        'dao' => 'CRM_Price_DAO_LineItem',
        'fields' =>
        array(
          'participant_count' => array(
            'title' => ts('Participants'),
            'default' => TRUE,
            'statistics' =>
            array('count' => ts('Participants'),
            ),
          ),
          'line_total' => array(
            'title' => ts('Income Statistics'),
            'type' => CRM_Utils_Type::T_MONEY,
            'default' => TRUE,
            'statistics' =>
            array('sum' => ts('Income'),
              'avg' => ts('Average'),
            ),
          ),
        ),
      ),
      'civicrm_participant' =>
      array(
        'dao' => 'CRM_Event_DAO_Participant',
        'filters' =>
        array(
          'sid' => array('name' => 'status_id',
            'title' => ts('Participant Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(),
          ),
          'rid' => array(
            'name' => 'role_id',
            'title' => ts('Participant Role'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
          ),
          'participant_register_date' => array('title' => ts('Registration Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
      ),
    );
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if (CRM_Utils_Array::value('statistics', $field)) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'count':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_MONEY;

                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'avg':
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;

                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_MONEY;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                }
              }
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select);
  }

  function from() {
    $this->_from = " 
        FROM civicrm_event {$this->_aliases['civicrm_event']}
             LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']} 
                    ON {$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id AND 
                       {$this->_aliases['civicrm_participant']}.is_test = 0 
             LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
                    ON {$this->_aliases['civicrm_participant']}.id ={$this->_aliases['civicrm_line_item']}.entity_id AND 
                       {$this->_aliases['civicrm_line_item']}.entity_table = 'civicrm_participant' ";
  }

  function where() {
    $clauses = array();
    $this->_participantWhere = "";
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
          if (!empty($this->_params['id_value'])) {
            $participant = implode(', ', $this->_params['id_value']);
            $this->_participantWhere = " AND civicrm_participant.event_id IN ( {$participant} ) ";
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }
    $clauses[] = "({$this->_aliases['civicrm_event']}.is_template IS NULL OR {$this->_aliases['civicrm_event']}.is_template = 0)";
    $this->_where = "WHERE  " . implode(' AND ', $clauses);
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $select = "
         SELECT SUM( {$this->_aliases['civicrm_line_item']}.participant_count ) as count,
                SUM( {$this->_aliases['civicrm_line_item']}.line_total )  as amount";

    $sql = "{$select} {$this->_from} {$this->_where}";

    $dao = CRM_Core_DAO::executeQuery($sql);

    if ($dao->fetch()) {
      if ($dao->count && $dao->amount) {
        $avg = $dao->amount / $dao->count;
      }
      $statistics['counts']['count'] = array(
        'value' => $dao->count,
        'title' => 'Total Participants',
        'type' => CRM_Utils_Type::T_INT,
      );
      $statistics['counts']['amount'] = array(
        'value' => $dao->amount,
        'title' => 'Total Income',
        'type' => CRM_Utils_Type::T_MONEY,
      );
      $statistics['counts']['avg   '] = array(
        'value' => $avg,
        'title' => 'Average',
        'type' => CRM_Utils_Type::T_MONEY,
      );
    }
    return $statistics;
  }

  function groupBy() {
    $this->assign('chartSupported', TRUE);
    $this->_rollup = " WITH ROLLUP";
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_event']}.id  {$this->_rollup}";
  }

  function postProcess() {

    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $dao = CRM_Core_DAO::executeQuery($sql);

    //set pager before execution of query in function participantInfo()
    $this->setPager();

    $rows = $graphRows = array();
    $count = 0;

    while ($dao->fetch()) {
      $row = array();
      foreach ($this->_columnHeaders as $key => $value) {
        if (($key == 'civicrm_event_start_date') || ($key == 'civicrm_event_end_date')) {
          //get event start date and end date in custom datetime format
          $row[$key] = CRM_Utils_Date::customFormat($dao->$key);
        }
        elseif ($key == 'civicrm_participant_fee_amount_avg') {
          if ($dao->civicrm_participant_fee_amount_sum && $dao->civicrm_line_item_participant_count_count) {
            $row[$key] = $dao->civicrm_participant_fee_amount_sum / $dao->civicrm_line_item_participant_count_count;
          }
        }
        elseif ($key == 'civicrm_line_item_line_total_avg') {
          if ($dao->civicrm_line_item_line_total_sum && $dao->civicrm_line_item_participant_count_count) {
            $row[$key] = $dao->civicrm_line_item_line_total_sum / $dao->civicrm_line_item_participant_count_count;
          }
        }
        else {
          if (isset($dao->$key)) {
            $row[$key] = $dao->$key;
          }
        }
      }
      $rows[] = $row;
    }

    // do not call pager here
    $this->formatDisplay($rows, FALSE);
    unset($this->_columnHeaders['civicrm_event_id']);

    $this->doTemplateAssignment($rows);

    $this->endPostProcess($rows);
  }

  function buildChart(&$rows) {

    $this->_interval = 'events';
    $countEvent = NULL;
    if (CRM_Utils_Array::value('charts', $this->_params)) {
      foreach ($rows as $key => $value) {
        if ($value['civicrm_event_id']) {
          $graphRows['totalParticipants'][] = ($rows[$key]['civicrm_line_item_participant_count_count']);
          $graphRows[$this->_interval][] = substr($rows[$key]['civicrm_event_title'], 0, 12) . "..(" . $rows[$key]['civicrm_event_id'] . ") ";
          $graphRows['value'][] = ($rows[$key]['civicrm_line_item_participant_count_count']);
        }
      }

      if (($rows[$key]['civicrm_line_item_participant_count_count']) == 0) {
        $countEvent = count($rows);
      }

      if ((!empty($rows)) && $countEvent != 1) {
        $chartInfo = array(
          'legend' => 'Participants Summary',
          'xname' => 'Event',
          'yname' => 'Total Participants',
        );
        if (!empty($graphRows)) {
          foreach ($graphRows[$this->_interval] as $key => $val) {
            $graph[$val] = $graphRows['value'][$key];
          }
          $chartInfo['values'] = $graph;
          $chartInfo['tip'] = 'Participants : #val#';
          $chartInfo['xLabelAngle'] = 20;

          // build the chart.
          CRM_Utils_OpenFlashChart::buildChart($chartInfo, $this->_params['charts']);
        }
      }
    }
  }

  function alterDisplay(&$rows) {

    if (is_array($rows)) {
      $eventType = CRM_Core_OptionGroup::values('event_type');

      foreach ($rows as $rowNum => $row) {
        if (array_key_exists('civicrm_event_title', $row)) {
          if ($value = $row['civicrm_event_id']) {
            CRM_Event_PseudoConstant::event($value, FALSE);
            $url = CRM_Report_Utils_Report::getNextUrl('event/participantlist',
              'reset=1&force=1&event_id_op=eq&event_id_value=' . $value,
              $this->_absoluteUrl, $this->_id, $this->_drilldownReport
            );
            $rows[$rowNum]['civicrm_event_title_link'] = $url;
            $rows[$rowNum]['civicrm_event_title_hover'] = ts("View Event Participants For this Event");
          }
        }

        //handle event type
        if (array_key_exists('civicrm_event_event_type_id', $row)) {
          if ($value = $row['civicrm_event_event_type_id']) {
            $rows[$rowNum]['civicrm_event_event_type_id'] = $eventType[$value];
          }
        }
      }
    }
  }
}

