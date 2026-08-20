<?php

// Angular module afDummy.
// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
return [
  'js' => [
    'ang/afDummy.js',
    'ang/afDummy/*.js',
  ],
  'partials' => ['ang/afDummy'],
  'settings' => [],
  'requires' => ['afCheckout'],
  'exports' => [
    'af-dummy-checkout' => 'E',
  ],
];
