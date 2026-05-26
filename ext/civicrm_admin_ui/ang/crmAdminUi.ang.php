<?php

// Angular module crmAdminUi.
// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
return [
  'js' => [
    'ang/crmAdminUi.js',
    'ang/crmAdminUi/*.js',
    'ang/crmAdminUi/*/*.js',
  ],
  'css' => [
    'ang/crmAdminUi.css',
  ],
  'partials' => ['ang/crmAdminUi'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute'],
  'settings' => [],
];
