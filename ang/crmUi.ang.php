<?php
// This file declares an Angular module which can be autoloaded
$isDebug = Civi::settings()->get('debug_enabled');

return [
  'ext' => 'civicrm',
  'js' => ['ang/crmUi.js'],
  'partials' => ['ang/crmUi'],
  'requires' => array_merge(
    [
      'crmResource',
      'ui.utils',
    ],
    // Only require the +10kb if we're likely to need it.
    $isDebug ? ['jsonFormatter'] : []
  ),
];
