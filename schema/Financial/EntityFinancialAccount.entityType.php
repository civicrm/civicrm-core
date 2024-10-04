<?php

return [
  'name' => 'EntityFinancialAccount',
  'table' => 'civicrm_entity_financial_account',
  'class' => 'CRM_Financial_DAO_EntityFinancialAccount',
  'getInfo' => fn() => [
    'title' => ts('Entity Financial Account'),
    'title_plural' => ts('Entity Financial Accounts'),
    'description' => ts('Map between an entity and a financial account, where there is a specific relationship between the financial account and the entity, e.g. Income Account for or AR Account for'),
    'log' => TRUE,
    'add' => '4.3',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/financial/financialType/accounts?action=add&reset=1&aid=[entity_id]',
    'update' => 'civicrm/admin/financial/financialType/accounts?action=update&id=[id]&aid=[entity_id]&reset=1',
    'delete' => 'civicrm/admin/financial/financialType/accounts?action=delete&id=[id]&aid=[entity_id]&reset=1',
  ],
  'getIndices' => fn() => [
    'index_entity_id_entity_table_account_relationship' => [
      'fields' => [
        'entity_id' => TRUE,
        'entity_table' => TRUE,
        'account_relationship' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '4.7',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Entity Financial Account ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('ID'),
      'add' => '4.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Links to an entity_table like civicrm_financial_type'),
      'add' => '4.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Entity Type'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Financial_BAO_EntityFinancialAccount', 'entityTables'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Links to an id in the entity_table, such as vid in civicrm_financial_type'),
      'add' => '4.3',
      'input_attrs' => [
        'label' => ts('Entity'),
      ],
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'account_relationship' => [
      'title' => ts('Account Relationship'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('FK to a new civicrm_option_value (account_relationship)'),
      'add' => '4.3',
      'pseudoconstant' => [
        'option_group_name' => 'account_relationship',
      ],
    ],
    'financial_account_id' => [
      'title' => ts('Financial Account ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('FK to the financial_account_id'),
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
        'on_delete' => 'RESTRICT',
      ],
    ],
  ],
];
