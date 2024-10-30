<?php

return [
  'name' => 'Currency',
  'table' => 'civicrm_currency',
  'class' => 'CRM_Financial_DAO_Currency',
  'getInfo' => fn() => [
    'title' => ts('Currency'),
    'title_plural' => ts('Currencies'),
    'description' => ts('List of currencies'),
    'log' => TRUE,
    'add' => '1.7',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Currency ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Currency ID'),
      'add' => '1.7',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Currency'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Currency Name'),
      'add' => '1.7',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'symbol' => [
      'title' => ts('Currency Symbol'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Text',
      'description' => ts('Currency Symbol'),
      'add' => '1.7',
    ],
    'numeric_code' => [
      'title' => ts('Currency Numeric Code'),
      'sql_type' => 'varchar(3)',
      'input_type' => 'Text',
      'description' => ts('Numeric currency code'),
      'add' => '1.9',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'full_name' => [
      'title' => ts('Full Currency Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Full currency name'),
      'add' => '1.9',
    ],
  ],
];
