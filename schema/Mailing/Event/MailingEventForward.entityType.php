<?php

return [
  'name' => 'MailingEventForward',
  'table' => 'civicrm_mailing_event_forward',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventForward',
  'getInfo' => fn() => [
    'title' => ts('Mailing Forward'),
    'title_plural' => ts('Mailing Forwards'),
    'description' => ts('Tracks when a contact forwards a mailing to a (new) contact'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Forward ID'),
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
    'dest_queue_id' => [
      'title' => ts('Destination Queue ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to EventQueue for destination'),
      'input_attrs' => [
        'label' => ts('Destination Queue'),
      ],
      'entity_reference' => [
        'entity' => 'MailingEventQueue',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'time_stamp' => [
      'title' => ts('Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('When this forward event occurred.'),
      'default' => 'CURRENT_TIMESTAMP',
    ],
  ],
];
