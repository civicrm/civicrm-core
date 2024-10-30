<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Scheduled Jobs Log'),
  'server_route' => 'civicrm/admin/joblog',
  'permission' => ['administer CiviCRM system'],
];
