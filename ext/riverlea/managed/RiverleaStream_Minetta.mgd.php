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
        'extension' => E::SHORT_NAME,
        'file_prefix' => 'streams/minetta/',
        'css_file' => '_variables.css',
        'css_file_dark' => '_dark.css',
        'vars' => [
          '--crm-version' => "'Minetta, v' var(--crm-release)",
        ],
      ],
      'match' => ['name'],
    ],
  ],
];
