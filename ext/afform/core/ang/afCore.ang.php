<?php
// This file declares an Angular module which can be autoloaded
return [
  'js' => [
    'ang/afCore.js',
    'ang/afCore/*.js',
    'ang/afCore/*/*.js',
  ],
  'css' => ['ang/afCore.css'],
  'requires' => ['crmUi', 'crmUtil', 'api4', 'checklist-model', 'angularFileUpload', 'ngSanitize'],
  'partials' => ['ang/afCore'],
  'basePages' => [],
];
