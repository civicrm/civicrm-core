<?php
use CRM_Grant_ExtensionUtil as E;

// This enables custom fields for Grant entities
return [
  [
    'name' => 'note_used_for:Grant',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'note_used_for',
        'label' => E::ts('Grants'),
        'value' => 'civicrm_grant',
        'name' => 'Grant',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => [
        'name',
        'option_group_id',
      ],
    ],
  ],
];
