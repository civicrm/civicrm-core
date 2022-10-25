<?php
use CRM_Search_ExtensionUtil as E;

// Adds option group for SearchDisplay.type
return [
  [
    'name' => 'SearchDisplayType',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'search_display_type',
        'title' => 'Search Display Type',
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
    'name' => 'SearchDisplayType:table',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'search_display_type',
        'value' => 'table',
        'name' => 'crm-search-display-table',
        'label' => E::ts('Table'),
        'icon' => 'fa-table',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'domain_id' => NULL,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'SearchDisplayType:list',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'search_display_type',
        'value' => 'list',
        'name' => 'crm-search-display-list',
        'label' => E::ts('List'),
        'icon' => 'fa-list',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'domain_id' => NULL,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'SearchDisplayType:grid',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'search_display_type',
        'value' => 'grid',
        'name' => 'crm-search-display-grid',
        'label' => E::ts('Grid'),
        'icon' => 'fa-th',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'domain_id' => NULL,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
