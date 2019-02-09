<?php
// This file declares an Angular module which can be autoloaded

return [
  'js' => [
    'ang/afformGui.js',
    'ang/afformGui/*.js',
    'ang/afformGui/*/*.js',
  ],
  'css' => ['ang/afformGui.css'],
  'partials' => ['ang/afformGui'],
  'settings' => [],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'ui.sortable', 'api4'],
];
