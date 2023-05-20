<?php

return CRM_Core_CodeGen_SqlData::create('civicrm_location_type')
  ->addValues([
    // CRM-9120 for legacy reasons we are continuing to translate the 'name', but this
    // field is used mainly as an ID, and display_name will be shown to the user, but
    // we have not yet finished modifying all places where the 'name' is shown.
    [
      'name' => ts('Home'),
      'display_name' => ts('Home'),
      'vcard_name' => 'HOME',
      'description' => ts('Place of residence'),
      'is_reserved' => 0,
      'is_default' => 1,
    ],
    [
      'name' => ts('Work'),
      'display_name' => ts('Work'),
      'vcard_name' => 'WORK',
      'description' => ts('Work location'),
      'is_reserved' => 0,
    ],
    [
      'name' => ts('Main'),
      'display_name' => ts('Main'),
      'vcard_name' => NULL,
      'description' => ts('Main office location'),
      'is_reserved' => 0,
    ],
    [
      'name' => ts('Other'),
      'display_name' => ts('Other'),
      'vcard_name' => NULL,
      'description' => ts('Other location'),
      'is_reserved' => 0,
    ],
    // -- the following location must stay with the untranslated Billing name, CRM-2064
    [
      'name' => 'Billing',
      'display_name' => ts('Billing'),
      'vcard_name' => NULL,
      'description' => ts('Billing Address location'),
      'is_reserved' => 1,
    ],
  ])
  ->addDefaults([
    'is_active' => 1,
    'is_default' => NULL,
    // FIXME: Doesn't 0 make more sense than NULL?
  ]);
