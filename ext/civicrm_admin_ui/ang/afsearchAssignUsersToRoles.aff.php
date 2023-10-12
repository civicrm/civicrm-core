<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Assign Users to Roles'),
  'server_route' => 'civicrm/acl/entityrole',
  'permission' => ['administer CiviCRM'],
];
