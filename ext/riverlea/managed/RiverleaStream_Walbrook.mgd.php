<?php

use CRM_riverlea_ExtensionUtil as E;

return [
  [
    'name' => 'RiverleaStream_Walbrook',
    'entity' => 'RiverleaStream',
    'update' => 'unmodified',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'walbrook',
        'label' => E::ts('Walbrook'),
        'description' => 'Named after after River Walbrook, which runs under Shoreditch, London.',
        'is_reserved' => FALSE,
        'extension' => E::SHORT_NAME,
        'file_prefix' => 'streams/walbrook/',
        'css_file' => '_main.css',
        'css_file_dark' => '_dark.css',
      ],
      'match' => ['name'],
    ],
  ],
];
