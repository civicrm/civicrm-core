<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Membership Types'),
  'icon' => 'fa-id-badge',
  'server_route' => 'civicrm/admin/member/membershipType',
  'permission' => [
    'administer CiviCRM',
  ],
];
