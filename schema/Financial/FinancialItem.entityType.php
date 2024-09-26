<?php

return [
  'name' => 'FinancialItem',
  'table' => 'civicrm_financial_item',
  'class' => 'CRM_Financial_DAO_FinancialItem',
  'getInfo' => fn() => [
    'title' => ts('Financial Item'),
    'title_plural' => ts('Financial Items'),
    'description' => ts('Financial data for civicrm_line_item, etc.'),
    'log' => TRUE,
    'add' => '4.3',
  ],
  'getIndices' => fn() => [
    'IX_created_date' => [
      'fields' => [
        'created_date' => TRUE,
      ],
      'add' => '4.3',
    ],
    'IX_transaction_date' => [
      'fields' => [
        'transaction_date' => TRUE,
      ],
      'add' => '4.3',
    ],
    'index_entity_id_entity_table' => [
      'fields' => [
        'entity_id' => TRUE,
        'entity_table' => TRUE,
      ],
      'add' => '4.7',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Financial Item ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '4.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'created_date' => [
      'title' => ts('Financial Item Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('Date and time the item was created'),
      'add' => '4.3',
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'transaction_date' => [
      'title' => ts('Financial Item Transaction Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'description' => ts('Date and time of the source transaction'),
      'add' => '4.3',
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to Contact ID of contact the item is from'),
      'add' => '4.3',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'description' => [
      'title' => ts('Financial Item Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Human readable description of this item, to ease display without lookup of source item.'),
      'add' => '4.3',
    ],
    'amount' => [
      'title' => ts('Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('Total amount of this item'),
      'add' => '4.3',
      'default' => '0',
    ],
    'currency' => [
      'title' => ts('Financial Item Currency'),
      'sql_type' => 'varchar(3)',
      'input_type' => 'Select',
      'description' => ts('Currency for the amount'),
      'add' => '4.3',
      'usage' => [
        'export',
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
    'financial_account_id' => [
      'title' => ts('Financial Account ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to civicrm_financial_account'),
      'add' => '4.3',
      'input_attrs' => [
        'label' => ts('Financial Account'),
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
    'status_id' => [
      'title' => ts('Financial Item Status ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Payment status: test, paid, part_paid, unpaid (if empty assume unpaid)'),
      'add' => '4.3',
      'usage' => [
        'export',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'financial_item_status',
      ],
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('May contain civicrm_line_item, civicrm_financial_trxn etc'),
      'add' => '4.3',
      'pseudoconstant' => [
        'callback' => ['CRM_Financial_BAO_FinancialItem', 'entityTables'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('The specific source item that is responsible for the creation of this financial_item'),
      'add' => '4.3',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
  ],
];
