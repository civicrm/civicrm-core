<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_role',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'role',
        'title' => E::ts('Role'),
        'description' => E::ts('A role is grants permissions to users in CiviCRM Standalone.'),
        'data_type' => 'Integer',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'is_locked' => FALSE,
        'option_value_fields' => [
          'label',
          'description',
          'icon',
          'color',
          'name',
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_role_OptionValue_admin',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'role',
        'label' => E::ts('Administrator'),
        'value' => '1',
        'name' => 'admin',
        'grouping' => NULL,
        'filter' => 0,
        'is_default' => FALSE,
        'description' => NULL,
        'is_optgroup' => FALSE,
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'component_id' => NULL,
        'domain_id' => NULL,
        'visibility_id' => NULL,
        'icon' => NULL,
        'color' => NULL,
      ],
      'match' => [
        'name',
        'option_group_id',
      ],
    ],
  ],
];
