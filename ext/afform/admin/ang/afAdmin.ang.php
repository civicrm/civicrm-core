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
  'settingsFactory' => ['CRM_AfformAdmin_Utils', 'getAdminSettings'],
  'basePages' => ['civicrm/admin/afform'],
  'bundles' => ['bootstrap3'],
];
