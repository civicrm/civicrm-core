<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('CiviCRM Queues'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/queue',
  'permission' => ['administer queues'],
  'placement' => [],
  'permission_operator' => "AND",
];
