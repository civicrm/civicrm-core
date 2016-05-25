<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Report_Form_Mailing_Detail extends CRM_Report_Form {

  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
    'Household',
    'Organization',
  );

  protected $_exposeContactID = FALSE;

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = TRUE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = array();

    $this->_columns['civicrm_contact'] = array(
      'dao' => 'CRM_Contact_DAO_Contact',
      'fields' => array(
        'id' => array(
          'name' => 'id',
          'title' => ts('Contact ID'),
          'required' => TRUE,
          'no_display' => TRUE,
        ),
        'sort_name' => array(
          'title' => ts('Contact Name'),
          'required' => TRUE,
        ),
      ),
      'filters' => array(
        'sort_name' => array(
          'title' => ts('Contact Name'),
        ),
        'id' => array(
          'title' => ts('Contact ID'),
          'no_display' => TRUE,
        ),
      ),
      'order_bys' => array(
        'sort_name' => array(
          'title' => ts('Contact Name'),
          'default' => TRUE,
          'default_order' => 'ASC',
        ),
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
      ),
      'filters' => array(
        'mailing_id' => array(
          'name' => 'id',
          'title' => ts('Mailing'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'type' => CRM_Utils_Type::T_INT,
          'options' => CRM_Mailing_BAO_Mailing::getMailingsList(),
        ),
      ),
      'order_bys' => array(
        'mailing_name' => array(
          'name' => 'name',
          'title' => ts('Mailing'),
        ),
      ),
      'grouping' => 'mailing-fields',
    );

    // adding dao just to have alias
    $this->_columns['civicrm_mailing_event_bounce'] = array(
      'dao' => 'CRM_Mailing_Event_DAO_Bounce',
    );

    $this->_columns['civicrm_mailing_event_delivered'] = array(
      'dao' => 'CRM_Mailing_Event_DAO_Delivered',
      'fields' => array(
        'delivery_id' => array(
          'name' => 'id',
          'title' => ts('Delivery Status'),
          'default' => TRUE,
        ),
      ),
      'filters' => array(
        'delivery_status' => array(
          'name' => 'delivery_status',
          'title' => ts('Delivery Status'),
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'type' => CRM_Utils_Type::T_STRING,
          'options' => array(
            '' => 'Any',
            'successful' => 'Successful',
            'bounced' => 'Bounced',
          ),
        ),
      ),
      'grouping' => 'mailing-fields',
    );

    $this->_columns['civicrm_mailing_event_unsubscribe'] = array(
      'dao' => 'CRM_Mailing_Event_DAO_Unsubscribe',
      'fields' => array(
        'unsubscribe_id' => array(
          'name' => 'id',
          'title' => ts('Unsubscribe'),
          'default' => TRUE,
        ),
        'optout_id' => array(
          'name' => 'id',
          'title' => ts('Opt-out'),
          'default' => TRUE,
          'alias' => 'mailing_event_unsubscribe_civireport2',
        ),
      ),
      'filters' => array(
        'is_unsubscribed' => array(
          'name' => 'id',
          'title' => ts('Unsubscribed'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'options' => array(
            '' => ts('Any'),
            '0' => ts('No'),
            '1' => ts('Yes'),
          ),
          'clause' => 'mailing_event_unsubscribe_civireport.id IS NULL',
        ),
        'is_optout' => array(
          'title' => ts('Opted-out'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'options' => array(
            '' => ts('Any'),
            '0' => ts('No'),
            '1' => ts('Yes'),
          ),
          'clause' => 'mailing_event_unsubscribe_civireport2.id IS NULL',
        ),
      ),
      'grouping' => 'mailing-fields',
    );

    $this->_columns['civicrm_mailing_event_reply'] = array(
      'dao' => 'CRM_Mailing_Event_DAO_Reply',
      'fields' => array(
        'reply_id' => array(
          'name' => 'id',
          'title' => ts('Reply'),
        ),
      ),
      'filters' => array(
        'is_replied' => array(
          'name' => 'id',
          'title' => ts('Replied'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'options' => array(
            '' => ts('Any'),
            '0' => ts('No'),
            '1' => ts('Yes'),
          ),
          'clause' => 'mailing_event_reply_civireport.id IS NULL',
        ),
      ),
      'grouping' => 'mailing-fields',
    );

    $this->_columns['civicrm_mailing_event_forward'] = array(
      'dao' => 'CRM_Mailing_Event_DAO_Forward',
      'fields' => array(
        'forward_id' => array(
          'name' => 'id',
          'title' => ts('Forwarded to Email'),
        ),
      ),
      'filters' => array(
        'is_forwarded' => array(
          'name' => 'id',
          'title' => ts('Forwarded'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'options' => array(
            '' => ts('Any'),
            '0' => ts('No'),
            '1' => ts('Yes'),
          ),
          'clause' => 'mailing_event_forward_civireport.id IS NULL',
        ),
      ),
      'grouping' => 'mailing-fields',
    );

    $this->_columns['civicrm_email'] = array(
      'dao' => 'CRM_Core_DAO_Email',
      'fields' => array(
        'email' => array(
          'title' => ts('Email'),
        ),
      ),
      'grouping' => 'contact-fields',
    );

    $this->_columns['civicrm_phone'] = array(
      'dao' => 'CRM_Core_DAO_Phone',
      'fields' => array('phone' => NULL),
      'grouping' => 'contact-fields',
    );

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;

    parent::__construct();
  }

  public function select() {
    $select = $columns = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if (in_array($fieldName, array(
              'unsubscribe_id',
              'optout_id',
              'forward_id',
              'reply_id',
            ))) {
              $select[] = "IF({$field['dbAlias']} IS NULL, 'No', 'Yes') as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = CRM_Utils_Array::value('no_display', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
              unset($this->_columns[$tableName]['fields'][$fieldName]);
              $columns[$tableName][$fieldName] = $field;
            }
            elseif ($fieldName == 'delivery_id') {
              $select[] = "IF(mailing_event_bounce_civireport.id IS NOT NULL, 'Bounced', IF(mailing_event_delivered_civireport.id IS NOT NULL, 'Successful', 'Unknown')) as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = CRM_Utils_Array::value('no_display', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
              unset($this->_columns[$tableName]['fields'][$fieldName]);
              $columns[$tableName][$fieldName] = $field;
            }
          }
        }
      }
    }

    parent::select();
    if (!empty($select)) {
      $this->_select .= ', ' . implode(', ', $select) . " ";
    }

    // put the fields that were unset, back in place
    foreach ($columns as $tableName => $table) {
      foreach ($table as $fieldName => $fields) {
        $this->_columns[$tableName]['fields'][$fieldName] = $fields;
      }
    }

    // simple sort
    ksort($this->_columnHeaders);
  }

  public function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']}";

    $this->_from .= "
        INNER JOIN civicrm_mailing_event_queue
          ON civicrm_mailing_event_queue.contact_id = {$this->_aliases['civicrm_contact']}.id
        LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
          ON civicrm_mailing_event_queue.email_id = {$this->_aliases['civicrm_email']}.id";

    if (array_key_exists('delivery_id', $this->_params['fields'])) {
      $this->_from .= "
                LEFT JOIN  civicrm_mailing_event_delivered {$this->_aliases['civicrm_mailing_event_delivered']}
                    ON  {$this->_aliases['civicrm_mailing_event_delivered']}.event_queue_id = civicrm_mailing_event_queue.id
        LEFT JOIN civicrm_mailing_event_bounce {$this->_aliases['civicrm_mailing_event_bounce']}
          ON {$this->_aliases['civicrm_mailing_event_bounce']}.event_queue_id = civicrm_mailing_event_queue.id";
      if (CRM_Utils_Array::value('delivery_status_value', $this->_params) ==
        'bounced'
      ) {
        $this->_columns['civicrm_mailing_event_delivered']['filters']['delivery_status']['clause'] = "{$this->_aliases['civicrm_mailing_event_bounce']}.id IS NOT NULL";
      }
      elseif (CRM_Utils_Array::value('delivery_status_value', $this->_params) ==
        'successful'
      ) {
        $this->_columns['civicrm_mailing_event_delivered']['filters']['delivery_status']['clause'] = "{$this->_aliases['civicrm_mailing_event_delivered']}.id IS NOT NULL AND {$this->_aliases['civicrm_mailing_event_bounce']}.id IS NULL";
      }
    }
    else {
      unset($this->_columns['civicrm_mailing_event_delivered']['filters']['delivery_status']);
    }

    if (array_key_exists('reply_id', $this->_params['fields']) ||
      is_numeric(CRM_Utils_Array::value('is_replied_value', $this->_params))
    ) {
      if (CRM_Utils_Array::value('is_replied_value', $this->_params) == 1) {
        $joinType = 'INNER';
        $this->_columns['civicrm_mailing_event_reply']['filters']['is_replied']['clause'] = '(1)';
      }
      else {
        $joinType = 'LEFT';
      }
      $this->_from .= "
                {$joinType} JOIN  civicrm_mailing_event_reply {$this->_aliases['civicrm_mailing_event_reply']}
                    ON  {$this->_aliases['civicrm_mailing_event_reply']}.event_queue_id = civicrm_mailing_event_queue.id";
    }
    else {
      unset($this->_columns['civicrm_mailing_event_reply']['filters']['is_replied']);
    }

    if (array_key_exists('unsubscribe_id', $this->_params['fields']) ||
      is_numeric(CRM_Utils_Array::value('is_unsubscribed_value', $this->_params))
    ) {
      if (CRM_Utils_Array::value('is_unsubscribed_value', $this->_params) == 1
      ) {
        $joinType = 'INNER';
        $this->_columns['civicrm_mailing_event_unsubscribe']['filters']['is_unsubscribed']['clause'] = '(1)';
      }
      else {
        $joinType = 'LEFT';
      }
      $this->_from .= "
                {$joinType} JOIN  civicrm_mailing_event_unsubscribe {$this->_aliases['civicrm_mailing_event_unsubscribe']}
                    ON  {$this->_aliases['civicrm_mailing_event_unsubscribe']}.event_queue_id = civicrm_mailing_event_queue.id
                        AND {$this->_aliases['civicrm_mailing_event_unsubscribe']}.org_unsubscribe = 0";
    }
    else {
      unset($this->_columns['civicrm_mailing_event_unsubscribe']['filters']['is_unsubscribed']);
    }

    if (array_key_exists('optout_id', $this->_params['fields']) ||
      is_numeric(CRM_Utils_Array::value('is_optout_value', $this->_params))
    ) {
      if (CRM_Utils_Array::value('is_optout_value', $this->_params) == 1) {
        $joinType = 'INNER';
        $this->_columns['civicrm_mailing_event_unsubscribe']['filters']['is_optout']['clause'] = '(1)';
      }
      else {
        $joinType = 'LEFT';
      }
      $this->_from .= "
                {$joinType} JOIN  civicrm_mailing_event_unsubscribe {$this->_aliases['civicrm_mailing_event_unsubscribe']}2
                    ON  {$this->_aliases['civicrm_mailing_event_unsubscribe']}2.event_queue_id = civicrm_mailing_event_queue.id
                        AND {$this->_aliases['civicrm_mailing_event_unsubscribe']}2.org_unsubscribe = 1";
    }
    else {
      unset($this->_columns['civicrm_mailing_event_unsubscribe']['filters']['is_optout']);
    }

    if (array_key_exists('forward_id', $this->_params['fields']) ||
      is_numeric(CRM_Utils_Array::value('is_forwarded_value', $this->_params))
    ) {
      if (CRM_Utils_Array::value('is_forwarded_value', $this->_params) == 1) {
        $joinType = 'INNER';
        $this->_columns['civicrm_mailing_event_forward']['filters']['is_forwarded']['clause'] = '(1)';
      }
      else {
        $joinType = 'LEFT';
      }
      $this->_from .= "
                {$joinType} JOIN  civicrm_mailing_event_forward {$this->_aliases['civicrm_mailing_event_forward']}
                    ON  {$this->_aliases['civicrm_mailing_event_forward']}.event_queue_id = civicrm_mailing_event_queue.id";
    }
    else {
      unset($this->_columns['civicrm_mailing_event_forward']['filters']['is_forwarded']);
    }

    $this->_from .= "
        INNER JOIN civicrm_mailing_job
          ON civicrm_mailing_event_queue.job_id = civicrm_mailing_job.id
        INNER JOIN civicrm_mailing {$this->_aliases['civicrm_mailing']}
          ON civicrm_mailing_job.mailing_id = {$this->_aliases['civicrm_mailing']}.id
          AND civicrm_mailing_job.is_test = 0";

    if ($this->_phoneField) {
      $this->_from .= "
            LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                      {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
    }
  }

  public function where() {
    parent::where();
    $this->_where .= " AND {$this->_aliases['civicrm_mailing']}.sms_provider_id IS NULL";
  }

  /**
   * @return array
   */
  public function mailingList() {

    $data = array();
    $mailing = new CRM_Mailing_BAO_Mailing();
    $query = "SELECT name FROM civicrm_mailing ";
    $mailing->query($query);

    while ($mailing->fetch()) {
      $data[CRM_Core_DAO::escapeString($mailing->name)] = $mailing->name;
    }

    return $data;
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
