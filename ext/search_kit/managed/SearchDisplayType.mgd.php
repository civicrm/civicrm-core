<?php
// Adds option group for SearchDisplay.type

return [
  [
    'name' => 'SearchDisplayType',
    'entity' => 'OptionGroup',
    'update' => 'always',
    'cleanup' => 'always',
    'params' => [
      'name' => 'search_display_type',
      'title' => 'Search Display Type',
      'option_value_fields' => ['name', 'label', 'icon', 'description'],
    ],
  ],
  [
    'name' => 'SearchDisplayType:table',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'search_display_type',
      'value' => 'table',
      'name' => 'crm-search-display-table',
      'label' => 'Table',
      'icon' => 'fa-table',
    ],
  ],
  [
    'name' => 'SearchDisplayType:list',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'search_display_type',
      'value' => 'list',
      'name' => 'crm-search-display-list',
      'label' => 'List',
      'icon' => 'fa-list',
    ],
  ],
  [
    'name' => 'SearchDisplayType:grid',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'search_display_type',
      'value' => 'grid',
      'name' => 'crm-search-display-grid',
      'label' => 'Grid',
      'icon' => 'fa-th',
    ],
  ],
];
