<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array(
  'js' => [
    AFFORM_HTML_MONACO . '/loader.js',
    'ang/afMonaco.js',
    //    'ang/afMonaco/*.js',
    //    'ang/afMonaco/*/*.js',
  ],
  'css' => ['ang/afMonaco.css'],
  // 'partials' => ['ang/afMonaco'],
  'requires' => ['crmUi', 'crmUtil'],
  'settings' => [
    'paths' => [
      'vs' => CRM_AfformHtml_ExtensionUtil::url(AFFORM_HTML_MONACO),
    ],
  ],
  'basePages' => [],
  'exports' => [
    'af-monaco' => 'A',
  ],
);
