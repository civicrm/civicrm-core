<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Find Contacts'),
  'server_route' => 'civicrm/searchui/contact/search',
  'permission' => ['access CiviCRM'],
];
