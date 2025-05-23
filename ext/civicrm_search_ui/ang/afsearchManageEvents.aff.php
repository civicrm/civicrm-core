<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Manage Events'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/searchui/event/manage',
  'permission' => [
    'access CiviCRM',
    'access CiviEvent',
  ],
  'modified_date' => '2023-12-04 18:48:01',
];
