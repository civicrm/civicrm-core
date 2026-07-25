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
class CRM_Report_Form_Mailing_Clicks extends CRM_Report_Form_Mailing_Base {

  protected $_summary = NULL;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {
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
      'grouping' => 'contact-fields',
    ];

    $this->_columns['civicrm_phone'] = [
      'dao' => 'CRM_Core_DAO_Phone',
      'fields' => ['phone' => NULL],
      'grouping' => 'contact-fields',
    ];

    $this->_columns['civicrm_mailing_trackable_url'] = [
      'dao' => 'CRM_Mailing_DAO_MailingTrackableURL',
      'fields' => [
        'url' => [
          'title' => ts('Click through URL'),
        ],
      ],
      // To do this filter should really be like mailing id filter a multi select, However
      // Not clear on how to make filter dependant on selected mailings at this stage so have set a
      // text filter which works for now
      'filters' => [
        'url' => [
          'title' => ts('URL'),
          'type' => CRM_Utils_Type::T_STRING,
          'operator' => 'like',
        ],
      ],
      'order_bys' => [
        'url' => ['title' => ts('Click through URL')],
      ],
      'grouping' => 'mailing-fields',
    ];

    $this->_columns['civicrm_mailing_event_trackable_url_open'] = [
      'dao' => 'CRM_Mailing_Event_DAO_MailingEventTrackableURLOpen',
      'fields' => [
        'time_stamp' => [
          'title' => ts('Click Date'),
          'default' => TRUE,
        ],
      ],
      'filters' => [
        'time_stamp' => [
          'title' => ts('Click Date'),
          'operatorType' => CRM_Report_Form::OP_DATE,
          'type' => CRM_Utils_Type::T_DATE,
        ],
      ],
      'order_bys' => [
        'time_stamp' => [
          'title' => ts('Click Date'),
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
      $select[] = "COUNT({$this->_aliases['civicrm_mailing_event_trackable_url_open']}.id) as civicrm_mailing_click_count";
      $this->_columnHeaders["civicrm_mailing_click_count"]['title'] = ts('Click Count');
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
      INNER JOIN civicrm_mailing_event_trackable_url_open {$this->_aliases['civicrm_mailing_event_trackable_url_open']}
        ON {$this->_aliases['civicrm_mailing_event_trackable_url_open']}.event_queue_id = civicrm_mailing_event_queue.id
      INNER JOIN civicrm_mailing_trackable_url {$this->_aliases['civicrm_mailing_trackable_url']}
        ON {$this->_aliases['civicrm_mailing_event_trackable_url_open']}.trackable_url_id = {$this->_aliases['civicrm_mailing_trackable_url']}.id
      INNER JOIN civicrm_mailing_job
        ON civicrm_mailing_event_queue.job_id = civicrm_mailing_job.id
      INNER JOIN civicrm_mailing {$this->_aliases['civicrm_mailing']}
        ON civicrm_mailing_job.mailing_id = {$this->_aliases['civicrm_mailing']}.id
        AND civicrm_mailing_job.is_test = 0
    ";
    $this->joinPhoneFromContact();
  }

  public function groupBy() {
    if (!empty($this->_params['charts'])) {
      $groupBy = "{$this->_aliases['civicrm_mailing']}.id";
    }
    else {
      $groupBy = "{$this->_aliases['civicrm_mailing_event_trackable_url_open']}.id";
    }
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
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
      'legend' => ts('Mail Click-Through Report'),
      'xname' => ts('Mailing'),
      'yname' => ts('Clicks'),
      'xLabelAngle' => 20,
      'tip' => ts('Clicks: %1', [1 => '#val#']),
    ];
    foreach ($rows as $row) {
      $chartInfo['values'][$row['civicrm_mailing_mailing_name_alias']] = $row['civicrm_mailing_click_count'];
    }

    // build the chart.
    CRM_Utils_Chart::buildChart($chartInfo, $this->_params['charts']);
    $this->assign('chartType', $this->_params['charts']);
  }

}
