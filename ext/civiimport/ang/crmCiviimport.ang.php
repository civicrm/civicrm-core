<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n
return [
  'js' => [
    'ang/crmCiviimport.js',
    'ang/crmCiviimport/*.js',
    'ang/crmCiviimport/*/*.js',
  ],
  'css' => [
    'ang/crmCiviimport.css',
  ],
  'partials' => [
    'ang/crmCiviimport',
  ],
  'requires' => [
    'crmUi',
    'crmUtil',
    'ngRoute',
    'api4',
  ],
];
