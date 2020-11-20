<?php
// Search Admin module - for composing & saving searches & displays.
return [
  'js' => [
    'ang/crmSearchAdmin.module.js',
    'ang/crmSearchAdmin/*.js',
    'ang/crmSearchAdmin/*/*.js',
  ],
  'css' => [
    'css/*.css',
  ],
  'partials' => [
    'ang/crmSearchAdmin',
  ],
  'basePages' => ['civicrm/admin/search'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'ui.sortable', 'ui.bootstrap', 'api4', 'crmSearchActions', 'crmSearchKit', 'crmRouteBinder'],
  'settingsFactory' => ['\Civi\Search\Admin', 'getAdminSettings'],
];
