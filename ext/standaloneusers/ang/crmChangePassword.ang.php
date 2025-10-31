<?php
return [
  'ext' => 'standaloneusers',
  'js' => [
    'ang/crmChangePassword.js',
    // 'ang/crmQueueMonitor/*.js',
    // 'ang/crmQueueMonitor/*/*.js',
  ],
  // 'css' => ['ang/crmQueueMonitor.css'],
  'partials' => ['ang/crmChangePassword'],
  'requires' => ['crmUi', 'crmUtil', 'api4'],
  'basePages' => ['civicrm/my-account/password'],
  'exports' => [
    // Export the module as an [E]lement
    'crm-change-password' => 'E',
  ],
];
