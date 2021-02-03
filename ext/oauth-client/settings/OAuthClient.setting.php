<?php
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
    'title' => ts('Redirect URL'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Override the redirect URL for OAuth2 requests. This is an absolute URL which should be equivalent to "civicrm/oauth-client/return".'),
  ],
];
