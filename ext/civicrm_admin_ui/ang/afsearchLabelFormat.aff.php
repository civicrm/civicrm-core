<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Label Formats'),
  'description' => E::ts('Label Formats'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/labelFormats',
  'permission' => [
    'administer CiviCRM',
  ],
  'modified_date' => '2023-12-06 18:52:54',
];
