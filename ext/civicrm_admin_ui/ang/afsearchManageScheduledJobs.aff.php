<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Manage Scheduled Jobs'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/job',
  'permission' => ['access CiviCRM'],
];
