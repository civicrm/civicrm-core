<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Manage Tags'),
  'server_route' => 'civicrm/admin/tags',
  'permission' => ['administer CiviCRM', 'manage tags'],
  'permission_operator' => 'OR',
];
