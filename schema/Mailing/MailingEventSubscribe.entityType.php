<?php

return [
  'name' => 'MailingEventSubscribe',
  'table' => 'civicrm_mailing_event_subscribe',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventSubscribe',
  'getInfo' => fn() => [
    'title' => ts('Mailing Opt-In'),
    'title_plural' => ts('Mailing Opt-Ins'),
    'description' => ts('Tracks when a (new) contact subscribes to a group by email'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mailing Subscribe ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'input_attrs' => [
        'label' => ts('ID'),
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'group_id' => [
      'title' => ts('Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('FK to Group'),
      'input_attrs' => [
        'label' => ts('Group'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_group',
        'key_column' => 'id',
        'label_column' => 'title',
      ],
      'entity_reference' => [
        'entity' => 'Group',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to Contact'),
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'hash' => [
      'title' => ts('Mailing Subscribe Hash'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Security hash'),
    ],
    'time_stamp' => [
      'title' => ts('Mailing Subscribe Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('When this subscription event occurred.'),
      'default' => 'CURRENT_TIMESTAMP',
    ],
  ],
];
