<?php
// Angular module oembedSharing.
// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
return [
  'js' => [
    'ang/crmOembedSharing.js',
    'ang/crmOembedSharing/*.js',
    'ang/crmOembedSharing/*/*.js',
  ],
  'css' => [
    'ang/crmOembedSharing.css',
  ],
  'partials' => [
    'ang/crmOembedSharing',
  ],
  'requires' => ['crmUi', 'crmUtil'],
  'basePages' => [],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'oembed-sharing' => 'A',
  ],
];
