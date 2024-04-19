<?php

return [
  'name' => 'MailingEventDelivered',
  'table' => 'civicrm_mailing_event_delivered',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventDelivered',
  'getInfo' => fn() => [
    'title' => ts('Mailing Delivery'),
    'title_plural' => ts('Mailing Deliveries'),
    'description' => ts('Tracks when a queued email is actually delivered to the MTA'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Delivered ID'),
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
      'description' => ts('When this delivery event occurred.'),
      'default' => 'CURRENT_TIMESTAMP',
    ],
  ],
];
