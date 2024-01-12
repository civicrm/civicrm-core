<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Manage Contribution Pages'),
  'server_route' => 'civicrm/admin/contribute',
  'permission' => ['access CiviContribute'],
];
