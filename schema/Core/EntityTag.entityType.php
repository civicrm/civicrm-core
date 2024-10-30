<?php

return [
  'name' => 'EntityTag',
  'table' => 'civicrm_entity_tag',
  'class' => 'CRM_Core_DAO_EntityTag',
  'getInfo' => fn() => [
    'title' => ts('Entity Tag'),
    'title_plural' => ts('Entity Tags'),
    'description' => ts('Tag entities (Contacts, Groups, Actions) to categories.'),
    'log' => TRUE,
    'add' => '1.1',
  ],
  'getIndices' => fn() => [
    'UI_entity_id_entity_table_tag_id' => [
      'fields' => [
        'entity_id' => TRUE,
        'entity_table' => TRUE,
        'tag_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '3.4',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Entity Tag ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('primary key'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('physical tablename for entity being joined to file, e.g. civicrm_contact'),
      'add' => '3.2',
      'pseudoconstant' => [
        'option_group_name' => 'tag_used_for',
      ],
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to entity table specified in entity_table column.'),
      'add' => '3.2',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'tag_id' => [
      'title' => ts('Tag ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('FK to civicrm_tag'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Tag'),
        'control_field' => 'entity_table',
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_tag',
        'key_column' => 'id',
        'name_column' => 'name',
        'label_column' => 'label',
        'description_column' => 'description',
        'color_column' => 'color',
        'condition_provider' => ['CRM_Core_BAO_EntityTag', 'alterTagOptions'],
      ],
      'entity_reference' => [
        'entity' => 'Tag',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
