<?php

return [
  'name' => 'MailingRecipients',
  'table' => 'civicrm_mailing_recipients',
  'class' => 'CRM_Mailing_DAO_MailingRecipients',
  'getInfo' => fn() => [
    'title' => ts('Mailing Recipient'),
    'title_plural' => ts('Mailing Recipients'),
    'description' => ts('Stores information about the recipients of a mailing.'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mailing Recipients ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'mailing_id' => [
      'title' => ts('Mailing ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('The ID of the mailing this Job will send.'),
      'input_attrs' => [
        'label' => ts('Mailing'),
      ],
      'entity_reference' => [
        'entity' => 'Mailing',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_id' => [
      'title' => ts('Recipient ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to Contact'),
      'input_attrs' => [
        'label' => ts('Recipient'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
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
