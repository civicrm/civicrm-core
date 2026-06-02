<?php

use CRM_Postbox_ExtensionUtil as E;

return [
  'name' => 'EmailMessage',
  'table' => 'civicrm_email_message',
  'class' => 'CRM_Postbox_DAO_EmailMessage',
  'getInfo' => fn() => [
    'title' => E::ts('Email Message'),
    'title_plural' => E::ts('Email Messages'),
    'description' => E::ts('Email Messages to send out - e.g. triggered notification emails'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique Email Message ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'from_site_email_address_id' => [
      'title' => E::ts('Site Email Addres'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('Site Email Address to use as Sender'),
      'entity_reference' => [
        'entity' => 'SiteEmailAddress',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    // restricted to SiteEmailAddress for now
    // 'from_name' => [
    //   'title' => E::ts('From Name'),
    //   'sql_type' => 'varchar(500)',
    //   'input_type' => 'Text',
    // ],
    // 'from_email' => [
    //   'title' => E::ts('From Email Address'),
    //   'sql_type' => 'varchar(500)',
    //   'input_type' => 'Text',
    // ],
    'to_contact_id' => [
      'title' => E::ts('To Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to TO Contact'),
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'location_type' => [
      'title' => E::ts('Location Type'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => E::ts('Preferred location type when selecting recipient email'),
    ],
    'subject' => [
      'title' => E::ts('Subject'),
      'sql_type' => 'text',
      'input_type' => 'Text',
    ],
    'body' => [
      'title' => E::ts('Message'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Content of email in HTML format'),
    ],
    'created_id' => [
      'title' => ts('Created By'),
      'readonly' => TRUE,
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Contact who created the email'),
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'date_created' => [
      'title' => ts('Date Created'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('When was the message created (ie added to the queue)'),
      'unique_name' => 'email_message_date_created',
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'date_sent' => [
      'title' => E::ts('Date Sent'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('When was the message successfully sent'),
      'unique_name' => 'email_message_date_sent',
      'default' => NULL,
    ],
    'error_message' => [
      'title' => E::ts('Error Message'),
      'sql_type' => 'text',
      'input_type' => 'Text',
      'description' => ts('Error message when attempting to send'),
      'default' => NULL,
    ],
    'extra' => [
      'title' => E::ts('Additional Configuration'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Additional configuration - such as additional cc recipients'),
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
  ],
];
