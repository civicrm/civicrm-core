<?php
// This file declares an Angular module which can be autoloaded
// ODDITY: This only loads if CiviCase is active.

return [
  'ext' => 'civicrm',
  'js' => ['ang/crmCaseType.js'],
  'css' => ['ang/crmCaseType.css'],
  'partials' => ['ang/crmCaseType'],
  'requires' => ['ngRoute', 'ui.utils', 'crmUi', 'unsavedChanges', 'crmUtil', 'crmResource'],
];
