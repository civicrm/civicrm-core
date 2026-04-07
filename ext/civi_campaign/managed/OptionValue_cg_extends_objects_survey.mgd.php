<?php
use CRM_Campaign_ExtensionUtil as E;

// This enables custom fields for Survey entities
return [
  [
    'name' => 'cg_extend_objects:Survey',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'cg_extend_objects',
        'label' => E::ts('Survey'),
        'value' => 'Survey',
        'name' => 'civicrm_survey',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'grouping' => NULL,
      ],
      'match' => [
        'name',
        'option_group_id',
      ],
    ],
  ],
];
