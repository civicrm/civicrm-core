<?php
use CRM_Grant_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_mapping_type_OptionValue_Export Grant',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'mapping_type',
        'label' => E::ts('Export Grant'),
        'value' => '13',
        'name' => 'Export Grant',
        'grouping' => NULL,
        'filter' => 0,
        'is_default' => FALSE,
        'weight' => 13,
        'description' => NULL,
        'is_optgroup' => FALSE,
        'is_reserved' => TRUE,
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
