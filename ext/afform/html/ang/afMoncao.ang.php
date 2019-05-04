<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array(
  'js' => [
    'node_modules/monaco-editor/min/vs/loader.js',
    'ang/afMoncao.js',
    //    'ang/afMoncao/*.js',
    //    'ang/afMoncao/*/*.js',
  ],
  'css' => ['ang/afMoncao.css'],
  // 'partials' => ['ang/afMoncao'],
  'requires' => ['crmUi', 'crmUtil'],
  'settings' => [
    'paths' => [
      'vs' => CRM_AfformHtml_ExtensionUtil::url('node_modules/monaco-editor/min/vs'),
    ],
  ],
  'basePages' => [],
);
