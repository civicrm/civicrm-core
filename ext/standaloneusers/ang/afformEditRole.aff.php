<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'form',
  'title' => E::ts('Edit Role'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/role',
  'permission' => ['access CiviCRM'],
  'redirect' => 'civicrm/admin/roles',
];
