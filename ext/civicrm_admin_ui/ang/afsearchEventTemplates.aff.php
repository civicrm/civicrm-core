<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Event Templates'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/eventTemplate',
  'permission' => [
    'access CiviEvent',
  ],
];
