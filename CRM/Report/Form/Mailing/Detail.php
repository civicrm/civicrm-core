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
class CRM_Report_Form_Mailing_Detail extends CRM_Report_Form {

  protected $_customGroupExtends = [
    'Contact',
    'Individual',
    'Household',
    'Organization',
  ];

  protected $_exposeContactID = FALSE;

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
    $this->_columns = [];

    $this->_columns['civicrm_contact'] = [
      'dao' => 'CRM_Contact_DAO_Contact',
      'fields' => [
        'id' => [
          'name' => 'id',
          'title' => ts('Contact ID'),
          'required' => TRUE,
          'no_display' => TRUE,
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

    // adding dao just to have alias
    $this->_columns['civicrm_mailing_event_bounce'] = [
      'dao' => 'CRM_Mailing_Event_DAO_MailingEventBounce',
    ];

    $this->_columns['civicrm_mailing_event_delivered'] = [
      'dao' => 'CRM_Mailing_Event_DAO_MailingEventDelivered',
      'fields' => [
        'delivery_id' => [
          'name' => 'id',
          'title' => ts('Delivery Status'),
          'default' => TRUE,
        ],
        'time_stamp' => [
          'title' => ts('Delivery Date'),
          'default' => TRUE,
        ],
      ],
      'filters' => [
        'delivery_status' => [
          'name' => 'delivery_status',
          'title' => ts('Delivery Status'),
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'type' => CRM_Utils_Type::T_STRING,
          'options' => [
            '' => 'Any',
            'successful' => 'Successful',
            'bounced' => 'Bounced',
          ],
        ],
        'time_stamp' => [
          'name' => 'time_stamp',
          'title' => ts('Delivery Date'),
          'operatorType' => CRM_Report_Form::OP_DATE,
          'type' => CRM_Utils_Type::T_DATE,
        ],
      ],
      'grouping' => 'mailing-fields',
    ];

    $this->_columns['civicrm_mailing_event_unsubscribe'] = [
      'dao' => 'CRM_Mailing_Event_DAO_MailingEventUnsubscribe',
      'fields' => [
        'unsubscribe_id' => [
          'name' => 'id',
          'title' => ts('Unsubscribe'),
          'default' => TRUE,
        ],
        'optout_id' => [
          'name' => 'id',
          'title' => ts('Opt-out'),
          'default' => TRUE,
          'alias' => 'mailing_event_unsubscribe_civireport2',
        ],
      ],
      'filters' => [
        'is_unsubscribed' => [
          'name' => 'id',
          'title' => ts('Unsubscribed'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'options' => [
            '' => ts('Any'),
            '0' => ts('No'),
            '1' => ts('Yes'),
          ],
          'clause' => 'mailing_event_unsubscribe_civireport.id IS NULL',
        ],
        'is_optout' => [
          'title' => ts('Opted-out'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'options' => [
            '' => ts('Any'),
            '0' => ts('No'),
            '1' => ts('Yes'),
          ],
          'clause' => 'mailing_event_unsubscribe_civireport2.id IS NULL',
        ],
      ],
      'grouping' => 'mailing-fields',
    ];

    $this->_columns['civicrm_mailing_event_reply'] = [
      'dao' => 'CRM_Mailing_Event_DAO_MailingEventReply',
      'fields' => [
        'reply_id' => [
          'name' => 'id',
          'title' => ts('Reply'),
        ],
      ],
      'filters' => [
        'is_replied' => [
          'name' => 'id',
          'title' => ts('Replied'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'options' => [
            '' => ts('Any'),
            '0' => ts('No'),
            '1' => ts('Yes'),
          ],
          'clause' => 'mailing_event_reply_civireport.id IS NULL',
        ],
      ],
      'grouping' => 'mailing-fields',
    ];

    $this->_columns['civicrm_email'] = [
      'dao' => 'CRM_Core_DAO_Email',
      'fields' => [
        'email' => [
          'title' => ts('Email'),
        ],
      ],
      'grouping' => 'contact-fields',
    ];

    $this->_columns['civicrm_phone'] = [
      'dao' => 'CRM_Core_DAO_Phone',
      'fields' => ['phone' => NULL],
      'grouping' => 'contact-fields',
    ];

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;

    parent::__construct();
  }

  public function select() {
    $select = $columns = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if (in_array($fieldName, ['unsubscribe_id', 'optout_id', 'reply_id'])) {
              $select[] = "IF({$field['dbAlias']} IS NULL, 'No', 'Yes') as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
              unset($this->_columns[$tableName]['fields'][$fieldName]);
              $columns[$tableName][$fieldName] = $field;
            }
            elseif ($fieldName == 'delivery_id') {
              $select[] = "IF(mailing_event_bounce_civireport.id IS NOT NULL, 'Bounced', IF(mailing_event_delivered_civireport.id IS NOT NULL, 'Successful', 'Unknown')) as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
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
      if (($this->_params['delivery_status_value'] ?? NULL) ==
        'bounced'
      ) {
        $this->_columns['civicrm_mailing_event_delivered']['filters']['delivery_status']['clause'] = "{$this->_aliases['civicrm_mailing_event_bounce']}.id IS NOT NULL";
      }
      elseif (($this->_params['delivery_status_value'] ?? NULL) ==
        'successful'
      ) {
        $this->_columns['civicrm_mailing_event_delivered']['filters']['delivery_status']['clause'] = "{$this->_aliases['civicrm_mailing_event_delivered']}.id IS NOT NULL AND {$this->_aliases['civicrm_mailing_event_bounce']}.id IS NULL";
      }
    }
    else {
      unset($this->_columns['civicrm_mailing_event_delivered']['filters']['delivery_status']);
    }

    if (array_key_exists('reply_id', $this->_params['fields']) ||
      is_numeric($this->_params['is_replied_value'] ?? '')
    ) {
      if (($this->_params['is_replied_value'] ?? NULL) == 1) {
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
      is_numeric($this->_params['is_unsubscribed_value'] ?? '')
    ) {
      if (($this->_params['is_unsubscribed_value'] ?? NULL) == 1
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
      is_numeric($this->_params['is_optout_value'] ?? '')
    ) {
      if (($this->_params['is_optout_value'] ?? NULL) == 1) {
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

    $this->_from .= "
        INNER JOIN civicrm_mailing_job
          ON civicrm_mailing_event_queue.job_id = civicrm_mailing_job.id
        INNER JOIN civicrm_mailing {$this->_aliases['civicrm_mailing']}
          ON civicrm_mailing_job.mailing_id = {$this->_aliases['civicrm_mailing']}.id
          AND civicrm_mailing_job.is_test = 0";

    $this->joinPhoneFromContact();
  }

  public function where() {
    parent::where();
    $this->_where .= " AND {$this->_aliases['civicrm_mailing']}.sms_provider_id IS NULL";
  }

  /**
   * @return array
   */
  public function mailingList() {

    $data = [];
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
          $rows[$rowNum]['civicrm_email_email'] = '<del>' . ts('Email address deleted.') . '</del>';
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
