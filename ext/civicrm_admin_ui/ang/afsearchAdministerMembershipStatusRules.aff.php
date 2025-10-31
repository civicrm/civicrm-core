<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Membership Status Rules'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/member/membershipStatus',
  'permission' => [
    'administer CiviCRM',
  ],
];
