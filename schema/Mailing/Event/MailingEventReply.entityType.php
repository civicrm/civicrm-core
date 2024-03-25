<?php

return [
  'name' => 'MailingEventReply',
  'table' => 'civicrm_mailing_event_reply',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventReply',
  'getInfo' => fn() => [
    'title' => ts('Mailing Reply'),
    'title_plural' => ts('Mailing Replies'),
    'description' => ts('Tracks when a contact replies to a mailing'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Reply ID'),
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
      'title' => ts('Reply Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('When this reply event occurred.'),
      'default' => 'CURRENT_TIMESTAMP',
    ],
  ],
];
