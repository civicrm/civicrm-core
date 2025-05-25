<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Find Activities'),
  'icon' => 'fa-list-alt',
  'server_route' => 'civicrm/searchkit_ui/activity/search',
];
