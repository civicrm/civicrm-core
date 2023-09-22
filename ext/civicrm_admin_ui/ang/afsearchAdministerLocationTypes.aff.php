<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Location Types'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/locationType',
  'permission' => ['administer CiviCRM'],
];
