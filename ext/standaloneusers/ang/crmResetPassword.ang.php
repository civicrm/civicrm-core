<?php
return [
  'ext' => 'standaloneusers',
  'js' => [
    'ang/crmResetPassword.js',
    // 'ang/crmQueueMonitor/*.js',
    // 'ang/crmQueueMonitor/*/*.js',
  ],
  // 'css' => ['ang/crmQueueMonitor.css'],
  'partials' => ['ang/crmResetPassword'],
  'requires' => ['crmUi', 'crmUtil', 'api4'],
  'basePages' => ['civicrm/login/password'],
  'exports' => [
    // Export the module as an [E]lement
    'crm-reset-password' => 'E',
  ],
];
