<?php
// Autoloader data for Api4 Explorer Angular module.
return [
  'ext' => 'civicrm',
  'js' => [
    'ang/api4Explorer.js',
    'ang/api4Explorer/*.js',
  ],
  'css' => [
    'css/api4-explorer.css',
  ],
  'partials' => [
    'ang/api4Explorer',
  ],
  'basePages' => [],
  'bundles' => ['bootstrap3'],
  'permissions' => ['view debug output', 'edit groups', 'administer reserved groups'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'crmRouteBinder', 'ui.sortable', 'api4', 'ngSanitize'],
];
