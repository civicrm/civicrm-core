<?php

use CRM_Afform_ExtensionUtil as E;

// Option group for Afform.placement field
return [
  [
    'name' => 'AfformPlacement:msg_token',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_placement',
        'name' => 'msg_token',
        'value' => 'msg_token',
        'label' => E::ts('Message Tokens (Login)'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-code',
        'description' => E::ts('Generate emails with login links for this form'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
