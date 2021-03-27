<?php

use CRM_Flexmailer_ExtensionUtil as E;

return [
  'flexmailer_traditional' => [
    'group_name' => 'Flexmailer Preferences',
    'group' => 'flexmailer',
    'name' => 'flexmailer_traditional',
    'type' => 'String',
    'html_type' => 'select',
    'html_attributes' => ['class' => 'crm-select2'],
    'pseudoconstant' => ['callback' => '_flexmailer_traditional_options'],
    'default' => 'auto',
    'add' => '5.13',
    'title' => E::ts('Traditional Mailing Handler'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('For greater backward-compatibility, process "<code>traditional</code>" mailings with the CiviMail\'s hard-coded BAO.') . '<br/>'
    . E::ts('For greater forward-compatibility, process "<code>traditional</code>" mailings with Flexmailer\'s extensible pipeline.'),
    'help_text' => NULL,
    'settings_pages' => [
      'flexmailer' => [
        'weight' => 5,
      ],
    ],
  ],
];
