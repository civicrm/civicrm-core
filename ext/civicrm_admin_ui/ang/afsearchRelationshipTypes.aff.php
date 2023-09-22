<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'requires' => [],
  'entity_type' => NULL,
  'join_entity' => NULL,
  'title' => E::ts('Relationship Types'),
  'description' => '',
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'contact_summary' => NULL,
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/reltype',
  'permission' => ['access CiviCRM'],
  'redirect' => NULL,
  'create_submission' => FALSE,
  'navigation' => NULL,
];
