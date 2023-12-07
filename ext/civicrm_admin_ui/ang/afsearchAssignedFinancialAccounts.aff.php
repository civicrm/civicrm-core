<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Assigned Financial Accounts'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/financial/financialType/accounts/list',
  'permission' => ['administer CiviCRM'],
];
