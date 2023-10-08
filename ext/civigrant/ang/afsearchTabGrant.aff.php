<?php
use CRM_Grant_ExtensionUtil as E;

return [
  'type' => 'search',
  'title' => E::ts('Grants'),
  'placement' => ['contact_summary_tab'],
  'summary_weight' => 60,
  'icon' => 'fa-money',
  'server_route' => '',
  'permission' => ['access CiviGrant'],
];
