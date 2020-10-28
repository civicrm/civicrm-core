<?php
// Autoloader data for search actions.
return [
  'js' => [
    'ang/searchActions.module.js',
    'ang/searchActions/*.js',
    'ang/searchActions/*/*.js',
  ],
  'partials' => [
    'ang/searchActions',
  ],
  'basePages' => [],
  'requires' => ['crmUi', 'crmUtil', 'dialogService', 'api4'],
  'settingsFactory' => ['\Civi\Search\Actions', 'getActionSettings'],
  'permissions' => ['edit groups', 'administer reserved groups'],
];
