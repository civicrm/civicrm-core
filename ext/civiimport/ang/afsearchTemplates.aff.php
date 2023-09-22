<?php
use CRM_Civiimport_ExtensionUtil as E;

return [
  'type' => 'search',
  'requires' => [],
  'entity_type' => NULL,
  'join_entity' => NULL,
  'title' => E::ts('Import Templates'),
  'description' => '',
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'contact_summary' => NULL,
  'summary_contact_type' => NULL,
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/imports/templates',
  'permission' => ['access CiviCRM'],
  'redirect' => NULL,
  'create_submission' => FALSE,
  'navigation' => [
    'parent' => 'Reports',
    'label' => E::ts('Import Templates'),
    'weight' => 16,
  ],
];
