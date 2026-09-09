<?php

use CRM_AfformAdmin_ExtensionUtil as E;

// Option group for Afform field styles
$entities = [
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

// Radio "columns" styles share a common `af-radio-style-columns` base class (see afCore.css)
// plus a `af-radio-style-columns-<suffix>` modifier that sets the column behavior.
$radioColumnStyles = [
  'RadioColumns1' => ['name' => 'radio_columns_1', 'suffix' => '1', 'label' => E::ts('Single Column')],
  'RadioColumns2' => ['name' => 'radio_columns_2', 'suffix' => '2', 'label' => E::ts('Two Columns')],
  'RadioColumns3' => ['name' => 'radio_columns_3', 'suffix' => '3', 'label' => E::ts('Three Columns')],
  'RadioColumns3Responsive' => ['name' => 'radio_columns_3_responsive', 'suffix' => '3-responsive', 'label' => E::ts('Three Columns (responsive)')],
];
foreach ($radioColumnStyles as $entityKey => $style) {
  $entities[] = [
    'name' => "AfformFieldStyle:$entityKey",
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_field_style',
        'name' => $style['name'],
        'value' => "af-radio-style-columns af-radio-style-columns-{$style['suffix']}",
        'label' => $style['label'],
        'grouping' => 'Radio',
        'description' => NULL,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ];
}

return $entities;
