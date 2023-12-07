<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Mail Accounts'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/mailSettings',
  'permission' => ['administer CiviCRM'],
];
