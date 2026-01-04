<?php
use CRM_UserDashboard_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('User Dashboard'),
  'server_route' => 'civicrm/user',
  'permission' => ['access Contact Dashboard'],
  // NOTE: Layout dynamically generated via hook_civicrm_alterAngular
  'layout' => '',
  // temporary, remove after merging https://github.com/civicrm/civicrm-core/pull/27783
  'requires' => ['af', 'afCore', 'crmSearchDisplayTable'],
];
