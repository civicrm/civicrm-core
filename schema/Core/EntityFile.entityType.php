<?php

return [
  'name' => 'EntityFile',
  'table' => 'civicrm_entity_file',
  'class' => 'CRM_Core_DAO_EntityFile',
  'getInfo' => fn() => [
    'title' => ts('Entity File'),
    'title_plural' => ts('Entity Files'),
    'description' => ts('Attaches (joins) uploaded files (images, documents, etc.) to entities (Contacts, Groups, Actions).'),
    'log' => TRUE,
    'add' => '1.5',
  ],
  'getIndices' => fn() => [
    'UI_entity_id_entity_table_file_id' => [
      'fields' => [
        'entity_id' => TRUE,
        'entity_table' => TRUE,
        'file_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Entity File ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('primary key'),
      'add' => '1.5',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('physical tablename for entity being joined to file, e.g. civicrm_contact'),
      'add' => '1.5',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_File', 'getEntityTables'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to entity table specified in entity_table column.'),
      'add' => '1.5',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'file_id' => [
      'title' => ts('File ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to civicrm_file'),
      'add' => '1.5',
      'input_attrs' => [
        'label' => ts('File'),
      ],
      'entity_reference' => [
        'entity' => 'File',
        'key' => 'id',
      ],
    ],
  ],
];
