<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Custom Field Groups'),
  'description' => E::ts('Administer custom field groups list'),
  'server_route' => 'civicrm/admin/custom/group',
  'permission' => ['administer CiviCRM data'],
];
