<?php

// Angular module afCheckout.
// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
return [
  'js' => [
    'ang/afCheckout.js',
    'ang/afCheckout/*.js',
    'ang/afCheckout/*/*.js',
  ],
  'css' => [
    'ang/afCheckout.css',
  ],
  'partials' => ['ang/afCheckout'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute'],
  'settingsFactory' => ['Civi\Checkout\Afform', 'getSettings'],
  'exports' => [
    'af-checkout-block' => 'E',
  ],
];
