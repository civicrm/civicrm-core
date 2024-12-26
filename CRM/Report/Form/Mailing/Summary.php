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
class CRM_Report_Form_Mailing_Summary extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupExtends = [];

  protected $_add2groupSupported = FALSE;

  public $_drilldownReport = ['mailing/detail' => 'Link to Detail Report'];

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = [];

    $this->_columns['civicrm_mailing'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'id' => [
          'name' => 'id',
          'title' => ts('Mailing ID'),
          'required' => TRUE,
          'no_display' => TRUE,
        ],
        'name' => [
          'title' => ts('Mailing Name'),
          'required' => TRUE,
        ],
        'created_date' => [
          'title' => ts('Date Created'),
        ],
        'subject' => [
          'title' => ts('Subject'),
        ],
        'from_name' => [
          'title' => ts('Sender Name'),
        ],
        'from_email' => [
          'title' => ts('Sender Email'),
        ],
      ],
      'filters' => [
        'is_completed' => [
          'title' => ts('Mailing Status'),
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'type' => CRM_Utils_Type::T_INT,
          'options' => [
            0 => 'Incomplete',
            1 => 'Complete',
          ],
          //'operator' => 'like',
          'default' => 1,
        ],
        'mailing_id' => [
          'name' => 'id',
          'title' => ts('Mailing Name'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'type' => CRM_Utils_Type::T_INT,
          'options' => CRM_Mailing_BAO_Mailing::getMailingsList(),
          'operator' => 'like',
        ],
        'mailing_subject' => [
          'name' => 'subject',
          'title' => ts('Mailing Subject'),
          'type' => CRM_Utils_Type::T_STRING,
          'operator' => 'like',
        ],
        'is_archived' => [
          'name' => 'is_archived',
          'title' => ts('Is archived?'),
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ],
      ],
      'order_bys' => [
        'mailing_name' => [
          'name' => 'name',
          'title' => ts('Mailing Name'),
        ],
        'mailing_subject' => [
          'name' => 'subject',
          'title' => ts('Mailing Subject'),
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_job'] = [
      'dao' => 'CRM_Mailing_DAO_MailingJob',
      'fields' => [
        'start_date' => [
          'title' => ts('Start Date'),
          'dbAlias' => 'MIN(mailing_job_civireport.start_date)',
        ],
        'end_date' => [
          'title' => ts('End Date'),
          'dbAlias' => 'MAX(mailing_job_civireport.end_date)',
        ],
      ],
      'filters' => [
        'status' => [
          'type' => CRM_Utils_Type::T_STRING,
          'default' => 'Complete',
          'no_display' => TRUE,
        ],
        'is_test' => [
          'type' => CRM_Utils_Type::T_INT,
          'default' => 0,
          'no_display' => TRUE,
        ],
        'start_date' => [
          'title' => ts('Start Date'),
          'default' => 'this.year',
          'operatorType' => CRM_Report_Form::OP_DATE,
          'type' => CRM_Utils_Type::T_DATE,
        ],
        'end_date' => [
          'title' => ts('End Date'),
          'default' => 'this.year',
          'operatorType' => CRM_Report_Form::OP_DATE,
          'type' => CRM_Utils_Type::T_DATE,
        ],
      ],
      'order_bys' => [
        'start_date' => [
          'title' => ts('Start Date'),
          'dbAlias' => 'MIN(mailing_job_civireport.start_date)',
        ],
        'end_date' => [
          'title' => ts('End Date'),
          'default_weight' => '1',
          'default_order' => 'DESC',
          'dbAlias' => 'MAX(mailing_job_civireport.end_date)',
        ],
      ],
      'grouping' => 'mailing-fields',
    ];

    $this->_columns['civicrm_mailing_event_queue'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'queue_count' => [
          'name' => 'id',
          'title' => ts('Intended Recipients'),
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_delivered'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'delivered_count' => [
          'name' => 'event_queue_id',
          'title' => ts('Delivered'),
        ],
        'accepted_rate' => [
          'title' => ts('Accepted Rate'),
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_delivered.delivered_count',
            'base' => 'civicrm_mailing_event_queue.queue_count',
          ],
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_bounce'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'bounce_count' => [
          'name' => 'event_queue_id',
          'title' => ts('Bounce'),
        ],
        'bounce_rate' => [
          'title' => ts('Bounce Rate'),
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_bounce.bounce_count',
            'base' => 'civicrm_mailing_event_queue.queue_count',
          ],
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_opened'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'unique_open_count' => [
          'name' => 'id',
          'alias' => 'mailing_event_opened_civireport',
          'dbAlias' => 'mailing_event_opened_civireport.event_queue_id',
          'title' => ts('Unique Opens'),
        ],
        'unique_open_rate' => [
          'title' => ts('Unique Open Rate'),
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_opened.unique_open_count',
            'base' => 'civicrm_mailing_event_delivered.delivered_count',
          ],
        ],
        'open_count' => [
          'name' => 'event_queue_id',
          'title' => ts('Total Opens'),
        ],
        'open_rate' => [
          'title' => ts('Total Open Rate'),
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_opened.open_count',
            'base' => 'civicrm_mailing_event_delivered.delivered_count',
          ],
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_trackable_url_open'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'click_count' => [
          'name' => 'event_queue_id',
          'title' => ts('Unique Clicks'),
        ],
        'CTR' => [
          'title' => ts('Click through Rate'),
          'default' => 0,
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_trackable_url_open.click_count',
            'base' => 'civicrm_mailing_event_delivered.delivered_count',
          ],
        ],
        'CTO' => [
          'title' => ts('Click to Open Rate'),
          'default' => 0,
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_trackable_url_open.click_count',
            'base' => 'civicrm_mailing_event_opened.open_count',
          ],
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_unsubscribe'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'unsubscribe_count' => [
          'name' => 'id',
          'title' => ts('Unsubscribe'),
          'alias' => 'mailing_event_unsubscribe_civireport',
          'dbAlias' => 'mailing_event_unsubscribe_civireport.event_queue_id',
        ],
        'optout_count' => [
          'name' => 'id',
          'title' => ts('Opt-outs'),
          'alias' => 'mailing_event_optout_civireport',
          'dbAlias' => 'mailing_event_optout_civireport.event_queue_id',
        ],
      ],
    ];
    $this->_columns['civicrm_mailing_group'] = [
      'dao' => 'CRM_Mailing_DAO_MailingGroup',
      'filters' => [
        'entity_id' => [
          'title' => ts('Groups Included in Mailing'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'type' => CRM_Utils_Type::T_INT,
          'options' => CRM_Core_PseudoConstant::group(),
        ],
      ],
    ];
    // If we have campaigns enabled, add those elements to both the fields, filters.
    $this->addCampaignFields('civicrm_mailing');

    // Add charts support
    $this->_charts = [
      '' => ts('Tabular'),
      'barChart' => ts('Bar Chart'),
    ];

    parent::__construct();
  }

  public function preProcess() {
    $this->assign('chartSupported', TRUE);
    parent::preProcess();
  }

  /**
   * manipulate the select function to query count functions.
   */
  public function select() {

    $count_tables = [
      'civicrm_mailing_event_queue',
      'civicrm_mailing_event_delivered',
      'civicrm_mailing_event_opened',
      'civicrm_mailing_event_bounce',
      'civicrm_mailing_event_trackable_url_open',
      'civicrm_mailing_event_unsubscribe',
    ];

    // Define a list of columns that should be counted with the DISTINCT
    // keyword. For example, civicrm_mailing_event_opened.unique_open_count
    // should display the number of unique records, whereas something like
    // civicrm_mailing_event_opened.open_count should display the total number.
    // Each string here is in the form $tableName.$fieldName, where $tableName
    // is the key in $this->_columns, and $fieldName is the key in that array's
    // ['fields'] array.
    // Reference: CRM-20660
    $distinctCountColumns = [
      'civicrm_mailing_event_queue.queue_count',
      'civicrm_mailing_event_delivered.delivered_count',
      'civicrm_mailing_event_bounce.bounce_count',
      'civicrm_mailing_event_opened.unique_open_count',
      'civicrm_mailing_event_trackable_url_open.click_count',
      'civicrm_mailing_event_unsubscribe.unsubscribe_count',
      'civicrm_mailing_event_unsubscribe.optout_count',
    ];

    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['pseudofield'])) {
            continue;
          }
          if (!empty($field['required']) || !empty($this->_params['fields'][$fieldName])) {
            // For statistics
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
                // Use the DISTINCT keyword appropriately, based on the contents
                // of $distinct_count_columns.
                $distinct = '';
                if (in_array("{$tableName}.{$fieldName}", $distinctCountColumns)) {
                  $distinct = 'DISTINCT';
                }
                $select[] = "count($distinct {$field['dbAlias']}) as {$tableName}_{$fieldName}";
              }
              else {
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              }
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }

    $this->_selectClauses = $select;
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

    if ($this->isTableSelected('civicrm_mailing_group')) {
      $this->_from .= "
        LEFT JOIN civicrm_mailing_group {$this->_aliases['civicrm_mailing_group']}
    ON {$this->_aliases['civicrm_mailing_group']}.mailing_id = {$this->_aliases['civicrm_mailing']}.id";
    }
    // need group by and order by

    //print_r($this->_from);
  }

  public function where() {
    $clauses = [];
    //to avoid the sms listings
    $clauses[] = "{$this->_aliases['civicrm_mailing']}.sms_provider_id IS NULL";

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (($field['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($this->_aliases[$tableName] . '.' . $field['name'], $relative, $from, $to, $field['type']);
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
  }

  public function groupBy() {
    $groupBy = [
      "{$this->_aliases['civicrm_mailing']}.id",
    ];
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
  }

  public function orderBy() {
    parent::orderBy();
    CRM_Contact_BAO_Query::getGroupByFromOrderBy($this->_groupBy, $this->_orderByArray);
  }

  public function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause(CRM_Utils_Array::value('civicrm_contact', $this->_aliases));

    $sql = $this->buildQuery(TRUE);

    // print_r($sql);

    $rows = $graphRows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  /**
   * @return array
   */
  public static function getChartCriteria() {
    return [
      'count' => [
        'civicrm_mailing_event_delivered_delivered_count' => ts('Delivered'),
        'civicrm_mailing_event_bounce_bounce_count' => ts('Bounce'),
        'civicrm_mailing_event_opened_open_count' => ts('Total Opens'),
        'civicrm_mailing_event_opened_unique_open_count' => ts('Unique Opens'),
        'civicrm_mailing_event_trackable_url_open_click_count' => ts('Unique Clicks'),
        'civicrm_mailing_event_unsubscribe_unsubscribe_count' => ts('Unsubscribe'),
      ],
      'rate' => [
        'civicrm_mailing_event_delivered_accepted_rate' => ts('Accepted Rate'),
        'civicrm_mailing_event_bounce_bounce_rate' => ts('Bounce Rate'),
        'civicrm_mailing_event_opened_open_rate' => ts('Total Open Rate'),
        'civicrm_mailing_event_opened_unique_open_rate' => ts('Unique Open Rate'),
        'civicrm_mailing_event_trackable_url_open_CTR' => ts('Click through Rate'),
        'civicrm_mailing_event_trackable_url_open_CTO' => ts('Click to Open Rate'),
      ],
    ];
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

    if (empty($fields['charts'])) {
      return $errors;
    }

    $criteria = self::getChartCriteria();
    $isError = TRUE;
    foreach ($fields['fields'] as $fld => $isActive) {
      if (in_array($fld, [
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
      ])) {
        $isError = FALSE;
      }
    }

    if ($isError) {
      $errors['_qf_default'] = ts('For Chart view, please select at least one field from %1 OR %2.', [
        1 => implode(', ', $criteria['count']),
        2 => implode(', ', $criteria['rate']),
      ]);
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

    $chartInfo = [
      'legend' => ts('Mail Summary'),
      'xname' => ts('Mailing'),
      'yname' => ts('Statistics'),
      'xLabelAngle' => 20,
      'tip' => [],
    ];

    $plotRate = $plotCount = TRUE;
    foreach ($rows as $row) {
      $chartInfo['values'][$row['civicrm_mailing_name']] = [];
      if ($plotCount) {
        foreach ($criteria['count'] as $criteriaName => $label) {
          if (isset($row[$criteriaName])) {
            $chartInfo['values'][$row['civicrm_mailing_name']][$label] = $row[$criteriaName];
            $chartInfo['tip'][$label] = "{$label} #val#";
            $plotRate = FALSE;
          }
          elseif (isset($criteria['count'][$criteriaName])) {
            unset($criteria['count'][$criteriaName]);
          }
        }
      }
      if ($plotRate) {
        foreach ($criteria['rate'] as $criteriaName => $label) {
          if (isset($row[$criteria])) {
            $chartInfo['values'][$row['civicrm_mailing_name']][$label] = $row[$criteriaName];
            $chartInfo['tip'][$label] = "{$label} #val#";
            $plotCount = FALSE;
          }
          elseif (isset($criteria['rate'][$criteriaName])) {
            unset($criteria['rate'][$criteriaName]);
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
    CRM_Utils_Chart::buildChart($chartInfo, $this->_params['charts']);
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
      if (array_key_exists('civicrm_mailing_id', $row)) {
        if (array_key_exists('civicrm_mailing_name', $row)) {
          $rows[$rowNum]['civicrm_mailing_name_link'] = CRM_Report_Utils_Report::getNextUrl('mailing/detail',
            'reset=1&force=1&mailing_id_op=eq&mailing_id_value=' . $row['civicrm_mailing_id'],
            $this->_absoluteUrl, $this->_id, $this->_drilldownReport
          );
          $rows[$rowNum]['civicrm_mailing_name_hover'] = ts('View Mailing details for this mailing');
          $entryFound = TRUE;
        }
        if (array_key_exists('civicrm_mailing_event_opened_open_count', $row)) {
          $rows[$rowNum]['civicrm_mailing_event_opened_open_count'] = CRM_Mailing_Event_BAO_MailingEventOpened::getTotalCount($row['civicrm_mailing_id']);
          $entryFound = TRUE;
        }
      }
      // convert campaign_id to campaign title
      if (array_key_exists('civicrm_mailing_campaign_id', $row)) {
        if ($value = $row['civicrm_mailing_campaign_id']) {
          $rows[$rowNum]['civicrm_mailing_campaign_id'] = $this->campaigns[$value];
          $entryFound = TRUE;
        }
      }
      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

}
