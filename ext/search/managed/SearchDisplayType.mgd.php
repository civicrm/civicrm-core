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
      'name' => 'table',
      'value' => 'table',
      'label' => 'Table',
      'icon' => 'fa-table',
    ],
  ],
  [
    'name' => 'SearchDisplayType:list',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'search_display_type',
      'name' => 'list',
      'value' => 'list',
      'label' => 'List',
      'icon' => 'fa-list',
    ],
  ],
];
