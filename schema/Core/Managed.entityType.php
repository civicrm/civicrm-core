<?php

return [
  'name' => 'Managed',
  'table' => 'civicrm_managed',
  'class' => 'CRM_Core_DAO_Managed',
  'getInfo' => fn() => [
    'title' => ts('Managed Record'),
    'title_plural' => ts('Managed Records'),
    'description' => ts('List of declaratively managed objects'),
    'log' => FALSE,
    'add' => '4.2',
  ],
  'getIndices' => fn() => [
    'UI_managed_module_name' => [
      'fields' => [
        'module' => TRUE,
        'name' => TRUE,
      ],
      'add' => '4.2',
    ],
    'UI_managed_entity' => [
      'fields' => [
        'entity_type' => TRUE,
        'entity_id' => TRUE,
      ],
      'add' => '4.2',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Managed ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Surrogate Key'),
      'add' => '4.2',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'module' => [
      'title' => ts('Module'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Name of the module which declared this object (soft FK to civicrm_extension.full_name)'),
      'add' => '4.2',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_Managed', 'getBaseModules'],
      ],
    ],
    'name' => [
      'title' => ts('Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Symbolic name used by the module to identify the object'),
      'add' => '4.2',
    ],
    'entity_type' => [
      'title' => ts('Entity Type'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('API entity type'),
      'add' => '4.2',
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Soft foreign key to the referenced item.'),
      'add' => '4.2',
    ],
    'checksum' => [
      'title' => ts('Checksum'),
      'sql_type' => 'varchar(45)',
      'input_type' => 'Text',
      'required' => FALSE,
      'description' => ts('Configuration of the managed-entity when last stored'),
      'add' => '6.2',
    ],
    'cleanup' => [
      'title' => ts('Cleanup Setting'),
      'sql_type' => 'varchar(16)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Policy on when to cleanup entity (always, never, unused)'),
      'add' => '4.5',
      'default' => 'always',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_ManagedEntities', 'getCleanupOptions'],
      ],
    ],
    'entity_modified_date' => [
      'title' => ts('Entity Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('When the managed entity was changed from its original settings.'),
      'add' => '5.45',
      'default' => NULL,
    ],
  ],
];
