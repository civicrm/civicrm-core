<?php
use CRM_Mailing_ExtensionUtil as E;

// This enables custom fields for Mailing entities
return [
  [
    'name' => 'cg_extend_objects:Mailing',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'cg_extend_objects',
        'label' => E::ts('Mailing'),
        'value' => 'Mailing',
        'name' => 'civicrm_mailing',
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
