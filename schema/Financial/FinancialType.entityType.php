<?php

return [
  'name' => 'FinancialType',
  'table' => 'civicrm_financial_type',
  'class' => 'CRM_Financial_DAO_FinancialType',
  'getInfo' => fn() => [
    'title' => ts('Financial Type'),
    'title_plural' => ts('Financial Types'),
    'description' => ts('Formerly civicrm_contribution_type merged into this table in 4.3'),
    'log' => TRUE,
    'add' => '1.3',
    'label_field' => 'name',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/financial/financialType/edit?action=add&reset=1',
    'update' => 'civicrm/admin/financial/financialType/edit?action=update&id=[id]&reset=1',
    'delete' => 'civicrm/admin/financial/financialType/edit?action=delete&id=[id]&reset=1',
    'browse' => 'civicrm/admin/financial/financialType',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Financial Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('ID of original financial_type so you can search this table by the financial_type.id and then select the relevant version based on the timestamp'),
      'add' => '1.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Financial Type'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Financial Type Name.'),
      'add' => '1.3',
      'unique_name' => 'financial_type',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Name'),
      ],
    ],
    'description' => [
      'title' => ts('Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'TextArea',
      'description' => ts('Financial Type Description.'),
      'add' => '1.3',
      'input_attrs' => [
        'rows' => 6,
        'cols' => 50,
        'label' => ts('Description'),
      ],
    ],
    'is_deductible' => [
      'title' => ts('Is Tax Deductible?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this financial type tax-deductible? If TRUE, contributions of this type may be fully OR partially deductible - non-deductible amount is stored in the Contribution record.'),
      'add' => '1.3',
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Tax-Deductible?'),
      ],
    ],
    'is_reserved' => [
      'title' => ts('Financial Type is Reserved?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this a predefined system object?'),
      'add' => '1.3',
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Reserved?'),
      ],
    ],
    'is_active' => [
      'title' => ts('Financial Type Is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this property active?'),
      'add' => '1.3',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
  ],
];
