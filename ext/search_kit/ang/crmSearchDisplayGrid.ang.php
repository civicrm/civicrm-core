<?php
// Module for rendering List Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplayGrid.module.js',
    'ang/crmSearchDisplayGrid/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayGrid',
  ],
  'basePages' => ['civicrm/search', 'civicrm/admin/search'],
  'requires' => ['crmSearchDisplay', 'crmUi', 'ui.bootstrap'],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'crm-search-display-grid' => 'E',
  ],
];
