<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n
return [
  'js' =>
    [
      0 => 'ang/mockBespoke.js',
      1 => 'ang/mockBespoke/*.js',
      2 => 'ang/mockBespoke/*/*.js',
    ],
  'css' =>
    [
      0 => 'ang/mockBespoke.css',
    ],
  'partials' =>
    [
      0 => 'ang/mockBespoke',
    ],
  'requires' =>
    [
      0 => 'crmUi',
      1 => 'crmUtil',
      2 => 'ngRoute',
    ],
];
