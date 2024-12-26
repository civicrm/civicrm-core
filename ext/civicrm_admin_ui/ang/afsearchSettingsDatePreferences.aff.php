<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'title' => E::ts('Settings - Date Preferences'),
  'permission' => [
    'administer CiviCRM',
  ],
  'type' => 'search',
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/setting/preferences/date',
  'permission_operator' => 'AND',
];
