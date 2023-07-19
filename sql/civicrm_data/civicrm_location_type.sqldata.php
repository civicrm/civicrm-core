<?php

return CRM_Core_CodeGen_SqlData::create('civicrm_location_type')
  ->addValues([
    [
      'name' => 'Home',
      'display_name' => ts('Home'),
      'vcard_name' => 'HOME',
      'description' => ts('Place of residence'),
      'is_default' => 1,
    ],
    [
      'name' => 'Work',
      'display_name' => ts('Work'),
      'vcard_name' => 'WORK',
      'description' => ts('Work location'),
    ],
    [
      'name' => 'Main',
      'display_name' => ts('Main'),
      'vcard_name' => NULL,
      'description' => ts('Main office location'),
    ],
    [
      'name' => 'Other',
      'display_name' => ts('Other'),
      'vcard_name' => NULL,
      'description' => ts('Other location'),
    ],
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
    'is_default' => 0,
    'is_reserved' => 0,
  ]);
