<?php

use CRM_riverlea_ExtensionUtil as E;

return [
  [
    'name' => 'RiverleaStream_HackneyBrook',
    'entity' => 'RiverleaStream',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'hackneybrook',
        'label' => E::ts('Hackney Brook'),
        'description' => 'named after the Hackney Brook, a tributary of the River Lea that ran through Finsbury Park',
        'is_reserved' => TRUE,
        'extension' => 'riverlea',
        'file_prefix' => 'streams/hackneybrook/',
        'css_file' => 'css/_variables.css',
        'css_file_dark' => 'css/_dark.css',
        'vars' => [],
        'vars_dark' => [],
      ],
      'match' => ['name'],
    ],
  ],
];
