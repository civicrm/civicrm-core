<?php
// This file declares an Angular module which can be autoloaded
$isPretty = \Civi::settings()->get('debug_enabled') && !defined('CIVICRM_KARMA');

return [
  'ext' => 'civicrm',
  'js' => ['ang/crmUi.js'],
  'partials' => ['ang/crmUi'],
  'css' => ['ang/crmUI.css'],
  'requires' => array_merge(
    [
      'crmResource',
    ],
    // Only require the +10kb if we're likely to need it.
    $isPretty ? ['jsonFormatter'] : []
  ),
];
