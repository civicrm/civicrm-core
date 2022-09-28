<?php
// Adds option group for SearchDisplay.type

return [
  [
    'name' => 'SearchDisplayType',
    'entity' => 'OptionGroup',
    'params' => [
      'name' => 'search_display_type',
      'title' => 'Search Display Type',
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
];
