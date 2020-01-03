<?php
// Angular module for afform gui editor
return [
  'js' => [
    'ang/afGuiEditor.js',
    'ang/afGuiEditor/*.js',
  ],
  'css' => ['ang/afGuiEditor.css'],
  'partials' => ['ang/afGuiEditor'],
  'requires' => ['crmUi', 'crmUtil', 'dialogService', 'api4'],
  'settings' => [],
  'basePages' => [],
  'exports' => [
    'af-gui-editor' => 'A',
  ],
];
