<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Headers, Footers, and Automated Messages'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/component',
  'permission' => [
    'access CiviCRM',
    'access CiviMail',
  ],
  'modified_date' => '2023-12-04 10:53:21',
];
