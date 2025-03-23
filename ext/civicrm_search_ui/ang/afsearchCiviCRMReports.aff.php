<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('CiviCRM Reports'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/searchkit_ui/report/list',
  'permission' => [
    'access CiviReport',
  ],
  'modified_date' => '2023-12-05 02:57:53',
];
