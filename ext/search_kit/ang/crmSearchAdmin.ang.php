<?php
// Search Admin module - for composing & saving searches & displays.
return [
  'js' => [
    'ang/crmSearchAdmin.module.js',
    'ang/crmSearchAdmin/*.js',
    'ang/crmSearchAdmin/*/*.js',
    'ang/crmSearchAdmin/*/*/*.js',
  ],
  'css' => [
    'css/crmSearchAdmin.css',
  ],
  'partials' => [
    'ang/crmSearchAdmin',
  ],
  'bundles' => ['bootstrap3'],
  'basePages' => ['civicrm/admin/search'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'ui.sortable', 'ui.bootstrap', 'api4', 'crmSearchTasks', 'crmRouteBinder', 'crmDialog', 'md5'],
  'settingsFactory' => ['\Civi\Search\Admin', 'getAdminSettings'],
  'permissions' => [
    // Note the super permission "all CiviCRM permissions and ACLs" is used in the JS layer to determine if users can create search displays that bypass ACLs
    'all CiviCRM permissions and ACLs',
    'administer CiviCRM',
    'administer afform',
    'view debug output',
    'schedule communications',
    'manage own search_kit',
  ],
];
