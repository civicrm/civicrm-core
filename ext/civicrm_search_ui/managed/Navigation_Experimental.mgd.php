<?php

use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_Experimental',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'domain_id' => 'current_domain',
        'label' => E::ts('Experimental'),
        'name' => 'Experimental',
        'url' => NULL,
        'icon' => 'crm-i fa-flask',
        'permission' => [
          'access CiviCRM',
        ],
        'permission_operator' => 'AND',
        'parent_id' => NULL,
        'is_active' => TRUE,
        'has_separator' => 0,
        'weight' => 113,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
];
