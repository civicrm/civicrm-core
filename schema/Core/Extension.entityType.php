<?php

return [
  'name' => 'Extension',
  'table' => 'civicrm_extension',
  'class' => 'CRM_Core_DAO_Extension',
  'getInfo' => fn() => [
    'title' => ts('Extension'),
    'title_plural' => ts('Extensions'),
    'description' => ts('Table of extensions'),
    'log' => FALSE,
    'add' => '4.2',
    'label_field' => 'label',
  ],
  'getIndices' => fn() => [
    'UI_extension_full_name' => [
      'fields' => [
        'full_name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '4.2',
    ],
    'UI_extension_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'add' => '4.2',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Extension ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Local Extension ID'),
      'add' => '4.2',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'type' => [
      'title' => ts('Type'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'required' => TRUE,
      'add' => '4.2',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'getExtensionTypes'],
      ],
    ],
    'full_name' => [
      'title' => ts('Key'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Fully qualified extension name'),
      'add' => '4.2',
    ],
    'name' => [
      'title' => ts('Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Short name'),
      'add' => '4.2',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'label' => [
      'title' => ts('Label'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Short, printable name'),
      'add' => '4.2',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'file' => [
      'title' => ts('File'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Primary PHP file'),
      'add' => '4.2',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'schema_version' => [
      'title' => ts('Schema Version'),
      'sql_type' => 'varchar(63)',
      'input_type' => 'Text',
      'description' => ts('Revision code of the database schema; the format is module-defined'),
      'add' => '4.2',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'is_active' => [
      'title' => ts('Extension is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'description' => ts('Is this extension active?'),
      'add' => '4.2',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
  ],
];
