<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Manage Groups'),
  'server_route' => 'civicrm/group',
  'permission' => ['access CiviCRM'],
];
