<?php

use CRM_riverlea_ExtensionUtil as E;

return [
  [
    'name' => 'RiverleaStream_HackneyBrook',
    'entity' => 'RiverleaStream',
    'update' => 'unmodified',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'hackneybrook',
        'label' => E::ts('Hackney Brook'),
        'description' => 'Named after the Hackney Brook, a tributary of the River Lea that ran through Finsbury Park.',
        'is_reserved' => FALSE,
        'extension' => E::SHORT_NAME,
        'file_prefix' => 'streams/hackneybrook/',
        'css_file' => '_main.css',
        'css_file_dark' => '_dark.css',
      ],
      'match' => ['name'],
    ],
  ],
];
