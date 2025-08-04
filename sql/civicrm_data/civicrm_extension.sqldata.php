<?php

// Auto-install core extension (low-level; avoid if you can)
//
// There are MULTIPLE PLACES where you can install extensions by default. Compare:
//
// + `sql/civicrm_data/civicrm_extension.sqldata.php`
//      - Only safe for -very basic- extensions (no dependencies, no new tables, no install-routines)
//      - BYPASSES dependencies (activation/ordering/validation)
//      - BYPASSES hooks (hook_install, hook_enable, etc)
//      - Goes into the raw template for all databases
//      - Affects modern installers... and also ancient installers and *ALL* headless tests.
//      - Difficult to alter programmatically
//
// + `setup/plugins/init/DefaultExtension.civi-setup.php`
//      - Safe for many extensions
//      - RESPECTS dependencies (activating them in topological order)
//      - RESPECTS hooks (hook_install, hook_enable, etc)
//      - During installation, it triggers the regular ext-install logic
//      - Affects modern installers
//      - Amenable to programmatic alteration
//
// Generally, if you're just tweaking policy preferences, then `DefaultExtension` is a better place.
//
// Historically, some policy preferences were put here before Setup API was introduced across-the-board.
// We should look at changing those.

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
    [
      'full_name' => 'legacybatchentry',
      'name' => 'Legacy Batch Data Entry',
    ],
  ]);
