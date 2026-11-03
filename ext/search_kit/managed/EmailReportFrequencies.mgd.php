<?php
use CRM_Search_ExtensionUtil as E;

return [
  [
    'name' => 'OptionGroup_email_report_frequencies',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'email_report_frequencies',
        'title' => E::ts('Email Report Frequencies'),
        'description' => E::ts('Search Kit Email Report Frequencies'),
        'data_type' => 'String',
        'is_reserved' => FALSE,
        'option_value_fields' => [
          'name',
          'label',
          'description',
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'OptionValue_Weekly',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'email_report_frequencies',
        'label' => E::ts('Weekly'),
        'value' => 'weekly',
        'name' => 'Weekly',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'OptionValue_Daily',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'email_report_frequencies',
        'label' => E::ts('Daily'),
        'value' => 'daily',
        'name' => 'Daily',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'OptionValue_Monthly',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'email_report_frequencies',
        'label' => E::ts('Monthly'),
        'value' => 'monthly',
        'name' => 'Monthly',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'OptionValue_First',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'email_report_frequencies',
        'label' => E::ts('First of the Month'),
        'value' => 'first',
        'name' => 'First',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'OptionValue_Custom',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'email_report_frequencies',
        'label' => E::ts('Custom'),
        'value' => 'custom',
        'name' => 'Custom',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
];
