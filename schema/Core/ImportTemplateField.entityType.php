<?php

return [
  'name' => 'ImportTemplateField',
  'table' => 'civicrm_import_template_field',
  'class' => 'CRM_Core_DAO_ImportTemplateField',
  'getInfo' => fn() => [
    'title' => ts('Import Template Field'),
    'title_plural' => ts('Import Template Fields'),
    'description' => ts('Individual field for an import template'),
    'add' => '5.83',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Field ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '5.83',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'user_job_id' => [
      'title' => ts('Job ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Template to which this field belongs'),
      'add' => '5.83',
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
      'input_type' => 'Select',
      'description' => ts('Template field key'),
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_ImportTemplateField', 'getImportableFieldOptions'],
        'suffixes' => ['name', 'label', 'description'],
      ],
      'add' => '5.83',
    ],
    'column_number' => [
      'title' => ts('Column Number to map to'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Column number for the import dataset'),
      'add' => '5.83',
    ],
    'entity' => [
      'title' => ts('Entity'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('Import entity'),
      'add' => '5.83',
    ],
    'default_value' => [
      'title' => ts('Default Value'),
      'sql_type' => 'varchar(1024)',
      'input_type' => 'Text',
      'add' => '5.83',
    ],
    'data' => [
      'title' => ts('Data'),
      'sql_type' => 'text',
      'description' => ts('Configuration data for the field'),
      'add' => '5.83',
      'default' => NULL,
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
  ],
];
