<?php

use CRM_Oembed_ExtensionUtil as E;

$basic = [
  'group_name' => 'Oembed Preferences',
  'group' => 'oembed',
  'is_domain' => 1,
  'is_contact' => 0,
  'add' => '5.70',
];

return [
  'oembed_allow' => $basic + [
    'name' => 'oembed_allow',
    'type' => 'Array',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      'multiple' => 1,
      'class' => 'huge crm-select2',
    ],
    'default' => ['public', 'ajax'],
    'title' => ts('Allow pages (Common)'),
    'description' => ts('List of pages and use-cases which may be accessed via embedded IFRAME.'),
    'pseudoconstant' => [
      'callback' => 'CRM_Oembed_Utils::getAllowOptions',
    ],
  ],
  'oembed_allow_other' => $basic + [
    'name' => 'oembed_allow_other',
    'type' => 'String',
    'html_type' => 'textarea',
    'default' => "",
    'title' => ts('Allow pages (Other)'),
    'description' => E::ts('List of other pages that may be embedded. One line per item. May use wildcards. Example: "<code>civicrm/ajax/*</code>"'),
    'help_text' => NULL,
  ],
  'oembed_theme' => $basic + [
    'name' => 'oembed_theme',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'callback' => 'call://themes/getAvailable',
    ],
    'default' => 'default',
    'title' => E::ts('Theme'),
    'description' => E::ts('Apply styling to elements inside the IFRAME. In "Automatic" mode, inherit styling from the CiviCRM frontend.'),
    'help_text' => NULL,
  ],
  'oembed_layout' => $basic + [
    'name' => 'oembed_layout',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      'class' => 'huge crm-select2',
    ],
    'default' => 'auto',
    'title' => E::ts('Layout'),
    'description' => E::ts('Apply wrapping to the page layout.'),
    'help_text' => NULL,
    'pseudoconstant' => [
      'callback' => 'CRM_Oembed_Utils::getLayoutOptions',
    ],
  ],
];
