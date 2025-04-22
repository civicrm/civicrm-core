<?php

return [
  'name' => 'PrevNextCache',
  'table' => 'civicrm_prevnext_cache',
  'class' => 'CRM_Core_DAO_PrevNextCache',
  'getInfo' => fn() => [
    'title' => ts('Prev Next Cache'),
    'title_plural' => ts('Prev Next Caches'),
    'description' => ts('Table to cache items for navigation on civicrm searched results.'),
    'add' => '3.4',
  ],
  'getIndices' => fn() => [
    'index_all' => [
      'fields' => [
        'cachekey' => TRUE,
        'entity_id1' => TRUE,
        'entity_id2' => TRUE,
        'entity_table' => TRUE,
        'is_selected' => TRUE,
      ],
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Prev Next Cache ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '3.4',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Prev Next Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('physical tablename for entity being joined to discount, e.g. civicrm_event'),
      'add' => '3.4',
    ],
    'entity_id1' => [
      'title' => ts('Prev Next Entity ID 1'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('FK to entity table specified in entity_table column.'),
      'add' => '3.4',
    ],
    'entity_id2' => [
      'title' => ts('Prev Next Entity ID 2'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('FK to entity table specified in entity_table column.'),
      'add' => '3.4',
    ],
    'cachekey' => [
      'title' => ts('Cache Key'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Unique path name for cache element of the searched item'),
      'add' => '3.4',
    ],
    'data' => [
      'title' => ts('Prev Next Data'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('cached snapshot of the serialized data'),
      'add' => '3.4',
      'serialize' => CRM_Core_DAO::SERIALIZE_PHP,
    ],
    'is_selected' => [
      'title' => ts('Is Selected'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '4.2',
      'default' => FALSE,
    ],
  ],
];
