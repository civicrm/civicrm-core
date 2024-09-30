<?php

return [
  'standaloneusers_session_max_lifetime' => [
    'name'        => 'standaloneusers_session_max_lifetime',
    'group'       => 'standaloneusers',
    'type'        => 'Integer',
    'title'       => ts('Maxiumum Session Lifetime'),
    'description' => ts('Duration (in minutes) until a user session expires'),
    // 24 days (= Drupal default)
    'default'     => 24 * 24 * 60,
    'html_type'   => 'text',
    'is_domain'   => 1,
    'is_contact'  => 0,
  ],
  // Example value: 'TOTP', not 'Civi\Standalone\MFA\TOTP'
  'standalone_mfa_enabled' => [
    'name'        => 'standalone_mfa_enabled',
    'group'       => 'standaloneusers',
    'type'        => 'Array',
    'title'       => ts('Multi-Factor Authentication classes'),
    'description' => ts('Choose which multi-factor options are required/accepted. Leave blank to disable MFA. TOTP is Time-based One-Time Password which requires an authenticator app to provide a code.'),
    'default'     => '',
    'is_domain'   => 1,
    'is_contact'  => 0,
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2',
      'multiple' => 1,
    ],
    'pseudoconstant' => ['callback' => '\\Civi\\Standalone\\MFA\\Base::getMFAclasses'],
  ],
];
