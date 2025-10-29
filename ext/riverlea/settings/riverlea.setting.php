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
    'title' => E::ts('Backend Dark Mode Control'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => E::ts('Control whether and how dark mode can be activated (for supported Riverlea themes only).'),
    'options' => [
      'inherit' => E::ts('Inherit from browser/OS'),
      'light' => E::ts('Always use light mode'),
      'dark' => E::ts('Always use dark mode'),
    ],
    'settings_pages' => [
      'riverlea' => ['weight' => 100],
      // show alongside backend theme selector on Display settings page
      'display' => ['section' => 'theme', 'weight' => 20],
    ],
  ],
  'riverlea_dark_mode_frontend' => [
    'name' => 'riverlea_dark_mode_frontend',
    'group' => 'riverlea',
    'type' => 'String',
    'default' => 'light',
    'html_type' => 'select',
    'add' => 1.0,
    'title' => E::ts('Frontend Dark Mode Control'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => E::ts('Control whether and how dark mode can be activated (for supported Riverlea themes only).'),
    'options' => [
      'inherit' => E::ts('Inherit from browser/OS'),
      'light' => E::ts('Always use light mode'),
      'dark' => E::ts('Always use dark mode'),
    ],
    'settings_pages' => [
      'riverlea' => ['weight' => 110],
      // show alongside frontend theme selector on Display settings page
      'display' => ['section' => 'theme', 'weight' => 40],
    ],
  ],
  'riverlea_font_size' => [
    'name' => 'riverlea_font_size',
    'group' => 'riverlea',
    'default' => '1',
    'type' => 'String',
    'html_type' => 'select',
    'add' => 1.0,
    'options' => [
      '0.75' => E::ts('Smallest'),
      '0.875' => E::ts('Small'),
      '1' => E::ts('Default'),
      '1.125' => E::ts('Big'),
      '1.5' => E::ts('Bigger'),
    ],
    'on_change' => [
      '\\Civi\\Riverlea\\StyleLoader::onChangeFontsize',
    ],
    'validate_callback' => '\\Civi\\Riverlea\\StyleLoader::validateFontsize',
    'title' => E::ts('Font size'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => E::ts('For systems where 1rem = 16px (which is the default in RiverLea and all browsers) these sizes represent: Smallest 12px, Small 14px, Default 16px, Big 18px, Bigger 24px.'),
    'settings_pages' => [
      'riverlea' => ['weight' => 500],
    ],
  ],
];
