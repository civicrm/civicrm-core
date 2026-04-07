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
  'permissions' => [
    'administer afform',
    'manage own afform',
    // Used to check permissions by afGuiSearchDisplay component
    'all CiviCRM permissions and ACLs',
    'administer search_kit',
    'manage own search_kit',
  ],
];
