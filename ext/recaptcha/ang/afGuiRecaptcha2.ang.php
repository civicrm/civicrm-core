<?php
// Module for editing ReCaptcha2 element in the AfformAdmin GUI
return [
  'js' => [
    'ang/afGuiRecaptcha2.module.js',
    'ang/afGuiRecaptcha2/*.js',
  ],
  'partials' => [
    'ang/afGuiRecaptcha2',
  ],
  'css' => ['ang/css/afGuiRecaptcha2.css'],
  // Ensure module is loaded on the afform_admin GUI page
  'basePages' => ['civicrm/admin/afform'],
  'requires' => [],
  'bundles' => [],
  'exports' => [],
];
