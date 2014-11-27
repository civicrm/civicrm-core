<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Report_Form_Mailing_Clicks extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_customGroupExtends = array('Contact', 'Individual', 'Household', 'Organization');

  protected $_charts = array(
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  );

  /**
   *
   */
  /**
   *
   */
  function __construct() {
    $this->_columns = array();

    $this->_columns['civicrm_contact'] = array(
      'dao' => 'CRM_Contact_DAO_Contact',
      'fields' => array(
        'id' => array(
          'title' => ts('Contact ID'),
          'required' => TRUE,
        ),
        'sort_name' =>
        array(
          'title' => ts('Contact Name'),
          'required' => TRUE,
        ),
      ),
      'filters' => array(
        'sort_name' => array(
          'title' => ts('Contact Name'),
        ),
        'source' => array(
          'title' => ts('Contact Source'),
          'type' => CRM_Utils_Type::T_STRING,
        ),
        'id' => array(
          'title' => ts('Contact ID'),
          'no_display' => TRUE,
        ),
      ),
      'order_bys' =>
      array(
        'sort_name' =>
        array('title' => ts('Contact Name'), 'default' => TRUE, 'default_order' => 'ASC'),
      ),
      'grouping' => 'contact-fields',
    );

    $this->_columns['civicrm_mailing'] = array(
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => array(
        'mailing_name' => array(
          'name' => 'name',
          'title' => ts('Mailing'),
          'default' => TRUE,
        ),
        'mailing_name_alias' => array(
          'name' => 'name',
          'required' => TRUE,
          'no_display' => TRUE,
        ),
      ),
      'filters' => array(
        'mailing_id' => array(
          'name' => 'id',
          'title' => ts('Mailing'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'type' => CRM_Utils_Type::T_INT,
          'options' => CRM_Mailing_BAO_Mailing::getMailingsList(),
          'operator' => 'like',
        ),
      ),
      'order_bys' =>
      array(
        'mailing_name' =>
        array(
          'name' => 'name',
          'title' => ts('Mailing'),
        ),
      ),
      'grouping' => 'mailing-fields',
    );

    $this->_columns['civicrm_email'] = array(
      'dao' => 'CRM_Core_DAO_Email',
      'fields' => array(
        'email' => array(
          'title' => ts('Email'),
          'no_repeat' => TRUE,
          'required' => TRUE,
        ),
      ),
      'grouping' => 'contact-fields',
    );

    $this->_columns['civicrm_phone'] = array(
      'dao' => 'CRM_Core_DAO_Phone',
      'fields' => array('phone' => NULL),
      'grouping' => 'contact-fields',
    );

    $this->_columns['civicrm_mailing_trackable_url'] = array(
      'dao' => 'CRM_Mailing_DAO_TrackableURL',
      'fields' => array(
        'url' => array(
          'title' => ts('Click through URL'),
        ),
      ),
      'order_bys' =>
      array(
        'url' =>
        array('title' => ts('Click through URL')),
      ),
      'grouping' => 'mailing-fields',
    );

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    $this->assign('chartSupported', TRUE);
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) || !empty($this->_params['fields'][$fieldName])) {
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = CRM_Utils_Array::value('no_display', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
          }
        }
      }
    }

    if (!empty($this->_params['charts'])) {
      $select[] = "COUNT(civicrm_mailing_event_trackable_url_open.id) as civicrm_mailing_click_count";
      $this->_columnHeaders["civicrm_mailing_click_count"]['title'] = ts('Click Count');
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    return $errors;
  }

  function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}";

    $this->_from .= "
        INNER JOIN civicrm_mailing_event_queue
          ON civicrm_mailing_event_queue.contact_id = {$this->_aliases['civicrm_contact']}.id
        INNER JOIN civicrm_email {$this->_aliases['civicrm_email']}
          ON civicrm_mailing_event_queue.email_id = {$this->_aliases['civicrm_email']}.id
        INNER JOIN civicrm_mailing_event_trackable_url_open
          ON civicrm_mailing_event_trackable_url_open.event_queue_id = civicrm_mailing_event_queue.id
        INNER JOIN civicrm_mailing_trackable_url {$this->_aliases['civicrm_mailing_trackable_url']}
          ON civicrm_mailing_event_trackable_url_open.trackable_url_id = {$this->_aliases['civicrm_mailing_trackable_url']}.id
        INNER JOIN civicrm_mailing_job
          ON civicrm_mailing_event_queue.job_id = civicrm_mailing_job.id
        INNER JOIN civicrm_mailing {$this->_aliases['civicrm_mailing']}
          ON civicrm_mailing_job.mailing_id = {$this->_aliases['civicrm_mailing']}.id
          AND civicrm_mailing_job.is_test = 0
      ";
    if ($this->_phoneField) {
      $this->_from .= "
            LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                      {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
    }
  }

  function where() {
    parent::where();
    $this->_where .= " AND {$this->_aliases['civicrm_mailing']}.sms_provider_id IS NULL";
  }

  function groupBy() {

    $this->_groupBy = '';
    if (!empty($this->_params['charts'])) {
      $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_mailing']}.id";
    }
    else {
      $this->_groupBy = " GROUP BY civicrm_mailing_event_trackable_url_open.id";
    }
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);

    $sql = $this->buildQuery(TRUE);

    $rows = $graphRows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  /**
   * @param $rows
   */
  function buildChart(&$rows) {
    if (empty($rows)) {
      return;
    }

    $chartInfo = array('legend' => ts('Mail Clickthrough Report'),
      'xname' => ts('Mailing'),
      'yname' => ts('Clicks'),
      'xLabelAngle' => 20,
      'tip' => ts('Clicks: %1', array(1 => '#val#')),
    );
    foreach ($rows as $row) {
      $chartInfo['values'][$row['civicrm_mailing_mailing_name_alias']] = $row['civicrm_mailing_click_count'];
    }

    // build the chart.
    CRM_Utils_OpenFlashChart::buildChart($chartInfo, $this->_params['charts']);
    $this->assign('chartType', $this->_params['charts']);
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
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

