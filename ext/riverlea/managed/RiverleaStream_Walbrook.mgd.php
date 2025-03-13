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
        'description' => 'Based on Shoreditch theme. Named after after River Walbrook, which runs under Shoreditch, London',
        'label' => E::ts('Walbrook'),
        'is_reserved' => TRUE,
        'extension' => 'riverlea',
        'file_prefix' => 'streams/walbrook/',
        'css_file' => 'css/_variables.css',
        'css_file_dark' => 'css/_dark.css',
        'vars' => [],
      ],
      'match' => ['name'],
    ],
  ],
];
