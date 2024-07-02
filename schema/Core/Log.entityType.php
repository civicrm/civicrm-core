<?php

return [
  'name' => 'Log',
  'table' => 'civicrm_log',
  'class' => 'CRM_Core_DAO_Log',
  'getInfo' => fn() => [
    'title' => ts('Log'),
    'title_plural' => ts('Logs'),
    'description' => ts('Log can be linked to any object in the application.'),
    'add' => '1.5',
  ],
  'getIndices' => fn() => [
    'index_entity' => [
      'fields' => [
        'entity_table' => TRUE,
        'entity_id' => TRUE,
      ],
      'add' => '1.5',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Log ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Log ID'),
      'add' => '1.5',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Name of table where item being referenced is stored.'),
      'add' => '1.5',
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Foreign key to the referenced item.'),
      'add' => '1.5',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'data' => [
      'title' => ts('Data'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Updates does to this object if any.'),
      'add' => '1.5',
    ],
    'modified_id' => [
      'title' => ts('Modified By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID of person under whose credentials this data modification was made.'),
      'add' => '1.5',
      'input_attrs' => [
        'label' => ts('Modified By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('When was the referenced entity created or modified or deleted.'),
      'add' => '1.5',
    ],
  ],
];
