<?php
// Autoloader data for search actions.
return [
  'js' => [
    'ang/crmSearchActions.module.js',
    'ang/crmSearchActions/*.js',
    'ang/crmSearchActions/*/*.js',
  ],
  'partials' => [
    'ang/crmSearchActions',
  ],
  'basePages' => [],
  'requires' => ['crmUi', 'crmUtil', 'dialogService', 'api4'],
  'settingsFactory' => ['\Civi\Search\Actions', 'getActionSettings'],
  'permissions' => ['edit groups', 'administer reserved groups'],
];
