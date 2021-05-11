<?php
// Module for rendering Table Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplaySchedulable.module.js',
    'ang/crmSearchDisplaySchedulable/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplaySchedulable',
  ],
  'basePages' => ['civicrm/search', 'civicrm/admin/search'],
  'requires' => ['crmSearchDisplay', 'crmUi', 'crmSearchTasks', 'ui.bootstrap'],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'crm-search-display-schedulable' => 'E',
  ],
];
