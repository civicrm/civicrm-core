<?php
use CRM_Mailing_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afsearchMailingAB',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Manage A/B Tests'),
        'name' => 'Manage A/B Tests',
        'url' => 'civicrm/mailing/abtest',
        'icon' => 'crm-i fa-flask',
        'permission' => [
          'access CiviMail',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Mailings',
        'weight' => 16,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
