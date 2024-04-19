<?php

return [
  'name' => 'Spool',
  'table' => 'civicrm_mailing_spool',
  'class' => 'CRM_Mailing_DAO_Spool',
  'getInfo' => fn() => [
    'title' => ts('Spool'),
    'title_plural' => ts('Spools'),
    'description' => ts('Stores the outbond mails'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Spool ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'job_id' => [
      'title' => ts('Job ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('The ID of the Job .'),
      'input_attrs' => [
        'label' => ts('Job'),
      ],
      'entity_reference' => [
        'entity' => 'MailingJob',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'recipient_email' => [
      'title' => ts('Recipient Email'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('The email of the recipients this mail is to be sent.'),
    ],
    'headers' => [
      'title' => ts('Headers'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('The header information of this mailing .'),
    ],
    'body' => [
      'title' => ts('Body'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('The body of this mailing.'),
    ],
    'added_at' => [
      'title' => ts('Added'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('date on which this job was added.'),
      'default' => NULL,
    ],
    'removed_at' => [
      'title' => ts('Removed'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('date on which this job was removed.'),
      'default' => NULL,
    ],
  ],
];
