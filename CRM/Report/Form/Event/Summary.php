<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Report_Form_Event_Summary extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_charts = [
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  ];

  protected $_add2groupSupported = FALSE;

  protected $_customGroupExtends = [
    'Event',
  ];
  public $_drilldownReport = ['event/income' => 'Link to Detail Report'];

  /**
   * Class constructor.
   */
  public function __construct() {

    $this->_columns = [
      'civicrm_event' => [
        'dao' => 'CRM_Event_DAO_Event',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'title' => [
            'title' => ts('Event Title'),
            'required' => TRUE,
          ],
          'event_type_id' => [
            'title' => ts('Event Type'),
            'required' => TRUE,
          ],
          'fee_label' => ['title' => ts('Fee Label')],
          'event_start_date' => [
            'title' => ts('Event Start Date'),
          ],
          'event_end_date' => ['title' => ts('Event End Date')],
          'max_participants' => [
            'title' => ts('Capacity'),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
        'filters' => [
          'id' => [
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_ENTITYREF,
            'type' => CRM_Utils_Type::T_INT,
            'attributes' => ['select' => ['minimumInputLength' => 0]],
          ],
          'event_type_id' => [
            'name' => 'event_type_id',
            'title' => ts('Event Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ],
          'event_start_date' => [
            'title' => ts('Event Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'event_end_date' => [
            'title' => ts('Event End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
      ],
    ];
    $this->_currencyColumn = 'civicrm_participant_fee_currency';
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
          }
        }
      }
    }

    $this->_selectClauses = $select;
    $this->_select = 'SELECT ' . implode(', ', $select);
  }

  public function from() {
    $this->_from = " FROM civicrm_event {$this->_aliases['civicrm_event']} ";
  }

  public function where() {
    $clauses = [];
    $this->_participantWhere = "";
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
          if (!empty($this->_params['id_value'])) {
            $idValue = is_array($this->_params['id_value']) ? implode(',', $this->_params['id_value']) : $this->_params['id_value'];
            $this->_participantWhere = " AND civicrm_participant.event_id IN ( $idValue ) ";
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }
    $clauses[] = "{$this->_aliases['civicrm_event']}.is_template = 0";
    $this->_where = 'WHERE  ' . implode(' AND ', $clauses);
  }

  public function groupBy() {
    $this->assign('chartSupported', TRUE);
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, "{$this->_aliases['civicrm_event']}.id");
  }

  /**
   * get participants information for events.
   * @return array
   */
  public function participantInfo() {

    $statusType1 = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1');
    $statusType2 = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 0');

    $sql = "
          SELECT civicrm_participant.event_id    AS event_id,
                 civicrm_participant.status_id   AS statusId,
                 COUNT( civicrm_participant.id ) AS participant,
                 SUM( civicrm_participant.fee_amount ) AS amount,
                 civicrm_participant.fee_currency

            FROM civicrm_participant

            WHERE civicrm_participant.is_test = 0
                  $this->_participantWhere

        GROUP BY civicrm_participant.event_id,
                 civicrm_participant.status_id,
                 civicrm_participant.fee_currency";

    $info = CRM_Core_DAO::executeQuery($sql);
    $participant_data = $participant_info = $currency = [];

    while ($info->fetch()) {
      $participant_data[$info->event_id][$info->statusId]['participant'] = $info->participant;
      $participant_data[$info->event_id][$info->statusId]['amount'] = $info->amount;
      $currency[$info->event_id] = $info->fee_currency;
    }

    $amt = $particiType1 = $particiType2 = 0;

    foreach ($participant_data as $event_id => $event_data) {
      foreach ($event_data as $status_id => $data) {

        if (array_key_exists($status_id, $statusType1)) {
          //total income of event
          $amt = $amt + $data['amount'];

          //number of Registered/Attended participants
          $particiType1 = $particiType1 + $data['participant'];
        }
        elseif (array_key_exists($status_id, $statusType2)) {

          //number of No-show/Cancelled/Pending participants
          $particiType2 = $particiType2 + $data['participant'];
        }
      }

      $participant_info[$event_id]['totalAmount'] = $amt;
      $participant_info[$event_id]['statusType1'] = $particiType1;
      $participant_info[$event_id]['statusType2'] = $particiType2;
      $participant_info[$event_id]['currency'] = $currency[$event_id];
      $amt = $particiType1 = $particiType2 = 0;
    }

    return $participant_info;
  }

  /**
   * Build header for table.
   */
  public function buildColumnHeaders() {
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
          }
        }
      }
    }

    $statusType1 = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1', 'label');
    $statusType2 = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 0', 'label');

    //make column header for participant status  Registered/Attended
    $type1_header = implode('/', $statusType1);

    //make column header for participant status No-show/Cancelled/Pending
    $type2_header = implode('/', $statusType2);

    $this->_columnHeaders['statusType1'] = [
      'title' => $type1_header,
      'type' => CRM_Utils_Type::T_INT,
    ];
    $this->_columnHeaders['statusType2'] = [
      'title' => $type2_header,
      'type' => CRM_Utils_Type::T_INT,
    ];
    $this->_columnHeaders['totalAmount'] = [
      'title' => ts('Total Income'),
      'type' => CRM_Utils_Type::T_STRING,
    ];
  }

  public function postProcess() {

    $this->beginPostProcess();

    $this->buildColumnHeaders();

    $sql = $this->buildQuery(TRUE);

    $dao = CRM_Core_DAO::executeQuery($sql);

    //set pager before exicution of query in function participantInfo()
    $this->setPager();

    $rows = $graphRows = [];
    $count = 0;
    while ($dao->fetch()) {
      $row = [];
      foreach ($this->_columnHeaders as $key => $value) {
        if (($key == 'civicrm_event_start_date') ||
          ($key == 'civicrm_event_end_date')
        ) {
          //get event start date and end date in custom datetime format
          $row[$key] = CRM_Utils_Date::customFormat($dao->$key);
        }
        else {
          if (isset($dao->$key)) {
            $row[$key] = $dao->$key;
          }
        }
      }
      $rows[] = $row;
    }
    if (!empty($rows)) {
      $participant_info = $this->participantInfo();
      foreach ($rows as $key => $value) {
        if (array_key_exists($value['civicrm_event_id'], $participant_info)) {
          foreach ($participant_info[$value['civicrm_event_id']] as $k => $v) {
            $rows[$key][$k] = $v;
          }
        }
      }
    }
    // do not call pager here
    $this->formatDisplay($rows, FALSE);
    unset($this->_columnHeaders['civicrm_event_id']);

    $this->doTemplateAssignment($rows);

    $this->endPostProcess($rows);
  }

  /**
   * @param $rows
   */
  public function buildChart(&$rows) {
    $this->_interval = 'events';
    $countEvent = NULL;
    if (!empty($this->_params['charts'])) {
      foreach ($rows as $key => $value) {
        $graphRows['totalAmount'][] = $graphRows['value'][] = CRM_Utils_Array::value('totalAmount', $rows[$key]);
        $graphRows[$this->_interval][] = substr($rows[$key]['civicrm_event_title'], 0, 12) . "..(" .
          $rows[$key]['civicrm_event_id'] . ") ";
      }

      if (CRM_Utils_Array::value('totalAmount', $rows[$key]) == 0) {
        $countEvent = count($rows);
      }

      if ((!empty($rows)) && $countEvent != 1) {
        $config = CRM_Core_Config::Singleton();
        $chartInfo = [
          'legend' => ts('Event Summary'),
          'xname' => ts('Event'),
          'yname' => ts('Total Amount (%1)', [1 => $config->defaultCurrency]),
        ];
        if (!empty($graphRows)) {
          foreach ($graphRows[$this->_interval] as $key => $val) {
            $graph[$val] = $graphRows['value'][$key];
          }
          $chartInfo['values'] = $graph;
          $chartInfo['xLabelAngle'] = 20;

          // build the chart.
          CRM_Utils_OpenFlashChart::buildChart($chartInfo, $this->_params['charts']);
          $this->assign('chartType', $this->_params['charts']);
        }
      }
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

    if (is_array($rows)) {
      $eventType = CRM_Core_OptionGroup::values('event_type');

      foreach ($rows as $rowNum => $row) {
        if (array_key_exists('totalAmount', $row) &&
          array_key_exists('currency', $row)
        ) {
          $rows[$rowNum]['totalAmount'] = CRM_Utils_Money::format($rows[$rowNum]['totalAmount'], $rows[$rowNum]['currency']);
        }
        if (array_key_exists('civicrm_event_title', $row)) {
          if ($value = $row['civicrm_event_id']) {
            //CRM_Event_PseudoConstant::event( $value, false );
            $url = CRM_Report_Utils_Report::getNextUrl('event/income',
              'reset=1&force=1&id_op=in&id_value=' . $value,
              $this->_absoluteUrl, $this->_id, $this->_drilldownReport
            );
            $rows[$rowNum]['civicrm_event_title_link'] = $url;
            $rows[$rowNum]['civicrm_event_title_hover'] = ts('View Event Income For this Event');
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
