<?php
use CRM_SearchKitReports_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Search Kit Reports'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/search_kit_reports',
  'navigation' => [
    'parent' => 'Reports',
    'label' => E::ts('Search Kit Reports'),
    'name' => 'afsearchSearchKitReports',
    'weight' => 10000,
  ],
  'permission' => [
    'access Reports',
  ],
];
