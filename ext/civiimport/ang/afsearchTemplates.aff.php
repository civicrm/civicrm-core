<?php
use CRM_Civiimport_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Import Templates'),
  'icon' => 'fa-file-import',
  'server_route' => 'civicrm/imports/templates',
  'permission' => ['access CiviCRM'],
  'navigation' => [
    'parent' => 'Reports',
    'label' => E::ts('Import Templates'),
    'weight' => 16,
  ],
];
