<?php
use CRM_OAuth_ExtensionUtil as E;

return [
  'oauthClientRedirectUrl' => [
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'oauthClientRedirectUrl',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'default' => NULL,
    'add' => '5.32',
    'title' => E::ts('Redirect URL'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('Override the redirect URL for OAuth2 requests. This is an absolute URL which should be equivalent to "civicrm/oauth-client/return".'),
  ],
  'oauth_civi_connect_keypair' => [
    'group_name' => 'OAuth Preferences',
    'group' => 'oauth',
    'name' => 'oauth_civi_connect_keypair',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'default' => NULL,
    'add' => '6.8',
    'title' => E::ts('CiviConnect Key Pair'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('Secret key to identify this site to the CiviConnect bridge. Use CryptoToken format.'),
  ],
];
