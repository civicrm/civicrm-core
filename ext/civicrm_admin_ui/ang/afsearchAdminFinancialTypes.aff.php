<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Financial Types'),
  'server_route' => 'civicrm/admin/financial/financialType',
  'permission' => ['administer CiviCRM'],
];
