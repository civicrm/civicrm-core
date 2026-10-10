<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Settings Inspector'),
  'description' => E::ts('Allows inspecting settings on the CiviCRM site'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/settings-inspector',
  'permission' => [
    'administer CiviCRM system',
  ],
  'search_displays' => [
    'Settings_Inspector.Settings_Inspector',
  ],
];
