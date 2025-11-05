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
  'requires' => ['crmUi', 'crmUtil', 'crmDialog', 'api4', 'crmMonaco', 'ui.sortable'],
  'settingsFactory' => ['Civi\AfformAdmin\AfformAdminMeta', 'getMetadata'],
  'basePages' => [],
  'exports' => [
    'af-gui-editor' => 'E',
  ],
];
