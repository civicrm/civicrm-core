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
class CRM_Report_Form_Mailing_Opened extends CRM_Report_Form_Mailing_Base {

  protected $_summary = NULL;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->optimisedForOnlyFullGroupBy = FALSE;
    $this->_columns = [];

    $this->_columns['civicrm_contact'] = $this->getContactColumns();

    $this->_columns['civicrm_mailing'] = $this->getMailingColumns();

    $this->_columns['civicrm_email'] = [
      'dao' => 'CRM_Core_DAO_Email',
      'fields' => [
        'email' => [
          'title' => ts('Email'),
          'no_repeat' => TRUE,
        ],
      ],
      'order_bys' => [
        'email' => ['title' => ts('Email'), 'default_order' => 'ASC'],
      ],
      'grouping' => 'contact-fields',
    ];

    $this->_columns['civicrm_phone'] = [
      'dao' => 'CRM_Core_DAO_Phone',
      'fields' => ['phone' => NULL],
      'grouping' => 'contact-fields',
    ];

    $this->_columns['civicrm_mailing_event_opened'] = [
      'dao' => 'CRM_Mailing_Event_DAO_MailingEventOpened',
      'fields' => [
        'id' => [
          'required' => TRUE,
          'no_display' => TRUE,
          'dbAlias' => CRM_Utils_SQL::supportsFullGroupBy() ? 'ANY_VALUE(mailing_event_opened_civireport.id)' : NULL,
        ],
        'time_stamp' => [
          'title' => ts('Open Date'),
          'default' => TRUE,
        ],
      ],
      'filters' => [
        'time_stamp' => [
          'title' => ts('Open Date'),
          'operatorType' => CRM_Report_Form::OP_DATE,
          'type' => CRM_Utils_Type::T_DATE,
        ],
        'unique_opens' => [
          'title' => ts('Unique Opens'),
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'pseudofield' => TRUE,
        ],
      ],
      'order_bys' => [
        'time_stamp' => [
          'title' => ts('Open Date'),
        ],
      ],
      'grouping' => 'mailing-fields',
    ];

    // Add charts support
    $this->_charts = [
      '' => ts('Tabular'),
      'barChart' => ts('Bar Chart'),
      'pieChart' => ts('Pie Chart'),
    ];

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  public function preProcess() {
    $this->assign('chartSupported', TRUE);
    parent::preProcess();
  }

  public function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
          }
        }
      }
    }

    if (!empty($this->_params['charts'])) {
      $select[] = "COUNT({$this->_aliases['civicrm_mailing_event_opened']}.id) as civicrm_mailing_opened_count";
      $this->_columnHeaders["civicrm_mailing_opened_count"]['title'] = ts('Opened Count');
    }

    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param self $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = [];
    return $errors;
  }

  public function from() {
    $this->_from = "
      FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}";

    $this->_from .= "
      INNER JOIN civicrm_mailing_event_queue
        ON civicrm_mailing_event_queue.contact_id = {$this->_aliases['civicrm_contact']}.id
      LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
        ON civicrm_mailing_event_queue.email_id = {$this->_aliases['civicrm_email']}.id
      INNER JOIN civicrm_mailing_event_opened {$this->_aliases['civicrm_mailing_event_opened']}
        ON {$this->_aliases['civicrm_mailing_event_opened']}.event_queue_id = civicrm_mailing_event_queue.id
      INNER JOIN civicrm_mailing_job
        ON civicrm_mailing_event_queue.job_id = civicrm_mailing_job.id
      INNER JOIN civicrm_mailing {$this->_aliases['civicrm_mailing']}
        ON civicrm_mailing_job.mailing_id = {$this->_aliases['civicrm_mailing']}.id
        AND civicrm_mailing_job.is_test = 0
    ";
    $this->joinPhoneFromContact();
  }

  public function groupBy() {
    $groupBys = [];
    // Do not use group by clause if distinct = 0 mentioned in url params. flag is used in mailing report screen, default value is TRUE
    // this report is used to show total opened and unique opened
    if (CRM_Utils_Request::retrieve('distinct', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, TRUE)) {
      $groupBys = empty($this->_params['charts']) ? ["civicrm_mailing_event_queue.email_id"] : ["{$this->_aliases['civicrm_mailing']}.id"];
      if (!empty($this->_params['unique_opens_value'])) {
        $groupBys[] = "civicrm_mailing_event_queue.id";
      }
    }
    if (!empty($groupBys)) {
      $this->_groupBy = "GROUP BY " . implode(', ', $groupBys);
    }
  }

  public function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);

    $sql = $this->buildQuery(TRUE);

    $rows = $graphRows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  /**
   * @param $rows
   */
  public function buildChart(&$rows) {
    if (empty($rows)) {
      return;
    }

    $chartInfo = [
      'legend' => ts('Mail Opened Report'),
      'xname' => ts('Mailing'),
      'yname' => ts('Opened'),
      'xLabelAngle' => 20,
      'tip' => ts('Mail Opened: %1', [1 => '#val#']),
    ];
    foreach ($rows as $row) {
      $chartInfo['values'][$row['civicrm_mailing_mailing_name_alias']] = $row['civicrm_mailing_opened_count'];
    }

    // build the chart.
    CRM_Utils_Chart::buildChart($chartInfo, $this->_params['charts']);
    $this->assign('chartType', $this->_params['charts']);
  }

}
