<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'requires' => [],
  'entity_type' => NULL,
  'join_entity' => NULL,
  'title' => E::ts('Custom Field Groups'),
  'description' => E::ts('Administer custom field groups list'),
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'contact_summary' => NULL,
  'summary_contact_type' => NULL,
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/custom/group',
  'permission' => ['administer CiviCRM data'],
  'redirect' => NULL,
  'create_submission' => FALSE,
  'navigation' => NULL,
];
