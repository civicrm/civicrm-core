<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'requires' => [],
  'entity_type' => NULL,
  'join_entity' => NULL,
  'title' => E::ts('Manage Contribution Pages'),
  'description' => '',
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'contact_summary' => NULL,
  'summary_contact_type' => NULL,
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/contribute',
  'permission' => ['access CiviContribute'],
  'redirect' => NULL,
  'create_submission' => FALSE,
  'navigation' => NULL,
];
