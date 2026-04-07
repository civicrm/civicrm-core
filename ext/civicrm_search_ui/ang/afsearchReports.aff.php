<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Reports'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/searchui/report/list',
  'navigation' => [
    'parent' => 'Reports',
    'label' => E::ts('Reports'),
    'name' => 'afsearchReports',
    'weight' => 10000,
  ],
  'permission' => [
    'access Reports',
  ],
];
