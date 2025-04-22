<?php
use CRM_Afform_ExtensionUtil as E;

return [
  'name' => 'AfformSubmission',
  'table' => 'civicrm_afform_submission',
  'class' => 'CRM_Afform_DAO_AfformSubmission',
  'getInfo' => fn() => [
    'title' => E::ts('FormBuilder Submission'),
    'title_plural' => E::ts('FormBuilder Submissions'),
    'description' => E::ts('Recorded form submissions'),
    'log' => TRUE,
  ],
  'getPaths' => fn() => [
    'view' => '[afform_name:url]#?sid=[id]',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('Form Submission ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique Submission ID'),
      'add' => '5.41',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => E::ts('User Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'add' => '5.41',
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'afform_name' => [
      'title' => E::ts('Afform Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => E::ts('Name of submitted afform'),
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
    'data' => [
      'title' => E::ts('Submission Data'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('IDs of saved entities'),
      'add' => '5.41',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
    ],
    'submission_date' => [
      'title' => E::ts('Submission Date/Time'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'add' => '5.41',
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'status_id' => [
      'title' => E::ts('Submission Status'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => E::ts('fk to Afform Submission Status options in civicrm_option_values'),
      'add' => '5.66',
      'default' => 1,
      'pseudoconstant' => [
        'option_group_name' => 'afform_submission_status',
      ],
    ],
  ],
];
