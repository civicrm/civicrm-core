<?php
// Search Display base module - provides services used commonly by search display implementations.
return [
  'js' => [
    'ang/crmSearchDisplay.module.js',
    'ang/crmSearchDisplay/*.js',
    'ang/crmSearchDisplay/*/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplay',
  ],
  'css' => [
    'css/crmSearchDisplay.css',
  ],
  'basePages' => [],
  'requires' => ['api4', 'ngSanitize'],
  'exports' => [
    'crm-search-display-table' => 'E',
  ],
];
