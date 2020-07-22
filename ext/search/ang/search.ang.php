<?php
// Autoloader data for search builder.
return [
  'js' => [
    'ang/*.js',
    'ang/search/*.js',
    'ang/search/*/*.js',
  ],
  'css' => [
    'css/*.css',
  ],
  'partials' => [
    'ang/search',
  ],
  'basePages' => [],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'crmRouteBinder', 'ui.sortable', 'ui.bootstrap', 'dialogService', 'api4'],
];
