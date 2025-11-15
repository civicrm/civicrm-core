<?php

use CRM_SearchKitReports_ExtensionUtil as E;

return [
  [
    'name' => 'Tag_SearchKitReport',
    'entity' => 'Tag',
    'cleanup' => 'unused',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'SearchKitReport',
        'label' => E::ts('Report'),
        'used_for' => [
          'civicrm_saved_search',
        ],
      ],
      'match' => ['name'],
    ],
  ],
];
