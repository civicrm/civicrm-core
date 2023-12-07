<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Label Formats'),
  'description' => E::ts('Label Formats'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/searchkit_ui/admin/labelFormats',
  'permission' => [
    'administer CiviCRM',
  ],
  'modified_date' => '2023-12-06 18:52:54',
];
