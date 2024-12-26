<?php

return [
  'name' => 'MailingEventConfirm',
  'table' => 'civicrm_mailing_event_confirm',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventConfirm',
  'getInfo' => fn() => [
    'title' => ts('Mailing Opt-In Confirmation'),
    'title_plural' => ts('Mailing Opt-In Confirmations'),
    'description' => ts('Tracks when a subscription event is confirmed by email'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mailing Confirmation ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'input_attrs' => [
        'label' => ts('ID'),
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'event_subscribe_id' => [
      'title' => ts('Mailing Subscribe ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to civicrm_mailing_event_subscribe'),
      'input_attrs' => [
        'label' => ts('Mailing Subscribe'),
      ],
      'entity_reference' => [
        'entity' => 'MailingEventSubscribe',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'time_stamp' => [
      'title' => ts('Confirm Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('When this confirmation event occurred.'),
      'default' => 'CURRENT_TIMESTAMP',
    ],
  ],
];
