<?php
// Autoloader data for Api4 explorer.
return [
  'js' => [
    'ang/api4Explorer.js',
    'ang/api4Explorer/*.js',
    'ang/api4Explorer/*/*.js',
    'lib/*.js',
  ],
  'css' => [
    'css/explorer.css',
  ],
  'partials' => [
    'ang/api4Explorer',
  ],
  'basePages' => [],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'crmRouteBinder', 'ui.sortable', 'api4'],
];
