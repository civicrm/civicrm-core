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
        'title' => E::ts('Search Display Type'),
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
        'description' => E::ts('A table displays results in rows and columns, with an optional pager and selectable actions. The styling of rows and columns is highly configurable.'),
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
        'description' => E::ts('Lists are highly flexible and display results on one or more lines. Each field can be styled differently or given a custom css class.'),
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
        'description' => E::ts('Grid displays are useful for image gallery thumbnails and other content to show side-by side.'),
        'icon' => 'fa-th',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'domain_id' => NULL,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
  [
    'name' => 'SearchDisplayType:autocomplete',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'search_display_type',
        'value' => 'autocomplete',
        'name' => 'crm-search-display-autocomplete',
        'label' => E::ts('Autocomplete'),
        'description' => E::ts('Autocompletes are form fields that give results from this search as the user types. Creating a display is optional and allows customization of how each result appears in the dropdown.'),
        'icon' => 'fa-keyboard-o',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'domain_id' => NULL,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
