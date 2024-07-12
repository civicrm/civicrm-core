<?php

use CRM_riverlea_ExtensionUtil as E;

$settings = [];

if (in_array(CIVICRM_UF, ['WordPress', 'Joomla'])) {
  $settings['riverlea_hide_cms_menubar'] = [
    'name' => 'riverlea_hide_cms_menubar',
    'type' => 'Boolean',
    'default' => FALSE,
    'html_type' => 'checkbox',
    'title' => E::ts('Hide the %1 menubar when in CiviCRM', [1 => CIVICRM_UF]),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('When logged-in as an editor or administrator, the %1 menubar can be a bit of a distraction. It can still be displayed again by clicking on the CiviCRM logo in the top menu, then clicking on "Hide the menu".', [1 => CIVICRM_UF]),
    'settings_pages' => ['riverlea' => ['weight' => 10]],
  ];
}

$settings['riverlea_dark_mode'] = [
  'name' => 'riverlea_dark_mode',
  'type' => 'String',
  'default' => 'inherit',
  'html_type' => 'select',
  'add' => 1.0,
  'title' => E::ts('Dark Mode preference'),
  'is_domain' => 1,
  'is_contact' => 0,
  'description' => E::ts('Control whether Dark mode is always active or not.'),
  'options' => [
    'inherit' => E::ts('Inherit from browser'),
    'light' => E::ts('Always use light mode'),
    'dark' => E::ts('Always use dark mode'),
  ],
  'settings_pages' => ['riverlea' => ['weight' => 20]],
];

return $settings;
