<?php
use CRM_Campaign_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Campaign Dashboard'),
  'icon' => 'fa-table',
  'server_route' => 'civicrm/campaign',
  'permission' => ['administer CiviCampaign', 'manage campaign'],
  'permission_operator' => 'OR',
  'navigation' => [
    'parent' => 'Campaigns',
    'label' => E::ts('Campaign Dashboard'),
    'weight' => -1,
  ],
];
