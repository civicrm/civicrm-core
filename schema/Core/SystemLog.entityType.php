<?php

return [
  'name' => 'SystemLog',
  'table' => 'civicrm_system_log',
  'class' => 'CRM_Core_DAO_SystemLog',
  'getInfo' => fn() => [
    'title' => ts('System Log'),
    'title_plural' => ts('System Logs'),
    'description' => ts('FIXME'),
    'add' => '4.5',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('System Log ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Primary key ID'),
      'add' => '4.4',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'message' => [
      'title' => ts('System Log Message'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Standardized message'),
      'add' => '4.5',
      'input_attrs' => [
        'maxlength' => 128,
      ],
    ],
    'context' => [
      'title' => ts('Detailed Log Data'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('JSON encoded data'),
      'add' => '4.5',
    ],
    'level' => [
      'title' => ts('Detailed Log Data'),
      'sql_type' => 'varchar(9)',
      'input_type' => 'Text',
      'description' => ts('error level per PSR3'),
      'add' => '4.5',
      'default' => 'info',
      'input_attrs' => [
        'maxlength' => 9,
      ],
    ],
    'timestamp' => [
      'title' => ts('Log Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('Timestamp of when event occurred.'),
      'add' => '4.5',
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'contact_id' => [
      'title' => ts('Log Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Optional Contact ID that created the log. Not an FK as we keep this regardless'),
      'add' => '4.5',
      'input_attrs' => [
        'maxlength' => 11,
      ],
    ],
    'hostname' => [
      'title' => ts('Log Host'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Optional Name of logging host'),
      'add' => '4.5',
      'input_attrs' => [
        'maxlength' => 128,
      ],
    ],
  ],
];
