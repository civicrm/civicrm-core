<?php

return [
  'name' => 'ImportTemplateField',
  'table' => 'civicrm_import_template_field',
  'class' => 'CRM_Core_DAO_ImportTemplateField',
  'getInfo' => fn() => [
    'title' => ts('Import Template Field'),
    'title_plural' => ts('Import Template Fields'),
    'description' => ts('Individual field for an import template'),
    'add' => '5.82',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Field ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '5.82',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'user_job_id' => [
      'title' => ts('Job ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Template to which this field belongs'),
      'add' => '5.82',
      'input_attrs' => [
        'label' => ts('Template'),
      ],
      'entity_reference' => [
        'entity' => 'UserJob',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'name' => [
      'title' => ts('Field Name'),
      'sql_type' => 'varchar(1024)',
      'input_type' => 'Text',
      'description' => ts('Template field key'),
      'add' => '5.82',
    ],
    'column_number' => [
      'title' => ts('Column Number to map to'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Column number for the import dataset'),
      'add' => '5.82',
    ],
    'default_value' => [
      'title' => ts('Default Value'),
      'sql_type' => 'varchar(1024)',
      'input_type' => 'Text',
      'description' => ts('SQL WHERE value for search-builder mapping fields.'),
      'add' => '5.82',
    ],
    'entity_data' => [
      'title' => ts('Entity Data'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Configuration data for the field'),
      'add' => '5.82',
      'default' => NULL,
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
  ],
];
