<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Administer Roles'),
  'description' => E::ts('List of roles defined on the system.'),
  'icon' => 'fa-graduation-cap',
  'server_route' => 'civicrm/admin/roles',
  'permission' => ['cms:administer users'],
  'navigation' => [
    'parent' => 'Users and Permissions',
    'label' => E::ts('User Roles'),
    'weight' => 0,
  ],
];
