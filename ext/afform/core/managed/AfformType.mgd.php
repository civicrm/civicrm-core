<?php

use CRM_Afform_ExtensionUtil as E;

// Adds option group for Afform.type
return [
  [
    'name' => 'AfformType',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afform_type',
        'title' => E::ts('Afform Type'),
        'is_reserved' => TRUE,
        'option_value_fields' => [
          'name',
          'label',
          'icon',
          'description',
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'AfformType:form',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_type',
        'name' => 'form',
        'value' => 'form',
        'label' => E::ts('Submission Form'),
        'icon' => 'fa-list-alt',
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformType:search',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_type',
        'name' => 'search',
        'value' => 'search',
        'label' => E::ts('Search Form'),
        'icon' => 'fa-search',
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformType:block',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_type',
        'name' => 'block',
        'value' => 'block',
        'label' => E::ts('Field Block'),
        'icon' => 'fa-th-large',
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformType:system',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_type',
        'name' => 'system',
        'value' => 'system',
        'label' => E::ts('System Form'),
        'icon' => 'fa-lock',
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
