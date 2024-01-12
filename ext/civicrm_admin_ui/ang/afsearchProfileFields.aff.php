<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Profile Fields'),
  'server_route' => 'civicrm/admin/uf/group/field',
  'permission' => ['access CiviCRM'],
];
