<?php
return [
  'js' => [
    'ang/crmLogin/crmLogin.js',
  ],
  'css' => ['ang/crmLogin/crmLogin.css'],
  'partials' => ['ang/crmLogin'],
  'requires' => ['crmUi', 'crmUtil', 'api4'],
  'basePages' => ['civicrm/login'],
  'exports' => [
    // Export the module as an [E]lement
    'crm-login' => 'E',
  ],
];
