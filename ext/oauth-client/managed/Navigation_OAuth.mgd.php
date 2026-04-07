<?php
use CRM_OAuth_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_OAuth',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('OAuth'),
        'name' => 'OAuth',
        'url' => 'civicrm/admin/oauth',
        'permission' => [
          'manage OAuth client',
        ],
        'permission_operator' => 'OR',
        'parent_id.name' => 'System Settings',
      ],
      // Matching on `url` because `name` may have been null due to a bug in previous versions `CRM_OAuth_Upgrader::install()`
      'match' => ['url', 'domain_id'],
    ],
  ],
];
