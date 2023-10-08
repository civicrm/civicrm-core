<?php
use CRM_Civiimport_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('My Imports'),
  'server_route' => 'civicrm/imports/my-listing',
  'permission' => ['access CiviCRM'],
  'navigation' => [
    'parent' => 'Reports',
    'label' => E::ts('My Imports'),
    'weight' => 15,
  ],
];
