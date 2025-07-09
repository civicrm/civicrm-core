<?php
use CRM_BatchEntry_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afsearchContributionBatch',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Contribution batch'),
        'name' => 'afsearchContributionBatch',
        'url' => 'civicrm/batch/contribution',
        'icon' => 'crm-i fa-table-cells-large',
        'permission' => [
          'access CiviCRM',
          'edit contributions',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Batch Data Entry',
        'weight' => 1,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
