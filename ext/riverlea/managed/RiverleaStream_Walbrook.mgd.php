<?php

use CRM_riverlea_ExtensionUtil as E;

return [
  [
    'name' => 'RiverleaStream_Walbrook',
    'entity' => 'RiverleaStream',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'walbrook',
        'label' => E::ts('Walbrook'),
        'description' => 'Named after after River Walbrook, which runs under Shoreditch, London',
        'is_reserved' => TRUE,
        'extension' => E::SHORT_NAME,
        'file_prefix' => 'streams/walbrook/',
        'css_file' => '_main.css',
        'css_file_dark' => '_dark.css',
        'vars' => [],
      ],
      'match' => ['name'],
    ],
  ],
];
