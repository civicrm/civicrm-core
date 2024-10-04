<?php

return [
  'name' => 'ContributionProduct',
  'table' => 'civicrm_contribution_product',
  'class' => 'CRM_Contribute_DAO_ContributionProduct',
  'getInfo' => fn() => [
    'title' => ts('Contribution Product'),
    'title_plural' => ts('Contribution Products'),
    'description' => ts('Products for Contributions'),
    'log' => TRUE,
    'add' => '1.4',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Contribution Product ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '1.4',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'product_id' => [
      'title' => ts('Product ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'add' => '1.4',
      'entity_reference' => [
        'entity' => 'Product',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'contribution_id' => [
      'title' => ts('Contribution ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'add' => '1.4',
      'input_attrs' => [
        'label' => ts('Contribution'),
      ],
      'entity_reference' => [
        'entity' => 'Contribution',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'product_option' => [
      'title' => ts('Product Option'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Option value selected if applicable - e.g. color, size etc.'),
      'add' => '1.4',
      'usage' => [
        'export',
      ],
    ],
    'quantity' => [
      'title' => ts('Quantity'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'add' => '1.4',
      'usage' => [
        'export',
      ],
    ],
    'fulfilled_date' => [
      'title' => ts('Fulfilled Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Optional. Can be used to record the date this product was fulfilled or shipped.'),
      'add' => '1.4',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'format_type' => 'activityDate',
      ],
    ],
    'start_date' => [
      'title' => ts('Start date for premium'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Actual start date for a time-delimited premium (subscription, service or membership)'),
      'add' => '1.4',
      'unique_name' => 'contribution_start_date',
      'usage' => [
        'export',
      ],
    ],
    'end_date' => [
      'title' => ts('End date for premium'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Actual end date for a time-delimited premium (subscription, service or membership)'),
      'add' => '1.4',
      'unique_name' => 'contribution_end_date',
      'usage' => [
        'export',
      ],
    ],
    'comment' => [
      'title' => ts('Premium comment'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'add' => '1.4',
    ],
    'financial_type_id' => [
      'title' => ts('Financial Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Financial Type(for membership price sets only).'),
      'add' => '4.3',
      'default' => NULL,
      'input_attrs' => [
        'label' => ts('Financial Type'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_financial_type',
        'key_column' => 'id',
        'label_column' => 'label',
      ],
      'entity_reference' => [
        'entity' => 'FinancialType',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
  ],
];
