<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'form',
  'requires' => [],
  'entity_type' => NULL,
  'join_entity' => NULL,
  'title' => E::ts('Edit User account'),
  'description' => '',
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'contact_summary' => NULL,
  'summary_contact_type' => NULL,
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/user',
  'permission' => ['cms:administer users'],
  'redirect' => '/civicrm/admin/users',
  'create_submission' => TRUE,
  'navigation' => NULL,
];
