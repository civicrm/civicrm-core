<?php

return [
  'name' => 'MailingEventQueue',
  'table' => 'civicrm_mailing_event_queue',
  'class' => 'CRM_Mailing_Event_DAO_MailingEventQueue',
  'getInfo' => fn() => [
    'title' => ts('Mailing Recipient'),
    'title_plural' => ts('Mailing Recipients'),
    'description' => ts('Intended recipients of a mailing.'),
  ],
  'getIndices' => fn() => [
    'index_hash' => [
      'fields' => [
        'hash' => TRUE,
      ],
      'add' => '4.7',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mailing Event Queue ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'input_attrs' => [
        'label' => ts('ID'),
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'job_id' => [
      'title' => ts('Job ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Mailing Job'),
      'input_attrs' => [
        'label' => ts('Outbound Mailing'),
      ],
      'entity_reference' => [
        'entity' => 'MailingJob',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'mailing_id' => [
      'title' => ts('Mailing ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Related mailing. Used for reporting on mailing success, if present.'),
      'add' => '5.67',
      'input_attrs' => [
        'label' => ts('Mailing'),
      ],
      'entity_reference' => [
        'entity' => 'Mailing',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'is_test' => [
      'title' => ts('Test'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'readonly' => TRUE,
      'add' => '5.67',
      'default' => FALSE,
    ],
    'email_id' => [
      'title' => ts('Email ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Email'),
      'default' => NULL,
      'input_attrs' => [
        'label' => ts('Email'),
      ],
      'entity_reference' => [
        'entity' => 'Email',
        'key' => 'id',
        'on_delete' => 'SET NULL',
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
      'title' => ts('Security Hash'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Security hash'),
    ],
    'phone_id' => [
      'title' => ts('Phone ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Phone'),
      'default' => NULL,
      'input_attrs' => [
        'label' => ts('Phone'),
      ],
      'entity_reference' => [
        'entity' => 'Phone',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
  ],
];
