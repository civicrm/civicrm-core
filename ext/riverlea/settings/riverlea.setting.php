<?php

use CRM_riverlea_ExtensionUtil as E;

return [
  'riverlea_dark_mode' => [
    'name' => 'riverlea_dark_mode',
    'type' => 'String',
    'default' => 'inherit',
    'html_type' => 'select',
    'add' => 1.0,
    'title' => E::ts('Dark mode control'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('Control whether and how dark mode can be activated for supported (=Riverlea) themes'),
    'options' => [
      'inherit' => E::ts('Inherit from browser/OS'),
      'light' => E::ts('Always use light mode'),
      'dark' => E::ts('Always use dark mode'),
    ],
    'settings_pages' => ['display' => ['weight' => 940]],
  ],
];
