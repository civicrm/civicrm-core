<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'type' => 'search',
  'requires' => [],
  'entity_type' => NULL,
  'join_entity' => NULL,
  'title' => E::ts('Administer User Accounts'),
  'description' => '',
  'is_dashlet' => FALSE,
  'is_public' => FALSE,
  'is_token' => FALSE,
  'contact_summary' => NULL,
  'summary_contact_type' => NULL,
  'icon' => 'fa-users',
  'server_route' => 'civicrm/admin/users',
  'permission' => ['cms:administer users'],
  'redirect' => NULL,
  'create_submission' => FALSE,
  'navigation' => [
    'parent' => 'Users and Permissions',
    'label' => E::ts('User Accounts'),
    'weight' => 0,
  ],
];
