<?php

use CRM_riverlea_ExtensionUtil as E;

return [
  'riverlea_dark_mode_backend' => [
    'name' => 'riverlea_dark_mode_backend',
    'group' => 'riverlea',
    'type' => 'String',
    'default' => 'light',
    'html_type' => 'select',
    'add' => 1.0,
    'title' => E::ts('Backend Dark Mode Setting'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => E::ts('Control whether and how dark mode can be activated (for supported Riverlea themes only).'),
    'options' => [
      'inherit' => E::ts('Inherit from browser/OS'),
      'light' => E::ts('Always use light mode'),
      'dark' => E::ts('Always use dark mode'),
    ],
    'settings_pages' => [
      'theme' => ['weight' => 110],
    ],
  ],
  'riverlea_user_controls_backend' => [
    'name' => 'riverlea_user_controls_backend',
    'group' => 'riverlea',
    'type' => 'Array',
    'default' => [],
    'html_type' => 'checkboxes',
    'title' => E::ts('Backend User Controls'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => E::ts('Enable users to tweak the theme to their preferences.'),
    'options' => [
      // TODO: add other controls
      'dark-mode' => E::ts('Dark-mode'),
      //'font-size' => E::ts('Font size'),
    ],
    'settings_pages' => [
      'theme' => ['weight' => 210],
    ],
  ],
  'riverlea_dark_mode_frontend' => [
    'name' => 'riverlea_dark_mode_frontend',
    'group' => 'riverlea',
    'type' => 'String',
    'default' => 'light',
    'html_type' => 'select',
    'add' => 1.0,
    'title' => E::ts('Frontend Dark Mode Setting'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => E::ts('Control whether and how dark mode can be activated (for supported Riverlea themes only).'),
    'options' => [
      'inherit' => E::ts('Inherit from browser/OS'),
      'light' => E::ts('Always use light mode'),
      'dark' => E::ts('Always use dark mode'),
    ],
    'settings_pages' => [
      'theme' => ['weight' => 210],
    ],
  ],
];
