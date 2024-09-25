<?php

return [
  'name' => 'Cache',
  'table' => 'civicrm_cache',
  'class' => 'CRM_Core_DAO_Cache',
  'getInfo' => fn() => [
    'title' => ts('Cache'),
    'title_plural' => ts('Caches'),
    'description' => ts('Table to cache items for civicrm components.'),
    'add' => '2.1',
  ],
  'getIndices' => fn() => [
    'UI_group_name_path' => [
      'fields' => [
        'group_name' => TRUE,
        'path' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '5.61',
    ],
    'index_expired_date' => [
      'fields' => [
        'expired_date' => TRUE,
      ],
      'add' => '5.61',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Cache ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique table ID'),
      'add' => '2.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'group_name' => [
      'title' => ts('Group Name'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('group name for cache element, useful in cleaning cache elements'),
      'add' => '2.1',
    ],
    'path' => [
      'title' => ts('Path'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Unique path name for cache element'),
      'add' => '2.1',
    ],
    'data' => [
      'title' => ts('Data'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('data associated with this path'),
      'add' => '2.1',
    ],
    'component_id' => [
      'title' => ts('Component ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Component that this menu item belongs to'),
      'add' => '2.1',
      'input_attrs' => [
        'label' => ts('Component'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_component',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'Component',
        'key' => 'id',
      ],
    ],
    'created_date' => [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('When was the cache item created'),
      'add' => '2.1',
      'required' => TRUE,
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'expired_date' => [
      'title' => ts('Expired Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('When should the cache item expire'),
      'add' => '2.1',
      'default' => NULL,
    ],
  ],
];
