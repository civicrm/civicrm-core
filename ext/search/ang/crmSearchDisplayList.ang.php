<?php
// Module for rendering List Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplayList.module.js',
    'ang/crmSearchDisplayList/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayList',
  ],
  'basePages' => ['civicrm/search', 'civicrm/admin/search'],
  'requires' => ['crmSearchDisplay', 'crmUi', 'ui.bootstrap'],
  'exports' => [
    'crm-search-display-list' => 'E',
  ],
];
