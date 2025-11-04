<?php

return [
  'name' => 'TranslationSource',
  'table' => 'civicrm_translation_source',
  'class' => 'CRM_Core_DAO_TranslationSource',
  'getInfo' => fn() => [
    'title' => ts('Translated Source String'),
    'title_plural' => ts('Translated Source Strings'),
    'description' => ts('A source reference for strings that should be translated.'),
    'log' => TRUE,
    'add' => '6.7.alpha1',
  ],
  'getIndices' => fn() => [
    'index_source_key' => [
      'fields' => [
        'source_key' => TRUE,
      ],
      'add' => '6.7.alpha1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Source ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Source ID'),
      'add' => '6.7.alpha1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'entity' => [
      'title' => ts('Translated Entity'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Table where referenced item is stored'),
      'add' => '6.7.alpha1',
      /*'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_Translation', 'getEntityTables'],
      ],*/
    ],
    'entity_field' => [
      'title' => ts('Translated Field'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => FALSE,
      'description' => ts('Field where referenced item is stored'),
      'add' => '6.7.alpha1',
      'pseudoconstant' => [
        'callback' => ['CRM_Core_BAO_Translation', 'getEntityFields'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Translated Entity ID'),
      'sql_type' => 'int',
      'input_type' => 'EntityRef',
      'required' => FALSE,
      'description' => ts('ID of the relevant entity.'),
      'add' => '6.7.alpha1',
      'entity_reference' => [
        'dynamic_entity' => 'entity',
        'key' => 'id',
      ],
    ],
    'context_key' => [
      'title' => ts('Context Key'),
      'sql_type' => 'char(22)',
      'required' => TRUE,
      'description' => ts('FIXME'),
      'add' => '6.7.alpha1',
    ],
    'source' => [
      'title' => ts('Source Text'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'required' => TRUE,
      'description' => ts('Source text for referencing translations'),
      'add' => '6.7.alpha1',
    ],
    'source_key' => [
      'title' => ts('Source Key'),
      'sql_type' => 'char(22)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('FIXME'),
      'add' => '6.7.alpha1',
    ],
  ],
];
