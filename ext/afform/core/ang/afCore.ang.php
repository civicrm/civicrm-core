<?php
// This file declares an Angular module which can be autoloaded
return [
  'js' => [
    'ang/afCore.js',
    'ang/afCore/*.js',
    'ang/afCore/*/*.js',
  ],
  'css' => ['ang/afCore.css'],
  'requires' => ['crmUi', 'crmUtil', 'api4', 'checklist-model', 'angularFileUpload', 'ngSanitize'],
  'partials' => ['ang/afCore'],
  'basePages' => [],
  // Permissions needed for conditionally displaying edit-links
  // See: \Civi\AfformAdmin\AfformAdminInjector and afCoreDirective.checkLinkPerm
  'permissions' => [
    'administer afform',
    'manage own afform',
    'administer search_kit',
    'manage own search_kit',
    'all CiviCRM permissions and ACLs',
  ],
];
