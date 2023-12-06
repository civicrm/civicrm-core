<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'title' => E::ts('Import/Export Mappings'),
  'permission' => [
    'administer CiviCRM',
  ],
  'type' => 'search',
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/mapping',
  'permission_operator' => 'AND',
];
