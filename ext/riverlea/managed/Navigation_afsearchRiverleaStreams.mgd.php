<?php
use CRM_riverlea_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afsearchRiverleaStreams',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Manage Riverlea Streams'),
        'name' => 'afsearchRiverleaStreams',
        'url' => 'civicrm/admin/riverlea/streams',
        'icon' => 'crm-i fa-palette',
        'permission' => ['access CiviCRM'],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Customize Data and Screens',
        'weight' => 16,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
