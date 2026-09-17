<?php

return [
  [
    'name' => 'OptionGroup_thankyou_mode',
    'entity' => 'OptionGroup',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'thankyou_mode',
        'title' => ts('Thank-You Modes'),
        'description' => ts('Thank-you options for Contribution Pages'),
        'data_type' => 'String',
        'is_reserved' => FALSE,
        'option_value_fields' => ['name', 'label', 'description'],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'OptionGroup_thankyou_mode_OptionValue_Page',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'thankyou_mode',
        'label' => ts('Page'),
        'value' => 'page',
        'name' => 'Page',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
  [
    'name' => 'OptionGroup_thankyou_mode_OptionValue_Redirect',
    'entity' => 'OptionValue',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'thankyou_mode',
        'label' => ts('Redirect'),
        'value' => 'redirect',
        'name' => 'Redirect',
      ],
      'match' => [
        'option_group_id',
        'name',
        'value',
      ],
    ],
  ],
];
