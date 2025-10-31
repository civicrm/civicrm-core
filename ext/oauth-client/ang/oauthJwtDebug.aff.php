<?php
use CRM_OAuth_ExtensionUtil as E;

return [
  'title' => E::ts('OAuth2 JWT Debug'),
  'requires' => ["unvalidatedJwtDecode", "afCore", "oauthUtil"],
  'server_route' => 'civicrm/admin/oauth-jwt-debug',
  'permission' => ['manage OAuth client secrets'],
];
