<?php
// Autoloader data for Api4 explorer.
return [
  'ext' => 'civicrm',
  'js' => [
    'ang/api4Explorer.js',
    'ang/api4Explorer/Explorer.js',
  ],
  'css' => [
    'css/api4-explorer.css',
  ],
  'partials' => [
    'ang/api4Explorer',
  ],
  'basePages' => [],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'crmRouteBinder', 'ui.sortable', 'api4', 'ngSanitize', 'dialogService', 'checklist-model'],
];
