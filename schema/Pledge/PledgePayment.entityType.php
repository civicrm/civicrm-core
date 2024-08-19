<?php

return [
  'name' => 'PledgePayment',
  'table' => 'civicrm_pledge_payment',
  'class' => 'CRM_Pledge_DAO_PledgePayment',
  'getInfo' => fn() => [
    'title' => ts('Pledge Payment'),
    'title_plural' => ts('Pledge Payments'),
    'description' => ts('Pledge Payment'),
    'log' => TRUE,
    'add' => '2.1',
  ],
  'getIndices' => fn() => [
    'index_contribution_pledge' => [
      'fields' => [
        'contribution_id' => TRUE,
        'pledge_id' => TRUE,
      ],
      'add' => '2.1',
    ],
    'index_status' => [
      'fields' => [
        'status_id' => TRUE,
      ],
      'add' => '2.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Payment ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '2.1',
      'unique_name' => 'pledge_payment_id',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'pledge_id' => [
      'title' => ts('Pledge ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to Pledge table'),
      'add' => '2.1',
      'input_attrs' => [
        'label' => ts('Pledge'),
      ],
      'entity_reference' => [
        'entity' => 'Pledge',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contribution_id' => [
      'title' => ts('Contribution ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to contribution table.'),
      'add' => '2.1',
      'input_attrs' => [
        'label' => ts('Contribution'),
      ],
      'entity_reference' => [
        'entity' => 'Contribution',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'scheduled_amount' => [
      'title' => ts('Scheduled Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('Pledged amount for this payment (the actual contribution amount might be different).'),
      'add' => '2.1',
      'unique_name' => 'pledge_payment_scheduled_amount',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'actual_amount' => [
      'title' => ts('Actual Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => NULL,
      'description' => ts('Actual amount that is paid as the Pledged installment amount.'),
      'add' => '3.2',
      'unique_name' => 'pledge_payment_actual_amount',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'currency' => [
      'title' => ts('Currency'),
      'sql_type' => 'varchar(3)',
      'input_type' => 'Select',
      'description' => ts('3 character string, value from config setting or input via user.'),
      'add' => '3.2',
      'default' => NULL,
      'pseudoconstant' => [
        'table' => 'civicrm_currency',
        'key_column' => 'name',
        'label_column' => 'full_name',
        'name_column' => 'name',
        'abbr_column' => 'symbol',
      ],
    ],
    'scheduled_date' => [
      'title' => ts('Scheduled Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => ts('The date the pledge payment is supposed to happen.'),
      'add' => '2.1',
      'unique_name' => 'pledge_payment_scheduled_date',
      'unique_title' => 'Payment Scheduled',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'reminder_date' => [
      'title' => ts('Last Reminder'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('The date that the most recent payment reminder was sent.'),
      'add' => '2.1',
      'unique_name' => 'pledge_payment_reminder_date',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'reminder_count' => [
      'title' => ts('Reminders Sent'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('The number of payment reminders sent.'),
      'add' => '2.1',
      'unique_name' => 'pledge_payment_reminder_count',
      'default' => 0,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'status_id' => [
      'title' => ts('Payment Status'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'add' => '2.1',
      'unique_name' => 'pledge_payment_status_id',
      'usage' => [
        'import',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'contribution_status',
      ],
    ],
  ],
];
