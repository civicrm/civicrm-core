<?php
// Module for rendering Tree Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplayTree.module.js',
    'ang/crmSearchDisplayTree/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayTree',
  ],
  'css' => [
    'css/crmSearchDisplayTree.css',
  ],
  'basePages' => ['civicrm/search', 'civicrm/admin/search'],
  'requires' => ['crmSearchDisplay', 'crmUi', 'crmSearchTasks', 'ui.bootstrap', 'ui.sortable'],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'crm-search-display-tree' => 'E',
  ],
];
