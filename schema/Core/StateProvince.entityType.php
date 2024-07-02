<?php

return [
  'name' => 'StateProvince',
  'table' => 'civicrm_state_province',
  'class' => 'CRM_Core_DAO_StateProvince',
  'getInfo' => fn() => [
    'title' => ts('State/Province'),
    'title_plural' => ts('States/Provinces'),
    'description' => ts('Table containing a list of states/provinces for all countries'),
    'add' => '1.1',
    'label_field' => 'name',
  ],
  'getIndices' => fn() => [
    'UI_name_country_id' => [
      'fields' => [
        'name' => TRUE,
        'country_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('State ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('State/Province ID'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('State'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Name of State/Province'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'abbreviation' => [
      'title' => ts('State Abbreviation'),
      'sql_type' => 'varchar(4)',
      'input_type' => 'Text',
      'description' => ts('2-4 Character Abbreviation of State/Province'),
      'add' => '1.1',
    ],
    'country_id' => [
      'title' => ts('Country ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('ID of Country that State/Province belong'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Country'),
      ],
      'entity_reference' => [
        'entity' => 'Country',
        'key' => 'id',
      ],
    ],
    'is_active' => [
      'title' => ts('StateProvince Is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this StateProvince active?'),
      'add' => '5.35',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
  ],
];
