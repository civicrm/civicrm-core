<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'name' => 'Session',
  'table' => 'civicrm_session',
  'class' => 'CRM_Standaloneusers_DAO_Session',
  'getInfo' => fn() => [
    'title' => E::ts('Session'),
    'title_plural' => E::ts('Sessions'),
    'description' => E::ts('Standalone User Sessions'),
    'log' => FALSE,
  ],
  'getIndices' => fn() => [
    'index_session_id' => [
      'fields' => [
        'session_id' => TRUE,
      ],
      'unique' => TRUE,
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Unique Session ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'session_id' => [
      'title' => E::ts('Session ID'),
      'sql_type' => 'char(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Hexadecimal Session Identifier'),
      'input_attrs' => [
        'maxlength' => 64,
      ],
    ],
    'data' => [
      'title' => E::ts('Data'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => E::ts('Session Data'),
    ],
    'last_accessed' => [
      'title' => E::ts('Last Accessed'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => E::ts('Timestamp of the last session access'),
    ],
  ],
];
