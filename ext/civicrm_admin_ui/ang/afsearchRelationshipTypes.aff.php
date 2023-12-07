<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Relationship Types'),
  'server_route' => 'civicrm/admin/reltype',
  'permission' => ['access CiviCRM'],
];
