<?php
use CRM_Grant_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Grants'),
  'contact_summary' => 'tab',
  'summary_weight' => 60,
  'icon' => 'fa-money',
  'server_route' => '',
  'permission' => ['access CiviGrant'],
];
