<?php

return [
  'name' => 'MailingEventUnsubscribe',
  'table' => 'civicrm_mailing_event_unsubscribe',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventUnsubscribe',
  'getInfo' => fn() => [
    'title' => ts('Mailing Unsubscribe'),
    'title_plural' => ts('Mailing Unsubscribes'),
    'description' => ts('Tracks when a recipient unsubscribes from a group/domain'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Unsubscribe ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'input_attrs' => [
        'label' => ts('ID'),
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'event_queue_id' => [
      'title' => ts('Event Queue ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to EventQueue'),
      'input_attrs' => [
        'label' => ts('Recipient'),
      ],
      'entity_reference' => [
        'entity' => 'MailingEventQueue',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'org_unsubscribe' => [
      'title' => ts('Unsubscribe is for Organization?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Unsubscribe at org- or group-level'),
    ],
    'time_stamp' => [
      'title' => ts('Unsubscribe Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('When this delivery event occurred.'),
      'default' => 'CURRENT_TIMESTAMP',
    ],
  ],
];
