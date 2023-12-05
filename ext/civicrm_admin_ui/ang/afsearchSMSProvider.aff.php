<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'title' => E::ts('SMS Provider'),
  'permission' => [
    'administer CiviCRM',
  ],
  'type' => 'search',
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/sms/provider',
  'permission_operator' => 'AND',
];
