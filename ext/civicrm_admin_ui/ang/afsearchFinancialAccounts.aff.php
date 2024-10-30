<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Financial Accounts'),
  'description' => E::ts('Administer Financial Accounts'),
  'is_public' => FALSE,
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/financial/financialAccount',
  'permission' => ['administer CiviCRM'],
];
