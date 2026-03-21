<?php
use CRM_Mailing_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_Mailing_Bounces',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Review Bounces'),
        'name' => 'afsearchMailingBounces',
        'url' => 'civicrm/mailing/bounces',
        'icon' => '',
        'permission' => [
          'access CiviMail',
        ],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Mailings',
        'weight' => 5,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
