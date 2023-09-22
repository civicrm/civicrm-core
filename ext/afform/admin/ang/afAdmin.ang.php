<?php
// Angular module for afform gui editor
return [
  'js' => [
    'ang/afAdmin.js',
    'ang/afAdmin/*.js',
    'ang/afAdmin/*/*.js',
  ],
  'css' => [],
  'partials' => ['ang/afAdmin'],
  'requires' => ['api4', 'afGuiEditor', 'crmRouteBinder'],
  'settingsFactory' => ['Civi\AfformAdmin\AfformAdminMeta', 'getAdminSettings'],
  'basePages' => ['civicrm/admin/afform'],
  'bundles' => ['bootstrap3'],
];
