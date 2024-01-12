<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Profiles'),
  'server_route' => 'civicrm/admin/uf/group',
  'permission' => ['access CiviCRM'],
];
