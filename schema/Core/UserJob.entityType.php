<?php

return [
  'name' => 'UserJob',
  'table' => 'civicrm_user_job',
  'class' => 'CRM_Core_DAO_UserJob',
  'getInfo' => fn() => [
    'title' => ts('User Job'),
    'title_plural' => ts('User Jobs'),
    'description' => ts('Tracking for user jobs (eg. imports).'),
    'log' => FALSE,
    'add' => '5.50',
  ],
  'getPaths' => fn() => [
    'view' => 'civicrm/import/contact/summary?reset=1&user_job_id=[id]',
  ],
  'getIndices' => fn() => [
    'UI_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '5.50',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('User Job ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Job ID'),
      'add' => '5.50',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('User job name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Unique name for job.'),
      'add' => '5.50',
    ],
    'created_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to contact table.'),
      'add' => '5.50',
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
    'created_date' => [
      'title' => ts('Import Job Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => ts('Date and time this job was created.'),
      'add' => '5.50',
      'default' => 'CURRENT_TIMESTAMP',
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'start_date' => [
      'title' => ts('Import Job Started Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('Date and time this import job started.'),
      'add' => '5.50',
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'end_date' => [
      'title' => ts('Job Ended Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('Date and time this import job ended.'),
      'add' => '5.50',
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'expires_date' => [
      'title' => ts('Import Job Expires Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('Date and time to clean up after this import job (temp table deletion date).'),
      'add' => '5.50',
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'status_id' => [
      'title' => ts('User Job Status ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'add' => '5.50',
      'input_attrs' => [
        'label' => ts('Job Status'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_UserJob', 'getStatuses'],
      ],
    ],
    'job_type' => [
      'title' => ts('User Job Type'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Name of the job type, which will allow finding the correct class'),
      'add' => '5.50',
      'input_attrs' => [
        'label' => ts('Job Type'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_UserJob', 'getTypes'],
        'suffixes' => [
          'name',
          'label',
          'url',
        ],
      ],
    ],
    'queue_id' => [
      'title' => ts('Queue ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Queue'),
      'input_attrs' => [
        'label' => ts('Queue'),
      ],
      'entity_reference' => [
        'entity' => 'Queue',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'metadata' => [
      'title' => ts('Job metadata'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Data pertaining to job configuration'),
      'add' => '5.50',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
    'is_template' => [
      'title' => ts('Is Template'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this a template configuration (for use by other/future jobs)?'),
      'add' => '5.51',
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Is Template'),
      ],
    ],
  ],
];
