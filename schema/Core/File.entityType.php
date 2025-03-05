<?php

return [
  'name' => 'File',
  'table' => 'civicrm_file',
  'class' => 'CRM_Core_DAO_File',
  'getInfo' => fn() => [
    'title' => ts('File'),
    'title_plural' => ts('Files'),
    'description' => ts('Data store for uploaded (attached) files (pointer to file on disk OR blob). Maybe be joined to entities via custom_value.file_id or entity_file table.'),
    'log' => TRUE,
    'add' => '1.5',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('File ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique ID'),
      'add' => '1.5',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'file_type_id' => [
      'title' => ts('File Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Type of file (e.g. Transcript, Income Tax Return, etc). FK to civicrm_option_value.'),
      'add' => '1.5',
      'pseudoconstant' => [
        'option_group_name' => 'file_type',
      ],
    ],
    'mime_type' => [
      'title' => ts('Mime Type'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('mime type of the document'),
      'add' => '1.5',
    ],
    'uri' => [
      'title' => ts('Path'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Location of file on disk relative to $config.customFileUploadDir'),
      'add' => '1.5',
    ],
    'document' => [
      'title' => ts('File Contents'),
      'sql_type' => 'mediumblob',
      'input_type' => NULL,
      'description' => ts('contents of the document'),
      'add' => '1.5',
    ],
    'description' => [
      'title' => ts('File Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Additional descriptive text regarding this attachment (optional).'),
      'add' => '1.5',
    ],
    'upload_date' => [
      'title' => ts('File Upload Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('Date and time that this attachment was uploaded or written to server.'),
      'add' => '1.5',
    ],
    'created_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to civicrm_contact, who uploaded this file'),
      'add' => '5.3',
      'default_callback' => ['CRM_Core_Session', 'getLoggedInContactID'],
      'input_attrs' => [
        'label' => ts('Created By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
  ],
];
