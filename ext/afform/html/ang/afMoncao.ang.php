<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array(
  'js' => [
    AFFORM_HTML_MONACO . '/loader.js',
    'ang/afMoncao.js',
    //    'ang/afMoncao/*.js',
    //    'ang/afMoncao/*/*.js',
  ],
  'css' => ['ang/afMoncao.css'],
  // 'partials' => ['ang/afMoncao'],
  'requires' => ['crmUi', 'crmUtil'],
  'settings' => [
    'paths' => [
      'vs' => CRM_AfformHtml_ExtensionUtil::url(AFFORM_HTML_MONACO),
    ],
  ],
  'basePages' => [],
  'exports' => [
    'attr' => ['af-monaco'],
  ],
);
