<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afsearchCiviCRMReports',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('List Reports'),
        'name' => 'afsearchCiviCRMReports',
        'url' => 'civicrm/searchkit_ui/report/list',
        'icon' => 'crm-i fa-list-alt',
        'permission' => [
          'access CiviReport',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Experimental',
        'weight' => 1,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
