<?php
return [
  'ext' => 'civicrm',
  'js' => [
    'ang/crmQueueMonitor.js',
    // 'ang/crmQueueMonitor/*.js',
    // 'ang/crmQueueMonitor/*/*.js',
  ],
  // 'css' => ['ang/crmQueueMonitor.css'],
  // 'partials' => ['ang/crmQueueMonitor'],
  'requires' => ['crmUi', 'crmUtil'],
  'basePages' => [],
  'exports' => [
    'crm-queue-monitor' => 'E',
  ],
];
