<?php

use CRM_Afform_ExtensionUtil as E;

// Option group for Afform container styles
return [
  [
    'name' => 'AfformContainerStyle',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afform_container_style',
        'title' => E::ts('Afform Container Style'),
        'description' => NULL,
        'data_type' => 'String',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'is_locked' => FALSE,
        'option_value_fields' => [
          'label',
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'AfformContainerStyle:Processed',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_container_style',
        'name' => 'afform_container_style_pane',
        'value' => 'af-container-style-pane',
        'label' => E::ts('Panel Pane'),
        'description' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => ['option_group_id', 'value'],
    ],
  ],
];
