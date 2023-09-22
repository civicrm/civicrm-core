<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Manage Groups'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/group',
  'permission' => ['access CiviCRM'],
  'requires' => [],
  'description' => '',
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'entity_type' => NULL,
  'join_entity' => NULL,
  'contact_summary' => NULL,
  'summary_contact_type' => NULL,
  'redirect' => NULL,
  'create_submission' => NULL,
  'navigation' => NULL,
];
