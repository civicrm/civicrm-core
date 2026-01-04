<?php

return [
  'name' => 'EntityBatch',
  'table' => 'civicrm_entity_batch',
  'class' => 'CRM_Batch_DAO_EntityBatch',
  'getInfo' => fn() => [
    'title' => ts('Entity Batch'),
    'title_plural' => ts('Entity Batches'),
    'description' => ts('Batch of Entities typically used for batch data entry (ex: Contribution, Participants, Contacts)'),
    'add' => '3.3',
  ],
  'getIndices' => fn() => [
    'index_entity' => [
      'fields' => [
        'entity_table' => TRUE,
        'entity_id' => TRUE,
      ],
      'add' => '3.3',
    ],
    'UI_batch_entity' => [
      'fields' => [
        'batch_id' => TRUE,
        'entity_id' => TRUE,
        'entity_table' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '3.3',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('EntityBatch ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('primary key'),
      'add' => '3.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('EntityBatch Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('physical tablename for entity being joined to batch, e.g. civicrm_contact'),
      'add' => '3.3',
      'pseudoconstant' => [
        'option_group_name' => 'entity_batch_extends',
      ],
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to entity table specified in entity_table column.'),
      'add' => '3.3',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'batch_id' => [
      'title' => ts('Batch ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to civicrm_batch'),
      'add' => '3.3',
      'input_attrs' => [
        'label' => ts('Batch'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_batch',
        'key_column' => 'id',
        'label_column' => 'title',
      ],
      'entity_reference' => [
        'entity' => 'Batch',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
