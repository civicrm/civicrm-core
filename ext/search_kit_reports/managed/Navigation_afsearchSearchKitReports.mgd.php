<?php
use CRM_SearchKitReports_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afsearchSearchKitReports',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('SearchKit Reports'),
        'name' => 'afsearchSearchKitReports',
        'url' => 'civicrm/report/search_kit',
        'icon' => 'crm-i fa-list-alt',
        'permission' => [
          'access CiviReport',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Reports',
        'weight' => 1,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
