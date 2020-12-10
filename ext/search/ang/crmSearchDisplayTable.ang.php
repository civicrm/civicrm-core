<?php
// Module for rendering Table Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplayTable.module.js',
    'ang/crmSearchDisplayTable/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayTable',
  ],
  'basePages' => ['civicrm/search', 'civicrm/admin/search'],
  'requires' => ['crmSearchDisplay', 'crmUi', 'crmSearchActions', 'ui.bootstrap'],
  'exports' => [
    'crm-search-display-table' => 'E',
  ],
];
