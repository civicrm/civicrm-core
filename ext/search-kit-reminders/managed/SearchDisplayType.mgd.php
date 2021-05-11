<?php
// Adds option group for SearchDisplay.type

// Install search display if searchkit is installed.
if (!civicrm_api3('Extension', 'getcount', [
    'full_name' => 'org.civicrm.search_kit',
    'status' => 'installed',
  ])
  && !civicrm_api3('Extension', 'getcount', [
    // Once we are fully on 5.38 we only need to check search_kit
    // as the renaming will be complete.
    'full_name' => 'org.civicrm.search',
    'status' => 'installed',
  ])
) {
  return [];
}

return [
  [
    'name' => 'SearchDisplayType:schedulable',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'search_display_type',
      'value' => 'schedulable',
      'name' => 'crm-search-display-schedulable',
      'label' => 'Schedulable',
      'icon' => 'fa-calendar-times-o',
    ],
  ],
];
