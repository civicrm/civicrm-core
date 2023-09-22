<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Find Contacts'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/searchui/contact/search',
  'permission' => ['access CiviCRM'],
  'navigation' => NULL,
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
];
