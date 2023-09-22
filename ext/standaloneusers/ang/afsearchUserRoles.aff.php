<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('User Roles'),
  'description' => E::ts('List of roles defined on the system.'),
  'icon' => 'fa-graduation-cap',
  'server_route' => 'civicrm/admin/roles',
  'permission' => ['cms:administer users'],
  'navigation' => [
    'parent' => 'Users and Permissions',
    'label' => E::ts('User Roles'),
    'weight' => 0,
  ],
  'requires' => [],
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'entity_type' => NULL,
  'join_entity' => NULL,
  'contact_summary' => NULL,
  'summary_contact_type' => NULL,
  'redirect' => NULL,
  'create_submission' => NULL,
];
