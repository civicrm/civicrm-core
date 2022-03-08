<?php
return [
  [
    'name' => 'OptionGroup_grant_type',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'grant_type',
        'title' => 'Grant Type',
        'description' => NULL,
        'data_type' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'is_locked' => FALSE,
      ],
    ],
  ],
];
