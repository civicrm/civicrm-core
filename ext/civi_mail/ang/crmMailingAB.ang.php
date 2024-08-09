<?php
// This file declares crmMailingAB Angular module.

return [
  'js' => [
    'ang/crmMailingAB.js',
    'ang/crmMailingAB/*.js',
    'ang/crmMailingAB/*/*.js',
  ],
  'css' => ['ang/crmMailingAB.css'],
  'partials' => ['ang/crmMailingAB'],
  'requires' => ['ngRoute', 'crmUi', 'crmAttachment', 'crmMailing', 'crmResource'],
  'bundles' => ['visual'],
];
