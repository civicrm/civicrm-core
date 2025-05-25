<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_afsearchFindActivities',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Find Activities'),
        'name' => 'afsearchFindActivities',
        'url' => 'civicrm/searchkit_ui/activity/search',
        'icon' => 'crm-i fa-list-alt',
        'permission' => ['access CiviCRM'],
        'permission_operator' => 'AND',
        'parent_id.name' => 'Experimental',
        'weight' => 1,
      ],
      'match' => ['name', 'domain_id'],
    ],
  ],
];
