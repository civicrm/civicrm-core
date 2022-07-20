<?php
// This file declares an Angular module which can be autoloaded
// ODDITY: Only loads if you have CiviMail permissions.

return [
  'ext' => 'civicrm',
  'js' => [
    'ang/crmMailingAB.js',
    'ang/crmMailingAB/*.js',
    'ang/crmMailingAB/*/*.js',
  ],
  'css' => ['ang/crmMailingAB.css'],
  'partials' => ['ang/crmMailingAB'],
  'requires' => ['ngRoute', 'crmUi', 'crmAttachment', 'crmMailing', 'crmD3', 'crmResource'],
];
