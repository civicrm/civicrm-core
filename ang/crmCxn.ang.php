<?php
// This file declares an Angular module which can be autoloaded
return [
  'ext' => 'civicrm',
  'js' => ['ang/crmCxn.js', 'ang/crmCxn/*.js'],
  'css' => ['ang/crmCxn.css'],
  'partials' => ['ang/crmCxn'],
  'requires' => ['crmUtil', 'ngRoute', 'ngSanitize', 'crmUi', 'dialogService', 'crmResource'],
];
