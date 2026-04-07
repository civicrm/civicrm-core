<?php

use CRM_Oembed_ExtensionUtil as E;

$basic = [
  'group_name' => 'oEmbed Preferences',
  'group' => 'oembed',
  'is_domain' => 1,
  'is_contact' => 0,
  'add' => '5.72',
];

return [
  'oembed_standard' => $basic + [
    'name' => 'oembed_standard',
    'type' => 'Boolean',
    'default' => TRUE,
    'html_type' => 'toggle',
    'title' => E::ts('oEmbed Auto Discovery'),
    'description' => E::ts('All pages with IFRAME support will support auto-discovery via oEmbed.'),
    'help_text' => NULL,
  ],
  'oembed_share' => $basic + [
    'name' => 'oembed_share',
    'type' => 'Boolean',
    'default' => TRUE,
    'html_type' => 'toggle',
    'title' => E::ts('oEmbed Explicit Sharing'),
    'description' => E::ts('When administrators ("administer oembed") view a public page, present options for advanced sharing.'),
    'help_text' => NULL,
  ],
];
