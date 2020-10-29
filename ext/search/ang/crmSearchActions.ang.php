<?php
// Search actions module - provides dropdown menu with bulk actions to take on selected rows.
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
  'requires' => ['crmUi', 'crmUtil', 'dialogService', 'api4', 'crmSearchKit'],
  'settingsFactory' => ['\Civi\Search\Actions', 'getActionSettings'],
  'permissions' => ['edit groups', 'administer reserved groups'],
];
