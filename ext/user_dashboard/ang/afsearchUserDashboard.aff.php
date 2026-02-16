<?php
use CRM_UserDashboard_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('User Dashboard'),
  'server_route' => 'civicrm/user',
  'permission' => ['access Contact Dashboard'],
];
