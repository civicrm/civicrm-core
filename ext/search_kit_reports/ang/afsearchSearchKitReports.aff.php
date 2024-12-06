<?php
use CRM_SearchKitReports_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('SearchKit Reports'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/report/search_kit',
  'search_displays' => [
    'SearchKit_Reports.SearchKit_Reports_Table',
  ],
];
