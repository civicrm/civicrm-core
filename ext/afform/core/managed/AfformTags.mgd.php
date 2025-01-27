<?php

use CRM_Afform_ExtensionUtil as E;

// Option group for Afform.tags field
return [
  [
    'name' => 'AfformTags',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afform_tags',
        'title' => E::ts('Afform Tags'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'option_value_fields' => [
          'name',
          'label',
          'icon',
          'description',
          'color',
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'AfformTags:donor_journey',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_tags',
        'name' => 'donor_journey',
        'value' => 'donor_journey',
        'label' => E::ts('Donor Journey'),
        'is_reserved' => FALSE,
        'is_active' => TRUE,
        'icon' => 'fa-coins',
        'description' => E::ts('Forms relating to donor journey.'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
