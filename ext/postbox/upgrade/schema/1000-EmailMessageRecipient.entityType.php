<?php
use CRM_Postbox_ExtensionUtil as E;

return [
  'name' => 'EmailMessageRecipient',
  'table' => 'civicrm_email_message_recipient',
  'class' => 'CRM_Postbox_DAO_EmailMessageRecipient',
  'getInfo' => fn() => [
    'title' => E::ts('Email Message Recipient'),
    'title_plural' => E::ts('Email Message Recipients'),
    'description' => E::ts('Bridge between Email Messages and Recipient Contacts'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique EmailMessageRecipient ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'email_message_id' => [
      'title' => E::ts('Email Message ID'),
      'sql_type' => 'int unsigned',
      'required' => TRUE,
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Email Message'),
      'entity_reference' => [
        'entity' => 'EmailMessage',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_id' => [
      'title' => E::ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Contact'),
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'recipient_type' => [
      'title' => E::ts('Recipient Type'),
      'sql_type' => 'varchar(8)',
      'required' => TRUE,
      'input_type' => 'Select',
      'description' => E::ts('To, CC or BCC'),
      'pseudoconstant' => [
        'callback' => ['CRM_Postbox_BAO_EmailMessageRecipient', 'getTypes'],
      ],
    ],
    'email_address' => [
      'title' => E::ts('Email Address'),
      'sql_type' => 'varchar(320)',
      'input_type' => 'Text',
      'description' => E::ts('The email address to use for sending. Usually this is determined automatically from the contact_id at send time, but can be specified directly if desired.'),
    ],
  ],
  'getIndices' => fn() => [],
  'getPaths' => fn() => [],
];
