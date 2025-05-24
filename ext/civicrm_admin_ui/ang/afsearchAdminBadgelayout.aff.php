<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Badge Layouts'),
  'description' => E::ts('Administer Badge Layouts list'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/badgelayout',
  'permission' => [
    'administer CiviCRM data',
    'access CiviEvent',
  ],
];
