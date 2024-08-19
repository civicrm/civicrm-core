<?php

return [
  'name' => 'JobLog',
  'table' => 'civicrm_job_log',
  'class' => 'CRM_Core_DAO_JobLog',
  'getInfo' => fn() => [
    'title' => ts('Job Log'),
    'title_plural' => ts('Job Logs'),
    'description' => ts('Scheduled jobs log.'),
    'log' => FALSE,
    'add' => '4.1',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Job Log ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Job log entry ID'),
      'add' => '4.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'domain_id' => [
      'title' => ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Which Domain is this scheduled job for'),
      'add' => '4.1',
      'input_attrs' => [
        'label' => ts('Domain'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_domain',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'Domain',
        'key' => 'id',
      ],
    ],
    'run_time' => [
      'title' => ts('Timestamp'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('Log entry date'),
      'add' => '4.1',
    ],
    'job_id' => [
      'title' => ts('Job ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Pointer to job id'),
      'add' => '4.1',
      'entity_reference' => [
        'entity' => 'Job',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'name' => [
      'title' => ts('Job Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Title of the job'),
      'add' => '4.1',
    ],
    'command' => [
      'title' => ts('Command'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Full path to file containing job script'),
      'add' => '4.1',
    ],
    'description' => [
      'title' => ts('Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Title line of log entry'),
      'add' => '4.1',
    ],
    'data' => [
      'title' => ts('Extended Data'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('Potential extended data for specific job run (e.g. tracebacks).'),
      'add' => '4.1',
    ],
  ],
];
