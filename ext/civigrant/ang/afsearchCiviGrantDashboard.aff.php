<?php
use CRM_Grant_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('CiviGrant Dashboard'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/grant',
  'permission' => [
    'access CiviGrant',
  ],
];
