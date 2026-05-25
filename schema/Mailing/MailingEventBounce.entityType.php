<?php

return [
  'name' => 'MailingEventBounce',
  'table' => 'civicrm_mailing_event_bounce',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventBounce',
  'getInfo' => fn() => [
    'title' => ts('Mailing Bounce'),
    'title_plural' => ts('Mailing Bounces'),
    'description' => ts('Mailings that failed to reach the inbox of the recipient.'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Bounce ID'),
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
    'bounce_type_id' => [
      'title' => ts('Bounce Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('What type of bounce was it?'),
      'input_attrs' => [
        'label' => ts('Bounce Type'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_mailing_bounce_type',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
    ],
    'bounce_reason' => [
      'title' => ts('Bounce Reason'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Text',
      'description' => ts('The reason the email bounced.'),
    ],
    'time_stamp' => [
      'title' => ts('Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => ts('When this bounce event occurred.'),
      'unique_name' => 'civicrm_mailing_bounce_time_stamp',
      'default' => 'CURRENT_TIMESTAMP',
      'input_attrs' => [
        'label' => ts('Bounce Date'),
      ],
      'usage' => [
        'export',
      ],
    ],
  ],
];
