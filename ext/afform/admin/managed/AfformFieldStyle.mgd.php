<?php

use CRM_AfformAdmin_ExtensionUtil as E;

// Option group for Afform field styles
return [
  [
    'name' => 'AfformFieldStyle',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afform_field_style',
        'title' => E::ts('Afform Field Style'),
        'description' => NULL,
        'data_type' => 'String',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'is_locked' => FALSE,
        'option_value_fields' => ['label', 'grouping'],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'AfformFieldStyle:RadioButtons',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_field_style',
        'name' => 'radio_buttons',
        'value' => 'btn btn-default',
        'label' => E::ts('Buttons'),
        'grouping' => 'Radio',
        'description' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformFieldStyle:PlainCheckboxes',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_field_style',
        'name' => 'plain_checkboxes',
        'value' => 'checkbox-plain',
        'label' => E::ts('Plain'),
        'grouping' => 'CheckBox',
        'description' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
