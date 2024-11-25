<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;
return [
  'type' => 'search',
  'title' => E::ts('Administer Navigation Menu'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/navigation',
  'search_displays' => [
    'Administer_Navigation_Menu.Administer_Navigation_Menu',
  ],
];
