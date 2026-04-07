<?php

use CRM_Afform_ExtensionUtil as E;

// Adds option group for Afform.confirmation type
return [
  [
    'name' => 'AfformConfirmationType',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afform_confirmation_type',
        'title' => E::ts('Afform Confirmation Type'),
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
    'name' => 'AfformConfirmationType:redirect_to_url',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_confirmation_type',
        'name' => 'redirect_to_url',
        'value' => 'redirect_to_url',
        'label' => E::ts('Redirect to a URL'),
        'icon' => 'fa-turn-up',
        'is_reserved' => TRUE,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformConfirmationType:afform_confirmation_type',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_confirmation_type',
        'name' => 'show_confirmation_message',
        'value' => 'show_confirmation_message',
        'label' => E::ts('Show confirmation message'),
        'icon' => 'fa-message',
        'is_reserved' => TRUE,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
