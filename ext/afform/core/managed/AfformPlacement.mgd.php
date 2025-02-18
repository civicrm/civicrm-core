<?php

use CRM_Afform_ExtensionUtil as E;

// Defines OptionGroup for Afform.placement field
$placements = [
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
        'grouping' => 'Contact',
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
        'grouping' => 'Contact',
        'description' => E::ts('Add block to main contact summary tab.'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'AfformPlacement:contact_summary_actions',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_placement',
        'name' => 'contact_summary_actions',
        'value' => 'contact_summary_actions',
        'label' => E::ts('Contact Summary Actions'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-bars',
        'grouping' => 'Contact',
        // Indicates that a server_route is required for this placement
        'filter' => 1,
        'description' => E::ts('Add to the contact summary actions menu.'),
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

if (CRM_Core_Component::isEnabled('CiviCase')) {
  $placements[] = [
    'name' => 'AfformPlacement:case_summary_block',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_placement',
        'name' => 'case_summary_block',
        'value' => 'case_summary_block',
        'label' => E::ts('Case Summary'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-folder-open',
        'grouping' => 'Case,Contact',
        'description' => E::ts('Add to the Case Summary screen.'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ];
}

if (CRM_Core_Component::isEnabled('CiviEvent')) {
  $placements[] = [
    'name' => 'AfformPlacement:event_manage_tab',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'afform_placement',
        'name' => 'event_manage_tab',
        'value' => 'event_manage_tab',
        'label' => E::ts('Manage Event Tab'),
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'icon' => 'fa-calendar',
        'grouping' => 'Event',
        'description' => E::ts('Add tab to event management page.'),
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ];
}

return $placements;
