<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Label Formats'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/labelFormats',
  'permission' => [
    'administer CiviCRM',
  ],
];
