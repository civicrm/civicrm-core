<?php
use CRM_Search_ExtensionUtil as E;

// Adds option value to `tag_used_for`, allowing Saved Searches to be tagged
return [
  [
    'name' => 'SavedSearch:tag_used_for',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'tag_used_for',
        'value' => 'civicrm_saved_search',
        'name' => 'SavedSearch',
        'label' => E::ts('Saved Searches'),
        'is_reserved' => FALSE,
        'is_active' => TRUE,
        'domain_id' => NULL,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
