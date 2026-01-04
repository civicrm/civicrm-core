<?php

return [
  'name' => 'EntityFinancialTrxn',
  'table' => 'civicrm_entity_financial_trxn',
  'class' => 'CRM_Financial_DAO_EntityFinancialTrxn',
  'getInfo' => fn() => [
    'title' => ts('Entity Financial Trxn'),
    'title_plural' => ts('Entity Financial Trxns'),
    'description' => ts('Mapping table for FinancialTrxn to FinancialItem and Contribution'),
    'add' => '3.2',
  ],
  'getIndices' => fn() => [
    'UI_entity_financial_trxn_entity_table' => [
      'fields' => [
        'entity_table' => TRUE,
      ],
      'add' => '4.3',
    ],
    'UI_entity_financial_trxn_entity_id' => [
      'fields' => [
        'entity_id' => TRUE,
      ],
      'add' => '4.3',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Entity Financial Transaction ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('ID'),
      'add' => '3.2',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('May contain civicrm_financial_item, civicrm_contribution, civicrm_financial_trxn, civicrm_grant, etc'),
      'add' => '3.2',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Financial_BAO_EntityFinancialTrxn', 'entityTables'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'add' => '3.2',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'financial_trxn_id' => [
      'title' => ts('Financial Transaction ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '3.2',
      'input_attrs' => [
        'label' => ts('Financial Transaction'),
      ],
      'entity_reference' => [
        'entity' => 'FinancialTrxn',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'amount' => [
      'title' => ts('Amount'),
      'sql_type' => 'decimal(20,2)',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => ts('allocated amount of transaction to this entity'),
      'add' => '3.2',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
  ],
];
