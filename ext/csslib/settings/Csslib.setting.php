<?php
return [
  'csslib_autoprefixer' => [
    'group_name' => 'Csslib Preferences',
    'group' => 'csslib',
    'name' => 'csslib_autoprefixer',
    'quick_form_type' => 'Select',
    'type' => 'String',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'callback' => '\Civi\Csslib\Options::autoprefixers',
    ],
    'default' => 'php-autoprefixer',
    'add' => '5.29',
    'title' => 'Csslib Autoprefixer',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => ts('The autoprefixer provides backward compatibility for some browsers. The PHP implementation comes bundled in. The NodeJS implementation requires manual installation The NodeJS is more widely used and may provide more polish (e.g. better source-maps).'),
  ],
  'csslib_srcmap' => [
    'group_name' => 'Csslib Preferences',
    'group' => 'csslib',
    'name' => 'csslib_srcmap',
    'quick_form_type' => 'Select',
    'type' => 'String',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'callback' => '\Civi\Csslib\Options::srcmaps',
    ],
    'default' => 'none',
    'add' => '5.29',
    'title' => 'Csslib graphics driver',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => ts('The source-map provides support for designers and developers. It may add a few seconds to build time.'),
  ],
];
