<?php
use CRM_Afform_ExtensionUtil as E;

return [
  'afform_mail_auth_token' => [
    'group_name' => 'Afform Preferences',
    'group' => 'afform',
    'name' => 'afform_mail_auth_token',
    'type' => 'String',
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2',
    ],
    'pseudoconstant' => [
      'callback' => 'CRM_Afform_Utils::getMailAuthOptions',
    ],
    // Traditional default. Might be nice to change, but need to tend to upgrade process.
    'default' => 'session',
    'add' => '4.7',
    'title' => E::ts('Mail Authentication Tokens'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('How do authenticated email hyperlinks work?'),
    'help_text' => NULL,
    'settings_pages' => ['afform' => ['weight' => 10]],
  ],
];
