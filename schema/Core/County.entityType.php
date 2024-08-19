<?php

return [
  'name' => 'County',
  'table' => 'civicrm_county',
  'class' => 'CRM_Core_DAO_County',
  'getInfo' => fn() => [
    'title' => ts('County'),
    'title_plural' => ts('Counties'),
    'description' => ts('Table that contains a list of counties (if populated)'),
    'add' => '1.1',
    'label_field' => 'name',
  ],
  'getIndices' => fn() => [
    'UI_name_state_id' => [
      'fields' => [
        'name' => TRUE,
        'state_province_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('County ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('County ID'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('County'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Name of County'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'abbreviation' => [
      'title' => ts('County Abbreviation'),
      'sql_type' => 'varchar(4)',
      'input_type' => 'Text',
      'description' => ts('2-4 Character Abbreviation of County'),
      'add' => '1.1',
    ],
    'state_province_id' => [
      'title' => ts('State ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('ID of State/Province that County belongs'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('State'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_state_province',
        'key_column' => 'id',
        'label_column' => 'name',
        'abbr_column' => 'abbreviation',
      ],
      'entity_reference' => [
        'entity' => 'StateProvince',
        'key' => 'id',
      ],
    ],
    'is_active' => [
      'title' => ts('County Is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this County active?'),
      'add' => '5.35',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
  ],
];
