<?php
// Export UI Angular module configuration
return [
  'ext' => 'civicrm',
  'js' => [
    'ang/exportui/*.js',
    'ang/exportui/*/*.js',
  ],
  'css' => [
    'ang/exportui.css',
  ],
  'partials' => [
    'ang/exportui',
  ],
  'basePages' => [],
  'requires' => [
    'crmUi',
    'crmUtil',
    'ui.sortable',
    'dialogService',
  ],
];
