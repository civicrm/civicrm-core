<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Contact Types'),
  'description' => E::ts('Administer contact types and sub-types'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/options/subtype',
  'permission' => ['access CiviCRM'],
];
