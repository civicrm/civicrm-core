<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afsearchReports',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('List Reports'),
        'name' => 'afsearchReports',
        'url' => 'civicrm/searchui/report/list',
        'icon' => 'crm-i fa-list-alt',
        'permission' => [
          'access Reports',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Experimental',
        'weight' => 1,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
