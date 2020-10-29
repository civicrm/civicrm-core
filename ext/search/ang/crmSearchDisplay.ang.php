<?php
// Search Display module - for rendering search displays.
return [
  'js' => [
    'ang/crmSearchDisplay.module.js',
    'ang/crmSearchDisplay/*.js',
    'ang/crmSearchDisplay/*/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplay',
  ],
  'basePages' => [],
  'requires' => ['crmUi', 'api4', 'crmSearchActions', 'ui.bootstrap'],
  'exports' => [
    'crm-search-display-table' => 'E',
  ],
];
