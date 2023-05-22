<?php

// Auto-install core extension.
// Note this is a limited interim technique for installing core extensions -  the goal is that core extensions would be installed
// in the setup routine based on their tags & using the standard extension install api.
// do not try this at home folks.

return CRM_Core_CodeGen_SqlData::create('civicrm_extension', 'INSERT IGNORE INTO')
  ->addDefaults([
    'type' => 'module',
    'is_active' => 1,
  ])
  ->addValues([
    [
      'full_name' => 'sequentialcreditnotes',
      'name' => 'Sequential credit notes',
      'label' => 'Sequential credit notes',
      'file' => 'sequentialcreditnotes',
    ],
    [
      'full_name' => 'greenwich',
      'name' => 'Theme: Greenwich',
      'label' => 'Theme: Greenwich',
      'file' => 'greenwich',
    ],
    [
      'full_name' => 'eventcart',
      'name' => 'Event cart',
      'label' => 'Event cart',
      'file' => 'eventcart',
    ],
    [
      'full_name' => 'financialacls',
      'name' => 'Financial ACLs',
      'label' => 'Financial ACLs',
      'file' => 'financialacls',
    ],
    [
      'full_name' => 'recaptcha',
      'name' => 'reCAPTCHA',
      'label' => 'reCAPTCHA',
      'file' => 'recaptcha',
    ],
    [
      'full_name' => 'ckeditor4',
      'name' => 'CKEditor4',
      'label' => 'CKEditor4',
      'file' => 'ckeditor4',
    ],
    [
      'full_name' => 'legacycustomsearches',
      'name' => 'Custom search framework',
      'label' => 'Custom search framework',
      'file' => 'legacycustomsearches',
    ],
    [
      'full_name' => 'org.civicrm.flexmailer',
      'name' => 'FlexMailer',
      'label' => 'FlexMailer',
      'file' => 'flexmailer',
    ],
  ]);
