<?php
// Adds option group for SearchDisplay.type

return [
  [
    'name' => 'SavedSearch:tag_used_for',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'tag_used_for',
      'value' => 'civicrm_saved_search',
      'name' => 'SavedSearch',
      'label' => ts('Saved Searches'),
    ],
  ],
];
