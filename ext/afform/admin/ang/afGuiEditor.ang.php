<?php
// Angular module for afform gui editor
return [
  'js' => [
    'ang/afGuiEditor.js',
    'ang/afGuiEditor/*.js',
    'ang/afGuiEditor/*/*.js',
  ],
  'css' => ['ang/afGuiEditor.css'],
  'partials' => ['ang/afGuiEditor'],
  'requires' => ['crmUi', 'crmUtil', 'dialogService', 'api4', 'crmMonaco', 'ui.sortable'],
  'settingsFactory' => ['CRM_AfformAdmin_Utils', 'getAngularSettings'],
  'basePages' => [],
  'exports' => [
    'af-gui-editor' => 'E',
  ],
];
