<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_report_type',
    'entity' => 'OptionGroup',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'report_type',
        'title' => E::ts('Report Types'),
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
    'name' => 'OptionGroup_report_type_OptionValue_afform',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'report_type',
        'label' => E::ts('SearchKit Report'),
        'value' => 'afform',
        'name' => 'afform',
        'grouping' => NULL,
        'filter' => 0,
        'is_default' => FALSE,
        'weight' => 30,
        'description' => E::ts('Reports created using SearchKit/FormBuilder'),
        'is_optgroup' => FALSE,
        'is_reserved' => FALSE,
        'is_active' => TRUE,
        'component_id' => NULL,
        'icon' => NULL,
        'color' => NULL,
        'domain_id' => NULL,
        'visibility_id' => NULL,
      ],
      'match' => ['name'],
    ],
  ],
];
