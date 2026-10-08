<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_standaloneusers_setting_admin',
    'entity' => 'Navigation',
    'cleanup' => 'never',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Login settings'),
        'name' => 'standaloneusers_setting_admin',
        'url' => 'civicrm/admin/setting/standaloneusers?reset=1',
        'icon' => 'crm-i fa-right-to-bracket',
        'permission' => [
          'cms:administer users',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Users and Permissions',
        'weight' => 13,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
