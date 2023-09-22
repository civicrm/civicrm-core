<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Manage ACLs'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/acl',
  'permission' => ['administer CiviCRM'],
  'navigation' => NULL,
];
