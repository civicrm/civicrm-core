<?php

return [
  'name' => 'Mapping',
  'table' => 'civicrm_mapping',
  'class' => 'CRM_Core_DAO_Mapping',
  'getInfo' => fn() => [
    'title' => ts('Field Mapping'),
    'title_plural' => ts('Field Mappings'),
    'description' => ts('Store field mappings in import or export for reuse'),
    'add' => '1.2',
    'label_field' => 'name',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/mapping/edit?reset=1&action=add',
    'browse' => 'civicrm/admin/mapping?reset=1',
    'update' => 'civicrm/admin/mapping/edit?reset=1&action=update&id=[id]',
  ],
  'getIndices' => fn() => [
    'UI_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.2',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mapping ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Mapping ID'),
      'add' => '1.2',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Mapping Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Unique name of Mapping'),
      'add' => '1.2',
    ],
    'description' => [
      'title' => ts('Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Description of Mapping.'),
      'add' => '1.2',
    ],
    'mapping_type_id' => [
      'title' => ts('Mapping Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Mapping Type'),
      'add' => '2.1',
      'pseudoconstant' => [
        'option_group_name' => 'mapping_type',
      ],
    ],
  ],
];
