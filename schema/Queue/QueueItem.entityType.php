<?php

return [
  'name' => 'QueueItem',
  'table' => 'civicrm_queue_item',
  'class' => 'CRM_Queue_DAO_QueueItem',
  'getInfo' => fn() => [
    'title' => ts('Queue Item'),
    'title_plural' => ts('Queue Items'),
    'description' => ts('Stores a list of queue items'),
    'add' => '4.2',
  ],
  'getIndices' => fn() => [
    'index_queueids' => [
      'fields' => [
        'queue_name' => TRUE,
        'weight' => TRUE,
        'id' => TRUE,
      ],
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Queue Item ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'queue_name' => [
      'title' => ts('Queue Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Name of the queue which includes this item'),
    ],
    'weight' => [
      'title' => ts('Order'),
      'sql_type' => 'int',
      'input_type' => 'Text',
      'required' => TRUE,
    ],
    'submit_time' => [
      'title' => ts('Submit Time'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => ts('date on which this item was submitted to the queue'),
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'release_time' => [
      'title' => ts('Release Time'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('date on which this job becomes available; null if ASAP'),
      'default' => NULL,
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'run_count' => [
      'title' => ts('Run Count'),
      'sql_type' => 'int',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Number of times execution has been attempted.'),
      'add' => '5.48',
      'default' => 0,
    ],
    'data' => [
      'title' => ts('Queue item data'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('Serialized queue data'),
      'serialize' => CRM_Core_DAO::SERIALIZE_PHP,
    ],
  ],
];
