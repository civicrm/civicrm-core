<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Personal Campaign Pages'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/admin/pcp',
  'permission' => [
    'access CiviCRM',
    'edit contributions',
  ],
  'search_displays' => [
    'Personal_Campaign_Pages.Personal_Campaign_Pages',
  ],
];
