<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Administer User Accounts'),
  'icon' => 'fa-users',
  'server_route' => 'civicrm/admin/users',
  'permission' => ['cms:administer users'],
  'navigation' => [
    'parent' => 'Users and Permissions',
    'label' => E::ts('User Accounts'),
    'weight' => 0,
  ],
];
