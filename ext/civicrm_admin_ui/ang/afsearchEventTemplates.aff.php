<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'title' => E::ts('Event Templates'),
  'permission' => [
    'access CiviEvent',
  ],
  'type' => 'search',
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/eventTemplate',
  'permission_operator' => 'AND',
];
