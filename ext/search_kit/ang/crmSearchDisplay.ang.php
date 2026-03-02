<?php
// Search Display base module - provides search display wrapper and base services.
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
  // Search display types are added by Civi\Search\AngularDependencyInjector
  'requires' => ['api4', 'ngSanitize'],
  'exports' => [
    'crm-search-display' => 'E',
  ],
  'settingsFactory' => ['\Civi\Search\Display', 'getModuleSettings'],
];
