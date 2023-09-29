<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'form',
  'title' => E::ts('Edit Role'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/role',
  'permission' => ['access CiviCRM'],
  'redirect' => 'civicrm/admin/roles',
  'create_submission' => TRUE,
  'requires' => [],
  'description' => '',
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'entity_type' => NULL,
  'join_entity' => NULL,
  'contact_summary' => NULL,
  'summary_contact_type' => NULL,
  'navigation' => NULL,
];
