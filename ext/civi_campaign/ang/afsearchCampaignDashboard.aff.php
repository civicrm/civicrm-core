<?php
use CRM_Campaign_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Campaign Dashboard'),
  'icon' => 'fa-table',
  'server_route' => 'civicrm/campaign',
  'permission' => ['manage campaign'],
  'navigation' => [
    'parent' => 'Campaigns',
    'label' => E::ts('Campaign Dashboard'),
    'weight' => -1,
  ],
];
