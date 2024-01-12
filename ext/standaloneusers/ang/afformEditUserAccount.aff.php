<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'form',
  'title' => E::ts('Edit User account'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/user',
  'permission' => ['cms:administer users'],
  'redirect' => '/civicrm/admin/users',
];
