<?php

use CRM_CivicrmSearchUi_ExtensionUtil as E;

// Option for Afform.placement field
return [
  [
    'name' => 'AfformPlacement:reports',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_placement',
        'name' => 'reports',
        'value' => 'reports',
        'label' => E::ts('Reports Listing'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-list-alt',
        'description' => E::ts('Include in the SearchKit Reports listing in the UI'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
