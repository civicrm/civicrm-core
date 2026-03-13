<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'standaloneusers_session_max_lifetime' => [
    'name' => 'standaloneusers_session_max_lifetime',
    'group' => 'standaloneusers',
    'type' => 'Integer',
    'title' => E::ts('Maximum Session Lifetime'),
    'description' => E::ts('Duration (in minutes) until a user session expires'),
    // 24 days (= Drupal default)
    'default' => 24 * 24 * 60,
    'html_type' => 'number',
    'is_domain' => 1,
    'is_contact' => 0,
  ],
  // Example value: 'TOTP', not 'Civi\Standalone\MFA\TOTP'
  'standalone_mfa_enabled' => [
    'name' => 'standalone_mfa_enabled',
    'group' => 'standaloneusers',
    'type' => 'Array',
    'title' => E::ts('Multi-Factor Authentication classes'),
    'description' => E::ts('Choose which multi-factor options are required/accepted. Leave blank to disable MFA. TOTP is Time-based One-Time Password which requires an authenticator app to provide a code.'),
    'default' => '',
    'is_domain' => 1,
    'is_contact' => 0,
    'html_type' => 'select',
    'html_attributes' => [
      'class' => 'crm-select2',
      'multiple' => 1,
    ],
    'pseudoconstant' => ['callback' => '\\Civi\\Standalone\\MFA\\Base::getMFAclasses'],
  ],
  'standalone_mfa_remember' => [
    'name' => 'standalone_mfa_remember',
    'group' => 'standaloneusers',
    'type' => 'Integer',
    'title' => E::ts('Remember this device expiry (days)'),
    'description' => E::ts('How many days should ‘remember this device’ allow a user to bypass MFA? Use zero to disable this feature.'),
    'default' => '',
    'is_domain' => 1,
    'is_contact' => 0,
    'html_type' => 'number',
    'html_attributes' => [
      'min' => 0,
      'max' => 31,
    ],
  ],
  'standalone_favicon' => [
    'name' => 'standalone_favicon',
    'group' => 'standaloneusers',
    'type' => 'File',
    'file_is_public' => TRUE,
    'title' => ts('Site Favicon'),
    'help_text' => [
      E::ts('Upload a logo or icon to represent this site in the browser tab.'),
      E::ts('For best results, use a small, square image (512x512 pixels or less) with a transparent background. PNG file format is recommended. Other formats may work depending on your site configuration.'),
    ],
    'default' => NULL,
    'validate_callback' => ['Civi\\Standalone\\Utils', 'validateFavicon'],
    'is_domain' => 1,
    'is_contact' => 0,
    'html_type' => 'file',
    'settings_pages' => ['display' => ['section' => 'theme', 'weight' => 120]],
  ],
];
