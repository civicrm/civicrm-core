<?php
// Search actions module - provides dropdown menu with bulk actions to take on selected rows.
return [
  'js' => [
    'ang/crmSearchTasks.module.js',
    'ang/crmSearchTasks/*.js',
    'ang/crmSearchTasks/*/*.js',
  ],
  'partials' => [
    'ang/crmSearchTasks',
  ],
  'css' => [
    'css/crmSearchTasks.css',
  ],
  'basePages' => [],
  'requires' => ['crmUi', 'crmUtil', 'dialogService', 'api4', 'checklist-model', 'crmDialog'],
  'settingsFactory' => ['\Civi\Search\Actions', 'getActionSettings'],
  'permissions' => ['edit groups', 'administer reserved groups'],
];
