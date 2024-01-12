<?php
use CRM_OAuth_ExtensionUtil as E;

return [
  'title' => E::ts('OAuth2 Client Administration'),
  'server_route' => 'civicrm/admin/oauth',
  'permission' => ['manage OAuth client'],
];
