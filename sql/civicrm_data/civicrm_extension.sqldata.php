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
  ->syncColumns('fill', ['full_name' => 'file', 'name' => 'label'])
  ->addValues([
    [
      'full_name' => 'sequentialcreditnotes',
      'name' => 'Sequential credit notes',
    ],
    [
      'full_name' => 'greenwich',
      'name' => 'Theme: Greenwich',
    ],
    [
      'full_name' => 'financialacls',
      'name' => 'Financial ACLs',
    ],
    [
      'full_name' => 'recaptcha',
      'name' => 'reCAPTCHA',
    ],
    [
      'full_name' => 'ckeditor4',
      'name' => 'CKEditor4',
    ],
    [
      'full_name' => 'org.civicrm.flexmailer',
      'name' => 'FlexMailer',
      'file' => 'flexmailer',
    ],
    [
      'full_name' => 'civi_campaign',
      'name' => 'CiviCampaign',
    ],
    [
      'full_name' => 'civi_case',
      'name' => 'CiviCase',
    ],
    [
      'full_name' => 'civi_contribute',
      'name' => 'CiviContribute',
    ],
    [
      'full_name' => 'civi_event',
      'name' => 'CiviEvent',
    ],
    [
      'full_name' => 'civi_mail',
      'name' => 'CiviMail',
    ],
    [
      'full_name' => 'civi_member',
      'name' => 'CiviMember',
    ],
    [
      'full_name' => 'civi_pledge',
      'name' => 'CiviPledge',
    ],
    [
      'full_name' => 'civi_report',
      'name' => 'CiviReport',
    ],
  ]);
