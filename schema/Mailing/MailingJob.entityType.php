<?php

return [
  'name' => 'MailingJob',
  'table' => 'civicrm_mailing_job',
  'class' => 'CRM_Mailing_DAO_MailingJob',
  'getInfo' => fn() => [
    'title' => ts('Outbound Mailing'),
    'title_plural' => ts('Outbound Mailings'),
    'description' => ts('Attempted delivery of a mailing.'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mailing Job ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'input_attrs' => [
        'label' => ts('ID'),
      ],
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
    'scheduled_date' => [
      'title' => ts('Mailing Scheduled Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('date on which this job was scheduled.'),
      'default' => NULL,
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'start_date' => [
      'title' => ts('Mailing Job Start Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('date on which this job was started.'),
      'unique_name' => 'mailing_job_start_date',
      'unique_title' => 'Mailing Start Date',
      'default' => NULL,
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'end_date' => [
      'title' => ts('Mailing Job End Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('date on which this job ended.'),
      'default' => NULL,
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'status' => [
      'title' => ts('Mailing Job Status'),
      'sql_type' => 'varchar(12)',
      'input_type' => 'Select',
      'description' => ts('The state of this job'),
      'unique_name' => 'mailing_job_status',
      'input_attrs' => [
        'label' => ts('Status'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'getMailingJobStatus'],
      ],
    ],
    'is_test' => [
      'title' => ts('Mailing Job Is Test?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this job for a test mail?'),
      'add' => '1.9',
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Test Mailing'),
      ],
    ],
    'job_type' => [
      'title' => ts('Mailing Job Type'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Type of mailling job: null | child'),
      'add' => '3.3',
    ],
    'parent_id' => [
      'title' => ts('Parent ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Parent job id'),
      'add' => '3.3',
      'default' => NULL,
      'input_attrs' => [
        'label' => ts('Parent'),
      ],
      'entity_reference' => [
        'entity' => 'MailingJob',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'job_offset' => [
      'title' => ts('Mailing Job Offset'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'description' => ts('Offset of the child job'),
      'add' => '3.3',
      'default' => 0,
    ],
    'job_limit' => [
      'title' => ts('Mailing Job Limit'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'description' => ts('Queue size limit for each child job'),
      'add' => '3.3',
      'default' => 0,
      'input_attrs' => [
        'label' => ts('Batch Limit'),
      ],
    ],
  ],
];
