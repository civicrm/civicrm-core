<?php
use CRM_Case_ExtensionUtil as E;

// This enables custom fields for Case entities
return [
  [
    'name' => 'cg_extend_objects:Case',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'cg_extend_objects',
        'label' => E::ts('Cases'),
        'value' => 'Case',
        'name' => 'civicrm_case',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'grouping' => 'case_type_id',
      ],
      'match' => [
        'name',
        'option_group_id',
      ],
    ],
  ],
];
