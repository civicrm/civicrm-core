<?php

use CRM_riverlea_ExtensionUtil as E;

return [
  [
    'name' => 'RiverleaStream_Minetta',
    'entity' => 'RiverleaStream',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'minetta',
        'description' => 'Generic CiviCRM UI, somewhat familiar to users of CiviCRM since 2014. Named after Minetta Creek, which runs under Greenwich, New York',
        'label' => E::ts('Minetta'),
        'is_reserved' => TRUE,
        'extension' => 'riverlea',
        'file_prefix' => 'streams/minetta/',
        'css_file' => 'css/_variables.css',
        'css_file_dark' => 'css/_dark.css',
        'vars' => [
          '--crm-version' => "'Minetta, v' var(--crm-release)",
        ],
      ],
      'match' => ['name'],
    ],
  ],
];
