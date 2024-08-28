<?php

use CRM_riverlea_ExtensionUtil as E;

$settings = [];

$settings['riverlea_dark_mode'] = [
  'name' => 'riverlea_dark_mode',
  'type' => 'String',
  'default' => 'inherit',
  'html_type' => 'select',
  'add' => 1.0,
  'title' => E::ts('Dark mode preference'),
  'is_domain' => 1,
  'is_contact' => 0,
  'description' => E::ts('Control the dark mode status - not currently working'),
  'options' => [
    'inherit' => E::ts('Inherit from browser/OS'),
    'light' => E::ts('Always use light mode'),
    'dark' => E::ts('Always use dark mode'),
  ],
  'settings_pages' => ['riverlea' => ['weight' => 20]],
];

return $settings;
