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
    'type'        => 'String',
    'title'       => ts('Multi-Factor Authentication classes'),
    'description' => ts('Comma separated list of classes of MFA that are required/accepted.'),
    'default'     => '',
    'html_type'   => 'text',
    'is_domain'   => 1,
    'is_contact'  => 0,
  ],
];
