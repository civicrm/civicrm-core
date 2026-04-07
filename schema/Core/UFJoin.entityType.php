<?php

return [
  'name' => 'UFJoin',
  'table' => 'civicrm_uf_join',
  'class' => 'CRM_Core_DAO_UFJoin',
  'getInfo' => fn() => [
    'title' => ts('Profile Use'),
    'title_plural' => ts('Profile Uses'),
    'description' => ts('Profile join table (formerly User Framework Join). Links various internal CiviCRM entities with a profile, such as which Contribution Pages embed a specific Profile.'),
    'log' => TRUE,
    'add' => '1.3',
  ],
  'getIndices' => fn() => [
    'index_entity' => [
      'fields' => [
        'entity_table' => TRUE,
        'entity_id' => TRUE,
      ],
      'add' => '1.3',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('UF Join ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique table ID'),
      'add' => '1.3',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'is_active' => [
      'title' => ts('Profile Use is active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this join currently active?'),
      'add' => '1.3',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'module' => [
      'title' => ts('Profile Module'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Module which owns this uf_join instance, e.g. User Registration, CiviDonate, etc.'),
      'add' => '1.3',
    ],
    'entity_table' => [
      'title' => ts('Profile Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('Name of table where item being referenced is stored. Modules which only need a single collection of uf_join instances may choose not to populate entity_table and entity_id.'),
      'add' => '1.3',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_UFJoin', 'entityTables'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Profile Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Foreign key to the referenced item.'),
      'add' => '1.3',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'weight' => [
      'title' => ts('Order'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Controls display order when multiple user framework groups are setup for concurrent display.'),
      'add' => '1.3',
      'default' => 1,
    ],
    'uf_group_id' => [
      'title' => ts('Profile ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Which form does this field belong to.'),
      'add' => '1.3',
      'input_attrs' => [
        'label' => ts('Profile'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_uf_group',
        'key_column' => 'id',
        'label_column' => 'title',
      ],
      'entity_reference' => [
        'entity' => 'UFGroup',
        'key' => 'id',
      ],
    ],
    'module_data' => [
      'title' => ts('Profile Use Data'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('Json serialized array of data used by the ufjoin.module'),
      'add' => '4.5',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
  ],
];
