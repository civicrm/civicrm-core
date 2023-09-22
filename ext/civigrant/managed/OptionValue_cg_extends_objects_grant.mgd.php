<?php
use CRM_Grant_ExtensionUtil as E;

// This enables custom fields for Grant entities
return [
  [
    'name' => 'cg_extend_objects:Grant',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'cg_extend_objects',
        'label' => E::ts('Grants'),
        'value' => 'Grant',
        'name' => 'civicrm_grant',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'grouping' => 'grant_type_id',
      ],
      'match' => [
        'name',
        'option_group_id',
      ],
    ],
  ],
];
