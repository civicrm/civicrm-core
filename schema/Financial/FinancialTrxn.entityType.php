<?php

return [
  'name' => 'FinancialTrxn',
  'table' => 'civicrm_financial_trxn',
  'class' => 'CRM_Financial_DAO_FinancialTrxn',
  'getInfo' => fn() => [
    'title' => ts('Financial Trxn'),
    'title_plural' => ts('Financial Trxns'),
    'description' => ts('Table containing Financial Transactions (including Payments)'),
    'log' => TRUE,
    'add' => '1.3',
  ],
  'getIndices' => fn() => [
    'UI_ftrxn_trxn_id' => [
      'fields' => [
        'trxn_id' => TRUE,
      ],
      'add' => '4.7',
    ],
    'UI_ftrxn_payment_instrument_id' => [
      'fields' => [
        'payment_instrument_id' => TRUE,
      ],
      'add' => '4.3',
    ],
    'UI_ftrxn_check_number' => [
      'fields' => [
        'check_number' => TRUE,
      ],
      'add' => '4.3',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Financial Transaction ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '1.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'from_financial_account_id' => [
      'title' => ts('From Account ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to financial_account table.'),
      'add' => '4.3',
      'input_attrs' => [
        'label' => ts('From Account'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_financial_account',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'FinancialAccount',
        'key' => 'id',
      ],
    ],
    'to_financial_account_id' => [
      'title' => ts('To Account ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to financial_financial_account table.'),
      'add' => '4.3',
      'input_attrs' => [
        'label' => ts('To Account'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_financial_account',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'FinancialAccount',
        'key' => 'id',
      ],
    ],
    'trxn_date' => [
      'title' => ts('Financial Transaction Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('date transaction occurred'),
      'add' => '1.3',
      'default' => NULL,
      'input_attrs' => [
        'format_type' => 'activityDateTime',
      ],
    ],
    'total_amount' => [
      'title' => ts('Financial Total Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('amount of transaction'),
      'add' => '1.3',
    ],
    'fee_amount' => [
      'title' => ts('Financial Fee Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => NULL,
      'description' => ts('actual processor fee if known - may be 0.'),
      'add' => '1.3',
    ],
    'net_amount' => [
      'title' => ts('Financial Net Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => NULL,
      'description' => ts('actual funds transfer amount. total less fees. if processor does not report actual fee during transaction, this is set to total_amount.'),
      'add' => '1.3',
    ],
    'currency' => [
      'title' => ts('Financial Currency'),
      'sql_type' => 'varchar(3)',
      'input_type' => 'Select',
      'description' => ts('3 character string, value from config setting or input via user.'),
      'add' => '1.3',
      'default' => NULL,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_currency',
        'key_column' => 'name',
        'label_column' => 'full_name',
        'name_column' => 'name',
        'abbr_column' => 'symbol',
        'description_column' => 'IFNULL(CONCAT(name, " (", symbol, ")"), name)',
      ],
    ],
    'is_payment' => [
      'title' => ts('Is Payment?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this entry either a payment or a reversal of a payment?'),
      'add' => '4.7',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'trxn_id' => [
      'title' => ts('Transaction ID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Transaction id supplied by external processor. This may not be unique.'),
      'add' => '1.3',
      'input_attrs' => [
        'size' => '10',
      ],
    ],
    'trxn_result_code' => [
      'title' => ts('Transaction Result Code'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('processor result code'),
      'add' => '1.3',
    ],
    'status_id' => [
      'title' => ts('Financial Transaction Status ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('pseudo FK to civicrm_option_value of contribution_status_id option_group'),
      'add' => '4.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'contribution_status',
      ],
    ],
    'payment_processor_id' => [
      'title' => ts('Payment Processor ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Payment Processor for this financial transaction'),
      'add' => '4.3',
      'input_attrs' => [
        'label' => ts('Payment Processor'),
      ],
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
    'payment_instrument_id' => [
      'title' => ts('Payment Method'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to payment_instrument option group values'),
      'add' => '4.3',
      'unique_name' => 'financial_trxn_payment_instrument_id',
      'pseudoconstant' => [
        'option_group_name' => 'payment_instrument',
      ],
    ],
    'card_type_id' => [
      'title' => ts('Card Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to accept_creditcard option group values'),
      'add' => '4.7',
      'unique_name' => 'financial_trxn_card_type_id',
      'pseudoconstant' => [
        'option_group_name' => 'accept_creditcard',
      ],
    ],
    'check_number' => [
      'title' => ts('Check Number'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Check number'),
      'add' => '4.3',
      'unique_name' => 'financial_trxn_check_number',
      'input_attrs' => [
        'size' => '6',
      ],
    ],
    'pan_truncation' => [
      'title' => ts('PAN Truncation'),
      'sql_type' => 'varchar(4)',
      'input_type' => 'Text',
      'description' => ts('Last 4 digits of credit card'),
      'add' => '4.7',
      'unique_name' => 'financial_trxn_pan_truncation',
      'input_attrs' => [
        'size' => '4',
      ],
    ],
    'order_reference' => [
      'title' => ts('Order Reference'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Payment Processor external order reference'),
      'add' => '5.20',
      'unique_name' => 'financial_trxn_order_reference',
      'input_attrs' => [
        'size' => '25',
      ],
    ],
  ],
];
