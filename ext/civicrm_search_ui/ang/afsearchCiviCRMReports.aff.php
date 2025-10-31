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
];
