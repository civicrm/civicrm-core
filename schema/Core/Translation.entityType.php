<?php

return [
  'name' => 'Translation',
  'table' => 'civicrm_translation',
  'class' => 'CRM_Core_DAO_Translation',
  'getInfo' => fn() => [
    'title' => ts('Translated String'),
    'title_plural' => ts('Translated Strings'),
    'description' => ts('Each string record is an alternate translation of some displayable string in the database.'),
    'log' => TRUE,
    'add' => '5.39',
  ],
  'getIndices' => fn() => [
    'index_entity_lang' => [
      'fields' => [
        'entity_id' => TRUE,
        'entity_table' => TRUE,
        'language' => TRUE,
      ],
      'add' => '5.39',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Translated String ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique String ID'),
      'add' => '5.39',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Translated Entity'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Table where referenced item is stored'),
      'add' => '5.39',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_Translation', 'getEntityTables'],
      ],
    ],
    'entity_field' => [
      'title' => ts('Translated Field'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Field where referenced item is stored'),
      'add' => '5.39',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_Translation', 'getEntityFields'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Translated Entity ID'),
      'sql_type' => 'int',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('ID of the relevant entity.'),
      'add' => '5.39',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'language' => [
      'title' => ts('Language'),
      'sql_type' => 'varchar(5)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Relevant language'),
      'add' => '5.39',
      'pseudoconstant' => [
        'option_group_name' => 'languages',
        'key_column' => 'name',
        'option_edit_path' => 'civicrm/admin/options/languages',
      ],
    ],
    'status_id' => [
      'title' => ts('Status'),
      'sql_type' => 'tinyint',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Specify whether the string is active, draft, etc'),
      'add' => '5.39',
      'default' => 1,
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_Translation', 'getStatuses'],
      ],
    ],
    'string' => [
      'title' => ts('Translated String'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'required' => FALSE,
      'description' => ts('Translated string'),
      'add' => '5.39',
    ],
  ],
];
