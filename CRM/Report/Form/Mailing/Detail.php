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
class CRM_Report_Form_Mailing_Detail extends CRM_Report_Form_Mailing_Base {

  protected $_exposeContactID = FALSE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = [];

    $this->_columns['civicrm_contact'] = $this->getContactColumns();
    $this->_columns['civicrm_contact']['fields']['id']['no_display'] = TRUE;

    $this->_columns['civicrm_mailing'] = $this->getMailingColumns();

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

}
