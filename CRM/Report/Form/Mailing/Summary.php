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
class CRM_Report_Form_Mailing_Summary extends CRM_Report_Form {

  protected $_summary = NULL;

  # just a toggle we use to build the from
  protected $_mailingidField = FALSE;

  protected $_customGroupExtends = array();

  public $_drilldownReport = array('contact/detail' => 'Link to Detail Report');

  protected $_charts = array(
    '' => 'Tabular',
    'bar_3dChart' => 'Bar Chart',
  );

  function __construct() {
    $this->_columns = array();

    $this->_columns['civicrm_mailing'] = array(
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => array(
        'name' => array(
          'title' => ts('Mailing Name'),
          'required' => TRUE,
        ),
        'created_date' => array(
          'title' => ts('Date Created'),
        ),
      ),
      'filters' => array(
        'is_completed' => array(
          'title' => ts('Mailing Status'),
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'type' => CRM_Utils_Type::T_INT,
          'options' => array(
            0 => 'Incomplete',
            1 => 'Complete',
          ),
          //'operator' => 'like',
          'default' => 1,
        ),
        'mailing_name' => array(
          'name' => 'name',
          'title' => ts('Mailing'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'type' => CRM_Utils_Type::T_STRING,
          'options' => self::mailing_select(),
          'operator' => 'like',
        ),
      ),
    );

    $this->_columns['civicrm_mailing_job'] = array(
      'dao' => 'CRM_Mailing_DAO_Job',
      'fields' => array(
        'start_date' => array(
          'title' => ts('Start Date'),
        ),
        'end_date' => array(
          'title' => ts('End Date'),
        ),
      ),
      'filters' => array(
        'status' => array(
          'type' => CRM_Utils_Type::T_STRING,
          'default' => 'Complete',
          'no_display' => TRUE,
        ),
        'is_test' => array(
          'type' => CRM_Utils_Type::T_INT,
          'default' => 0,
          'no_display' => TRUE,
        ),
        'start_date' => array(
          'title' => ts('Start Date'),
          'default' => 'this.year',
          'operatorType' => CRM_Report_Form::OP_DATE,
          'type' => CRM_Utils_Type::T_DATE,
        ),
        'end_date' => array(
          'title' => ts('End Date'),
          'default' => 'this.year',
          'operatorType' => CRM_Report_Form::OP_DATE,
          'type' => CRM_Utils_Type::T_DATE,
        ),
      ),
    );

    $this->_columns['civicrm_mailing_event_queue'] = array(
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => array(
        'queue_count' => array(
          'name' => 'id',
          'title' => ts('Intended Recipients'),
        ),
      ),
    );

    $this->_columns['civicrm_mailing_event_delivered'] = array(
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => array(
        'delivered_count' => array(
          'name' => 'id',
          'title' => ts('Delivered'),
        ),
        'accepted_rate' => array(
          'title' => 'Accepted Rate',
          'statistics' => array(
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_delivered.delivered_count',
            'base' => 'civicrm_mailing_event_queue.queue_count',
          ),
        ),
      ),
    );

    $this->_columns['civicrm_mailing_event_bounce'] = array(
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => array(
        'bounce_count' => array(
          'name' => 'id',
          'title' => ts('Bounce'),
        ),
        'bounce_rate' => array(
          'title' => 'Bounce Rate',
          'statistics' => array(
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_bounce.bounce_count',
            'base' => 'civicrm_mailing_event_queue.queue_count',
          ),
        ),
      ),
    );

    $this->_columns['civicrm_mailing_event_opened'] = array(
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => array(
        'open_count' => array(
          'name' => 'id',
          'title' => ts('Opened'),
        ),
        'open_rate' => array(
          'title' => 'Confirmed Open Rate',
          'statistics' => array(
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_opened.open_count',
            'base' => 'civicrm_mailing_event_delivered.delivered_count',
          ),
        ),
      ),
    );

    $this->_columns['civicrm_mailing_event_trackable_url_open'] = array(
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => array(
        'click_count' => array(
          'name' => 'id',
          'title' => ts('Clicks'),
        ),
        'CTR' => array(
          'title' => 'Click through Rate',
          'default' => 0,
          'statistics' => array(
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_trackable_url_open.click_count',
            'base' => 'civicrm_mailing_event_delivered.delivered_count',
          ),
        ),
        'CTO' => array(
          'title' => 'Click to Open Rate',
          'default' => 0,
          'statistics' => array(
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_trackable_url_open.click_count',
            'base' => 'civicrm_mailing_event_opened.open_count',
          ),
        ),
      ),
    );

    $this->_columns['civicrm_mailing_event_unsubscribe'] = array(
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => array(
        'unsubscribe_count' => array(
          'name' => 'id',
          'title' => ts('Unsubscribe'),
        ),
      ),
    );

    parent::__construct();
  }

  function mailing_select() {

    $data = array();

    $mailing = new CRM_Mailing_BAO_Mailing();
    $query = "SELECT name FROM civicrm_mailing WHERE sms_provider_id IS NULL";
    $mailing->query($query);

    while ($mailing->fetch()) {
      $data[mysql_real_escape_string($mailing->name)] = $mailing->name;
    }

    return $data;
  }

  function preProcess() {
    $this->assign('chartSupported', TRUE);
    parent::preProcess();
  }

  // manipulate the select function to query count functions
  function select() {

    $count_tables = array(
      'civicrm_mailing_event_queue',
      'civicrm_mailing_event_delivered',
      'civicrm_mailing_event_opened',
      'civicrm_mailing_event_bounce',
      'civicrm_mailing_event_trackable_url_open',
      'civicrm_mailing_event_unsubscribe',
    );

    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {

            # for statistics
            if (CRM_Utils_Array::value('statistics', $field)) {
              switch ($field['statistics']['calc']) {
                case 'PERCENTAGE':
                  $base_table_column = explode('.', $field['statistics']['base']);
                  $top_table_column = explode('.', $field['statistics']['top']);

                  $select[] = "CONCAT(round(
                    count(DISTINCT {$this->_columns[$top_table_column[0]]['fields'][$top_table_column[1]]['dbAlias']}) /
                    count(DISTINCT {$this->_columns[$base_table_column[0]]['fields'][$base_table_column[1]]['dbAlias']}) * 100, 2
                  ), '%') as {$tableName}_{$fieldName}";
                  break;
              }
            }
            else {
              if (in_array($tableName, $count_tables)) {
                $select[] = "count(DISTINCT {$field['dbAlias']}) as {$tableName}_{$fieldName}";
              }
              else {
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              }
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
    //print_r($this->_select);
  }

  function from() {

    $this->_from = "
    FROM civicrm_mailing {$this->_aliases['civicrm_mailing']}
      LEFT JOIN civicrm_mailing_job {$this->_aliases['civicrm_mailing_job']}
        ON {$this->_aliases['civicrm_mailing']}.id = {$this->_aliases['civicrm_mailing_job']}.mailing_id
      LEFT JOIN civicrm_mailing_event_queue {$this->_aliases['civicrm_mailing_event_queue']}
        ON {$this->_aliases['civicrm_mailing_event_queue']}.job_id = {$this->_aliases['civicrm_mailing_job']}.id
      LEFT JOIN civicrm_mailing_event_bounce {$this->_aliases['civicrm_mailing_event_bounce']}
        ON {$this->_aliases['civicrm_mailing_event_bounce']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id
      LEFT JOIN civicrm_mailing_event_delivered {$this->_aliases['civicrm_mailing_event_delivered']}
        ON {$this->_aliases['civicrm_mailing_event_delivered']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id
        AND {$this->_aliases['civicrm_mailing_event_bounce']}.id IS null
      LEFT JOIN civicrm_mailing_event_opened {$this->_aliases['civicrm_mailing_event_opened']}
        ON {$this->_aliases['civicrm_mailing_event_opened']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id
      LEFT JOIN civicrm_mailing_event_trackable_url_open {$this->_aliases['civicrm_mailing_event_trackable_url_open']}
        ON {$this->_aliases['civicrm_mailing_event_trackable_url_open']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id
      LEFT JOIN civicrm_mailing_event_unsubscribe {$this->_aliases['civicrm_mailing_event_unsubscribe']}
        ON {$this->_aliases['civicrm_mailing_event_unsubscribe']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id";
    // need group by and order by

    //print_r($this->_from);
  }

  function where() {
    $clauses = array();
    //to avoid the sms listings
    $clauses[] = "{$this->_aliases['civicrm_mailing']}.sms_provider_id IS NULL";

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);

            if ($op) {
              if ($fieldName == 'relationship_type_id') {
                $clause = "{$this->_aliases['civicrm_relationship']}.relationship_type_id=" . $this->relationshipId;
              }
              else {
                $clause = $this->whereClause($field,
                  $op,
                  CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
                );
              }
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 )";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    // if ( $this->_aclWhere ) {
    // $this->_where .= " AND {$this->_aclWhere} ";
    // }
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_mailing']}.id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_mailing_job']}.end_date DESC ";
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause(CRM_Utils_Array::value('civicrm_contact', $this->_aliases));

    $sql = $this->buildQuery(TRUE);

    // print_r($sql);

    $rows = $graphRows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  static function getChartCriteria() {
    return array('count' => array('civicrm_mailing_event_delivered_delivered_count' => ts('Delivered'),
        'civicrm_mailing_event_bounce_bounce_count' => ts('Bounce'),
        'civicrm_mailing_event_opened_open_count' => ts('Opened'),
        'civicrm_mailing_event_trackable_url_open_click_count' => ts('Clicks'),
        'civicrm_mailing_event_unsubscribe_unsubscribe_count' => ts('Unsubscribe'),
      ),
      'rate' => array('civicrm_mailing_event_delivered_accepted_rate' => ts('Accepted Rate'),
        'civicrm_mailing_event_bounce_bounce_rate' => ts('Bounce Rate'),
        'civicrm_mailing_event_opened_open_rate' => ts('Confirmed Open Rate'),
        'civicrm_mailing_event_trackable_url_open_CTR' => ts('Click through Rate'),
        'civicrm_mailing_event_trackable_url_open_CTO' => ts('Click to Open Rate'),
      ),
    );
  }

  function formRule($fields, $files, $self) {
    $errors = array();

    if (!CRM_Utils_Array::value('charts', $fields)) {
      return $errors;
    }

    $criterias = self::getChartCriteria();
    $isError = TRUE;
    foreach ($fields['fields'] as $fld => $isActive) {
      if (in_array($fld, array(
        'delivered_count', 'bounce_count', 'open_count', 'click_count', 'unsubscribe_count', 'accepted_rate', 'bounce_rate', 'open_rate', 'CTR', 'CTO'))) {
        $isError = FALSE;
      }
    }

    if ($isError) {
      $errors['_qf_default'] = ts('For Chart view, please select at least one field from %1 OR %2.', array(1 => implode(', ', $criterias['count']), 2 => implode(', ', $criterias['rate'])));
    }

    return $errors;
  }

  function buildChart(&$rows) {
    if (empty($rows)) {
      return;
    }

    $criterias = self::getChartCriteria();

    $chartInfo = array('legend' => ts('Mail Summary'),
      'xname' => ts('Mailing'),
      'yname' => ts('Statistics'),
      'xLabelAngle' => 20,
      'tip' => array(),
    );

    $plotRate = $plotCount = TRUE;
    foreach ($rows as $row) {
      $chartInfo['values'][$row['civicrm_mailing_name']] = array();
      if ($plotCount) {
        foreach ($criterias['count'] as $criteria => $label) {
          if (isset($row[$criteria])) {
            $chartInfo['values'][$row['civicrm_mailing_name']][$label] = $row[$criteria];
            $chartInfo['tip'][$label] = "{$label} #val#";
            $plotRate = FALSE;
          }
          elseif (isset($criterias['count'][$criteria])) {
            unset($criterias['count'][$criteria]);
          }
        }
      }
      if ($plotRate) {
        foreach ($criterias['rate'] as $criteria => $label) {
          if (isset($row[$criteria])) {
            $chartInfo['values'][$row['civicrm_mailing_name']][$label] = $row[$criteria];
            $chartInfo['tip'][$label] = "{$label} #val#";
            $plotCount = FALSE;
          }
          elseif (isset($criterias['rate'][$criteria])) {
            unset($criterias['rate'][$criteria]);
          }
        }
      }
    }

    if ($plotCount) {
      $criterias = $criterias['count'];
    }
    else {
      $criterias = $criterias['rate'];
    }

    $chartInfo['criteria'] = array_values($criterias);

    // dynamically set the graph size
    $chartInfo['xSize'] = ((count($rows) * 125) + (count($rows) * count($criterias) * 40));

    // build the chart.
    CRM_Utils_OpenFlashChart::buildChart($chartInfo, $this->_params['charts']);
    $this->assign('chartType', $this->_params['charts']);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      // convert display name to links
      if (array_key_exists('civicrm_contact_display_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      // handle country
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
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

