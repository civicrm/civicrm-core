<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'form',
  'title' => E::ts('User Permissions'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/rolepermissions',
  'permission' => ['cms:administer users'],
];
