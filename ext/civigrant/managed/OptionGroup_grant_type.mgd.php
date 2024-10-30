<?php
use CRM_Grant_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_grant_type',
    'entity' => 'OptionGroup',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'grant_type',
        'title' => E::ts('Grant Type'),
        'description' => NULL,
        'data_type' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'is_locked' => FALSE,
      ],
      'match' => ['name'],
    ],
  ],
];
