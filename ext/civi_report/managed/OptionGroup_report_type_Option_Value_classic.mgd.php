<?php
use CRM_Report_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_report_type_OptionValue_classic',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'report_type',
        'label' => E::ts('Classic CiviReport'),
        'value' => 'classic',
        'name' => 'classic',
        'grouping' => NULL,
        'filter' => 0,
        'is_default' => FALSE,
        'weight' => 30,
        'description' => E::ts('Report instances from classic CiviReport'),
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
