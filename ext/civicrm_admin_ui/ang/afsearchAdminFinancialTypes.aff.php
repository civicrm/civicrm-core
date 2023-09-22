<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Financial Types'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/financial/financialType',
  'permission' => ['administer CiviCRM'],
  'requires' => [],
  'description' => '',
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'entity_type' => NULL,
  'join_entity' => NULL,
  'contact_summary' => NULL,
  'redirect' => NULL,
  'create_submission' => NULL,
  'navigation' => NULL,
];
