<?php

use CRM_Afform_ExtensionUtil as E;

// Option group for Afform.placement field
return [
  [
    'name' => 'AfformPlacement',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'afform_placement',
        'title' => E::ts('Afform Placement'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
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
    'name' => 'AfformPlacement:dashboard_dashlet',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_placement',
        'name' => 'dashboard_dashlet',
        'value' => 'dashboard_dashlet',
        'label' => E::ts('Dashboard Dashlet'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-tachometer',
        'description' => E::ts('Allow CiviCRM users to add the form to their home dashboard.'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformPlacement:contact_summary_tab',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_placement',
        'name' => 'contact_summary_tab',
        'value' => 'contact_summary_tab',
        'label' => E::ts('Contact Summary Tab'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-address-card-o',
        'description' => E::ts('Add tab to contact summary page.'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformPlacement:contact_summary_block',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_placement',
        'name' => 'contact_summary_block',
        'value' => 'contact_summary_block',
        'label' => E::ts('Contact Summary Block'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-columns',
        'description' => E::ts('Add block to main contact summary tab.'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformPlacement:msg_token_single',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_placement',
        'name' => 'msg_token_single',
        'value' => 'msg_token_single',
        'label' => E::ts('Message Tokens'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-code',
        'description' => E::ts('Allows CiviMail authors to easily link to this page'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
