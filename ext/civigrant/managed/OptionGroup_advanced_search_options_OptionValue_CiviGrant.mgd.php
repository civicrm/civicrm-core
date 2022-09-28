<?php
use CRM_Grant_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_advanced_search_options_OptionValue_CiviGrant',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'advanced_search_options',
        'label' => E::ts('Grants'),
        'value' => '12',
        'name' => 'CiviGrant',
        'grouping' => NULL,
        'filter' => 0,
        'is_default' => FALSE,
        'weight' => 14,
        'description' => NULL,
        'is_optgroup' => FALSE,
        'is_reserved' => FALSE,
        'is_active' => TRUE,
        'icon' => NULL,
        'color' => NULL,
        'component_id' => NULL,
        'domain_id' => NULL,
        'visibility_id' => NULL,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
