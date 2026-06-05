<?php

use CRM_riverlea_ExtensionUtil as E;

return [
  [
    'name' => 'RiverleaStream_Minetta',
    'entity' => 'RiverleaStream',
    'update' => 'unmodified',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'minetta',
        'label' => E::ts('Minetta'),
        'description' => 'Familiar to users of CiviCRM since 2014. Named after Minetta Creek, which runs under Greenwich, New York.',
        'is_reserved' => FALSE,
        'extension' => E::SHORT_NAME,
        'file_prefix' => 'streams/minetta/',
        'css_file' => '_main.css',
        'css_file_dark' => '_dark.css',
      ],
      'match' => ['name'],
    ],
  ],
];
