<?php

return [
  'name' => 'PaymentprocessorWebhook',
  'table' => 'civicrm_paymentprocessor_webhook',
  'class' => 'CRM_Financial_DAO_PaymentprocessorWebhook',
  'getInfo' => fn() => [
    'title' => ts('Payment Processor Webhook'),
    'title_plural' => ts('Payment Processor Webhooks'),
    'description' => ts('Track the processing of payment processor webhooks'),
    'add' => '6.19',
    'log' => TRUE,
  ],
  'getIndices' => fn() => [
    'index_event_id' => [
      'fields' => [
        'event_id' => TRUE,
      ],
      'add' => '6.19',
    ],
    'index_created_date' => [
      'fields' => [
        'created_date' => TRUE,
      ],
      'add' => '6.19',
    ],
    'index_processed_date' => [
      'fields' => [
        'processed_date' => TRUE,
      ],
      'add' => '6.19',
    ],
    'index_status_processed_date' => [
      'fields' => [
        'status' => TRUE,
        'processed_date' => TRUE,
      ],
      'add' => '6.19',
    ],
    'index_identifier' => [
      'fields' => [
        'identifier' => TRUE,
      ],
      'add' => '6.19',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique PaymentprocessorWebhook ID'),
      'add' => '6.19',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'payment_processor_id' => [
      'title' => ts('Payment Processor'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Payment Processor for this webhook'),
      'add' => '6.19',
      'pseudoconstant' => [
        'table' => 'civicrm_payment_processor',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'PaymentProcessor',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'event_id' => [
      'title' => ts('Event ID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Webhook event ID'),
      'add' => '6.19',
    ],
    'trigger' => [
      'title' => ts('Trigger'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Webhook trigger event type'),
      'add' => '6.19',
    ],
    'created_date' => [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When the webhook was first received by the IPN code'),
      'add' => '6.19',
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'processed_date' => [
      'title' => ts('Processed Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('Has this webhook been processed yet?'),
      'add' => '6.19',
      'default' => NULL,
    ],
    'status' => [
      'title' => ts('Status'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Processing status'),
      'add' => '6.19',
      'default' => 'new',
    ],
    'identifier' => [
      'title' => ts('Identifier'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Optional key to group webhooks, as needed by some processors.'),
      'add' => '6.19',
    ],
    'message' => [
      'title' => ts('Message'),
      'sql_type' => 'varchar(1024)',
      'input_type' => 'Text',
      'description' => ts('Stores data sent that is needed for processing. JSON suggested.'),
      'add' => '6.19',
      'default' => '',
    ],
    'data' => [
      'title' => ts('Data'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Stores data sent that is needed for processing. JSON suggested.'),
      'add' => '6.19',
    ],
  ],
];
