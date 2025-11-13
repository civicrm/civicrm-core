<?php
use CRM_Afform_ExtensionUtil as E;

return [
  'name' => 'SearchParamSet',
  'table' => 'civicrm_search_param_set',
  'class' => 'CRM_Afform_DAO_SearchParamSet',
  'getInfo' => fn() => [
    'title' => E::ts('Search Settings Set'),
    'title_plural' => E::ts('Saved Search Settings'),
    'description' => E::ts('Save settings for a FormBuilder search form'),
    'label_column' => 'label',
  ],
  'getPaths' => fn() => [
    'view' => '[afform_name:url]#?_s=[id]',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('Search Param Set ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique Search Param Set ID'),
      'add' => '6.9',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'afform_name' => [
      'title' => E::ts('Afform Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => E::ts('Name of the form'),
      'add' => '5.41',
      'input_attrs' => [
        'maxlength' => 255,
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Afform_BAO_AfformSubmission', 'getAllAfformsByName'],
        'suffixes' => [
          'name',
          'label',
          'description',
          'abbr',
          'icon',
          'url',
        ],
      ],
    ],
    'label' => [
      'title' => ts('Label'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Editable label for this set of filters'),
    ],
    'filters' => [
      'title' => E::ts('Filters'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('JSON filter configuration'),
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
    'columns' => [
      'title' => E::ts('Columns'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('JSON of picked search display columns, indexed by search display'),
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
    'icon' => [
      'title' => ts('Icon'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Icon for this search param set'),
    ],
    'created_by' => [
      'title' => E::ts('Created By'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'readonly' => TRUE,
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'created_date' => [
      'title' => E::ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Text',
      'readonly' => TRUE,
      'description' => E::ts('When created.'),
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'modified_date' => [
      'title' => E::ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Text',
      'readonly' => TRUE,
      'description' => E::ts('When this search param set was last modified.'),
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],
  ],
];
