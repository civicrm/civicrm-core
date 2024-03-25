<?php

return [
  'name' => 'MailingEventOpened',
  'table' => 'civicrm_mailing_event_opened',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventOpened',
  'getInfo' => fn() => [
    'title' => ts('Mailing Opened'),
    'title_plural' => ts('Mailings Opened'),
    'description' => ts('Tracks when a delivered email is opened by the recipient'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mailing Opened ID'),
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
    'time_stamp' => [
      'title' => ts('Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('When this open event occurred.'),
      'default' => 'CURRENT_TIMESTAMP',
    ],
  ],
];
