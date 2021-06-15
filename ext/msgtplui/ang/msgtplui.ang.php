<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n
return [
  'js' => [
    'ang/msgtplui.js',
    'ang/msgtplui/*.js',
    'ang/msgtplui/*/*.js',
  ],
  'css' => [
    'ang/msgtplui.css',
  ],
  'partials' => [
    'ang/msgtplui',
  ],
  'bundles' => [
    'bootstrap3',
  ],
  'requires' => [
    'crmUi',
    'crmUtil',
    'ngRoute',
    'ngSanitize',
    'api4',
  ],
  'settings' => [],
  'basePages' => [],
  'permissions' => [
    'edit message templates',
    'edit user-driven message templates',
    'edit system workflow message templates',
    'access CiviMail',
  ],
];
