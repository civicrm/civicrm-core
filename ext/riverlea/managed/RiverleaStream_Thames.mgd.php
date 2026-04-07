<?php

use CRM_riverlea_ExtensionUtil as E;

return [
  [
    'name' => 'RiverleaStream_Thames',
    'entity' => 'RiverleaStream',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'thames',
        'label' => E::ts('Thames'),
        'description' => 'A kindly theme with lots of blue tones. Successor to Aah.',
        'is_reserved' => TRUE,
        'extension' => E::SHORT_NAME,
        'file_prefix' => 'streams/thames/',
        'css_file' => '_main.css',
        'css_file_dark' => '_dark.css',
        'vars' => [],
      ],
      'match' => ['name'],
    ],
  ],
];
