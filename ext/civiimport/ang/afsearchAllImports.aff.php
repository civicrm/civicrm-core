<?php
use CRM_Civiimport_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('All Imports'),
  'icon' => 'fa-file-import',
  'server_route' => 'civicrm/imports/all-imports',
  'permission' => ['administer queues'],
  'navigation' => [
    'parent' => 'Reports',
    'label' => E::ts('All imports'),
    'weight' => 17,
  ],
];
