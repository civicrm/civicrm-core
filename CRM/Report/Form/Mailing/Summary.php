<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Report_Form_Mailing_Summary extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupExtends = array();

  protected $_add2groupSupported = FALSE;

  public $_drilldownReport = array('mailing/detail' => 'Link to Detail Report');

  protected $_charts = array(
    '' => 'Tabular',
    'bar_3dChart' => 'Bar Chart',
  );

  public $campaignEnabled = FALSE;

  /**
   */
  /**
   */
  public function __construct() {
    $this->_columns = array();

    $this->_columns['civicrm_mailing'] = array(
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => array(
        'id' => array(
          'name' => 'id',
          'title' => ts('Mailing ID'),
          'required' => TRUE,
          'no_display' => TRUE,
        ),
        'name' => array(
          'title' => ts('Mailing Name'),
          'required' => TRUE,
        ),
        'created_date' => array(
          'title' => ts('Date Created'),
        ),
        'subject' => array(
          'title' => ts('Subject'),
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
      'dao' => 'CRM_Mailing_DAO_MailingJob',
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
          'name' => 'event_queue_id',
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
          'name' => 'event_queue_id',
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
        'unique_open_count' => array(
          'name' => 'id',
          'alias' => 'mailing_event_opened_civireport',
          'dbAlias' => 'mailing_event_opened_civireport.event_queue_id',
          'title' => ts('Unique Opens'),
        ),
        'unique_open_rate' => array(
          'title' => 'Unique Open Rate',
          'statistics' => array(
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_opened.unique_open_count',
            'base' => 'civicrm_mailing_event_delivered.delivered_count',
          ),
        ),
        'open_count' => array(
          'name' => 'event_queue_id',
          'title' => ts('Total Opens'),
        ),
        'open_rate' => array(
          'title' => 'Total Open Rate',
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
          'name' => 'event_queue_id',
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
          'alias' => 'mailing_event_unsubscribe_civireport',
          'dbAlias' => 'mailing_event_unsubscribe_civireport.event_queue_id',
        ),
        'optout_count' => array(
          'name' => 'id',
          'title' => ts('Opt-outs'),
          'alias' => 'mailing_event_optout_civireport',
          'dbAlias' => 'mailing_event_optout_civireport.event_queue_id',
        ),
      ),
    );
    $config = CRM_Core_Config::singleton();
    $this->campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($this->campaignEnabled) {
      $this->_columns['civicrm_campaign'] = array(
        'dao' => 'CRM_Campaign_DAO_Campaign',
        'fields' => array(
          'title' => array(
            'title' => ts('Campaign Name'),
          ),
        ),
        'filters' => array(
          'title' => array(
            'type' => CRM_Utils_Type::T_STRING,
          ),
        ),
      );
    }
    parent::__construct();
  }

  /**
   * @return array
   */
  public function mailing_select() {

    $data = array();

    $mailing = new CRM_Mailing_BAO_Mailing();
    $query = "SELECT name FROM civicrm_mailing WHERE sms_provider_id IS NULL";
    $mailing->query($query);

    while ($mailing->fetch()) {
      $data[mysql_real_escape_string($mailing->name)] = $mailing->name;
    }

    return $data;
  }

  public function preProcess() {
    $this->assign('chartSupported', TRUE);
    parent::preProcess();
  }

  /**
   * manipulate the select function to query count functions.
   */
  public function select() {

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
          if (!empty($field['required']) || !empty($this->_params['fields'][$fieldName])) {

            # for statistics
            if (!empty($field['statistics'])) {
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

  public function from() {

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
        ON {$this->_aliases['civicrm_mailing_event_unsubscribe']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id AND {$this->_aliases['civicrm_mailing_event_unsubscribe']}.org_unsubscribe = 0
      LEFT JOIN civicrm_mailing_event_unsubscribe mailing_event_optout_civireport
        ON mailing_event_optout_civireport.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id AND mailing_event_optout_civireport.org_unsubscribe = 1";

    if ($this->campaignEnabled) {
      $this->_from .= "
        LEFT JOIN civicrm_campaign {$this->_aliases['civicrm_campaign']}
        ON {$this->_aliases['civicrm_campaign']}.id = {$this->_aliases['civicrm_mailing']}.campaign_id";
    }

    // need group by and order by

    //print_r($this->_from);
  }

  public function where() {
    $clauses = array();
    //to avoid the sms listings
    $clauses[] = "{$this->_aliases['civicrm_mailing']}.sms_provider_id IS NULL";

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($this->_aliases[$tableName] . '.' . $field['name'], $relative, $from, $to, $field['type']);
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

  public function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_mailing']}.id";
  }

  public function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_mailing_job']}.end_date DESC ";
  }

  public function postProcess() {

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

  /**
   * @return array
   */
  public static function getChartCriteria() {
    return array(
      'count' => array(
        'civicrm_mailing_event_delivered_delivered_count' => ts('Delivered'),
        'civicrm_mailing_event_bounce_bounce_count' => ts('Bounce'),
        'civicrm_mailing_event_opened_open_count' => ts('Total Opens'),
        'civicrm_mailing_event_opened_unique_open_count' => ts('Unique Opens'),
        'civicrm_mailing_event_trackable_url_open_click_count' => ts('Clicks'),
        'civicrm_mailing_event_unsubscribe_unsubscribe_count' => ts('Unsubscribe'),
      ),
      'rate' => array(
        'civicrm_mailing_event_delivered_accepted_rate' => ts('Accepted Rate'),
        'civicrm_mailing_event_bounce_bounce_rate' => ts('Bounce Rate'),
        'civicrm_mailing_event_opened_open_rate' => ts('Total Open Rate'),
        'civicrm_mailing_event_opened_unique_open_rate' => ts('Unique Open Rate'),
        'civicrm_mailing_event_trackable_url_open_CTR' => ts('Click through Rate'),
        'civicrm_mailing_event_trackable_url_open_CTO' => ts('Click to Open Rate'),
      ),
    );
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

    if (empty($fields['charts'])) {
      return $errors;
    }

    $criteria = self::getChartCriteria();
    $isError = TRUE;
    foreach ($fields['fields'] as $fld => $isActive) {
      if (in_array($fld, array(
        'delivered_count',
        'bounce_count',
        'open_count',
        'click_count',
        'unsubscribe_count',
        'accepted_rate',
        'bounce_rate',
        'open_rate',
        'CTR',
        'CTO',
        'unique_open_rate',
        'unique_open_count',
      ))) {
        $isError = FALSE;
      }
    }

    if ($isError) {
      $errors['_qf_default'] = ts('For Chart view, please select at least one field from %1 OR %2.', array(
          1 => implode(', ', $criteria['count']),
          2 => implode(', ', $criteria['rate']),
        ));
    }

    return $errors;
  }

  /**
   * @param $rows
   */
  public function buildChart(&$rows) {
    if (empty($rows)) {
      return;
    }

    $criteria = self::getChartCriteria();

    $chartInfo = array(
      'legend' => ts('Mail Summary'),
      'xname' => ts('Mailing'),
      'yname' => ts('Statistics'),
      'xLabelAngle' => 20,
      'tip' => array(),
    );

    $plotRate = $plotCount = TRUE;
    foreach ($rows as $row) {
      $chartInfo['values'][$row['civicrm_mailing_name']] = array();
      if ($plotCount) {
        foreach ($criteria['count'] as $criteria => $label) {
          if (isset($row[$criteria])) {
            $chartInfo['values'][$row['civicrm_mailing_name']][$label] = $row[$criteria];
            $chartInfo['tip'][$label] = "{$label} #val#";
            $plotRate = FALSE;
          }
          elseif (isset($criteria['count'][$criteria])) {
            unset($criteria['count'][$criteria]);
          }
        }
      }
      if ($plotRate) {
        foreach ($criteria['rate'] as $criteria => $label) {
          if (isset($row[$criteria])) {
            $chartInfo['values'][$row['civicrm_mailing_name']][$label] = $row[$criteria];
            $chartInfo['tip'][$label] = "{$label} #val#";
            $plotCount = FALSE;
          }
          elseif (isset($criteria['rate'][$criteria])) {
            unset($criteria['rate'][$criteria]);
          }
        }
      }
    }

    if ($plotCount) {
      $criteria = $criteria['count'];
    }
    else {
      $criteria = $criteria['rate'];
    }

    $chartInfo['criteria'] = array_values($criteria);

    // dynamically set the graph size
    $chartInfo['xSize'] = ((count($rows) * 125) + (count($rows) * count($criteria) * 40));

    // build the chart.
    CRM_Utils_OpenFlashChart::buildChart($chartInfo, $this->_params['charts']);
    $this->assign('chartType', $this->_params['charts']);
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
    foreach ($rows as $rowNum => $row) {
      // CRM-16506
      if (array_key_exists('civicrm_mailing_name', $row) &&
        array_key_exists('civicrm_mailing_id', $row)
      ) {
        $rows[$rowNum]['civicrm_mailing_name_link'] = CRM_Report_Utils_Report::getNextUrl('mailing/detail',
          'reset=1&force=1&mailing_id_op=eq&mailing_id_value=' . $row['civicrm_mailing_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_mailing_name_hover'] = ts('View Mailing details for this mailing');
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
