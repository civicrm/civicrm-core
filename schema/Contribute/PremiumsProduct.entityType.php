<?php

return [
  'name' => 'PremiumsProduct',
  'table' => 'civicrm_premiums_product',
  'class' => 'CRM_Contribute_DAO_PremiumsProduct',
  'getInfo' => fn() => [
    'title' => ts('Product Premium'),
    'title_plural' => ts('Product Premiums'),
    'description' => ts('joins premiums (settings) to individual product/premium items - determines which products are available for a given contribution page'),
    'log' => TRUE,
    'add' => '1.4',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Premium Product ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Contribution ID'),
      'add' => '1.4',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'premiums_id' => [
      'title' => ts('Premium ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Foreign key to premiums settings record.'),
      'add' => '1.4',
      'input_attrs' => [
        'label' => ts('Premium'),
      ],
      'entity_reference' => [
        'entity' => 'Premium',
        'key' => 'id',
      ],
    ],
    'product_id' => [
      'title' => ts('Product ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Foreign key to each product object.'),
      'add' => '1.4',
      'input_attrs' => [
        'label' => ts('Product'),
      ],
      'entity_reference' => [
        'entity' => 'Product',
        'key' => 'id',
      ],
    ],
    'weight' => [
      'title' => ts('Order'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '2.0',
    ],
    'financial_type_id' => [
      'title' => ts('Financial Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Financial Type.'),
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
