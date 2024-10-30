<?php
// Module for recaptcha2 Afform element
return [
  'js' => [
    'ang/crmRecaptcha2.module.js',
    'ang/crmRecaptcha2/*.js',
  ],
  'partials' => [
    'ang/crmRecaptcha2',
  ],
  'css' => [],
  'basePages' => [],
  'requires' => [],
  'bundles' => [],
  'exports' => [
    // This triggers Afform to automatically require this module on forms using recaptcha2
    'crm-recaptcha2' => 'E',
  ],
];
