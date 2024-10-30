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
class CRM_Report_Form_Mailing_Opened extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Household',
    'Organization',
  ];

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * @var bool
   * @see https://issues.civicrm.org/jira/browse/CRM-19170
   */
  protected $groupFilterNotOptimised = TRUE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->optimisedForOnlyFullGroupBy = FALSE;
    $this->_columns = [];

    $this->_columns['civicrm_contact'] = [
      'dao' => 'CRM_Contact_DAO_Contact',
      'fields' => [
        'id' => [
          'title' => ts('Contact ID'),
          'required' => TRUE,
        ],
        'sort_name' => [
          'title' => ts('Contact Name'),
          'required' => TRUE,
        ],
      ],
      'filters' => [
        'sort_name' => [
          'title' => ts('Contact Name'),
        ],
        'source' => [
          'title' => ts('Contact Source'),
          'type' => CRM_Utils_Type::T_STRING,
        ],
        'id' => [
          'title' => ts('Contact ID'),
          'no_display' => TRUE,
        ],
      ],
      'order_bys' => [
        'sort_name' => [
          'title' => ts('Contact Name'),
          'default' => TRUE,
          'default_order' => 'ASC',
        ],
      ],
      'grouping' => 'contact-fields',
    ];

    $this->_columns['civicrm_mailing'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'mailing_name' => [
          'name' => 'name',
          'title' => ts('Mailing Name'),
          'default' => TRUE,
        ],
        'mailing_name_alias' => [
          'name' => 'name',
          'required' => TRUE,
          'no_display' => TRUE,
        ],
        'mailing_subject' => [
          'name' => 'subject',
          'title' => ts('Mailing Subject'),
          'default' => TRUE,
        ],
      ],
      'filters' => [
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
      'grouping' => 'mailing-fields',
    ];

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

  public function where() {
    parent::where();
    $this->_where .= " AND {$this->_aliases['civicrm_mailing']}.sms_provider_id IS NULL";
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

      // If the email address has been deleted
      if (array_key_exists('civicrm_email_email', $row)) {
        if (empty($rows[$rowNum]['civicrm_email_email'])) {
          $rows[$rowNum]['civicrm_email_email'] = '<del>Email address deleted</del>';
        }
        $entryFound = TRUE;
      }

      // make count columns point to detail report
      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact details for this contact.");
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
