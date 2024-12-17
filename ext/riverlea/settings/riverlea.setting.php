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
    'description' => E::ts('Control whether and how dark mode can be activated on backend pages (for supported Riverlea themes only)'),
    'options' => [
      'inherit' => E::ts('Inherit from browser/OS'),
      'light' => E::ts('Always use light mode'),
      'dark' => E::ts('Always use dark mode'),
    ],
    'settings_pages' => [
      'riverlea' => ['weight' => 100],
      // show alongside backend theme selector on Display settings page
      'display' => ['weight' => 900],
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
    'description' => E::ts('Control whether and how dark mode can be activated on frontend pages (for supported Riverlea themes only)'),
    'options' => [
      'inherit' => E::ts('Inherit from browser/OS'),
      'light' => E::ts('Always use light mode'),
      'dark' => E::ts('Always use dark mode'),
    ],
    'settings_pages' => [
      'riverlea' => ['weight' => 110],
      // show alongside frontend theme selector on Display settings page
      'display' => ['weight' => 950],
    ],
  ],
];
